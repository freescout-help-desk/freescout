<?php

namespace App\Jobs;

use App\Conversation;
use App\ConversationFolder;
use App\Folder;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateFolderCounters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Folder
     */
    private $folder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if ( \Eventy::filter( 'folder.update_counters', false, $this->folder ) ) {
                \Cache::forget( 'updating_folder_' . $this->folder->id );

                return;
            }
            if ( $this->folder->type == Folder::TYPE_MINE && $this->folder->user_id ) {
                $this->folder->active_count = Conversation::where( 'user_id', $this->folder->user_id )
                                                          ->where( 'mailbox_id', $this->folder->mailbox_id )
                                                          ->where( 'state', Conversation::STATE_PUBLISHED )
                                                          ->where( 'status', Conversation::STATUS_ACTIVE )
                                                          ->count();
                $this->folder->total_count  = Conversation::where( 'user_id', $this->folder->user_id )
                                                          ->where( 'mailbox_id', $this->folder->mailbox_id )
                                                          ->where( 'state', Conversation::STATE_PUBLISHED )
                                                          ->count();
            } else if ( $this->folder->type == Folder::TYPE_STARRED ) {
                $this->folder->active_count = count( Conversation::getUserStarredConversationIds( $this->folder->mailbox_id, $this->folder->user_id ) );
                $this->folder->total_count  = $this->folder->active_count;
            } else if ( $this->folder->type == Folder::TYPE_DELETED ) {
                $this->folder->active_count = $this->folder->conversations()->where( 'state', Conversation::STATE_DELETED )
                                                           ->count();
                $this->folder->total_count  = $this->folder->active_count;
            } else if ( $this->folder->isIndirect() ) {
                // Conversation are connected to folder via conversation_folder table.
                // Drafts.
                $this->folder->active_count = ConversationFolder::where( 'conversation_folder.folder_id', $this->folder->id )
                                                                ->join( 'conversations', 'conversations.id', '=', 'conversation_folder.conversation_id' )
                    //->where('state', Conversation::STATE_PUBLISHED)
                                                                ->count();
                $this->folder->total_count  = $this->folder->active_count;
            } else {
                $this->folder->active_count = $this->folder->conversations()
                                                           ->where( 'state', Conversation::STATE_PUBLISHED )
                                                           ->where( 'status', Conversation::STATUS_ACTIVE )
                                                           ->count();
                $this->folder->total_count  = $this->folder->conversations()
                                                           ->where( 'state', Conversation::STATE_PUBLISHED )
                                                           ->count();
            }
            $this->folder->save();
        } catch ( \Exception $e ) {
            // Always clear the cache
        }
        \Cache::forget( 'updating_folder_' . $this->folder->id );
    }
}
