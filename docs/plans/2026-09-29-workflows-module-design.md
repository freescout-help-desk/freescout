# Workflows Module Design

> Date: 2026-09-29
> Status: approved
> Target: FreeScout module at `Modules/Workflows`, alias `workflows`

Workflows are per-mailbox rules. Each rule is a list of conditions and a list of actions. An automatic workflow runs in the background when a conversation changes, or on a minute schedule when it contains a date condition. A manual workflow runs only when an agent executes it on one conversation or on a bulk selection.

This is an original implementation of the behavior described at https://freescout.net/module/workflows/ (module version 1.0.95). The official module source is not in this tree and is not copied.

The module lives in the FreeScout app so Laravel-Modules loads it from `Modules/`. The alias is `workflows`, because existing extensions call `\Module::isActive('workflows')`.

## Data model

| Table | Holds |
| --- | --- |
| `workflows` | `mailbox_id`, `name`, `type` (`automatic` or `manual`), `active`, `apply_to_previous`, `max_executions` (default 1), `match` (`all` or `any`), `sort_order`, timestamps |
| `workflow_conditions` | `workflow_id`, `type`, `operator`, `value` (JSON), `sort_order` |
| `workflow_actions` | `workflow_id`, `type`, `operator` (nullable), `value` (JSON), `sort_order` |
| `conversation_workflows` | `conversation_id`, `workflow_id`, `executions`. Unique on the pair. Index on `workflow_id` |

`value` is JSON so a row can store a string, a user id, a tag list, or `{number, unit}` for a date check.

## When a workflow runs

Automatic workflows with no date condition run when a message is created or when a value they care about changes: status, assignee, subject, state, customer, tags. They do not run on drafts (`Conversation::STATE_DRAFT`).

A move between mailboxes runs only workflows that include a `new_reply_moved` condition whose value is `moved`.

Workflows that use Waiting Since, Last User Reply, Last Customer Reply, or Date Created are not run from those events. A `freescout:workflows-process` command, registered through the `schedule` filter, checks them every minute. Date math uses days or hours only.

Manual workflows run from the conversation action menu and from the conversation-list bulk bar.

`apply_to_previous` off means the workflow ignores conversations whose `created_at` is earlier than the workflow's `created_at`.

`max_executions` caps runs per conversation, counted in `conversation_workflows`. While a workflow's actions are running, that same workflow cannot start again. That guard, plus the cap, is what stops two workflows from retriggering each other. **Stop processing** ends the current workflow's remaining actions and skips later automatic workflows for that same trigger. Workflows run in `sort_order`.

Saving a workflow after changing it from automatic to manual does not run it.

If a mailbox, user, or customer referenced by a saved workflow is missing, the workflow is deactivated. The same check runs when one of those records is deleted.

Replies, notes, and forwards are authored by a hidden user. The module creates that user on activation with `User::STATUS_DELETED`, so it stays out of assignee lists. The display name comes from `WORKFLOWS_USER_FULL_NAME`, default `Workflow`, truncated to 20 characters.

## Conditions

A workflow matches with **All** (every row passes) or **Any** (one row passes). Text comparison is case-insensitive. Values are trimmed on save. A regex must use matching delimiters, for example `/ tba /`. An invalid regex is written to the Laravel log and that row fails. The run continues.

When the triggering event includes a thread, message conditions use that thread when it matches the chosen source. Otherwise they use the latest published thread of that source.

| Group | Type key | Operators | Check |
| --- | --- | --- | --- |
| Customer | `customer_name`, `customer_email` | `contains`, `not_contains`, `equal`, `not_equal`, `regex` | Customer on the conversation |
| Message | `to`, `cc`, `subject`, `headers` | same text operators | That field. Headers are the triggering message's header block, or the latest customer message |
| Message | `body` | same text operators. Value includes `source`: `customer`, `user`, or `note` | HTML stripped to plain text |
| Message | `attachment` | `contains`, `not_contains` | The triggering or latest message has a file |
| Conversation | `conversation_type` | `equal`, `not_equal` | `email` or `phone` |
| Conversation | `status` | `equal`, `not_equal` | `active`, `pending`, `closed`, `spam` |
| Conversation | `assignee` | `equal`, `not_equal` | A user id, `anybody`, or `nobody` |
| Conversation | `user_action` | `replied`, `added_note` | The triggering user thread |
| Conversation | `new_reply_moved` | `is` | `new`, `customer_reply`, `user_reply`, or `moved` |
| Conversation | `customer_viewed` | `yes`, `no` | A message thread sent to the customer has `opened_at` set |
| Conversation | `channel` | `equal`, `not_equal` | `email` unless a channel module sets another value |
| Dates | `waiting_since` | `in_the_last`, `not_in_the_last`. Value is `{number, unit}` with unit `hours` or `days` | Passes only when the last reply is from the customer and the status is active or pending. A workflow reply counts as a user reply |
| Dates | `last_user_reply`, `last_customer_reply`, `date_created` | `in_the_last`, `not_in_the_last` | `not_in_the_last` fails when that reply has never happened |
| Tags | `tag` | `contains`, `not_contains`, `equal`, `not_equal` | Shown when the Tags module is active. A trigger caused by adding a tag checks only that new tag. Any other trigger checks every tag |
| Custom fields | `custom_field` | `equal`, `not_equal`, `contains`, `not_contains`, `is_set`, `is_not_set` | Shown when the Custom Fields module is active. The value is read through a filter |

Date rows are what put a workflow on the minute command. The other rows run from conversation events.

### Condition hooks

`workflows.conditions_config` receives the grouped config and the mailbox id.

```php
$config['dates']['items']['today_is_business_day'] = [
    'title' => '...',
    'operators' => ['yes' => 'Yes', 'no' => 'No'],
    'values' => [],
];
```

`workflow.check_condition` receives `($result, $type, $operator, $value, $conversation, $workflow)`. A listener returns `$result` unchanged for a type it does not own. Built-in types are evaluated first, then the filter may replace the result.

`workflow.conversation_channel` receives `('email', $conversation)` so Chat, Telegram, Twitter, Facebook, WhatsApp, and SMS modules can name the channel.

`workflow.custom_field_value` receives `(null, $fieldId, $conversation)` and returns the stored value.

`workflow.conversation_tags` receives `([], $conversation, $trigger)` and returns tag names. When `$trigger['added_tag']` is set, the tag condition checks only that name.

## Actions

Actions run in saved order.

| Group | Type key | Behavior |
| --- | --- | --- |
| Email | `notification` | Email the current assignee, the last user who replied, or one chosen user |
| Email | `reply` | Email the customer with the thread history. The author is the Workflow user |
| Email | `email_customer` | Email the customer with no history. The subject stays the conversation subject. Skipped when the conversation type is chat |
| Email | `forward` | Create a forwarded conversation to the given address, with the given note |
| Email | `disable_auto_reply` | Stop the auto-reply for this conversation. Sets `meta['ar_off']`, which `App\Jobs\SendAutoReply` already honors, and returns false from `autoreply.should_send` for this conversation |
| Conversation | `add_note` | Internal note from the Workflow user |
| Conversation | `change_status` | Active, pending, closed, or spam, through `Conversation::changeStatus()`, which writes the status line |
| Conversation | `assign` | Assign a user. Optional `only_if_available`. Availability is the `user.is_user_available` filter and defaults to `User::STATUS_ACTIVE` |
| Conversation | `move_mailbox` | Move the conversation. Destination workflows run only when they include a moved condition |
| Conversation | `move_deleted` | `Conversation::deleteToFolder()` |
| Conversation | `delete_forever` | Delete the conversation record |
| Tags | `add_tags`, `remove_tags` | Shown when Tags is active. Applied through filters |
| Custom fields | `set_custom_field` | Shown when Custom Fields is active. Applied through a filter |
| Webhook | `trigger_webhook` | Store an event name and fire `workflow.webhook` with that name, the conversation, and the workflow |
| Flow | `stop` | Stop the rest of this run, including later automatic workflows for this trigger |

Reply, email, forward, and note bodies use `App\Misc\Mail::replaceMailVars()`. The `user` passed in is always the Workflow user, so `{%user.fullName%}`, `{%user.firstName%}`, `{%user.lastName%}`, `{%user.email%}`, `{%user.phone%}`, and `{%user.jobTitle%}` resolve to that user. `{%customer.*%}`, `{%mailbox.*%}`, `{%subject%}`, and `{%conversation.number%}` keep their normal meanings.

### Action hooks

`workflows.actions_config` receives the grouped config and the mailbox id.

```php
$config['kanban'] = [
    'title' => 'Kanban',
    'items' => [
        'add_to_kanban' => [
            'title' => 'Add to Kanban Board',
            'values' => [],
        ],
    ],
];
```

`workflow.perform_action` receives `($performed, $type, $operator, $value, $conversation, $workflow)`. Return `$performed` unchanged for other types. Return true after handling the type.

`workflow.validate_action` receives `($hasError, $action, $workflow)`. Return true to reject the save.

`workflow.tags.add` and `workflow.tags.remove` receive the tag names and the conversation. `workflow.custom_field.set` receives the field id, the value, and the conversation.

## Who can manage workflows

Admins always can. A non-admin can when either of these is true:

* The Manage » Settings option `workflows.allow_non_admins` is on.
* The user has permission id `1001`, named Manage workflows. Module permission ids are above 1000.

The mailbox settings menu gains a Workflows item through `mailboxes.settings.menu`. The list, the editor, and a manual run all check this rule for that mailbox.

## Screens

The screens use the existing mailbox settings layout, so they follow the app's Bootstrap 3 styling and its right-to-left layout.

**List.** One mailbox. Columns are name, Automatic or Manual, active, and run order. Up and down controls change `sort_order`.

**Editor.** Name, type, active, apply to previous, max executions, and match mode. Max executions defaults to 1. A value above 1 shows a warning that workflows can retrigger each other. Condition rows are type, operator, and a value control that changes with the type. Action rows work the same way. Removing a row does not run the workflow.

**Conversation.** A Workflows menu on `conversation.append_action_buttons` lists manual workflows for that mailbox. Choosing one runs it and reloads the conversation. If the workflow emails, forwards, or deletes, the click asks for confirmation.

**Bulk.** The same manual workflows appear from `bulk_actions.before_delete` and run against the selected conversations.

## Module layout

```
Modules/Workflows/
  module.json                 alias workflows, license AGPL-3.0
  start.php
  composer.json
  Config/config.php           permission id, workflow user option key
  Providers/WorkflowsServiceProvider.php
  Providers/RouteServiceProvider.php
  Http/routes.php
  Http/Controllers/WorkflowsController.php
  Http/Requests/WorkflowRequest.php
  Entities/Workflow.php
  Entities/WorkflowCondition.php
  Entities/WorkflowAction.php
  Entities/ConversationWorkflow.php
  Services/WorkflowRunner.php
  Services/ConditionEvaluator.php
  Services/ActionRunner.php
  Services/WorkflowUser.php
  Console/WorkflowsProcess.php
  Database/Migrations/...create_workflow_tables.php
  Resources/views/...
  Resources/lang/en/messages.php
  Tests/Unit/ConditionEvaluatorTest.php
  Tests/Unit/WorkflowRunnerTest.php
  Tests/Feature/WorkflowAuthorizationTest.php
```

`phpunit.xml` gains a third testsuite directory, `./Modules/Workflows/Tests`, so module tests run with the app suite.

## Tests that define done

* All and Any matching, including an Any workflow where "does not contain" passes because another row matches.
* An invalid regex is logged and that row fails without aborting the run.
* Waiting Since stays false unless the last reply is the customer's and the status is active or pending. A workflow reply counts as a user reply.
* A tag added in the triggering event is checked alone. Any other trigger checks every tag.
* Apply to previous off skips older conversations. Drafts never run. A move runs only workflows that include a moved condition.
* Max executions stops a further run. A workflow cannot retrigger itself while its own actions are still running. Stop processing skips the remaining actions and later workflows for that trigger.
* Reply includes the thread history. Email the customer does not, and it is skipped for chat. Assign with "only if available" skips an inactive user. A status change writes the status line.
* A non-admin without the setting or the permission receives 403 on the editor. An admin does not.
* A listener on `workflow.check_condition` can add a condition and have that row evaluated.
