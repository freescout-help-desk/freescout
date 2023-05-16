<?php

namespace App\Http\Controllers;

use App\Conversation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //Index Route
    public function index()
    {
        $today = Carbon::today();
        $fourDaysAgo = Carbon::today()->subDays(4);
        $sevenDaysAgo = Carbon::today()->subDays(7);
        $thirtyDaysAgo = Carbon::today()->subDays(30);

        $results = Conversation::select(
            DB::raw('COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as opened_today_count'),
            DB::raw('COUNT(CASE WHEN created_by_user_id IS NULL THEN 1 END) as unassigned_count'),
            DB::raw('COUNT(CASE WHEN created_at < ? AND closed_at IS NULL THEN 1 END) as overdue_count'),
            DB::raw('COUNT(CASE WHEN closed_at IS NULL THEN 1 END) as unclosed_count'),
            DB::raw('COUNT(CASE WHEN closed_at IS NULL AND last_reply_at < ? THEN 1 END) as unclosed_replied_7_days_ago_count'),
            DB::raw('COUNT(CASE WHEN closed_at IS NULL AND created_at < ? THEN 1 END) as unclosed_created_30_days_ago_count')
        )
            ->setBindings([$today, $fourDaysAgo, $sevenDaysAgo, $thirtyDaysAgo])
            ->first();

        $openedTodayCount = $results->opened_today_count;
        $unassignedCount = $results->unassigned_count;
        $overdueCount = $results->overdue_count;
        $unclosedCount = $results->unclosed_count;
        $unclosedReplied7DaysAgoCount = $results->unclosed_replied_7_days_ago_count;
        $unclosedCreated30DaysAgoCount = $results->unclosed_created_30_days_ago_count;

        return view('/dashboard/dashboard', compact('openedTodayCount', 'unassignedCount','overdueCount', 'unclosedCount','unclosedReplied7DaysAgoCount', 'unclosedCreated30DaysAgoCount', ));
    }
}