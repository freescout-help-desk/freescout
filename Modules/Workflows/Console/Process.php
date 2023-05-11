<?php

namespace Modules\Workflows\Console;

use Modules\Workflows\Entities\Workflow;
use App\Mailbox;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;

class Process extends Command
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:workflows-process';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Process date-conditioned workflows.';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \Adldap\Models\ModelNotFoundException
     */
    public function handle()
    {
        $mailboxes = Mailbox::getActiveMailboxes();
        foreach ($mailboxes as $mailbox) {
            $executed_num = Workflow::processWorkflowsForMailbox($mailbox->id);
            $this->line($mailbox->name.': '.(int)$executed_num.' workflows executed');
        }
    }
}
