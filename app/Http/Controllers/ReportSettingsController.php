<?php

namespace App\Http\Controllers;
use App\Conversation;
use Illuminate\Http\Request;
use App\SLASetting;
use Illuminate\Support\Facades\DB;
use Mail;
use Dompdf\Dompdf;
use Dompdf\Options;
use \PDF;


class ReportSettingsController extends Controller
{
    public function index(){

    $settings=SLASetting::orderBy('id', 'desc')->first();
    // return $settings;
    return view('sla.settings',compact('settings'));
}

    public function addDataSettings(Request $request){
    $slaSettings = new SLASetting();
    $slaSettings->to_email=$request->to_email;

    $slaSettings->frequency=$request->frequency;
    if($request->frequency==="Daily"){
        $slaSettings->schedule='null';
    }else{
        $slaSettings->schedule=$request->schedule;
    }
   
    $slaSettings->time=$request->time;
    $auto=$request->auto_data;
    if($auto==""){
        $slaSettings->auto_data="0";
    }else{
        $slaSettings->auto_data=$request->auto_data;
    }
    $slaSettings->save();
    return redirect('/reports/settings');
   }

}
