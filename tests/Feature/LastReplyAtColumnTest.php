<?php

namespace Tests\Feature;

use App\Conversation;
use App\Folder;
use App\Mailbox;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Covers ARMS-36: the "Last Replied At" column in the conversation list.
 * Pure core (no paid-module dependency), so this runs against the real
 * conversations/folders/mailboxes tables inside a transaction, unlike the
 * ad hoc-table tests elsewhere in this suite.
 */
class LastReplyAtColumnTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeMailbox()
    {
        return factory(Mailbox::class)->create();
    }

    protected function makeFolder($mailboxId, $type = Folder::TYPE_UNASSIGNED)
    {
        return factory(Folder::class)->create(['mailbox_id' => $mailboxId, 'type' => $type]);
    }

    protected function makeUser($mailboxId = null)
    {
        $user = factory(User::class)->create(['role' => User::ROLE_USER]);
        if ($mailboxId) {
            $user->mailboxes()->attach($mailboxId);
        }

        return $user;
    }

    protected function makeConversation($mailboxId, $folderId, $lastReplyAt, $userId)
    {
        return factory(Conversation::class)->create([
            'mailbox_id'         => $mailboxId,
            'folder_id'          => $folderId,
            'last_reply_at'      => $lastReplyAt,
            'created_by_user_id' => $userId,
        ]);
    }

    /**
     * The whole point of the new column: it must keep showing
     * last_reply_at in folders where "Waiting Since" shows something else
     * entirely (closed_at here).
     */
    public function test_last_reply_at_is_independent_of_waiting_since_field()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id, Folder::TYPE_CLOSED);
        $user = $this->makeUser($mailbox->id);

        $conversation = $this->makeConversation($mailbox->id, $folder->id, '2026-01-05 10:00:00', $user->id);
        $conversation->closed_at = '2026-01-10 12:00:00';
        $conversation->save();

        // Waiting Since, in the Closed folder, reads closed_at...
        $this->assertSame(
            \App\User::dateDiffForHumans('2026-01-10 12:00:00'),
            $conversation->getWaitingSince($folder)
        );

        // ...but the new column must still reflect last_reply_at, not closed_at.
        $this->assertSame(
            \App\User::dateDiffForHumans('2026-01-05 10:00:00'),
            $conversation->getLastReplyAtHuman()
        );
    }

    public function test_last_reply_at_human_is_empty_when_there_is_no_reply_yet()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $user = $this->makeUser($mailbox->id);

        $conversation = $this->makeConversation($mailbox->id, $folder->id, null, $user->id);

        $this->assertSame('', $conversation->getLastReplyAtHuman());
    }

    public function test_sort_by_last_reply_at_is_accepted()
    {
        request()->merge(['sorting' => ['sort_by' => 'last_reply_at', 'order' => 'asc']]);

        $sorting = Conversation::getConvTableSorting();

        $this->assertSame('last_reply_at', $sorting['sort_by']);
        $this->assertSame('asc', $sorting['order']);
    }

    /**
     * Unrecognized sort_by values must still be rejected — proves the
     * whitelist addition didn't accidentally turn into an open allowlist.
     */
    public function test_arbitrary_sort_by_is_still_rejected()
    {
        request()->merge(['sorting' => ['sort_by' => 'phones', 'order' => 'asc']]);

        $sorting = Conversation::getConvTableSorting();

        $this->assertSame('date', $sorting['sort_by']);
    }

    /**
     * getOrderByArray()'s generic "anything but 'date' replaces the whole
     * order" branch must produce a plain last_reply_at order — checked
     * against a folder type whose own default order is NOT last_reply_at
     * (Closed defaults to closed_at), proving the sort request actually
     * overrides the folder default rather than being ignored.
     */
    public function test_order_by_array_uses_last_reply_at_when_requested()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id, Folder::TYPE_CLOSED);

        request()->merge(['sorting' => ['sort_by' => 'last_reply_at', 'order' => 'asc']]);

        $this->assertSame([['last_reply_at' => 'asc']], $folder->getOrderByArray());
    }

    /**
     * End-to-end proof, not just SQL-shape inspection: seed real
     * conversations and confirm the actual query returns them ordered by
     * last_reply_at, in a folder whose own default order is unrelated.
     */
    public function test_sorting_by_last_reply_at_orders_conversations_end_to_end()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id, Folder::TYPE_CLOSED);
        $user = $this->makeUser($mailbox->id);

        $oldest = $this->makeConversation($mailbox->id, $folder->id, '2026-01-01 00:00:00', $user->id);
        $newest = $this->makeConversation($mailbox->id, $folder->id, '2026-01-03 00:00:00', $user->id);
        $middle = $this->makeConversation($mailbox->id, $folder->id, '2026-01-02 00:00:00', $user->id);

        request()->merge(['sorting' => ['sort_by' => 'last_reply_at', 'order' => 'asc']]);

        $query = Conversation::getQueryByFolder($folder, $user->id);
        $results = $folder->queryAddOrderBy($query)->get();

        $this->assertSame(
            [$oldest->id, $middle->id, $newest->id],
            $results->pluck('id')->all()
        );
    }

    /**
     * Render-level check: the new header and cell actually appear in the
     * table output, with the sort attribute the generic JS click handler
     * (main.js's .conv-col-sort) reads.
     */
    public function test_table_renders_last_replied_at_header_and_cell()
    {
        $mailbox = $this->makeMailbox();
        $folder = $this->makeFolder($mailbox->id);
        $user = $this->makeUser($mailbox->id);
        $this->actingAs($user);

        $conversation = $this->makeConversation($mailbox->id, $folder->id, '2026-01-01 00:00:00', $user->id);

        $conversations = Conversation::getQueryByFolder($folder, $user->id)->paginate(50);

        $html = view('conversations/conversations_table', [
            'folder'        => $folder,
            'conversations' => $conversations,
            'params'        => [],
        ])->render();

        $this->assertStringContainsString('data-sort-by="last_reply_at"', $html);
        $this->assertStringContainsString('Last Replied At', $html);
        $this->assertStringContainsString('conv-last-reply-at', $html);
        $this->assertStringContainsString($conversation->getLastReplyAtHuman(), $html);
    }
}
