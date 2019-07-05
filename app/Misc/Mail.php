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

    const FETCH_SCHEDULE_EVERY_MINUTE = 1;
    const FETCH_SCHEDULE_EVERY_FIVE_MINUTES = 5;
    const FETCH_SCHEDULE_EVERY_TEN_MINUTES = 10;
    const FETCH_SCHEDULE_EVERY_FIFTEEN_MINUTES = 15;
    const FETCH_SCHEDULE_EVERY_THIRTY_MINUTES = 30;
    const FETCH_SCHEDULE_HOURLY = 60;

    /**
     * If reply is not extracted properly from the incoming email, add here a new separator.
     * Order is not important.
     * Idially separators must contain < or > to avoid false positives.
     * Regex separators has "regex:" in the beginning.
     */
    public static $alternative_reply_separators = [
        self::REPLY_SEPARATOR_HTML, // Our HTML separator
        self::REPLY_SEPARATOR_TEXT, // Our plain text separator

        // Email service providers specific separators.
        '<div class="gmail_quote">', // Gmail
        '<div name="quote" ',
        'yahoo_quoted_', // Yahoo, full: <div id=3D"ydp6h4f5c59yahoo_quoted_2937493705"
        '------------------ 原始邮件 ------------------', // QQ
        '------------------ Original ------------------', // QQ English
        '<div id=3D"divRplyFwdMsg" dir=', // Outlook
        'regex:/<div style="border:none;border\-top:solid \#[A-Z0-9]{6} 1\.0pt;padding:3\.0pt 0in 0in 0in">[^<]*<p class="MsoNormal"><b>/', // MS Outlook

        // General separators.
        '<blockquote', // General sepator
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
        if (!empty($data['user'])) {
            $vars['{%user.fullName%}'] = $data['user']->getFullName();
            $vars['{%user.firstName%}'] = $data['user']->getFirstName();
            $vars['{%user.lastName%}'] = $data['user']->last_name;
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
            'validate_cert' => $mailbox->in_validate_cert,
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
     * Check if email format is valid.
     *
     * @param [type] $email [description]
     *
     * @return [type] [description]
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
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

    /**
     * Get email marker for the outgoing email to track replies
     * in case Message-ID header is removed by mail service provider.
     *
     * @param [type] $message_id [description]
     *
     * @return [type] [description]
     */
    public static function getMessageMarker($message_id)
    {
        // It has to be BASE64, as Gmail converts it into link.
        return '{#FS:'.base64_encode($message_id).'#}';
    }

    /**
     * Fetch Message-ID from incoming email body.
     *
     * @param [type] $message_id [description]
     *
     * @return [type] [description]
     */
    public static function fetchMessageMarkerValue($body)
    {
        preg_match('/{#FS:([^#]+)#}/', $body, $matches);
        if (!empty($matches[1]) && base64_decode($matches[1])) {
            // Return first found marker.
            return base64_decode($matches[1]);
        }

        return '';
    }

    /**
     * Detect autoresponder by headers.
     * https://github.com/jpmckinney/multi_mail/wiki/Detecting-autoresponders
     * https://www.jitbit.com/maxblog/18-detecting-outlook-autoreplyout-of-office-emails-and-x-auto-response-suppress-header/.
     *
     * @return bool [description]
     */
    public static function isAutoResponder($headers_str)
    {
        $autoresponder_headers = [
            'x-autoreply'    => '',
            'x-autorespond'  => '',
            'auto-submitted' => 'auto-replied',
        ];
        $headers = explode("\n", $headers_str);

        foreach ($autoresponder_headers as $auto_header => $auto_header_value) {
            foreach ($headers as $header) {
                $parts = explode(':', $header, 2);
                if (count($parts) == 2) {
                    $name = trim(strtolower($parts[0]));
                    $value = trim($parts[1]);
                } else {
                    continue;
                }
                if (strtolower($name) == $auto_header) {
                    if (!$auto_header_value) {
                        return true;
                    } elseif ($value == $auto_header_value) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check Content-Type header.
     * This is not 100% reliable, detects only standard DSN bounces.
     *
     * @param [type] $headers [description]
     *
     * @return [type] [description]
     */
    public static function detectBounceByHeaders($headers)
    {
        if (preg_match("/Content-Type:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)/i", $headers, $match)
            && preg_match("/multipart\/report/i", $match[1])
            && preg_match("/report-type=[\"']?delivery-status[\"']?/i", $match[1])
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Parse email headers.
     *
     * @param [type] $headers_str [description]
     *
     * @return [type] [description]
     */
    public static function parseHeaders($headers_str)
    {
        try {
            return imap_rfc822_parse_headers($headers_str);
        } catch (\Exception $e) {
            return;
        }
    }

    public static function getHeader($headers_str, $header)
    {
        $headers = self::parseHeaders($headers_str);
        if (!$headers) {
            return;
        }
        $value = null;
        if (property_exists($headers, $header)) {
            $value = $headers->$header;
        } else {
            return;
        }
        switch ($header) {
            case 'message_id':
                $value = str_replace(['<', '>'], '', $value);
                break;
        }

        return $value;
    }
}
