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
        $this->folder->updateCountersNow();
    }
}
