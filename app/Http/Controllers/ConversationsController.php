<?php

namespace App\Http\Controllers;

use App\Conversation;
use Illuminate\Http\Request;

class ConversationsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * View conversation.
     */
    public function view($id)
    {
        $conversation = Conversation::findOrFail($id);

        $this->authorize('view', $conversation);

        return view('conversations/view', [
            'conversation' => $conversation,
            'mailbox'      => $conversation->mailbox,
            'customer'     => $conversation->customer,
            'threads'      => $conversation->threads()->orderBy('created_at', 'desc')->get(),
            'folder'       => $conversation->folder,
            'folders'      => $conversation->mailbox->getAssesibleFolders(),
        ]);
    }
}
