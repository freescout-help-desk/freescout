<?php

namespace Tests\Unit;

use App\Thread;
use Tests\TestCase;

/**
 * "Note" should read as "Internal Note" everywhere the internal-note
 * feature is shown, via translation overrides (resources/lang/en.json).
 * Unlike ClosedToSolvedLabelTest's single-key fix, "Note" is used for
 * several unrelated things elsewhere in the app (Customer::$notes, an
 * ArmsReports placeholder column), and most of the client's required
 * surfaces (email copy, flash messages, settings text, Workflows labels)
 * live under their own separate translation keys rather than the bare
 * "Note" key the toggle button uses. This covers every one of those keys,
 * plus a guard that the two collisions are untouched.
 */
class NoteToInternalNoteLabelTest extends TestCase
{
    public function test_note_translates_to_internal_note()
    {
        $this->assertSame('Internal Note', __('Note'));
    }

    /**
     * The reply/note toggle button (app/Misc/ConversationActionButtons.php)
     * is the ticket's primary target. Building the full action list needs a
     * real Conversation/User/Mailbox, so this asserts against the exact
     * live source instead - the same pattern ActiveToNewLabelTest uses for
     * the mailbox connection-health indicator.
     */
    public function test_note_toggle_button_uses_bare_note_key()
    {
        $source = file_get_contents(app_path('Misc/ConversationActionButtons.php'));

        $this->assertStringContainsString("'label'          => __('Note'),", $source);
    }

    public function test_print_view_tag_uses_bare_note_key()
    {
        $source = file_get_contents(resource_path('views/conversations/partials/thread.blade.php'));

        $this->assertStringContainsString("[{{ __('Note') }}]", $source);
    }

    public function test_add_note_button_translates()
    {
        $this->assertSame('Add Internal Note', __('Add Note'));
    }

    public function test_switch_to_note_sentence_translates()
    {
        $translated = __('This reply will go to the customer. :%switch_start%Switch to a note:%switch_end% if you are replying to :user_name.', [
            '%switch_start%' => '<a>',
            '%switch_end%'   => '</a>',
            'user_name'      => 'Jane',
        ]);

        $this->assertSame(
            'This reply will go to the customer. <a>Switch to an internal note</a> if you are replying to Jane.',
            $translated
        );
    }

    public function test_notification_email_subject_note_branch_translates()
    {
        $translated = __(':person added a note to conversation', ['person' => 'Jane']);

        $this->assertSame('Jane added an internal note to conversation', $translated);
    }

    public function test_notification_email_activity_line_note_branch_translates()
    {
        $translated = __(':person added a note');

        $this->assertSame(':person added an internal note', $translated);
    }

    /**
     * Thread::getActionText() feeds the per-thread activity line shown
     * directly in the agent conversation view and in notification emails -
     * a distinct key from the two email-template keys above, found on a
     * second, stricter investigation pass.
     */
    public function test_thread_action_text_note_branch_reads_internal_note()
    {
        $thread = new Thread();
        $thread->type = Thread::TYPE_NOTE;

        $text = $thread->getActionText('42');

        $this->assertSame(':person added an internal note to conversation #42', $text);
    }

    public function test_note_added_flash_message_with_view_link_translates()
    {
        $translated = __(':%tag_start%Note added:%tag_end% :%view_start%View:%a_end%', [
            '%tag_start%'  => '<strong>',
            '%tag_end%'    => '</strong>',
            '%view_start%' => '<a>',
            '%a_end%'      => '</a>',
        ]);

        $this->assertSame('<strong>Internal note added</strong> <a>View</a>', $translated);
    }

    public function test_note_added_flash_message_without_view_link_translates()
    {
        $this->assertSame('Internal note added', __('Note added'));
    }

    public function test_default_redirect_settings_helper_text_translates()
    {
        $translated = __('This setting gives you control over what page loads after you perform an action (send a reply, add a note, change conversation status or assignee).');

        $this->assertSame(
            'This setting gives you control over what page loads after you perform an action (send a reply, add an internal note, change conversation status or assignee).',
            $translated
        );
    }

    public function test_subscription_notify_person_string_translates()
    {
        $translated = __('Notify :person when another :app_name user replies or adds a note…', [
            'person'   => 'Jane',
            'app_name' => 'FreeScout',
        ]);

        $this->assertSame('Notify Jane when another FreeScout user replies or adds an internal note…', $translated);
    }

    public function test_subscription_notify_me_string_translates()
    {
        $translated = __('Notify me when another :app_name user replies or adds a note…', [
            'app_name' => 'FreeScout',
        ]);

        $this->assertSame('Notify me when another FreeScout user replies or adds an internal note…', $translated);
    }

    /**
     * Workflows is a paid, runtime-installed module not tracked by this
     * repo's git, but it reads its labels through the same global __()/
     * en.json mechanism as everything else here - these four keys reach it
     * without ever touching its files.
     */
    public function test_workflows_added_a_note_trigger_label_translates()
    {
        $this->assertSame('Added an internal note', __('Added a note'));
    }

    public function test_workflows_note_contains_condition_label_translates()
    {
        $this->assertSame('Internal note contains', __('Note contains'));
    }

    public function test_workflows_add_a_note_action_title_translates()
    {
        $this->assertSame('Add an Internal Note', __('Add a Note'));
    }

    public function test_workflows_edit_note_modal_title_translates()
    {
        $this->assertSame('Edit Internal Note', __('Edit Note'));
    }

    /**
     * Customer::$notes is an unrelated customer field (resources/views/
     * customers/partials/edit_form.blade.php:252) that happens to use the
     * literal plural key. Deliberately no "Notes" override exists, so this
     * must keep reading as the untranslated literal.
     */
    public function test_customer_notes_field_label_is_unaffected()
    {
        $this->assertSame('Notes', __('Notes'));
    }

    public function test_thread_type_note_value_is_unchanged()
    {
        $this->assertSame(3, Thread::TYPE_NOTE);
    }
}
