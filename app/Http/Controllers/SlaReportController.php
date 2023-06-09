<?php

namespace App\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Settingssla;
use Exception;

class SlaReportController extends Controller
{
    public function slaReport()
    {
        $tickets = Conversation::with('user', 'conversationCustomField.custom_field', 'conversationCategory','conversationPriority')->get();
        return view('sla/report', compact('tickets'));
    }
    // public function settings(){
    //     return view('settings');
    // }
    public function addDataSettings(Request $request){
        $settingslas=new Settingssla;
        $settingslas->to_email=$request->to_email;
        $settingslas->frequency=$request->frequency;
        $settingslas->schedule=$request->schedule;
        $settingslas->time=$request->time;
        $settingslas->save();
        
        return redirect('/reports/sla');
    
       }
}
