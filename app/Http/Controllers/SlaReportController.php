<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SlaReportController extends Controller
{
    public function slaReport()
    {
        return view('sla/report');
    }
}
