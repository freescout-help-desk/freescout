<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\Mailbox;
use Carbon\Carbon;
use Exception;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Reports\Http\Controllers\ReportsController;



class DashboardController extends Controller
{
    //Index Route
    public function index(Request $request)
    {
        
        
        $today = Carbon::today();
        $fourDaysAgo = Carbon::today()->subDays(4);
        $sevenDaysAgo = Carbon::today()->subDays(7);
        $thirtyDaysAgo = Carbon::today()->subDays(30);

        $ticketFilter=$request->input('ticket');
        $productFilter=$request->input('product');
        $typeFilter=$request->input('type');
        $mailboxId=$request->input('mailbox');

        $dateFromFilter=$request->input('from');
        $dateToFilter=$request->input('to');

        // Make empty query
        $query = Conversation::query();

        // Filtering based on Mailbox selected
        if(!empty($mailboxId)){
            $query = $query->where('mailbox_id', $mailboxId);
        }
        if(!empty($typeFilter)){
            $query = $query->where('type', $typeFilter);
        }
        //TODO : need to fixed this filter
        // if(!empty($ticketFilter)){
        //     $query = $query->where('mailbox_id', $ticketFilter);
        // }
        // if(!empty($productFilter)){
        //     $query = $query->where('mailbox_id', $productFilter);
        // }
        // if(!empty($dateFromFilter)){
        //     $query = $query->where('created_at', $dateFromFilter);
        // }
        // if(!empty($dateToFilter)){
        //     $query = $query->where('updated_at', $dateToFilter);
        // }

        // Extract the data
        $results = $query->select(
            DB::raw('COUNT(*) as total_count'),
            DB::raw('COUNT(CASE WHEN created_by_user_id IS NULL THEN 1 END) as unassigned_count'),
            // DB::raw('COUNT(CASE WHEN closed_at IS NULL THEN 1 END) as overdue_count'),
            // DB::raw('COUNT(CASE WHEN created_at < ? AND closed_at IS NULL THEN 1 END) as overdue_count'),
            DB::raw('COUNT(CASE WHEN closed_at IS NULL THEN 1 END) as unclosed_count'),
            DB::raw('COUNT(CASE WHEN closed_at IS NOT NULL THEN 1 END) as closed_tickets_count'),
            DB::raw('COUNT(CASE WHEN closed_at IS NULL THEN 1 END) as unclosed_created_30_days_ago_count')
        )
        ->first();


        $totalCount = $results->total_count;
        $unassignedCount = $results->unassigned_count;
        $overdueCount = 0;
        $unclosedCount = $results->unclosed_count;
        $closedCount = $results->closed_tickets_count;
        $unclosedCreated30DaysAgoCount = $results->unclosed_created_30_days_ago_count;

        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

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

        $categoryValues = [];
        if(!empty($values)){
            try{
                $options = json_decode($values[0], true);
                foreach ($options as $key => $value) {
                    array_push($categoryValues, $value);
                }
            }catch(Exception $ex){

            }
            // Product value Labels
        $values = DB::table('custom_fields')
            ->where('name', 'Product')
            ->pluck('options');

        $productValues = [];
        if(!empty($values)){
            try{
                $options = json_decode($values[0], true);
                foreach ($options as $key => $value) {
                    array_push($productValues, $value);
                }
            }catch(Exception $ex){

            }
        }

        }
        // Category Tickets
        return view('dashboard.dashboard', compact('totalCount', 'unassignedCount', 'overdueCount', 'unclosedCount', 'closedCount', 'unclosedCreated30DaysAgoCount', 'tickets', 'categoryValues','filters','productValues'));
    }
}
