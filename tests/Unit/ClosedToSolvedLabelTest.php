<?php

namespace Tests\Unit;

use App\Conversation;
use App\Folder;
use App\Thread;
use Tests\TestCase;

/**
 * ARMS-37: "Closed" should read as "Solved" everywhere, via a translation
 * override (resources/lang/en.json) rather than any code change to the
 * status itself. Covers the resolvers every UI surface (dropdowns, bulk
 * actions, advanced search, folder sidebar, notification emails) reads
 * the label from, plus a guard that the underlying status value and logic
 * are untouched, per the ticket's own acceptance criteria.
 */
class ClosedToSolvedLabelTest extends TestCase
{
    public function test_closed_translates_to_solved()
    {
        $this->assertSame('Solved', __('Closed'));
    }

    public function test_conversation_status_name_reads_solved()
    {
        $this->assertSame('Solved', Conversation::statusCodeToName(Conversation::STATUS_CLOSED));
    }

    public function test_thread_status_name_reads_solved()
    {
        $this->assertSame('Solved', Thread::statusCodeToName(Thread::STATUS_CLOSED));
    }

    public function test_closed_folder_name_reads_solved()
    {
        $folder = new Folder();
        $folder->type = Folder::TYPE_CLOSED;

        $this->assertSame('Solved', $folder->getTypeName());
    }

    /**
     * Only the label changes — the underlying status value, and anything
     * that compares against it, must be untouched.
     */
    public function test_status_closed_value_is_unchanged()
    {
        $this->assertSame(3, Conversation::STATUS_CLOSED);
        $this->assertSame(3, Thread::STATUS_CLOSED);
    }
}
