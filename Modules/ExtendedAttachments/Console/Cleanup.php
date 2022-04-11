<?php

namespace Modules\ExtendedAttachments\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;

class Cleanup extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:extendedattachments-cleanup';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Clear archives created by download all function.';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \Adldap\Models\ModelNotFoundException
     */
    public function handle()
    {
        $this->info("Starting cleaning up archives...");

        $storage = \Helper::getPrivateStorage();

        $archives = $storage->files(DIRECTORY_SEPARATOR.'extendedattachments');

        foreach ($archives as $path) {
            if (!preg_match("/\.zip$/", $path)) {
                continue;
            }
            // todo: check date.
            $storage->delete($path);
            $this->line("Deleted: ".$path);
        }

        $this->info("Finished");
    }
}
