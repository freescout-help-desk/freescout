<?php

namespace Modules\ArmsReports\Services;

use App\Conversation;
use App\Thread;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The "Agent Performance" page/export: §5.2 — per-assignee first-reply
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
        $conversations = $this->filters
            ->applyToConversations(DB::table('conversations'))
            ->whereNotNull('conversations.user_id')
            ->leftJoin('users', 'users.id', '=', 'conversations.user_id')
            ->select(
                'conversations.id',
                'conversations.created_at',
                'conversations.closed_at',
                'conversations.status',
                'conversations.first_reply_at',
                DB::raw("TRIM(CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, ''))) as agent")
            )
            ->get();

        $derived = [];
        if ($conversations->count()) {
            $derived = DB::table('threads')
                ->select('conversation_id', DB::raw('MIN(created_at) as first_reply'))
                ->whereIn('conversation_id', $conversations->pluck('id'))
                ->where('type', Thread::TYPE_MESSAGE)
                ->where('state', Thread::STATE_PUBLISHED)
                ->whereNotNull('created_by_user_id')
                ->groupBy('conversation_id')
                ->pluck('first_reply', 'conversation_id')
                ->all();
        }

        $byAgent = [];
        foreach ($conversations as $conv) {
            $agent = $conv->agent ?: __('Unknown');
            $byAgent[$agent] = $byAgent[$agent] ?? ['tickets' => 0, 'responses' => [], 'resolutions' => []];
            $byAgent[$agent]['tickets']++;

            $firstReply = $conv->first_reply_at ?: ($derived[$conv->id] ?? null);
            if ($firstReply) {
                $byAgent[$agent]['responses'][] = Carbon::parse($firstReply)->diffInSeconds(Carbon::parse($conv->created_at));
            }
            if ((int) $conv->status === Conversation::STATUS_CLOSED && $conv->closed_at) {
                $byAgent[$agent]['resolutions'][] = Carbon::parse($conv->closed_at)->diffInSeconds(Carbon::parse($conv->created_at));
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
