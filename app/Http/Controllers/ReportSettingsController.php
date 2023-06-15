<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Settingssla;

class ReportSettingsController extends Controller
{

    public function index(){
        
      $settings=Settingssla::orderBy('id', 'desc')->first();
      return view('/sla/settings',compact('settings'));
      
    }

    public function addDataSettings(Request $request){
    $settingslas=new Settingssla;
    $settingslas->to_email=$request->to_email;
    $settingslas->frequency=$request->frequency;
    $settingslas->schedule=$request->schedule;
    $settingslas->time=$request->time;
    $auto=$request->auto_data;
    if($auto==""){
        $settingslas->auto_data="0";
    }else{
        $settingslas->auto_data=$request->auto_data;
    }
    $settingslas->save();
    return redirect('/reports/settings');
   }

}
