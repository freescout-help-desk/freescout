<?php

namespace Tests\Feature;

use App\Conversation;
use App\Folder;
use App\Mailbox;
use App\User;
use Tests\TestCase;

class ConversationDeleteForeverTest extends TestCase
{
    /**
     * Deleting a starred conversation forever must reset the Starred counter.
     */
    public function testDeleteForeverUpdatesStarredCounter()
    {
        $user = factory(User::class)->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $mailbox = factory(Mailbox::class)->create();
        $conversation = factory(Conversation::class)->create([
            'mailbox_id'         => $mailbox->id,
            'created_by_user_id' => $user->id,
        ]);
        $conversation->star($user);

        $folder = Folder::where('mailbox_id', $mailbox->id)
            ->where('user_id', $user->id)
            ->where('type', Folder::TYPE_STARRED)
            ->first();
        $this->assertEquals(1, $folder->fresh()->active_count);

        $conversation->deleteForever();

        $this->assertEquals(0, $folder->fresh()->active_count);
    }
}
