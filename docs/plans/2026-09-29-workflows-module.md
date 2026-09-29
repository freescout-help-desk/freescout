# Workflows Module Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a FreeScout module, alias `workflows`, that runs per-mailbox automatic and manual workflows with the conditions, actions, and hooks in the approved design.

**Architecture:** `Modules/Workflows` is a Laravel-Module. `ConditionEvaluator` decides whether one row matches. `WorkflowRunner` chooses which workflows run, enforces the execution cap and the re-entry guard, and stops when an action says so. `ActionRunner` performs one action by calling existing conversation methods. Screens are mailbox settings Blade views. The design doc is `docs/plans/2026-09-29-workflows-module-design.md`.

**Tech Stack:** FreeScout on Laravel, nwidart/laravel-modules, Eventy, Blade, Bootstrap 3, PHPUnit 11, MySQL testing connection `freescout-test`.

**Worktree:** `/Users/dan/.config/superpowers/worktrees/freescout/feature-workflows-module` on branch `feature/workflows-module`. The main checkout at `/Volumes/1TB/Development/freescout` has unrelated uncommitted edits. Do not touch that working tree. `vendor/` is gitignored, so the worktree needs a symlink before tests:

```bash
cd /Users/dan/.config/superpowers/worktrees/freescout/feature-workflows-module
ln -sfn /Volumes/1TB/Development/freescout/vendor vendor
```

Run a single test with:

```bash
./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php --filter test_name
```

PHPUnit boots the app (`tests/TestCase.php` uses `CreatesApplication`). `composer.json` already maps `Modules\` to `Modules/`, so new module classes autoload without a dump.

---

### Task 1: Module skeleton and the condition catalog

**Files:**
- Create: `Modules/Workflows/module.json`
- Create: `Modules/Workflows/start.php`
- Create: `Modules/Workflows/composer.json`
- Create: `Modules/Workflows/Config/config.php`
- Create: `Modules/Workflows/Providers/WorkflowsServiceProvider.php`
- Create: `Modules/Workflows/Services/ConditionCatalog.php`
- Create: `Modules/Workflows/Tests/Unit/ConditionCatalogTest.php`
- Modify: `phpunit.xml` (add the module testsuite)

**Step 1: Write the failing test**

`Modules/Workflows/Tests/Unit/ConditionCatalogTest.php`:

```php
<?php

namespace Modules\Workflows\Tests\Unit;

use Modules\Workflows\Services\ConditionCatalog;
use Tests\TestCase;

class ConditionCatalogTest extends TestCase
{
    public function test_builtin_groups_include_the_documented_types(): void
    {
        $config = ConditionCatalog::configured(1);

        $this->assertArrayHasKey('customer_name', $config['customer']['items']);
        $this->assertArrayHasKey('waiting_since', $config['dates']['items']);
        $this->assertArrayHasKey('body', $config['message']['items']);
        $this->assertSame(
            ['contains', 'not_contains', 'equal', 'not_equal', 'regex'],
            array_keys($config['customer']['items']['customer_name']['operators'])
        );
    }

    public function test_extension_filter_can_append_a_dates_condition(): void
    {
        \Eventy::addFilter('workflows.conditions_config', function ($config, $mailboxId) {
            $config['dates']['items']['today_is_business_day'] = [
                'title' => 'Today is a business day',
                'operators' => ['yes' => 'Yes', 'no' => 'No'],
                'values' => [],
            ];
            return $config;
        }, 20, 2);

        $config = ConditionCatalog::configured(7);

        $this->assertSame(7, $config['mailbox_id']);
        $this->assertArrayHasKey('today_is_business_day', $config['dates']['items']);
    }
}
```

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionCatalogTest.php`
Expected: FAIL with "Class Modules\Workflows\Services\ConditionCatalog not found".

**Step 3: Write the minimal implementation**

`module.json` alias is `workflows`, license `AGPL-3.0`, provider `Modules\Workflows\Providers\WorkflowsServiceProvider`, `files` contains `start.php`. `start.php` requires `Http/routes.php` when routes are not cached. `Config/config.php` returns `['permission_id' => 1001, 'allow_non_admins_option' => 'workflows.allow_non_admins']`.

`ConditionCatalog::configured(int $mailboxId): array` builds the groups from the design table (`customer`, `message`, `conversation`, `dates`). It sets `$config['mailbox_id'] = $mailboxId` for the test and for extensions, then returns `\Eventy::filter('workflows.conditions_config', $config, $mailboxId)`. Tag and custom-field groups are added in Task 6.

Add this testsuite to `phpunit.xml` inside `<testsuites>`:

```xml
<testsuite name="Workflows">
  <directory suffix="Test.php">./Modules/Workflows/Tests</directory>
</testsuite>
```

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionCatalogTest.php`
Expected: PASS, 2 tests.

**Step 5: Commit**

```bash
git add Modules/Workflows phpunit.xml
git commit -m "Add the Workflows module skeleton and condition catalog."
```

---

### Task 2: Tables

**Files:**
- Create: `Modules/Workflows/Database/Migrations/2026_09_29_120000_create_workflow_tables.php`
- Create: `Modules/Workflows/Tests/Unit/WorkflowSchemaTest.php`
- Create: `Modules/Workflows/Entities/Workflow.php`
- Create: `Modules/Workflows/Entities/WorkflowCondition.php`
- Create: `Modules/Workflows/Entities/WorkflowAction.php`
- Create: `Modules/Workflows/Entities/ConversationWorkflow.php`

**Step 1: Write the failing test**

The test calls the migration's `up()` against the `testing` connection inside a transaction that rolls back, then asserts the four tables exist and `conversation_workflows` has an index whose column is `workflow_id`. Use `Schema::connection('testing')`. If the testing database is unreachable, the test fails with the connection error. Do not skip it.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowSchemaTest.php`
Expected: FAIL because the migration class does not exist, or because the tables are missing.

**Step 3: Write the minimal implementation**

Columns match the design doc. `workflows.type` is a string. `workflows.match` is a string, default `all`. `workflows.max_executions` is an unsigned integer, default 1. `workflows.apply_to_previous` and `workflows.active` are booleans. Condition and action `value` columns are `json` nullable. `conversation_workflows` has a unique index on `(conversation_id, workflow_id)` and a plain index on `workflow_id`.

Entities: `Workflow` has `conditions()` and `actions()` ordered by `sort_order`. Cast `active` and `apply_to_previous` to boolean, and leave JSON casting on the child `value` attributes. `$guarded` includes `id` only. Do not leave `$guarded` empty.

The module provider `boot()` calls `$this->loadMigrationsFrom(__DIR__.'/../Database/Migrations')`.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowSchemaTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows
git commit -m "Add workflow tables and models."
```

---

### Task 3: Text conditions

**Files:**
- Create: `Modules/Workflows/Services/ConditionEvaluator.php`
- Create: `Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php`

**Step 1: Write the failing test**

```php
public function test_contains_is_case_insensitive(): void
{
    $this->assertTrue(ConditionEvaluator::text('Refund request', 'contains', 'refund'));
    $this->assertFalse(ConditionEvaluator::text('Refund request', 'not_contains', 'refund'));
}

public function test_invalid_regex_fails_and_is_logged(): void
{
    \Log::shouldReceive('error')->once();
    $this->assertFalse(ConditionEvaluator::text('abc', 'regex', '/unterminated'));
}

public function test_regex_requires_delimiters(): void
{
    $this->assertTrue(ConditionEvaluator::text('say tba please', 'regex', '/ tba /'));
}
```

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php --filter test_contains`
Expected: FAIL with class not found.

**Step 3: Write the minimal implementation**

`ConditionEvaluator::text(?string $haystack, string $operator, ?string $needle): bool`

- Normalize both sides with `mb_strtolower`.
- `contains` uses `mb_strpos`. `not_contains` is the opposite.
- `equal` and `not_equal` compare the lowered strings.
- `regex`: if `preg_match` returns false, log `Invalid Workflow conditions regex: {pattern}` via `\Log::error` and return false. A pattern without a matching end delimiter fails this way. Do not let the warning escape the method. Use `preg_match($needle, (string) $haystack) === 1` on success. Compare the original haystack, not the lowered one, and add the `i` flag only when the pattern has no flags yet. Practical rule: run the pattern as saved. Authors include `(?i)` or wrap their own flags. The design says comparison is case-insensitive, so when the delimiter is `/`, rewrite `/pattern/` to `/pattern/i` if `i` is not already present. Leave other delimiters alone except for that same flag insert.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Services/ConditionEvaluator.php Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php
git commit -m "Match workflow text conditions, including a safe regex."
```

---

### Task 4: Conversation field conditions

**Files:**
- Modify: `Modules/Workflows/Services/ConditionEvaluator.php`
- Modify: `Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php`

**Step 1: Write the failing test**

Add tests that call `ConditionEvaluator::matches($type, $operator, $value, $context)`:

- `status` / `equal` / `pending` is true when `$context->status` is `App\Conversation::STATUS_PENDING`.
- `assignee` / `equal` / `nobody` is true when `user_id` is null, and false for `anybody` in that case.
- `assignee` / `equal` / `anybody` is true when `user_id` is 5.
- `conversation_type` / `equal` / `phone` is true for `Conversation::TYPE_PHONE`.
- `customer_viewed` / `yes` is true when `customer_viewed` is true.
- `user_action` / `replied` is true only when `trigger` is `user_reply`.
- `new_reply_moved` / `is` / `moved` is true only when `trigger` is `moved`.
- `body` with `source` `customer` uses `trigger_body` when `trigger_source` is `customer`, otherwise `latest_body_by_source['customer']`, passed through `ConditionEvaluator::text`. HTML in the body is already plain text in the context. The runner strips HTML before building the context, using `Thread::getBodyAsText()`.
- `attachment` / `contains` is true when `has_attachment` is true.

Build the context as a plain object or a small `ConditionContext` class with public properties. Keep Eloquent out of this test.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php --filter test_status`
Expected: FAIL with "Call to undefined method matches".

**Step 3: Write the minimal implementation**

`matches()` switches on `$type` and reads the context. Map status and type slugs to the conversation constants in one private array each. Unknown operators return false.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Services/ConditionEvaluator.php Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php
git commit -m "Match workflow conversation field conditions."
```

---

### Task 5: Date conditions

**Files:**
- Modify: `Modules/Workflows/Services/ConditionEvaluator.php`
- Modify: `Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php`

**Step 1: Write the failing test**

Use a fixed `now` of `2026-09-29 12:00:00` passed into the context. Value shape is `['number' => 1, 'unit' => 'days']` or `hours`.

- `waiting_since` / `in_the_last` is false when `last_reply_from` is `Conversation::PERSON_USER`.
- `waiting_since` / `in_the_last` is false when status is closed, even if the last reply is the customer.
- `waiting_since` / `not_in_the_last` with 1 day is true when the customer replied at `2026-09-27 12:00:00` and status is active.
- `waiting_since` treats `last_reply_from_workflow` the same as a user reply: the row fails.
- `last_customer_reply` / `not_in_the_last` is false when `last_customer_reply_at` is null.
- `date_created` / `in_the_last` with 2 hours is true when `created_at` is `2026-09-29 11:00:00`.
- A unit of `minutes` returns false.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php --filter test_waiting`
Expected: FAIL.

**Step 3: Write the minimal implementation**

Compare timestamps with Carbon. `in_the_last` means the timestamp is greater than or equal to `now` minus the interval. `not_in_the_last` means it is older than that. Waiting Since returns false unless `last_reply_from` is `Conversation::PERSON_CUSTOMER`, `last_reply_from_workflow` is false, and status is active or pending. Accept only `hours` and `days`.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Services/ConditionEvaluator.php Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php
git commit -m "Match workflow date conditions in days and hours."
```

---

### Task 6: Tags, channel, custom fields, and the check filter

**Files:**
- Modify: `Modules/Workflows/Services/ConditionCatalog.php`
- Modify: `Modules/Workflows/Services/ConditionEvaluator.php`
- Modify: `Modules/Workflows/Tests/Unit/ConditionCatalogTest.php`
- Modify: `Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php`

**Step 1: Write the failing test**

- Catalog hides `tag` when `\Module::isActive('tags')` is false. The test stubs this by calling `ConditionCatalog::configured($id, ['tags' => false, 'customfields' => false])`. The second argument is optional and defaults to reading `\Module::isActive`.
- With `tags` true, the `tag` item exists.
- Evaluator: `added_tag` of `refund` makes `tag` / `contains` / `refund` true, and a context tag list of `['other']` is ignored while `added_tag` is set.
- With `added_tag` null and tags `['refund', 'vip']`, `tag` / `equal` / `vip` is true.
- `channel` / `equal` / `telegram` is true when the context channel is `telegram`.
- `custom_field` / `is_set` is true when the value is non-empty, and `is_not_set` is true when it is null.
- After the built-in result is computed, `matches()` runs `\Eventy::filter('workflow.check_condition', $result, $type, $operator, $value, $context->conversation, $context->workflow)`. A test filter for type `today_is_business_day` returns true and the method returns true.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php --filter test_tag`
Expected: FAIL.

**Step 3: Write the minimal implementation**

Add the groups in the catalog only when the corresponding flag is true. `matches()` reads `added_tag`, `tags`, `channel`, and `custom_field_value` from the context. Apply the Eventy filter at the end of `matches()`. When the test has no conversation object, pass null for those two filter arguments.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ConditionCatalogTest.php Modules/Workflows/Tests/Unit/ConditionEvaluatorTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Services Modules/Workflows/Tests/Unit
git commit -m "Add tag, channel, and custom field conditions."
```

---

### Task 7: Runner guards

**Files:**
- Create: `Modules/Workflows/Services/WorkflowRunner.php`
- Create: `Modules/Workflows/Tests/Unit/WorkflowRunnerTest.php`

The runner methods under test take plain data so they do not hit MySQL:

- `eligible(array $workflow, array $conversation, array $trigger): bool`
- `allowsAnotherRun(int $executions, int $max): bool`
- `select(array $workflows, array $conversation, array $trigger, array $runningIds): array`

**Step 1: Write the failing test**

Assert all of these:

- A draft conversation (`state` = `Conversation::STATE_DRAFT`) is never eligible.
- `apply_to_previous` false and `conversation.created_at` before `workflow.created_at` is not eligible.
- Trigger `moved` keeps only workflows that have a condition type `new_reply_moved` with value `moved`.
- A workflow whose conditions include `waiting_since` is not eligible for trigger `customer_reply`. It is eligible for trigger `schedule`.
- A workflow with no date condition is eligible for `customer_reply` and not for `schedule`.
- `allowsAnotherRun(1, 1)` is false. `allowsAnotherRun(0, 1)` is true.
- `select()` drops a workflow whose id is in `$runningIds`.
- Workflows come back ordered by `sort_order`.
- A workflow with `match` `any` is selected when one of two conditions passes. Use `ConditionEvaluator` for that decision inside `select()`, with conditions embedded on the workflow array.
- `match` `all` is not selected when one row fails.
- An action result of `stop` from a fake action list causes `select()`'s companion `WorkflowRunner::stopped(array $actionResults): bool` to return true when any result is `stop`. The caller test shows the second workflow in sort order is not executed when the first returns stop. Put that loop in `WorkflowRunner::runList(array $workflows, callable $execute): array` so the test can pass a fake executor.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowRunnerTest.php`
Expected: FAIL with class not found.

**Step 3: Write the minimal implementation**

Date condition types are `waiting_since`, `last_user_reply`, `last_customer_reply`, `date_created`. `runList` sorts by `sort_order`, skips ids in a static `$running` array that the executor's real path will also use, pushes the id before execute, pops it after, and breaks when the executor returns `stop`.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowRunnerTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Services/WorkflowRunner.php Modules/Workflows/Tests/Unit/WorkflowRunnerTest.php
git commit -m "Decide which workflows run, and stop a loop."
```

---

### Task 8: Actions that change the conversation

**Files:**
- Create: `Modules/Workflows/Services/ActionRunner.php`
- Create: `Modules/Workflows/Tests/Unit/ActionRunnerTest.php`

**Step 1: Write the failing test**

`ActionRunner::perform($type, $value, $context)` returns `done` or `stop`. The context exposes the conversation methods the test records.

Use a test double conversation with public arrays `calls`. Assert:

- `change_status` with `closed` calls `changeStatus(Conversation::STATUS_CLOSED, $workflowUser, true)`.
- `assign` with `['user_id' => 4, 'only_if_available' => true]` calls `changeUser` when `user.is_user_available` returns true.
- The same assign does not call `changeUser` when the filter returns false.
- `assign` without the flag calls `changeUser` even when the filter would return false. Register the filter only for the "only if available" test.
- `add_note` calls `createUserThread` with `Thread::TYPE_NOTE` and the Workflow user.
- `move_deleted` calls `deleteToFolder` with the Workflow user.
- `delete_forever` calls `delete`.
- `move_mailbox` calls `moveToMailbox` with the mailbox id in the value.
- `stop` returns `stop` and records no conversation call.
- Unknown type `add_to_kanban` runs `\Eventy::filter('workflow.perform_action', false, $type, null, $value, $conversation, $workflow)` and returns `done` when the filter returns true.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ActionRunnerTest.php`
Expected: FAIL with class not found.

**Step 3: Write the minimal implementation**

Default `user.is_user_available` is not registered here. The provider registers it in Task 11 as "status is active". In this task the runner calls the filter with a default of true: `\Eventy::filter('user.is_user_available', true, $user)`. The test supplies the filter.

Map status slugs to `Conversation` constants. Do not delete or move inside the runner beyond the method call on the conversation. The test double implements those methods.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ActionRunnerTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Services/ActionRunner.php Modules/Workflows/Tests/Unit/ActionRunnerTest.php
git commit -m "Perform workflow actions that update a conversation."
```

---

### Task 9: Actions that send mail

**Files:**
- Modify: `Modules/Workflows/Services/ActionRunner.php`
- Modify: `Modules/Workflows/Tests/Unit/ActionRunnerTest.php`

**Step 1: Write the failing test**

- `reply` calls `createUserThread` with `Thread::TYPE_MESSAGE` and records `send_reply` true. The body stored on the context is the replaced body.
- `email_customer` records `send_plain` true and `send_reply` false. Subject recorded equals the conversation subject. When `type` is `Conversation::TYPE_CHAT`, it records neither send flag.
- `forward` calls `forward($workflowUser, $body, $to)`.
- `notification` records the recipient id from `assignee`, `last_user`, or a numeric `user_id`. `last_user` reads `last_user_id` on the context.
- `disable_auto_reply` calls `setMeta('ar_off', true, true)` and a filter the test registers on `autoreply.should_send` returns false for that conversation id.
- `trigger_webhook` fires `\Eventy::action('workflow.webhook', $eventName, $conversation, $workflow)`. The test listens with `\Eventy::addAction` and records the event name.
- Bodies pass through `App\Misc\Mail::replaceMailVars()` with `user` set to the Workflow user. A body `{%user.fullName%}` becomes that user's name, not the assignee's name.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ActionRunnerTest.php --filter test_reply`
Expected: FAIL.

**Step 3: Write the minimal implementation**

Keep the actual mail transport behind two methods on a small `MailGateway` class the test can replace: `sendReply(Conversation $conversation, Thread $thread)` and `sendPlain(Conversation $conversation, string $body)`. The production gateway dispatches `App\Jobs\SendReplyToCustomer` for a reply, and for the plain email sends a message whose subject is the conversation subject and whose body is only the given text. Look at `app/Jobs/SendReplyToCustomer.php` and `app/Mail/ReplyToCustomer.php` before writing the gateway, and call the same mailbox mail driver setup those classes use.

`disable_auto_reply` calls `setMeta('ar_off', true, true)`. The provider, in Task 11, adds the `autoreply.should_send` filter. This task's test registers that filter itself and asserts the meta call.

Replace variables before any send:

```php
$body = \App\Misc\Mail::replaceMailVars($body, [
    'conversation' => $conversation,
    'mailbox' => $conversation->mailbox,
    'customer' => $conversation->customer,
    'user' => $context->workflowUser,
], false, false);
```

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ActionRunnerTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Services Modules/Workflows/Tests/Unit/ActionRunnerTest.php
git commit -m "Perform workflow actions that send mail."
```

---

### Task 10: The Workflow user

**Files:**
- Create: `Modules/Workflows/Services/WorkflowUser.php`
- Create: `Modules/Workflows/Tests/Unit/WorkflowUserTest.php`

**Step 1: Write the failing test**

`WorkflowUser::displayName()` returns `Workflow` when the env var is empty, returns the env value when set, and truncates to 20 characters. `WorkflowUser::attributes()` returns `status` = `User::STATUS_DELETED`, `first_name` equal to the display name, and an email `workflow@localhost`.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowUserTest.php`
Expected: FAIL with class not found.

**Step 3: Write the minimal implementation**

Read `env('WORKFLOWS_USER_FULL_NAME', 'Workflow')`. `mb_substr` to 20. A second method `WorkflowUser::findOrCreate(): User` looks up that email and otherwise creates the user with `role` left unset and `password` set to a random hash. The test does not call `findOrCreate` unless the testing database is the target of a separate assertion. Keep `findOrCreate` in this task. Add one feature-style assertion only if `Schema::connection('testing')->hasTable('users')` is not the pattern. Call `findOrCreate` in the unit test inside `User::withoutEvents` is still a database write. Skip the database assertion in the unit test. Cover `findOrCreate` in Task 14's feature test, which already needs the database.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowUserTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Services/WorkflowUser.php Modules/Workflows/Tests/Unit/WorkflowUserTest.php
git commit -m "Add the hidden Workflow user name."
```

---

### Task 11: Wire events, the schedule, and availability

**Files:**
- Modify: `Modules/Workflows/Providers/WorkflowsServiceProvider.php`
- Create: `Modules/Workflows/Console/WorkflowsProcess.php`
- Create: `Modules/Workflows/Listeners/RunWorkflows.php`
- Create: `Modules/Workflows/Tests/Unit/RunWorkflowsTriggerTest.php`

**Step 1: Write the failing test**

`RunWorkflows::triggerFor($eventName, $conversation, $thread = null, $extra = []): array` returns a trigger array:

| Event | Trigger `name` |
| --- | --- |
| `conversation.created_by_customer` | `new` |
| `conversation.customer_replied` | `customer_reply` |
| `conversation.user_replied` | `user_reply` |
| `conversation.note_added` | `user_note` |
| `conversation.moved` | `moved` |
| `conversation.status_changed` and the other value-change events | `updated` |

Draft state returns null. `updated` is not a date-condition trigger. The test builds the array and does not load workflows from the database. A second test registers the provider's hooks by calling a public `WorkflowsServiceProvider::hooks($events)` that receives an array spy instead of `\Eventy`, and asserts the spy heard `schedule`, `user.is_user_available`, and `autoreply.should_send`.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/RunWorkflowsTriggerTest.php`
Expected: FAIL.

**Step 3: Write the minimal implementation**

`WorkflowsProcess` signature is `freescout:workflows-process`. Its `handle()` loads active automatic workflows that have a date condition and calls the runner with trigger `schedule`. The provider adds it on the `schedule` filter with `everyMinute()` and `withoutOverlapping()`.

`user.is_user_available` returns `$user->status == User::STATUS_ACTIVE` when the filtered value is still the default.

`autoreply.should_send` returns false when the conversation meta `ar_off` is set.

Subscribe with `\Eventy::addAction` to:

- `conversation.created_by_customer`
- `conversation.customer_replied`
- `conversation.user_replied`
- `conversation.note_added`
- `conversation.moved`
- `conversation.status_changed`
- `conversation.user_changed`
- `conversation.subject_changed`
- `conversation.state_changed`

Each action builds the trigger and calls `WorkflowRunner::runMailbox($conversation, $trigger)`. That method is the database entry point. Implement it by loading active automatic workflows for `$conversation->mailbox_id`, building a `ConditionContext` from the conversation and thread, and calling `runList`. Increment `conversation_workflows.executions` only after a workflow's conditions match and before its actions run. If the increment would exceed `max_executions`, skip it.

Changing type from automatic to manual is not an event. No code path runs workflows from the update controller. Task 15 tests that.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/RunWorkflowsTriggerTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Providers Modules/Workflows/Console Modules/Workflows/Listeners Modules/Workflows/Tests/Unit/RunWorkflowsTriggerTest.php
git commit -m "Run automatic workflows from conversation events and the minute command."
```

---

### Task 12: Deactivate a workflow when a referenced record is gone

**Files:**
- Create: `Modules/Workflows/Services/WorkflowHealth.php`
- Create: `Modules/Workflows/Tests/Unit/WorkflowHealthTest.php`

**Step 1: Write the failing test**

`WorkflowHealth::referencesMissing(array $workflow, array $existing): bool` is true when an assign action's `user_id` is not in `existing['users']`, when `move_mailbox` names a missing mailbox, or when a notification names a missing user. It is false when every id is present. `anybody` and `nobody` are not user ids.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowHealthTest.php`
Expected: FAIL.

**Step 3: Write the minimal implementation**

The provider listens to `UserDeleted` (the app event class `App\Events\UserDeleted`) and to `\Eventy` actions around mailbox and customer deletion if those actions exist. Search `app/` for `mailbox.delet` and `customer.delet` before wiring. When a matching action exists, call `WorkflowHealth::deactivateReferencing($id, $kind)`, which sets `active` false on workflows whose action or condition JSON contains that id. The unit test covers the pure check. The listener is a thin call.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowHealthTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Services/WorkflowHealth.php Modules/Workflows/Tests/Unit/WorkflowHealthTest.php Modules/Workflows/Providers/WorkflowsServiceProvider.php
git commit -m "Deactivate workflows that point at a deleted record."
```

---

### Task 13: Authorization

**Files:**
- Create: `Modules/Workflows/Services/WorkflowAuthorizer.php`
- Create: `Modules/Workflows/Tests/Unit/WorkflowAuthorizerTest.php`
- Modify: `Modules/Workflows/Providers/WorkflowsServiceProvider.php`

**Step 1: Write the failing test**

`WorkflowAuthorizer::canManage($user, bool $optionOn): bool` is true for `isAdmin()`, true when the option is on, true when `$user->hasPermission(1001)` is true, and false otherwise. Use a test double with `isAdmin()` and `hasPermission()`.

The provider adds permission `1001` through the `user_permissions.list` filter if that filter exists. Search `app/User.php` for the filter name that modules use to append permission ids. The existing method is `User::getUserPermissionName()` filtered by `user_permissions.name`. Add the id through whichever filter `User::getGlobalUserPermissions` or the permissions screen already uses. Assert `WorkflowAuthorizer::permissionName()` returns `Manage workflows`.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowAuthorizerTest.php`
Expected: FAIL.

**Step 3: Write the minimal implementation**

Read the option with `\Option::get('workflows.allow_non_admins')`. Admins short-circuit before the option. Register the permission name filter in the provider.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowAuthorizerTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Services/WorkflowAuthorizer.php Modules/Workflows/Tests/Unit/WorkflowAuthorizerTest.php Modules/Workflows/Providers/WorkflowsServiceProvider.php
git commit -m "Let admins and permitted users manage workflows."
```

---

### Task 14: Editor requests

**Files:**
- Create: `Modules/Workflows/Http/Requests/WorkflowRequest.php`
- Create: `Modules/Workflows/Http/Controllers/WorkflowsController.php`
- Create: `Modules/Workflows/Http/routes.php`
- Create: `Modules/Workflows/Tests/Unit/WorkflowRequestTest.php`

**Step 1: Write the failing test**

`WorkflowRequest::sanitize($input)`:

- Trims condition and action string values.
- Rejects `max_executions` below 1.
- Rejects a condition type that is not in the catalog.
- Runs `\Eventy::filter('workflow.validate_action', false, $action, $workflow)` and the sanitized result has `errors` true when the filter returns true.
- Does not include a `run` flag. Nothing in sanitize executes a workflow.

Build the catalog types from `ConditionCatalog::configured(1, ['tags' => true, 'customfields' => true])` so tag rows validate when the caller says the module is active.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowRequestTest.php`
Expected: FAIL.

**Step 3: Write the minimal implementation**

Routes, all behind `auth` and a controller middleware that calls `WorkflowAuthorizer`:

- `GET mailboxes/{id}/workflows` name `mailboxes.workflows`
- `GET mailboxes/{id}/workflows/create` name `mailboxes.workflows.create`
- `POST mailboxes/{id}/workflows` name `mailboxes.workflows.store`
- `GET mailboxes/{id}/workflows/{workflow}` name `mailboxes.workflows.edit`
- `POST mailboxes/{id}/workflows/{workflow}` name `mailboxes.workflows.update`
- `POST mailboxes/{id}/workflows/{workflow}/delete` name `mailboxes.workflows.delete`
- `POST mailboxes/{id}/workflows/sort` name `mailboxes.workflows.sort`
- `POST conversation/{id}/workflow/{workflow}` name `conversations.workflow.run`

`update` saves the new type and redirects. It does not call `WorkflowRunner`. The unit test covers sanitize. A controller method `update` is a straight save.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowRequestTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Http Modules/Workflows/Tests/Unit/WorkflowRequestTest.php
git commit -m "Validate and save a workflow without running it."
```

---

### Task 15: Mailbox screens

**Files:**
- Create: `Modules/Workflows/Resources/views/index.blade.php`
- Create: `Modules/Workflows/Resources/views/edit.blade.php`
- Create: `Modules/Workflows/Resources/views/partials/settings_menu.blade.php`
- Create: `Modules/Workflows/Resources/lang/en/messages.php`
- Modify: `Modules/Workflows/Providers/WorkflowsServiceProvider.php`
- Modify: `Modules/Workflows/Http/Controllers/WorkflowsController.php`

**Step 1: Write the failing test**

Render `workflows::index` and `workflows::edit` with `View::make` in `Modules/Workflows/Tests/Unit/WorkflowViewsTest.php`. The index HTML contains the workflow name and the word Automatic. The edit HTML contains the max-executions input and, when `max_executions` is 2, the warning string from the lang file: "A value above 1 can let workflows trigger each other." The edit form has no submit button named `run`.

The provider test from Task 11 grows an assertion that `mailboxes.settings.menu` is registered. Call the view composer hook by rendering `mailboxes.settings_menu` is optional. Assert the partial link href contains `/workflows`.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowViewsTest.php`
Expected: FAIL because the view is missing.

**Step 3: Write the minimal implementation**

Extend the mailbox settings layout used by `resources/views/mailboxes/update.blade.php`. Copy its section structure (`@extends('layouts.app')` and the settings menu include) rather than inventing a new page chrome. Use `__('workflows::messages.key')` for every visible string. The editor posts condition rows as `conditions[][type|operator|value]` and action rows as `actions[][type|value]`. Value widgets: a text input, a user `<select>` including Anybody and Nobody, a status `<select>`, and a number input plus a days/hours select. Show and hide them with a few lines of plain script that reads the selected type. Keep that script in the edit view.

Register the settings menu item on `mailboxes.settings.menu`, shown only when `WorkflowAuthorizer` allows the current user.

Up and down forms post to the sort route with `direction` `up` or `down`.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowViewsTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows/Resources Modules/Workflows/Providers Modules/Workflows/Http/Controllers/WorkflowsController.php Modules/Workflows/Tests/Unit/WorkflowViewsTest.php
git commit -m "Add the mailbox workflow list and editor."
```

---

### Task 16: Manual run from a conversation and from the bulk bar

**Files:**
- Create: `Modules/Workflows/Resources/views/partials/conversation_menu.blade.php`
- Create: `Modules/Workflows/Resources/views/partials/bulk_menu.blade.php`
- Modify: `Modules/Workflows/Providers/WorkflowsServiceProvider.php`
- Modify: `Modules/Workflows/Http/Controllers/WorkflowsController.php`
- Create: `Modules/Workflows/Tests/Unit/ManualRunTest.php`

**Step 1: Write the failing test**

`WorkflowsController::manualPayload($workflow): array` returns `confirm` true when any action type is `reply`, `email_customer`, `forward`, `move_deleted`, or `delete_forever`. Otherwise `confirm` is false.

The conversation partial, rendered with one manual workflow that replies, contains `data-confirm="1"`. A note-only workflow renders `data-confirm="0"`.

`runManual` on the controller calls `WorkflowRunner::runOne` with trigger name `manual` and returns a redirect to the conversation. The test uses a runner double bound in the container. It does not send mail.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ManualRunTest.php`
Expected: FAIL.

**Step 3: Write the minimal implementation**

Hook `conversation.append_action_buttons` and `bulk_actions.before_delete`. List active manual workflows for the mailbox. The bulk form posts the selected conversation ids to a route `conversations.workflow.bulk` that loops `runOne` for each id the user can view. Check the conversation policy `view` before each run. A manual run ignores the "date conditions only run on the schedule" rule: the agent asked for this workflow, so every condition is evaluated, including date rows.

**Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/ManualRunTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add Modules/Workflows
git commit -m "Run a manual workflow from a conversation or the bulk bar."
```

---

### Task 17: Settings option and the full module suite

**Files:**
- Create: `Modules/Workflows/Resources/views/partials/settings.blade.php`
- Modify: `Modules/Workflows/Providers/WorkflowsServiceProvider.php`
- Modify: `Modules/Workflows/Tests/Unit/WorkflowAuthorizerTest.php`

**Step 1: Write the failing test**

Extend the authorizer test: with the option stored as the string `1`, `canManage` is true for a non-admin. The settings partial contains a checkbox named `settings[workflows.allow_non_admins]`.

Look at `resources/views/settings/` and the `settings.sections` / `settings.view` filters in `SettingsController` before choosing the checkbox name, and match the name that controller already saves. The test asserts the name the controller expects.

**Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit Modules/Workflows/Tests/Unit/WorkflowAuthorizerTest.php`
Expected: FAIL on the new assertion.

**Step 3: Write the minimal implementation**

Register a settings section titled Workflows through the filter `SettingsController` already applies. Saving uses the existing settings form, so this module does not add a second settings post route.

**Step 4: Run the module suite**

Run: `./vendor/bin/phpunit --testsuite Workflows`
Expected: PASS, every test in `Modules/Workflows/Tests`.

**Step 5: Commit**

```bash
git add Modules/Workflows
git commit -m "Add the workflow permission setting and cover the module tests."
```
