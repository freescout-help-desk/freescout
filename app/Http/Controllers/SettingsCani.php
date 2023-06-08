<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Settingssla;

class SettingsCani extends Controller
{
    public function addDataSettings(Request $request){
    $settingslas=new Settingssla;
    $settingslas->to_email=$request->to_email;
    $settingslas->frequency=$request->frequency;
    $settingslas->schedule=$request->schedule;
    $settingslas->time=$request->time;
    $settingslas->save();

   

   }

}
