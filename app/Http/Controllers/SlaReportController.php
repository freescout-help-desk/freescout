<?php

namespace App\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;

class SlaReportController extends Controller
{
    public function slaReport()
    {
        $tickets = Conversation::with('user', 'conversationCustomField.custom_field', 'conversationCategory')->get();
        return view('sla/report', compact('tickets'));
    }
}
