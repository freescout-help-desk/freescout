<?php

namespace Barryvdh\TranslationManager\Console;

use Barryvdh\TranslationManager\Manager;
use Illuminate\Console\Command;

class CleanCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'translations:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean empty translations';

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
        $this->manager->cleanTranslations();
        $this->info('Done cleaning translations');
    }
}
