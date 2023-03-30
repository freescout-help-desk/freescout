<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class Alert extends Mailable
{
    /**
     * Alert text.
     */
    public $text;

    /**
     * Alert text.
     */
    public $title;

    /**
     * Create a new message instance.
     */
    public function __construct($text, $title = '')
    {
        $this->text = $text;
        $this->title = $title;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        \MailHelper::prepareMailable($this);
        
        $subject = '['.\Config::get('app.name').'] ';
        if (!empty($this->title)) {
            $subject .= $this->title;
        } else {
            // System emails are not translated
            $subject .= 'Alert';
        }
        $subject .= ' - '.\Helper::getDomain();
        $message = $this->subject($subject)
                    ->view('emails/user/alert', ['text' => $this->text, 'title' => $this->title]);

        return $message;
    }
}
