<?php

namespace App\Jobs;

use App\Folder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateFolderCounters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $folder;

    // Cache lock key
    private $lockKey;

    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
        $this->lockKey = "folder_update_lock_{$folder->id}";
    }

    public function handle()
    {
        // Check if another job is processing this folder
        if (Cache::has($this->lockKey)) {
            return; // Exit if already processing
        }

        // Set lock with a timeout of 5 minutes (adjust as needed)
        Cache::put($this->lockKey, true, now()->addMinutes(5));

        try {
            // Perform the folder update logic
            $this->folder->updateCountersNow();
        } finally {
            // Release the lock after processing
            Cache::forget($this->lockKey);
        }
    }
}