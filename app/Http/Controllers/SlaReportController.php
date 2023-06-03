<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SlaReportController extends Controller
{
    public function slaReport(Request $request)
    {

        // 1. Get all request parameter
        $ticketFilter=$request->input('ticket');
        $productFilter=$request->input('product');
        $typeFilter=$request->input('type');
        $mailboxId=$request->input('mailbox');
        $dateFromFilter=$request->input('from');
        $dateToFilter=$request->input('to');

        $filters['to'] = User::dateFormat(date('Y-m-d H:i:s'), 'Y-m-d', null, false);
        $filters['from'] = date('Y-m-d', strtotime($filters['to'].' -1 week'));

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

        $tickets = Conversation::with('user', 'conversationCustomField.custom_field', 'conversationCategory','conversationPriority','conversationEscalated');


        return view('sla/report', compact('tickets','categoryValues','productValues','filters','ticketFilter','productFilter','typeFilter','mailboxId'));
    }
}
