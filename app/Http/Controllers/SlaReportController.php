<?php

namespace App\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class SlaReportController extends Controller
{
    public function slaReport()
    {
        $tickets = Conversation::with('user', 'conversationCustomField.custom_field', 'conversationCategory','conversationPriority')->get();
        return view('sla/report', compact('tickets'));
    }
}
