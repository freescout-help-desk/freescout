<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Validation\Rule;
use App\Customer;
use App\Email;

class CustomersController extends Controller
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
     * Edit customer
     */
    public function update($id)
    {
        $customer = Customer::findOrFail($id);

        $customer_emails = $customer->emails;
        if (count($customer_emails)) {
            foreach ($customer_emails as $row) {
                $emails[] = $row->email;
            }
        } else {
            $emails = [''];
        }

        return view('customers/update', ['customer' => $customer, 'emails' => $emails]);
    }

    /**
     * Save customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateSave($id, Request $request)
    {
        $customer = Customer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:12',
            'country' => 'nullable|string|max:2',
        ]);

        if ($validator->fails()) {
            return redirect()->route('customers.update', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $customer->fill($request->all());
        $customer->save();

        $customer->syncEmails($request->emails);

        \Session::flash('flash_success', __('Customer saved successfully'));
        return redirect()->route('customers.update', ['id' => $id]);
    }

    /**
     * User mailboxes
     */
    public function permissions($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $mailboxes = Mailbox::all();

        return view('users/permissions', ['user' => $user, 'mailboxes' => $mailboxes, 'user_mailboxes' => $user->mailboxes]);
    }

    /**
     * Save user permissions
     * 
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     */
    public function permissionsSave($id, Request $request)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $user->mailboxes()->sync($request->mailboxes);

        \Session::flash('flash_success', __('Permissions saved successfully'));
        return redirect()->route('users.permissions', ['id' => $id]);
    }
}
