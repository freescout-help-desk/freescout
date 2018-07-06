<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Mailbox;
use App\User;

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
        $users = User::where('role', '!=', User::ROLE_ADMIN)->get();

        return view('mailboxes/create', ['users' => $users]);
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

        $mailbox->users()->sync($request->users);

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

        $users = User::where('role', '!=', User::ROLE_ADMIN)->get();

        return view('mailboxes/permissions', ['mailbox' => $mailbox, 'users' => $users, 'mailbox_users' => $mailbox->users]);
    }

    /**
     * Save mailbox permissions
     * 
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     */
    public function permissionsSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('update', $mailbox);

        $mailbox->users()->sync($request->users);

        \Session::flash('flash_success', __('Mailbox permissions saved!'));
        return redirect()->route('mailboxes.permissions', ['id' => $id]);
    }

    /**
     * Mailbox connection settings
     */
    public function connectionOutgoing($id)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('update', $mailbox);

        return view('mailboxes/connection', ['mailbox' => $mailbox, 'sendmail_path' => ini_get('sendmail_path')]);
    }

    /**
     * Save mailbox connection settings
     */
    public function connectionOutgoingSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('update', $mailbox);

        if ($request->out_method == Mailbox::OUT_METHOD_SMTP) {
            $validator = Validator::make($request->all(), [
                'out_server' => 'required|string|max:255',
                'out_port' => 'required|integer',
                'out_username' => 'required|string|max:100',
                'out_password' => 'required|string|max:255',
                'out_ssl' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return redirect()->route('mailboxes.connection', ['id' => $id])
                            ->withErrors($validator)
                            ->withInput();
            }
        }

        $mailbox->fill($request->all());
        $mailbox->save();

        \Session::flash('flash_success', __('Connection settings saved!'));
        return redirect()->route('mailboxes.connection', ['id' => $id]);
    }

    /**
     * Mailbox incoming settings
     */
    public function connectionIncoming($id)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('update', $mailbox);

        return view('mailboxes/connection_incoming', ['mailbox' => $mailbox]);
    }

    /**
     * Save mailbox connection settings
     */
    public function connectionIncomingSave($id, Request $request)
    {
        $mailbox = Mailbox::findOrFail($id);
        $this->authorize('update', $mailbox);

        $validator = Validator::make($request->all(), [
            'in_server' => 'required|string|max:255',
            'in_port' => 'required|integer',
            'in_username' => 'required|string|max:100',
            'in_password' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('mailboxes.connection.incoming', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $mailbox->fill($request->all());
        $mailbox->save();

        \Session::flash('flash_success', __('Connection settings saved!'));
        return redirect()->route('mailboxes.connection.incoming', ['id' => $id]);
    }
}
