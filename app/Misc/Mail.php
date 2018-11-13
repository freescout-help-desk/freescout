<?php

namespace App\Misc;

use App\Mailbox;
use App\Option;
use App\SendLog;
use Webklex\IMAP\Client;

// todo: rename into MailHelper
class Mail
{
    /**
     * Reply separators.
     */
    const REPLY_SEPARATOR_HTML = 'fsReplyAbove';
    const REPLY_SEPARATOR_TEXT = '-- Please reply above this line --';

    /**
     * Message-ID prefixes for outgoing emails.
     */
    const MESSAGE_ID_PREFIX_NOTIFICATION = 'notify';
    const MESSAGE_ID_PREFIX_NOTIFICATION_IN_REPLY = 'conversation';
    const MESSAGE_ID_PREFIX_REPLY_TO_CUSTOMER = 'reply';
    const MESSAGE_ID_PREFIX_AUTO_REPLY = 'autoreply';

    /**
     * Mail drivers.
     */
    const MAIL_DRIVER_MAIL = 'mail';
    const MAIL_DRIVER_SENDMAIL = 'sendmail';
    const MAIL_DRIVER_SMTP = 'smtp';

    /**
     * Encryptions.
     */
    const MAIL_ENCRYPTION_NONE = '';
    const MAIL_ENCRYPTION_SSL = 'ssl';
    const MAIL_ENCRYPTION_TLS = 'tls';

    /**
     * If reply is not extracted properly from the incoming email, add here new separator.
     * Order is not important.
     */
    public static $alternative_reply_separators = [
        self::REPLY_SEPARATOR_HTML,
        self::REPLY_SEPARATOR_TEXT,
        '<div class="gmail_quote">',
        '<blockquote',
        '<!-- originalMessage -->',
        //'---Original---', // QQ separator, wait for emails from QQ and check
    ];

    /**
     * Configure mail sending parameters.
     *
     * @param App\Mailbox $mailbox
     */
    public static function setMailDriver($mailbox = null, $user_from = null)
    {
        if ($mailbox) {
            // Configure mail driver according to Mailbox settings
            \Config::set('mail.driver', $mailbox->getMailDriverName());
            \Config::set('mail.from', $mailbox->getMailFrom($user_from));

            // SMTP
            if ($mailbox->out_method == Mailbox::OUT_METHOD_SMTP) {
                \Config::set('mail.host', $mailbox->out_server);
                \Config::set('mail.port', $mailbox->out_port);
                \Config::set('mail.username', $mailbox->out_username);
                \Config::set('mail.password', $mailbox->out_password);
                \Config::set('mail.encryption', $mailbox->getOutEncryptionName());
            }
        } else {
            // Use default settings
            \Config::set('mail.driver', \Config::get('mail.driver'));
            \Config::set('mail.from', ['address' => self::getSystemMailFrom(), 'name' => '']);
        }

        (new \Illuminate\Mail\MailServiceProvider(app()))->register();
    }

    /**
     * Set system mail driver for sending system emails to users.
     *
     * @param App\Mailbox $mailbox
     */
    public static function setSystemMailDriver()
    {
        \Config::set('mail.driver', self::getSystemMailDriver());
        \Config::set('mail.from', [
            'address' => self::getSystemMailFrom(),
            'name'    => Option::get('company_name', \Config::get('app.name')),
        ]);

        // SMTP
        if (\Config::get('mail.driver') == self::MAIL_DRIVER_SMTP) {
            \Config::set('mail.host', Option::get('mail_host'));
            \Config::set('mail.port', Option::get('mail_port'));
            \Config::set('mail.username', Option::get('mail_username'));
            \Config::set('mail.password', Option::get('mail_password'));
            \Config::set('mail.encryption', Option::get('mail_encryption'));
        }

        (new \Illuminate\Mail\MailServiceProvider(app()))->register();
    }

    /**
     * Replace mail vars in the text.
     */
    public static function replaceMailVars($text, $data = [])
    {
        // Available variables to insert into email in UI.
        $vars = [];

        if (!empty($data['conversation'])) {
            $vars['{%subject%}'] = $data['conversation']->subject;
            $vars['{%conversation.number%}'] = $data['conversation']->number;
            $vars['{%customer.email%}'] = $data['conversation']->customer_email;
        }
        if (!empty($data['mailbox'])) {
            $vars['{%mailbox.email%}'] = $data['mailbox']->email;
            $vars['{%mailbox.name%}'] = $data['mailbox']->name;
        }
        if (!empty($data['customer'])) {
            $vars['{%customer.fullName%}'] = $data['customer']->getFullName(true);
            $vars['{%customer.firstName%}'] = $data['customer']->getFirstName(true);
            $vars['{%customer.lastName%}'] = $data['customer']->last_name;
        }

        return strtr($text, $vars);
    }

    /**
     * Check if text has vars in it.
     */
    public static function hasVars($text)
    {
        return preg_match('/({%|%})/', $text);
    }

    /**
     * Remove email from a list of emails.
     */
    public static function removeEmailFromArray($list, $email)
    {
        return array_diff($list, [$email]);
    }

    /**
     * From address for sending system emails.
     */
    public static function getSystemMailFrom()
    {
        $mail_from = Option::get('mail_from');
        if (!$mail_from) {
            $mail_from = 'freescout@'.\Helper::getDomain();
        }

        return $mail_from;
    }

    /**
     * Mail driver for sending system emails.
     */
    public static function getSystemMailDriver()
    {
        return Option::get('mail_driver', 'mail');
    }

    /**
     * Send test email from mailbox.
     */
    public static function sendTestMail($to, $mailbox = null)
    {
        if ($mailbox) {
            // Configure mail driver according to Mailbox settings
            \MailHelper::setMailDriver($mailbox);

            $status_message = '';

            try {
                \Mail::to([$to])->send(new \App\Mail\Test($mailbox));
            } catch (\Exception $e) {
                // We come here in case SMTP server unavailable for example
                $status_message = $e->getMessage();
            }
        } else {
            // System email
            \MailHelper::setSystemMailDriver();

            $status_message = '';

            try {
                \Mail::to([['name' => '', 'email' => $to]])
                    ->send(new \App\Mail\Test());
            } catch (\Exception $e) {
                // We come here in case SMTP server unavailable for example
                $status_message = $e->getMessage();
            }
        }

        if (\Mail::failures() || $status_message) {
            SendLog::log(null, null, $to, SendLog::MAIL_TYPE_TEST, SendLog::STATUS_SEND_ERROR, null, null, $status_message);
            if ($status_message) {
                throw new \Exception($status_message, 1);
            } else {
                return false;
            }
        } else {
            SendLog::log(null, null, $to, SendLog::MAIL_TYPE_TEST, SendLog::STATUS_ACCEPTED);

            return true;
        }
    }

    /**
     * Check POP3/IMAP connection to the mailbox.
     */
    public static function fetchTest($mailbox)
    {
        $client = new Client([
            'host'          => $mailbox->in_server,
            'port'          => $mailbox->in_port,
            'encryption'    => $mailbox->getInEncryptionName(),
            'validate_cert' => true,
            'username'      => $mailbox->in_username,
            'password'      => $mailbox->in_password,
            'protocol'      => $mailbox->getInProtocolName(),
        ]);

        // Connect to the Server
        $client->connect();

        // Get folder
        $folder = $client->getFolder('INBOX');

        if (!$folder) {
            throw new \Exception('Could not get mailbox folder: INBOX', 1);
        }
        // Get unseen messages for a period
        $messages = $folder->query()->unseen()->since(now()->subDays(1))->leaveUnread()->get();

        if ($client->getLastError()) {
            throw new \Exception($client->getLastError(), 1);
        } else {
            return true;
        }
    }

    /**
     * Convert list of emails to array.
     *
     * @return array
     */
    public static function sanitizeEmails($emails)
    {
        $emails_array = [];

        if (is_array($emails)) {
            $emails_array = $emails;
        } else {
            $emails_array = explode(',', $emails);
        }

        foreach ($emails_array as $i => $email) {
            $emails_array[$i] = \App\Email::sanitizeEmail($email);
            if (!$emails_array[$i]) {
                unset($emails_array[$i]);
            }
        }

        return $emails_array;
    }

    /**
     * Send system alert to super admin.
     */
    public static function sendAlertMail($text, $title = '')
    {
        \App\Jobs\SendAlert::dispatch($text, $title)->onQueue('emails');
    }

    /**
     * Send email to developers team.
     */
    public static function sendEmailToDevs($subject, $body, $attachments = [], $from_user = null)
    {
        // Configure mail driver according to Mailbox settings
        \MailHelper::setSystemMailDriver();

        $status_message = '';

        try {
            \Mail::raw($body, function ($message) use ($subject, $attachments, $from_user) {
                $message
                    ->subject($subject)
                    ->to(\Config::get('app.freescout_email'));
                if ($attachments) {
                    foreach ($attachments as $attachment) {
                        $message->attach($attachment);
                    }
                }
                // Set user as Reply-To
                if ($from_user) {
                    $message->replyTo($from_user->email, $from_user->getFullName());
                }
            });
        } catch (\Exception $e) {
            \Log::error(\Helper::formatException($e));
            // We come here in case SMTP server unavailable for example
            return false;
        }

        if (\Mail::failures()) {
            return false;
        } else {
            return true;
        }
    }
}
