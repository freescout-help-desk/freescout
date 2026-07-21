<?php

namespace Tests\Unit;

use App\Conversation;
use App\Thread;
use App\User;
use Tests\TestCase;

/**
 * "Active" should read as "New" everywhere the conversation status is
 * shown, via a translation override (resources/lang/en.json), the same
 * mechanism ClosedToSolvedLabelTest covers for Closed -> Solved. Unlike
 * that rename, "Active" is also used for several unrelated things
 * elsewhere in the app - this covers that each of those was moved to its
 * own distinct translation key rather than being swept up by the override,
 * plus a guard that the underlying status value is untouched.
 */
class ActiveToNewLabelTest extends TestCase
{
    public function test_active_translates_to_new()
    {
        $this->assertSame('New', __('Active'));
    }

    public function test_conversation_status_name_reads_new()
    {
        $this->assertSame('New', Conversation::statusCodeToName(Conversation::STATUS_ACTIVE));
    }

    public function test_thread_status_name_reads_new()
    {
        $this->assertSame('New', Thread::statusCodeToName(Thread::STATUS_ACTIVE));
    }

    /**
     * Only the label changes - the underlying status value, and anything
     * that compares against it, must be untouched.
     */
    public function test_status_active_value_is_unchanged()
    {
        $this->assertSame(1, Conversation::STATUS_ACTIVE);
        $this->assertSame(1, Thread::STATUS_ACTIVE);
    }

    /**
     * An already-accepted user invite must keep reading "Activated", not
     * be swept up into "New" by the shared translation key.
     */
    public function test_user_invite_state_does_not_read_new()
    {
        $user = new User();
        $user->invite_state = User::INVITE_STATE_ACTIVATED;

        $this->assertSame('Activated', $user->getInviteStateName());
        $this->assertNotSame('New', $user->getInviteStateName());
    }

    /**
     * The Modules admin page's enabled badge must keep reading "Enabled",
     * not be swept up into "New" by the shared translation key.
     */
    public function test_module_card_enabled_badge_does_not_read_new()
    {
        $html = view('modules.partials.module_card', [
            'module' => [
                'alias'       => 'testmodule',
                'name'        => 'Test Module',
                'description' => 'A test module.',
                'version'     => '1.0.0',
                'active'      => true,
                'installed'   => true,
            ],
        ])->render();

        $this->assertStringContainsString(__('Enabled'), $html);
        $this->assertStringNotContainsString('>New<', $html);
    }

    /**
     * The mailbox connection-health indicator must keep reading "Working",
     * not be swept up into "New" by the shared translation key. A full
     * page render needs a real Mailbox plus the whole app layout for what
     * is otherwise a one-line check, so this asserts directly against the
     * Blade source instead: the isInActive() branch uses the new key, and
     * the old shared key is gone from that branch specifically.
     */
    public function test_connection_incoming_view_does_not_use_shared_active_key()
    {
        $source = file_get_contents(resource_path('views/mailboxes/connection_incoming.blade.php'));

        // The exact live Blade echo, not a bare substring match - this
        // file's own explanatory comment mentions __('Active') by name as
        // prose (why it's deliberately not used), which a plain substring
        // check would misread as the call still being present.
        $this->assertStringContainsString("{{ __('Working') }}", $source);
        $this->assertStringNotContainsString("{{ __('Active') }}", $source);
    }
}
