<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SlaReportController extends Controller
{
    public function slaReport(Request $request)
    {

        $today = Carbon::today();
        $fourDaysAgo = Carbon::today()->subDays(4);
        $sevenDaysAgo = Carbon::today()->subDays(7);
        $thirtyDaysAgo = Carbon::today()->subDays(30);
        $prev = false;
        $date_field = 'conversations.created_at';
        $date_field_to = '';
        // 1. Get all request parameter
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



        // Ticket Category Labels for Filters
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

        $tickets = Conversation::query();
        $tickets = $tickets->with('user', 'conversationCustomField.custom_field', 'conversationCategory','conversationPriority','conversationEscalated');
        if (!empty($categoryIndex) || !empty($productIndex)) {
            $tickets = $tickets->join('conversation_custom_field', 'conversations.id', '=', 'conversation_custom_field.conversation_id')
                ->join('custom_fields', 'conversation_custom_field.custom_field_id', '=', 'custom_fields.id')
                ->where('custom_fields.name', 'Ticket Category')
                ->where('conversation_custom_field.value', $categoryIndex)
                ->orWhere('custom_fields.name', 'Product')
                ->where('conversation_custom_field.value', $productIndex)
                ->select('conversations.*');
        }
        // Filtering based on Mailbox selected
        if (!empty($filters['mailbox'])) {
            $tickets = $tickets->where('conversations.mailbox_id', $filters['mailbox']);
        }
        if (!empty($filters['type'])) {
            $tickets = $tickets->where('conversations.type', $filters['type']);
        }

        if (!empty($from)) {
            $tickets->where($date_field, '>=', date('Y-m-d 00:00:00', strtotime($from)));
        }
        if (!empty($to)) {
            $tickets->where($date_field_to, '<=', date('Y-m-d 23:59:59', strtotime($to)));
        }

        $tickets = $tickets->where('conversations.threads_count', '!=', '0')->get();

        return view('sla/report', compact('tickets','categoryValues','productValues','filters'));
    }
}
