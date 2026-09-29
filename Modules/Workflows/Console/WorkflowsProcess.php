<?php

namespace Modules\Workflows\Console;

use App\Conversation;
use Illuminate\Console\Command;
use Modules\Workflows\Entities\Workflow;
use Modules\Workflows\Services\WorkflowRunner;

class WorkflowsProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:workflows-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled workflows';

    /**
     * Date rows are the only conditions this command runs.
     *
     * @var array
     */
    private static $dateTypes = [
        'waiting_since',
        'last_user_reply',
        'last_customer_reply',
        'date_created',
    ];

    /**
     * Run scheduled workflows for mailboxes that have an active automatic date rule.
     *
     * @return void
     */
    public function handle()
    {
        $mailboxIds = Workflow::query()
            ->where('active', 1)
            ->where('type', 'automatic')
            ->whereHas('conditions', function ($query) {
                $query->whereIn('type', self::$dateTypes);
            })
            ->pluck('mailbox_id')
            ->unique();

        foreach ($mailboxIds as $mailboxId) {
            Conversation::query()
                ->where('mailbox_id', $mailboxId)
                ->where('state', Conversation::STATE_PUBLISHED)
                ->chunk(100, function ($conversations) {
                    foreach ($conversations as $conversation) {
                        WorkflowRunner::runMailbox($conversation, ['name' => 'schedule']);
                    }
                });
        }
    }
}
