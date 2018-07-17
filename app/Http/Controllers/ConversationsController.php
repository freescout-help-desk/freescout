<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Conversation;

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
            'mailbox' => $conversation->mailbox,
            'folder' => $conversation->folder,
            'folders' => $conversation->mailbox->getAssesibleFolders()
        ]);
    }
}
