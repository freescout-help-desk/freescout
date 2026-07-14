<?php

namespace Modules\ArmsReports\Services;

use App\Conversation;
use App\Thread;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The "ARMS KPIs" page/export: §5.3 reports catalogue items that the paid
 * Reports module does not provide, computed from conversations + the
 * threads event table. All methods take ReportFilters so the December
 * portal API can reuse them verbatim.
 */
class KpiReportService
{
    /** @var ReportFilters */
    protected $filters;

    public function __construct(ReportFilters $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Everything the page needs: stat cards + table sections.
     */
    public function build()
    {
        $statusEvents = $this->statusEvents();

        return [
            'cards' => $this->cards($statusEvents),
            'sections' => [
                $this->byHour(),
                $this->byDayOfWeek(),
                $this->replyBrackets(),
                $this->timeInStatus($statusEvents),
                $this->ticketBrandPlaceholder(),
            ],
        ];
    }

    protected function conversations()
    {
        return $this->filters->applyToConversations(DB::table('conversations'));
    }

    protected function cards(array $statusEvents)
    {
        $today = Carbon::today();

        $createdToday = DB::table('conversations')
            ->where('state', Conversation::STATE_PUBLISHED)
            ->where('created_at', '>=', $today)
            ->when($this->filters->mailbox_id, function ($q) {
                $q->where('mailbox_id', $this->filters->mailbox_id);
            })
            ->count();

        $resolvedToday = DB::table('conversations')
            ->where('state', Conversation::STATE_PUBLISHED)
            ->where('status', Conversation::STATUS_CLOSED)
            ->where('closed_at', '>=', $today)
            ->when($this->filters->mailbox_id, function ($q) {
                $q->where('mailbox_id', $this->filters->mailbox_id);
            })
            ->count();

        $total = $this->conversations()->count();
        $days = $this->filters->days();

        $closedIds = $this->conversations()
            ->where('conversations.status', Conversation::STATUS_CLOSED)
            ->pluck('conversations.id');

        $oneTouch = 0;
        if (count($closedIds)) {
            $oneTouch = DB::table('threads')
                ->select('conversation_id', DB::raw('COUNT(*) as replies'))
                ->whereIn('conversation_id', $closedIds)
                ->where('type', Thread::TYPE_MESSAGE)
                ->where('state', Thread::STATE_PUBLISHED)
                ->whereNotNull('created_by_user_id')
                ->groupBy('conversation_id')
                ->havingRaw('COUNT(*) = 1')
                ->get()
                ->count();
        }

        $reopened = $this->reopenedCount($statusEvents);

        [$firstResponseMedian, $resolutionMedian] = $this->medians();

        return [
            ['label' => __('Created today'), 'value' => $createdToday],
            ['label' => __('Resolved today'), 'value' => $resolvedToday],
            ['label' => __('Avg created / day'), 'value' => round($total / $days, 1)],
            ['label' => __('Avg created / week'), 'value' => round($total / $days * 7, 1)],
            ['label' => __('One-touch tickets'), 'value' => $oneTouch.(count($closedIds) ? ' ('.round($oneTouch / count($closedIds) * 100).'%)' : '')],
            ['label' => __('Reopened tickets'), 'value' => $reopened],
            ['label' => __('First-response median'), 'value' => Stats::duration($firstResponseMedian)],
            ['label' => __('First-resolution median'), 'value' => Stats::duration($resolutionMedian)],
        ];
    }

    protected function byHour()
    {
        $rows = $this->conversations()
            ->select(DB::raw('HOUR(conversations.created_at) as h'), DB::raw('COUNT(*) as c'))
            ->groupBy('h')
            ->pluck('c', 'h');

        $max = max(1, $rows->max() ?: 1);
        $table = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $count = $rows[$hour] ?? 0;
            $table[] = [sprintf('%02d:00', $hour), $count, (int) round($count / $max * 100)];
        }

        return [
            'key' => 'by_hour',
            'title' => __('Tickets created by hour'),
            'headers' => [__('Hour'), __('Tickets')],
            'bar' => true, // third column is a percentage width for the CSS bar
            'rows' => $table,
        ];
    }

    protected function byDayOfWeek()
    {
        // MySQL DAYOFWEEK(): 1 = Sunday … 7 = Saturday.
        $rows = $this->conversations()
            ->select(DB::raw('DAYOFWEEK(conversations.created_at) as d'), DB::raw('COUNT(*) as c'))
            ->groupBy('d')
            ->pluck('c', 'd');

        $names = [2 => __('Monday'), 3 => __('Tuesday'), 4 => __('Wednesday'), 5 => __('Thursday'), 6 => __('Friday'), 7 => __('Saturday'), 1 => __('Sunday')];
        $max = max(1, $rows->max() ?: 1);
        $table = [];
        foreach ($names as $dow => $name) {
            $count = $rows[$dow] ?? 0;
            $table[] = [$name, $count, (int) round($count / $max * 100)];
        }

        return [
            'key' => 'by_dow',
            'title' => __('Tickets created by day of week'),
            'headers' => [__('Day'), __('Tickets')],
            'bar' => true,
            'rows' => $table,
        ];
    }

    protected function replyBrackets()
    {
        $convRows = $this->conversations()
            ->leftJoin('users', 'users.id', '=', 'conversations.user_id')
            ->select(
                'conversations.id',
                'conversations.user_id',
                DB::raw("TRIM(CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, ''))) as agent")
            )
            ->get();

        $replyCounts = [];
        if ($convRows->count()) {
            $replyCounts = DB::table('threads')
                ->select('conversation_id', DB::raw('COUNT(*) as c'))
                ->whereIn('conversation_id', $convRows->pluck('id'))
                ->where('type', Thread::TYPE_MESSAGE)
                ->where('state', Thread::STATE_PUBLISHED)
                ->whereNotNull('created_by_user_id')
                ->groupBy('conversation_id')
                ->pluck('c', 'conversation_id')
                ->all();
        }

        $brackets = Stats::bracketLabels();
        $byAgent = [];
        foreach ($convRows as $conv) {
            $agent = $conv->agent ?: __('Unassigned');
            $replies = $replyCounts[$conv->id] ?? 0;
            if ($replies === 0) {
                continue; // no agent replies yet — not attributable to a bracket
            }
            $bracket = Stats::replyBracket($replies);
            $byAgent[$agent] = $byAgent[$agent] ?? array_fill_keys($brackets, 0);
            $byAgent[$agent][$bracket]++;
        }
        ksort($byAgent);

        $rows = [];
        foreach ($byAgent as $agent => $counts) {
            $rows[] = array_merge([$agent], array_values($counts));
        }

        return [
            'key' => 'reply_brackets',
            'title' => __('Tickets by agent — reply brackets'),
            'headers' => array_merge([__('Agent')], $brackets),
            'rows' => $rows,
        ];
    }

    /**
     * Ordered status timeline events for every conversation in range:
     * creation (Active) + lineitem status changes + status-bearing threads.
     * Shared by reopenedCount() and timeInStatus().
     */
    protected function statusEvents()
    {
        $conversations = $this->conversations()
            ->select('conversations.id', 'conversations.created_at', 'conversations.status')
            ->get();

        if (!$conversations->count()) {
            return [];
        }

        $threads = DB::table('threads')
            ->whereIn('conversation_id', $conversations->pluck('id'))
            ->where('state', Thread::STATE_PUBLISHED)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('type', Thread::TYPE_LINEITEM)
                        ->where('action_type', Thread::ACTION_TYPE_STATUS_CHANGED);
                })->orWhereIn('type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE]);
            })
            ->orderBy('created_at')
            ->get(['conversation_id', 'type', 'status', 'created_at']);

        $events = [];
        foreach ($conversations as $conv) {
            $events[$conv->id] = [
                ['status' => Conversation::STATUS_ACTIVE, 'at' => $conv->created_at],
            ];
        }

        foreach ($threads as $thread) {
            $status = (int) $thread->status;
            // Skip "no change" markers and rows without a usable status.
            if (!$status || $status === Thread::STATUS_NOCHANGE) {
                continue;
            }
            $events[$thread->conversation_id][] = ['status' => $status, 'at' => $thread->created_at];
        }

        return $events;
    }

    protected function reopenedCount(array $statusEvents)
    {
        $reopened = 0;
        foreach ($statusEvents as $timeline) {
            $closed = false;
            foreach ($timeline as $event) {
                if ($event['status'] === Conversation::STATUS_CLOSED) {
                    $closed = true;
                } elseif ($closed && in_array($event['status'], [Conversation::STATUS_ACTIVE, Conversation::STATUS_PENDING])) {
                    $reopened++;
                    break;
                }
            }
        }

        return $reopened;
    }

    protected function timeInStatus(array $statusEvents)
    {
        $totals = [];
        $counts = [];

        foreach ($statusEvents as $timeline) {
            $perStatus = [];
            for ($i = 0; $i < count($timeline); $i++) {
                $start = Carbon::parse($timeline[$i]['at']);
                $end = isset($timeline[$i + 1]) ? Carbon::parse($timeline[$i + 1]['at']) : Carbon::now();
                $status = $timeline[$i]['status'];
                if ($status === Conversation::STATUS_CLOSED && !isset($timeline[$i + 1])) {
                    continue; // don't count open-ended time sitting closed
                }
                $perStatus[$status] = ($perStatus[$status] ?? 0) + max(0, $end->diffInSeconds($start));
            }
            foreach ($perStatus as $status => $seconds) {
                $totals[$status] = ($totals[$status] ?? 0) + $seconds;
                $counts[$status] = ($counts[$status] ?? 0) + 1;
            }
        }

        $rows = [];
        foreach ($totals as $status => $seconds) {
            $name = Conversation::statusCodeToName($status) ?: __('Status').' '.$status;
            $rows[] = [$name, $counts[$status], Stats::duration($seconds / max(1, $counts[$status]))];
        }

        return [
            'key' => 'time_in_status',
            'title' => __('Average time in status'),
            'headers' => [__('Status'), __('Tickets'), __('Avg time in status')],
            'rows' => $rows,
        ];
    }

    protected function ticketBrandPlaceholder()
    {
        return [
            'key' => 'ticket_brand',
            'title' => __('Ticket Brand'),
            'headers' => [__('Note')],
            'rows' => [[__('Pending ARMS definition of "Ticket Brand" — renders here once defined (see ARMS-13).')]],
        ];
    }

    /**
     * [first-response median seconds, first-resolution median seconds].
     * First response prefers the first_reply_at column (stamped from launch)
     * and derives from threads for historical rows.
     */
    protected function medians()
    {
        $conversations = $this->conversations()
            ->select('conversations.id', 'conversations.created_at', 'conversations.closed_at', 'conversations.status', 'conversations.first_reply_at')
            ->get();

        if (!$conversations->count()) {
            return [null, null];
        }

        $derived = DB::table('threads')
            ->select('conversation_id', DB::raw('MIN(created_at) as first_reply'))
            ->whereIn('conversation_id', $conversations->pluck('id'))
            ->where('type', Thread::TYPE_MESSAGE)
            ->where('state', Thread::STATE_PUBLISHED)
            ->whereNotNull('created_by_user_id')
            ->groupBy('conversation_id')
            ->pluck('first_reply', 'conversation_id');

        $responseDurations = [];
        $resolutionDurations = [];
        foreach ($conversations as $conv) {
            $firstReply = $conv->first_reply_at ?: ($derived[$conv->id] ?? null);
            if ($firstReply) {
                $responseDurations[] = Carbon::parse($firstReply)->diffInSeconds(Carbon::parse($conv->created_at));
            }
            if ((int) $conv->status === Conversation::STATUS_CLOSED && $conv->closed_at) {
                $resolutionDurations[] = Carbon::parse($conv->closed_at)->diffInSeconds(Carbon::parse($conv->created_at));
            }
        }

        return [Stats::median($responseDurations), Stats::median($resolutionDurations)];
    }
}
