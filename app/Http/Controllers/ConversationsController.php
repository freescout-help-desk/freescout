<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\Folder;
use App\Mailbox;

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

    /**
     * New conversation.
     */
    public function create($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        $this->authorize('view', $mailbox);

        $conversation = new Conversation();
        $conversation->body = '';

        $folder = $mailbox->folders()->where('type', Folder::TYPE_DRAFTS)->first();

        return view('conversations/create', [
            'conversation' => $conversation,
            'mailbox'      => $mailbox,
            'folder'       => $folder,
            'folders'      => $mailbox->getAssesibleFolders(),
        ]);
    }

    /**
     * Conversation draft.
     */
    public function draft($id)
    {
        $conversation = Conversation::findOrFail($id);

        $this->authorize('view', $conversation);

        return view('conversations/create', [
            'conversation' => $conversation,
            'mailbox'      => $conversation->mailbox,
            'folder'       => $conversation->folder,
            'folders'      => $conversation->mailbox->getAssesibleFolders(),
        ]);
    }
}
