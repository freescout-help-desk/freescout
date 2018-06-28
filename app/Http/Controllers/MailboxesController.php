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
     * Mailboxes list
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

    /**
     * Edit mailbox
     */
    public function update($id)
    {
        $mailbox = Mailbox::findOrFail($id);

        $this->authorize('update', $mailbox);

        $mailboxes = Mailbox::all()->except($id);

        return view('mailboxes/update', ['mailbox' => $mailbox, 'mailboxes' => $mailboxes]);
    }

    /**
     * Save mailbox
     * 
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     */
    public function updateSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);

        $this->authorize('update', $mailbox);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:40',
            'email' => 'required|string|email|max:128|unique:mailboxes,email,'.$id,
            'aliases' => 'string|max:128',
            'from_name' => 'required|integer',
            'from_name_custom' => 'nullable|string|max:128',
            'ticket_status' => 'required|integer',
            'template' => 'required|integer',
            'ticket_assignee' => 'required|integer',
            'signature' => 'nullable|string',
        ]);

        //event(new Registered($user = $this->create($request->all())));

        if ($validator->fails()) {
            return redirect()->route('mailboxes.update', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $mailbox->fill($request->all());

        $mailbox->save();

        \Session::flash('flash_success', __('Mailbox settings saved'));
        return redirect()->route('mailboxes.update', ['id' => $id]);
    }

    /**
     * Mailbox permissions
     */
    public function permissions($id)
    {
        $mailbox = Mailbox::findOrFail($id);
        
        $this->authorize('update', $mailbox);

        return view('mailboxes/permissions', ['mailbox' => $mailbox]);
    }
}
