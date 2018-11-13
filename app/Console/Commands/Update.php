<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class Update extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:update {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update application to the latest version from GitHub';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        @ini_set('memory_limit', '128M');

        if (\Updater::isNewVersionAvailable(config('app.version'))) {
            $this->info('Updating... This may take several minutes');

            try {
                // Script may fail here and stop with the error:
                // PHP Fatal error:  Allowed memory size of 94371840 bytes exhausted
                \Updater::update();
                $this->call('freescout:after-app-update');
            } catch (\Exception $e) {
                $this->error('Error occured: '.$e->getMessage());
            }
        } else {
            $this->info('You have the latest version installed: '.config('app.version'));
        }
    }
}
