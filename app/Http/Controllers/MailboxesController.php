<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Mailbox;

class MailboxesController extends Controller
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
     * Users list
     */
    public function mailboxes()
    {
        $mailboxes = Mailbox::all();

        return view('mailboxes/mailboxes', ['mailboxes' => $mailboxes]);
    }

    /**
     * New mailbox
     */
    public function create()
    {
        return view('mailboxes/create');
    }

    /**
     * Create new mailbox
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function createSave(Request $request)
    {
        $this->authorize('create', 'App\Mailbox');

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:128|unique:mailboxes',
            'name' => 'required|string|max:40',
        ]);

        // //event(new Registered($user = $this->create($request->all())));

        if ($validator->fails()) {
            return redirect()->route('mailboxes.create')
                        ->withErrors($validator)
                        ->withInput();
        }

        $mailbox = new Mailbox;
        $mailbox->fill($request->all());
        $mailbox->save();

        \Session::flash('flash_success', __('Mailbox created successfully'));
        return redirect()->route('mailboxes.update', ['id' => $mailbox->id]);
    }
}
