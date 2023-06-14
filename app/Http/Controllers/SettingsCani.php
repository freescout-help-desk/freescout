<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Settingssla;

class SettingsCani extends Controller
{

    public function index(){
        // $settingslas=Settingssla::all();
        // return view('/sla/settings',compact('settingslas'));
        // return $settingslas;
        $settings2=Settingssla::all();
        return view('/sla/settings',compact('settings2'));
       
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
    // // $settingslas->auto_data=$request->auto_data;
    // // if($auto=="1"){
    // //     $settingslas->save();
    // // }
    $settingslas->save();
    return redirect('/reports/settings');
    // return $settingslas;

   }

}
