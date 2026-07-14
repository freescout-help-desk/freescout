<?php

namespace Modules\ArmsReports\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Parsed filter bar state, shared by every report and export.
 */
class ReportFilters
{
    /** @var Carbon */
    public $from;

    /** @var Carbon */
    public $to;

    /** @var int|null */
    public $mailbox_id;

    /** @var int|null */
    public $user_id;

    public static function fromRequest(Request $request)
    {
        $filters = new self();

        $filters->from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->subDays(29)->startOfDay();

        $filters->to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        if ($filters->to->lt($filters->from)) {
            [$filters->from, $filters->to] = [$filters->to->copy()->startOfDay(), $filters->from->copy()->endOfDay()];
        }

        $filters->mailbox_id = $request->input('mailbox_id') ? (int) $request->input('mailbox_id') : null;
        $filters->user_id = $request->input('user_id') ? (int) $request->input('user_id') : null;

        return $filters;
    }

    /**
     * Apply the shared constraints to a conversations query builder
     * (works for both Eloquent and DB::table builders).
     */
    public function applyToConversations($query, $table = 'conversations')
    {
        $query->where($table.'.state', \App\Conversation::STATE_PUBLISHED)
            ->whereBetween($table.'.created_at', [$this->from, $this->to]);

        if ($this->mailbox_id) {
            $query->where($table.'.mailbox_id', $this->mailbox_id);
        }
        if ($this->user_id) {
            $query->where($table.'.user_id', $this->user_id);
        }

        return $query;
    }

    /**
     * Number of days in the selected range (min 1), for per-day averages.
     */
    public function days()
    {
        return max(1, $this->from->diffInDays($this->to) + 1);
    }
}
