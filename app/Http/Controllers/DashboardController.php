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
        $prev = false;
        $date_field = 'conversations.created_at';
        $date_field_to = '';

        $filters = [
            'ticket' => $request->input('ticket'),
            'product' => $request->input('product'),
            'type' => $request->input('type'),
            'mailbox' => $request->input('mailbox'),
            'from' => $request->input('from'),
            'to' => $request->input('to')
        ];

        //Filter accourding timezon
        if($request->has('from') && $request->has('to')){
            $from = $request->input('from');
            $to =  $request->input('to');
        }else{
            $from = Carbon::today()->subDays(7);
            $to = Carbon::today();
            $filters['from'] = $from->format('Y-m-d');
            $filters['to'] = $to->format('Y-m-d');
        }

        if (!$date_field_to) {
            $date_field_to = $date_field;
        }

        if ($prev) {
            if ($from && $to) {
                $from_carbon = Carbon::parse($from);
                $to_carbon = Carbon::parse($to);

                $days = $from_carbon->diffInDays($to_carbon);

                if ($days) {
                    $from = $from_carbon->subDays($days)->format('Y-m-d');
                    $to = $to_carbon->subDays($days)->format('Y-m-d');
                }
            }
        }

        // Ticket Category Labels
        $values = DB::table('custom_fields')
            ->where('name', 'Ticket Category')
            ->pluck('options');

        $categoryValues = [];
        if (!empty($values)) {
            try {
                $options = json_decode($values[0], true);
                foreach ($options as $key => $value) {
                    array_push($categoryValues, $value);
                }
            } catch (Exception $ex) {
            }
        }
        // Product value Labels
        $values = DB::table('custom_fields')
            ->where('name', 'Product')
            ->pluck('options');

        $productValues = [];
        if (!empty($values)) {
            try {
                $options = json_decode($values[0], true);
                foreach ($options as $key => $value) {
                    array_push($productValues, $value);
                }
            } catch (Exception $ex) {
            }
        }
        $categoryIndex = '';
        $productIndex = '';

        if ( $filters['ticket'] === '0' || $filters['ticket'] === null) {
            $categoryIndex = 0;
        } else {
            $categoryIndex = array_search($filters['ticket'], $categoryValues) + 1;
        }
        if ($filters['product'] === '0' || $filters['product'] === null) {
            $productIndex = 0;
        } else {
            $productIndex = array_search($filters['product'], $productValues) + 1;
        }


        // Make empty query
        $query = Conversation::query();


        if (!empty($categoryIndex) || !empty($productIndex)) {
            $query = $query->join('conversation_custom_field', 'conversations.id', '=', 'conversation_custom_field.conversation_id')
                ->join('custom_fields', 'conversation_custom_field.custom_field_id', '=', 'custom_fields.id')
                ->where('custom_fields.name', 'Ticket Category')
                ->where('conversation_custom_field.value', $categoryIndex)
                ->orWhere('custom_fields.name', 'Product')
                ->where('conversation_custom_field.value', $productIndex)
                ->select('conversations.*');
        }
        // Filtering based on Mailbox selected
        if (!empty($filters['mailbox'])) {
            $query = $query->where('conversations.mailbox_id', $filters['mailbox']);
        }
        if (!empty($filters['type'])) {
            $query = $query->where('conversations.type', $filters['type']);
        }

        if (!empty($from) || !empty($to)) {
            $query->whereBetween($date_field, [$from, $to]);
        }

        // Extract the data
        $results = $query->select(
            DB::raw('COUNT(*) as total_count'),
            DB::raw('COUNT(CASE WHEN user_id IS NULL THEN 1 END) as unassigned_count'),
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
        // Category Tickets
        return view('dashboard.dashboard', compact('totalCount', 'unassignedCount', 'overdueCount', 'unclosedCount', 'closedCount', 'unclosedCreated30DaysAgoCount', 'tickets', 'categoryValues', 'filters', 'productValues'));
    }
}
