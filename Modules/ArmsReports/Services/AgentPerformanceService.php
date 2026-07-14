<?php

namespace Modules\ArmsReports\Services;

use App\Conversation;
use App\Thread;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The "Agent Performance" page/export (ARMS-13): per-assignee first-reply
 * median and first-resolution median.
 */
class AgentPerformanceService
{
    /** @var ReportFilters */
    protected $filters;

    public function __construct(ReportFilters $filters)
    {
        $this->filters = $filters;
    }

    public function build()
    {
        // Inner join: user_id is non-null here, and name columns are
        // selected raw and concatenated in PHP for DB portability.
        $conversations = $this->filters
            ->applyToConversations(DB::table('conversations'))
            ->whereNotNull('conversations.user_id')
            ->join('users', 'users.id', '=', 'conversations.user_id')
            ->select(
                'conversations.id',
                'conversations.created_at',
                'conversations.closed_at',
                'conversations.status',
                'conversations.first_reply_at',
                'users.first_name',
                'users.last_name'
            )
            ->get();

        $derived = $this->filters
            ->applyToConversations(
                DB::table('threads')->join('conversations', 'conversations.id', '=', 'threads.conversation_id')
            )
            ->whereNotNull('conversations.user_id')
            ->where('threads.type', Thread::TYPE_MESSAGE)
            ->where('threads.state', Thread::STATE_PUBLISHED)
            ->whereNotNull('threads.created_by_user_id')
            ->select('threads.conversation_id', DB::raw('MIN(threads.created_at) as first_reply'))
            ->groupBy('threads.conversation_id')
            ->pluck('first_reply', 'conversation_id')
            ->all();

        $byAgent = [];
        foreach ($conversations as $conv) {
            $agent = trim(($conv->first_name ?? '').' '.($conv->last_name ?? '')) ?: __('Unknown');
            $byAgent[$agent] = $byAgent[$agent] ?? ['tickets' => 0, 'responses' => [], 'resolutions' => []];
            $byAgent[$agent]['tickets']++;

            $firstReply = $conv->first_reply_at ?: ($derived[$conv->id] ?? null);
            if ($firstReply) {
                $byAgent[$agent]['responses'][] = max(0, Carbon::parse($conv->created_at)->diffInSeconds(Carbon::parse($firstReply), false));
            }
            if ((int) $conv->status === Conversation::STATUS_CLOSED && $conv->closed_at) {
                $byAgent[$agent]['resolutions'][] = max(0, Carbon::parse($conv->created_at)->diffInSeconds(Carbon::parse($conv->closed_at), false));
            }
        }
        ksort($byAgent);

        $rows = [];
        foreach ($byAgent as $agent => $data) {
            $rows[] = [
                $agent,
                $data['tickets'],
                Stats::duration(Stats::median($data['responses'])),
                Stats::duration(Stats::median($data['resolutions'])),
            ];
        }

        return [
            'cards' => [],
            'sections' => [[
                'key' => 'agent_performance',
                'title' => __('Agent performance'),
                'headers' => [__('Agent'), __('Tickets handled'), __('First-reply median'), __('First-resolution median')],
                'rows' => $rows,
            ]],
        ];
    }
}
