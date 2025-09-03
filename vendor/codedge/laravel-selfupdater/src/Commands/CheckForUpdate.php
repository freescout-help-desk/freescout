<?php

namespace Codedge\Updater\Commands;

use Illuminate\Console\Command;
use Codedge\Updater\UpdaterManager;

class CheckForUpdate extends Command
{
    /**
     * @var string
     */
    protected $signature = 'updater:check-for-update
                            {--prefixVersionWith= : Prefix the currently installed version with something.}
                            {--suffixVersionWith= : Suffix the currently installed version with something.}';

    /**
     * @var string
     */
    protected $description = 'Check if a new update is available.';

    /**
     * @var UpdaterManager
     */
    protected $updater;

    /**
     * CheckForUpdate constructor.
     *
     * @param UpdaterManager $updater
     */
    public function __construct(UpdaterManager $updater)
    {
        parent::__construct();
        $this->updater = $updater;
    }

    /**
     * Execute the command.
     */
    public function handle()
    {
        $prefix = $this->option('prefixVersionWith');
        $suffix = $this->option('suffixVersionWith');

        $currentVersion = $this->updater->source()->getVersionInstalled($prefix, $suffix);
        $isAvail = $this->updater->source()->isNewVersionAvailable($currentVersion);

        if ($isAvail) {
            $newVersion = $this->updater->source()->getVersionAvailable();
            $this->info('A new version ['.$newVersion.'] is available.');
        } else {
            $this->comment('There\'s no new version available.');
        }
    }
}
