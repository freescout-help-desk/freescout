<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\Mailbox;
use App\SavedReply;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Validator;

class SavedRepliesController extends Controller
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
     * Mailboxes list.
     */
    public function savedReplies($mailbox_id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $this->authorize('update', $mailbox);


        $replies = SavedReply::where('mailbox_id', $mailbox->id)->orderBy('name', 'asc')->get();

        return view('saved_replies/saved_replies', [
            'mailbox' => $mailbox,
            'replies' => $replies,
            'is_new' => $request->query->has('is_new'),
            'reply_id' => $request->query->get('id', null)
        ]);
    }

    /**
     * Create new saved reply.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function createSave(Request $request)
    {
        $this->authorize('create', 'App\SavedReply');

        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:80',
            'mailbox_id' => 'required', 
            'body' => 'required',
        ]);

        $mailbox_id = $request->request->get('mailbox_id');

        if ($validator->fails()) {
            return redirect()->route('savedreplies', ['mailbox_id' => $mailbox_id, 'is_new' => 1])
                        ->withErrors($validator, 'createSave')
                        ->withInput();
        }

        $savedReply = new SavedReply();
        $savedReply->fill($request->all());
        $savedReply->save();

        \Session::flash('flash_success_floating', __('Saved Reply created successfully'));

        return redirect()->route('savedreplies', ['mailbox_id' => $mailbox_id, 'id' => $savedReply->id]);
    }

    /**
     * Update saved reply.
     * @param int $id
     * @param \Illuminate\Http\Request $request
     */
    public function updateSave($id, Request $request)
    {
        $savedReply = SavedReply::findOrFail($id);
        $mailbox = $savedReply->mailbox;

        $this->authorize('update', $savedReply);

        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:80',
            'body' => 'required',
        ]);


        if ($validator->fails()) {
            return redirect()->route('savedreplies', ['mailbox_id' => $mailbox->id, 'id' => $savedReply->id])
                        ->withErrors($validator, 'updateSave'+$id)
                        ->withInput();
        }


        $savedReply->fill($request->all());
        $savedReply->save();

        \Session::flash('flash_success_floating', __('Saved Reply updated successfully'));

        return redirect()->route('savedreplies', ['mailbox_id' => $mailbox->id, 'id' => $savedReply->id]);
    }

    /**
     * Delete saved reply.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function delete(Request $request)
    {
        $id = $request->request->get('id');

        $savedReply = SavedReply::findOrFail($id);

        $savedReply->delete();

        \Session::flash('flash_success_floating', __('Saved Reply deleted successfully'));

        return redirect()->route('savedreplies', ['mailbox_id' => $savedReply->mailbox->id]);
    }

    /**
     * 
     */
    public function ajaxGet($id, Request $request)
    {
        $savedReply = SavedReply::find($request->id);
        
        if (!$savedReply)
            return ['status' => 'error', 'msg' => __('Saved Reply not found')];

        return \Response::json(['status' => 'success', 'data' => $savedReply->toArray()]);

    }
}