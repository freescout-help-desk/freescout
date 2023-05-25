<?php

namespace App\Http\Controllers;

use App\Conversation;
use Carbon\Carbon;
use Exception;
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
            DB::raw('COUNT(*) as total_count'),
            DB::raw('COUNT(CASE WHEN created_by_user_id IS NULL THEN 1 END) as unassigned_count'),
            DB::raw('COUNT(CASE WHEN created_at < ? AND closed_at IS NULL THEN 1 END) as overdue_count'),
            DB::raw('COUNT(CASE WHEN closed_at IS NULL THEN 1 END) as unclosed_count'),
            DB::raw('COUNT(CASE WHEN closed_at IS NOT NULL AND created_at < ? THEN 1 END) as closed_tickets_count'),
            DB::raw('COUNT(CASE WHEN closed_at IS NULL AND created_at < ? THEN 1 END) as unclosed_created_30_days_ago_count')
        )
            ->setBindings([$today, $fourDaysAgo, $sevenDaysAgo, $thirtyDaysAgo])
            ->first();

        $totalCount = $results->total_count;
        $unassignedCount = $results->unassigned_count;
        $overdueCount = $results->overdue_count;
        $unclosedCount = $results->unclosed_count;
        $closedCount = $results->closed_tickets_count;
        $unclosedCreated30DaysAgoCount = $results->unclosed_created_30_days_ago_count;

        // For Weekly data
        $startDate = now()->startOfWeek();
        $endDate = now()->endOfWeek();
        $daysOfWeek = [
            'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'
        ];

        $ticketsInitial = Conversation::selectRaw('DAYNAME(created_at) as day, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();
        $tickets = [];
        foreach ($daysOfWeek as $day) {
            $tickets[$day] = $ticketsInitial[$day] ?? 0;
        };

        // Ticket Category Labels
        $values = DB::table('custom_fields')
            ->where('name', 'Ticket Category')
            ->pluck('options');

        // var_dump($values);die();
        $categoryValues = [];
        if(!empty($values)){
            try{
                $options = json_decode($values[0], true);
                foreach ($options as $key => $value) {
                    array_push($categoryValues, $value);
                }
            }catch(Exception $ex){

            }
        }
        // Category Tickets
        return view('/dashboard/dashboard', compact('totalCount', 'unassignedCount', 'overdueCount', 'unclosedCount', 'closedCount', 'unclosedCreated30DaysAgoCount', 'tickets', 'categoryValues'));
    }
}
