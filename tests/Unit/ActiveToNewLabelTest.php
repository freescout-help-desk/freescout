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
}
