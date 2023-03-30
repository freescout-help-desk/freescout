<?php

namespace Modules\EndUserPortal\Mail;

use Illuminate\Mail\Mailable;

class Login extends Mailable
{
    public $mailbox;
    public $customer;
    
    /**
     * Create a new message instance.
     */
    public function __construct($mailbox, $customer)
    {
        $this->mailbox = $mailbox;
        $this->customer = $customer;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $auth_link = route('enduserportal.login_from_email', [
            'id' => \EndUserPortal::encodeMailboxId($this->mailbox->id),
            'customer_id' => encrypt($this->customer->id),
        ]);
        $portal_name = \EndUserPortal::getPortalName($this->mailbox);

        $message = $this->subject(__('Authentication to :portal_name', ['portal_name' => $portal_name]))
                    ->view('enduserportal::emails/login', ['portal_name' => $portal_name, 'auth_link' => $auth_link])
                    ->text('enduserportal::emails/login_text', ['portal_name' => $portal_name, 'auth_link' => $auth_link]);

        return $message;
    }
}
