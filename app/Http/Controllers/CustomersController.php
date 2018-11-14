<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Email;
use Illuminate\Http\Request;
use Validator;

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
     * Edit customer.
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
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function updateSave($id, Request $request)
    {
        function mb_ucfirst($string)
        {
            return mb_strtoupper(mb_substr($string, 0, 1)).mb_strtolower(mb_substr($string, 1));
        }

        $customer = Customer::findOrFail($id);
        $flash_message = '';

        // First name or email must be specified
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255|required_without:emails.0',
            'last_name'  => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:255',
            'state'      => 'nullable|string|max:255',
            'zip'        => 'nullable|string|max:12',
            'country'    => 'nullable|string|max:2',
            //'emails'     => 'array|required_without:first_name',
            //'emails.1'   => 'nullable|email|required_without:first_name',
            'emails.*'   => 'nullable|email|distinct|required_without:first_name',
        ]);
        $validator->setAttributeNames([
            //'emails.1'   => __('Email'),
            'emails.*'   => __('Email'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('customers.update', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $new_emails = [];
        $new_emails_change_customer = [];
        $removed_emails = [];

        // Detect new emails added
        $customer_emails = $customer->emails()->pluck('email')->toArray();
        foreach ($request->emails as $email) {
            if (!in_array($email, $customer_emails)) {
                $new_emails[] = $email;
            }
        }

        // If new email belongs to another customer, let user know about it in the flash message
        foreach ($new_emails as $new_email) {
            $email = Email::where('email', $new_email)->first();
            if ($email && $email->customer) {
                // If customer whose email is removed does not have first name and other emails
                // we have to create first name for this customer
                if (!$email->customer->first_name && count($email->customer->emails) == 1) {
                    if ($request->first_name) {
                        $email->customer->first_name = $request->first_name;
                    } elseif ($customer->first_name) {
                        $email->customer->first_name = $customer->first_name;
                    } else {
                        $email->customer->first_name = mb_ucfirst($email->getNameFromEmail());
                    }
                    $email->customer->save();
                }

                $flash_message .= __('Email :tag_email_begin:email:tag_email_end has been moved from another customer:  :a_begin:customer:a_end.', [
                    'email'           => $email->email,
                    'tag_email_begin' => '<strong>',
                    'tag_email_end'   => '</strong>',
                    'customer'        => $email->customer->getFullName(),
                    'a_begin'         => '<strong><a href="'.$email->customer->url().'" target="_blank">',
                    'a_end'           => '</a></strong>',
                ]).' ';

                $new_emails_change_customer[] = $email->email;
            }
        }

        // Detect removed emails
        foreach ($customer_emails as $email) {
            if (!in_array($email, $request->emails)) {
                $removed_emails[] = $email;
            }
        }

        $customer->fill($request->all());
        // Websites
        if (!empty($request->websites)) {
            $customer->setWebsites($request->websites);
        }
        $customer->save();

        $customer->syncEmails($request->emails);

        // Update customer_id in all conversations to the current customer
        foreach ($new_emails_change_customer as $new_email) {
            $conversations_to_change_customer = Conversation::where('customer_email', $new_email)->get();
            foreach ($conversations_to_change_customer as $conversation) {
                // We have to pass user to create line item and let others know that customer has changed.
                // Conversation may be even in other mailbox where user does not have an access.
                $conversation->changeCustomer($new_email, $customer, auth()->user());
            }
        }

        // Update customer in conversations for removed emails
        foreach ($removed_emails as $removed_email) {
            $email = Email::where('email', $removed_email)->first();
            if ($email) {
                $conversations = Conversation::where('customer_email', $email->email)->get();
                foreach ($conversations as $conversation) {
                    $conversation->changeCustomer($email->email, $email->customer, auth()->user());
                }
            }
        }

        $flash_message = __('Customer saved successfully.').' '.$flash_message;
        \Session::flash('flash_success_unescaped', $flash_message);

        return redirect()->route('customers.update', ['id' => $id]);
    }

    /**
     * User mailboxes.
     */
    public function permissions($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $mailboxes = Mailbox::all();

        return view('users/permissions', ['user' => $user, 'mailboxes' => $mailboxes, 'user_mailboxes' => $user->mailboxes]);
    }

    /**
     * Save user permissions.
     *
     * @param int                      $id
     * @param \Illuminate\Http\Request $request
     */
    public function permissionsSave($id, Request $request)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $user->mailboxes()->sync($request->mailboxes);

        \Session::flash('flash_success', __('Permissions saved successfully'));

        return redirect()->route('users.permissions', ['id' => $id]);
    }

    /**
     * View customer conversations.
     *
     * @param intg $id
     */
    public function conversations($id)
    {
        $customer = Customer::findOrFail($id);

        $conversations = $customer->conversations()
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(Conversation::DEFAULT_LIST_SIZE);

        return view('customers/conversations', [
            'customer'      => $customer,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Customers ajax search.
     */
    public function ajaxSearch(Request $request)
    {
        $response = [
            'results'    => [],
            'pagination' => ['more' => false],
        ];

        $q = $request->q;

        $customers = Customer::select('customers.id', 'emails.email', 'first_name', 'last_name')
            ->join('emails', 'customers.id', '=', 'emails.customer_id')
            ->where('emails.email', 'like', '%'.$q.'%')
            ->where('emails.email', '<>', $request->exclude_email)
            ->orWhere('first_name', 'like', '%'.$q.'%')
            ->orWhere('last_name', 'like', '%'.$q.'%')
            ->paginate(20);

        foreach ($customers as $customer) {
            $text = $customer->email;
            if ($customer->getFullName()) {
                $text .= ' ('.$customer->getFullName().')';
            }
            if (!empty($request->use_id)) {
                $id = $customer->id;
            } else {
                $id = $customer->email;
            }
            $response['results'][] = [
                'id'   => $id,
                'text' => $text,
            ];
        }

        $response['pagination']['more'] = $customers->hasMorePages();

        return \Response::json($response);
    }
}
