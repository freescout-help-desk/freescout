<?php

namespace Barryvdh\TranslationManager\Console;

use Illuminate\Console\Command;
use Barryvdh\TranslationManager\Manager;

class ResetCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'translations:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all translations from the database';

    /** @var \Barryvdh\TranslationManager\Manager */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->manager->truncateTranslations();
        $this->info('All translations are deleted');
    }
}
