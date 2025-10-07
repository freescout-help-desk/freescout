<?php

namespace App\Misc;

use App\Mailbox;
use App\Option;
use App\SendLog;
//use Webklex\IMAP\Client;

// todo: rename into MailHelper
class Mail
{
    /**
     * Reply separators.
     */
    const REPLY_SEPARATOR_HTML = 'fsReplyAbove';
    const REPLY_SEPARATOR_TEXT = '-- Please reply above this line --';
    const REPLY_SEPARATOR_NOTIFICATION = 'fsNotifReplyAbove';

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
    const FETCH_SCHEDULE_EVERY_TWO_MINUTES = 2;
    const FETCH_SCHEDULE_EVERY_THREE_MINUTES = 3;
    const FETCH_SCHEDULE_EVERY_FIVE_MINUTES = 5;
    const FETCH_SCHEDULE_EVERY_TEN_MINUTES = 10;
    const FETCH_SCHEDULE_EVERY_FIFTEEN_MINUTES = 15;
    const FETCH_SCHEDULE_EVERY_THIRTY_MINUTES = 30;
    const FETCH_SCHEDULE_HOURLY = 60;

    const OAUTH_PROVIDER_MICROSOFT = 'ms';
    const OAUTH_MICROSOFT_SMTP = 'smtp.office365.com';

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
        // <div class="gmail_quote" style="font-family:sans-serif;">
        '<div class="gmail_quote">', // Gmail
        '<div class="gmail_quote" ', // Gmail
        '<div class="gmail_quote gmail_quote_container"', // Gmail
        '<div class="protonmail_quote">', // https://github.com/freescout-help-desk/freescout/issues/4537
        '<div id="appendonsend"></div>', // Outlook / Live / Hotmail / Microsoft
        '<div name="quote" ',
        'yahoo_quoted_', // Yahoo, full: <div id=3D"ydp6h4f5c59yahoo_quoted_2937493705"
        '------------------ 原始邮件 ------------------', // QQ
        '------------------ Original ------------------', // QQ English
        '<div id=3D"divRplyFwdMsg" dir=', // Outlook
        'regex:/<div style="border:none;border\-top:solid \#[A-Z0-9]{6} 1\.0pt;padding:3\.0pt 0in 0in 0in">[^<]*<p class="MsoNormal"><b>/', // MS Outlook
        // https://github.com/freescout-help-desk/freescout/issues/4629#issuecomment-2870297514
        'regex:/<div style="border:none;border\-top:solid \#[A-Z0-9]{6} 1\.0pt;padding:3\.0pt 0cm 0cm 0cm">[^<]*<p class="MsoNormal"><b>/', // MS Outlook

        // General separators.
        //'regex:/<blockquote((?!quote)[^>])*>/', // General sepator. Should skip Gmail's <blockquote class="gmail_quote">.
        // https://github.com/freescout-help-desk/freescout/issues/4629#issuecomment-2870299221
        '<blockquote type="cite"',
        // This is used both for quotes and replies.
        //'<blockquote class="gmail_quote"',
        // https://github.com/freescout-help-desk/freescout/issues/4629#issuecomment-2874816496
        '<div dir="auto" id="mail-editor-reference-message-container">',
        // https://github.com/freescout-help-desk/freescout/issues/4764
        '<!--html--><section>',
        'regex:/<!\-\-html\-\->\s*<section>/',
        '<!-- originalMessage -->',
        '‐‐‐‐‐‐‐ Original Message ‐‐‐‐‐‐‐',
        '--------------- Original Message ---------------',
        '-------- Αρχικό μήνυμα --------', // Greek
    ];

    /**
     * Used to substitue encoding during mail body decoding
     * via iconv() or mb_convert_encoding().
     * https://github.com/freescout-help-desk/freescout/issues/4282
     */
    public static $encoding_substitution = [
        'iso-2022-jp' => 'iso-2022-jp-ms',
        'gb2312' => 'gb18030',
    ];

    /**
     * Used when decoding mime strings.
     */
    public static $mime_encoding_substitution = [
        'iso-2022-jp' => 'iso-2022-jp-ms',
        'ks_c_5601-1987' => 'cp949',
        //'gb2312' => 'gb18030',
    ];

    /**
     * md5 of the last applied mail config.
     */
    public static $last_mail_config_hash = '';

    /**
     * Used to get SMTP queue id when sending emails to customers.
     */
    public static $smtp_queue_id_plugin_registered = false;
    
    /**
     * Used to store the last sent email message.
     */
    public static $smtp_mime_message = '';

    /**
     * Configure mail sending parameters.
     *
     * @param App\Mailbox $mailbox
     * @param App\User $user_from
     * @param App\Conversation $conversation
     */
    public static function setMailDriver($mailbox = null, $user_from = null, $conversation = null)
    {
        if ($mailbox) {
            // Configure mail driver according to Mailbox settings.
            $oauth = $mailbox->outOauthEnabled();

            // Refresh Access Token.
            if ($oauth) {
                if ((strtotime($mailbox->oauthGetParam('issued_on')) + (int)$mailbox->oauthGetParam('expires_in')) < time()) {
                    // Try to get an access token (using the authorization code grant)
                    $token_data = \MailHelper::oauthGetAccessToken(\MailHelper::OAUTH_PROVIDER_MICROSOFT, [
                        'client_id' => $mailbox->out_username,
                        'client_secret' => $mailbox->out_password,
                        'refresh_token' => $mailbox->oauthGetParam('r_token'),
                    ]);

                    if (!empty($token_data['a_token'])) {
                        $mailbox->setMetaParam('oauth', $token_data, true);
                    } elseif (!empty($token_data['error'])) {
                        $error_message = 'Error occurred refreshing oAuth Access Token: '.$token_data['error'];
                        \Helper::log(\App\ActivityLog::NAME_EMAILS_SENDING, 
                            \App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER, [
                            'error'   => $error_message,
                            'mailbox' => $mailbox->name,
                        ]);
                        //throw new \Exception($error_message, 1);
                    }
                }
            }

            \Config::set('mail.driver', $mailbox->getMailDriverName());
            \Config::set('mail.from', $mailbox->getMailFrom($user_from, $conversation));

            // SMTP.
            if ($mailbox->out_method == Mailbox::OUT_METHOD_SMTP) {
                \Config::set('mail.host', $mailbox->out_server);
                \Config::set('mail.port', $mailbox->out_port);
                if ($oauth) {
                    \Config::set('mail.auth_mode', 'XOAUTH2');
                    \Config::set('mail.username', $mailbox->email);
                    \Config::set('mail.password', $mailbox->oauthGetParam('a_token'));
                } else {
                    \Config::set('mail.auth_mode', '');
                    if (!$mailbox->out_username) {
                        \Config::set('mail.username', null);
                        \Config::set('mail.password', null);
                    } else {
                        \Config::set('mail.username', $mailbox->out_username);
                        \Config::set('mail.password', $mailbox->out_password);
                    }
                }
                \Config::set('mail.encryption', $mailbox->getOutEncryptionName());
            }
        } else {
            // Use default settings
            \Config::set('mail.driver', \Config::get('mail.driver'));
            \Config::set('mail.from', ['address' => self::getSystemMailFrom(), 'name' => '']);
        }

        self::reapplyMailConfig();
    }

    /**
     * Reapply new mail config.
     */
    public static function reapplyMailConfig()
    {
        // Check hash to avoid recreating MailServiceProvider.
        $mail_config_hash = md5(json_encode(\Config::get('mail')));

        if (self::$last_mail_config_hash != $mail_config_hash) {
            self::$last_mail_config_hash = $mail_config_hash;
        } else {
            return false;
        }

        // Without doing this, Swift mailer uses old config values
        // if there were emails sent with previous config.
        \App::forgetInstance('mailer');
        \App::forgetInstance('swift.mailer');
        \App::forgetInstance('swift.transport');

        (new \Illuminate\Mail\MailServiceProvider(app()))->register();
        // We have to update Mailer facade manually, as it does not happen automatically
        // and previous instance of app('mailer') is used.
        \Mail::swap(app('mailer'));

        \Eventy::action('mail.reapply_mail_config');
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
            if (!Option::get('mail_username')) {
                \Config::set('mail.username', null);
                \Config::set('mail.password', null);
            } else {
                \Config::set('mail.username', Option::get('mail_username'));
                \Config::set('mail.password', \Helper::decrypt(Option::get('mail_password')));
            }
            \Config::set('mail.encryption', Option::get('mail_encryption'));
        }

        self::reapplyMailConfig();
    }

    /**
     * Replace mail vars in the text.
     */
    public static function replaceMailVars($text, $data = [], $escape = false, $remove_non_replaced = false)
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
            // To avoid recursion.
            if (isset($data['mailbox_from_name'])) {
                $vars['{%mailbox.fromName%}'] = $data['mailbox_from_name'];
            } else {
                $vars['{%mailbox.fromName%}'] = $data['mailbox']->getMailFrom(!empty($data['user']) ? $data['user'] : null)['name'];
            }
        }
        if (!empty($data['customer'])) {
            $vars['{%customer.fullName%}'] = $data['customer']->getFullName(true);
            $vars['{%customer.firstName%}'] = $data['customer']->getFirstName(true);
            $vars['{%customer.lastName%}'] = $data['customer']->last_name;
            $vars['{%customer.company%}'] = $data['customer']->company;
        }
        if (!empty($data['user'])) {
            $vars['{%user.fullName%}'] = $data['user']->getFullName();
            $vars['{%user.firstName%}'] = $data['user']->getFirstName();
            $vars['{%user.phone%}'] = $data['user']->phone;
            $vars['{%user.email%}'] = $data['user']->email;
            $vars['{%user.jobTitle%}'] = $data['user']->job_title;
            $vars['{%user.lastName%}'] = $data['user']->last_name;
            $vars['{%user.photoUrl%}'] = $data['user']->getPhotoUrl();
        }

        $vars = \Eventy::filter('mail_vars.replace', $vars, $data);

        /**
         * Retrieves all mail var codes from the text, including fallback values.
         *
         * @link https://regex101.com/r/icWukp/1
         */
        preg_match_all(
            '#\{%(?<var>[a-zA-Z.]+)(,fallback=(?<fallback>[^}]*))?%\}#',
            $text,
            $matches
        );

        // Add fallback values to the $vars array, if present.
        foreach($matches['var'] as $i => $var) {
            $merge_code   = "{%{$var}%}";
            $full_match   = $matches[0][$i];
            $has_fallback = false !== strpos($full_match, ',fallback=');
            $fallback_val = $has_fallback ? $matches['fallback'][$i] ?? null : null;
            $merge_val    = isset($vars[$merge_code]) ? $vars[$merge_code] : $fallback_val;

            if (null !== $merge_val || true === $remove_non_replaced) {
                $vars[$full_match] = $merge_val;
                $vars[$merge_code] = $merge_val;
            }
        }

        $vars = \Eventy::filter('mail_vars.replace_after_fallback', $vars, $data);

        if ($escape) {
            foreach ($vars as $i => $var) {
                $vars[$i] = htmlspecialchars($var ?? '');
                $vars[$i] = nl2br($vars[$i]);
            }
        } else {
            foreach ($vars as $i => $var) {
                $vars[$i] = nl2br($var ?? '');
            }
        }

        $result = strtr($text, $vars);

        // Remove non-replaced placeholders.
        if ($remove_non_replaced) {
            $result = preg_replace('#\{%[^\.%\}]+\.[^%\}]+%\}#', '', $result ?? '');
            $result = trim($result);
        }

        return $result;
    }

    /**
     * Check if text has vars in it.
     */
    public static function hasVars($text)
    {
        return preg_match('/({%|%})/', $text ?? '');
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

    public static function registerSmtpLogger()
    {
        $logger = new \Swift_Plugins_Loggers_ArrayLogger();
        \Mail::getSwiftMailer()->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));

        return $logger;
    }

    /**
     * Send test email from mailbox.
     */
    public static function sendTestMail($to, $mailbox = null)
    {
        $result = [
            'status' => 'success',
            'msg' => '',
            'log' => '',
        ];

        if ($mailbox) {
            // Configure mail driver according to Mailbox settings
            \MailHelper::setMailDriver($mailbox);
            $smtp_logger = self::registerSmtpLogger();

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
            $smtp_logger = self::registerSmtpLogger();

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
                $result['msg'] = $status_message;
            }
            $result['status'] = 'error';
            $result['log'] = $smtp_logger->dump();
        } else {
            SendLog::log(null, null, $to, SendLog::MAIL_TYPE_TEST, SendLog::STATUS_ACCEPTED);

            $result['status'] = 'success';
        }

        return $result;
    }

    /**
     * Check POP3/IMAP connection to the mailbox.
     */
    public static function fetchTest($mailbox)
    {
        $result = [
            'result' => 'success',
            'msg' => '',
            'log' => '',
        ];

        $client = null;

        try {
            \Config::set('imap.options.debug', true);
            \Webklex\PHPIMAP\Connection\Protocols\ImapProtocol::$output_debug_log = false;
            \Webklex\PHPIMAP\Connection\Protocols\PopProtocol::$output_debug_log = false;

            $client = \MailHelper::getMailboxClient($mailbox);

            // Connect to the Server
            $client->connect();

            // Get folder
            $folder = $client->getFolder('INBOX');

            if (!$folder) {
                throw new \Exception('Could not get mailbox folder: INBOX', 1);
            }
            // Get unseen messages for a period
            $messages = $folder->query()->unseen()->since(now()->subDays(1))->leaveUnread()->get();

            $last_error = '';
            if (method_exists($client, 'getLastError')) {
                $last_error = $client->getLastError();
            }
            
            if ($last_error && stristr($last_error, 'The specified charset is not supported')) {
                // Solution for MS mailboxes.
                // https://github.com/freescout-helpdesk/freescout/issues/176
                $messages = $folder->query()->unseen()->since(now()->subDays(1))->leaveUnread()->setCharset(null)->get();
                if (count($client->getErrors()) > 1) {
                    $last_error = $client->getLastError();
                } else {
                    $last_error = null;
                }
            }

            if ($last_error) {
                //throw new \Exception($last_error, 1);
                $result['result'] = 'error';
                $result['msg'] = $last_error;
            }
        } catch (\Exception $e) {
            $result['result'] = 'error';
            $result['msg'] = $e->getMessage();
        }

        if ($result['result'] == 'error') {
            $result['log'] = \Webklex\PHPIMAP\Connection\Protocols\ImapProtocol::getDebugLog();
        }

        return $result;
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
            $emails_array = explode(',', $emails ?? '');
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
        preg_match('/{#FS:([^#]+)#}/', $body ?? '', $matches);
        if (!empty($matches[1]) && base64_decode($matches[1])) {
            // Return first found marker.
            return base64_decode($matches[1]);
        }

        return '';
    }

    public static function getMessageIdHash($thread_id)
    {
        return substr(md5($thread_id.config('app.key')), 0, 16);
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
            'x-autoresponder'  => '',
            'auto-submitted' => '', // this can be auto-replied, auto-generated, etc.
            'delivered-to' => ['autoresponder'],
            'precedence' => ['auto_reply', 'bulk', 'junk', 'list'],
            'x-precedence' => ['auto_reply', 'bulk', 'junk', 'list'],
        ];
        $headers = explode("\n", $headers_str ?? '');

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
                    } elseif (is_array($auto_header_value)) {
                        foreach ($auto_header_value as $auto_header_value_item) {
                            if ($value == $auto_header_value_item) {
                                return true;
                            }
                        }
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
        //try {
        //return imap_rfc822_parse_headers($headers_str);
        //return (new \Webklex\PHPIMAP\Header(''))->rfc822_parse_headers($headers_str);
        return \Webklex\PHPIMAP\Header::rfc822_parse_headers($headers_str);
        // } catch (\Exception $e) {
        //     return;
        // }
    }

    // Replacement for https://www.php.net/manual/en/function.imap-utf8.php
    public static function imapUtf8($mime_encoded_text)
    {
        if (function_exists('imap_utf8')) {
            return imap_utf8($mime_encoded_text);
        } else {
            return iconv_mime_decode($mime_encoded_text, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8");
        }
    }

    public static function getHeader($headers_str, $header)
    {
        $headers_str = $headers_str ?? '';
        
        // Quick check to same resources.
        if (!stristr($headers_str, $header)) {
            return '';
        }

        $header = strtolower($header);
        $header = str_replace('-', '_', $header);

        $headers = self::parseHeaders($headers_str);
        if (!$headers) {
            return;
        }
        $value = null;
        if (property_exists($headers, $header)) {
            $value = $headers->$header;
        } else {
            return '';
        }
        switch ($header) {
            case 'message_id':
                $value = str_replace(['<', '>'], '', $value);
                break;
        }

        return $value;
    }

    /**
     * Get client for fetching emails.
     */
    public static function getMailboxClient($mailbox)
    {
        $oauth = $mailbox->oauthEnabled();
        /*$new_library = config('app.new_fetching_library');

        if (!$new_library) {
            // Old.
            return new \Webklex\IMAP\Client([
                'host'          => $mailbox->in_server,
                'port'          => $mailbox->in_port,
                'encryption'    => $mailbox->getInEncryptionName(),
                'validate_cert' => $mailbox->in_validate_cert,
                'username'      => $mailbox->in_username,
                'password'      => $mailbox->in_password,
                'protocol'      => $mailbox->getInProtocolName(),
            ]);
        } else {*/
        // New
        if ($oauth) {
            \Config::set('imap.accounts.default', [
                'host'          => $mailbox->in_server,
                'port'          => $mailbox->in_port,
                'encryption'    => $mailbox->getInEncryptionName(),
                'validate_cert' => $mailbox->in_validate_cert,
                'username'      => $mailbox->email,
                'password'      => $mailbox->oauthGetParam('a_token'),
                'protocol'      => $mailbox->getInProtocolName(),
                'authentication' => 'oauth',
            ]);
        } else {
            \Config::set('imap.accounts.default', [
                'host'          => $mailbox->in_server,
                'port'          => $mailbox->in_port,
                'encryption'    => $mailbox->getInEncryptionName(),
                'validate_cert' => $mailbox->in_validate_cert,
                // 'username'      => $mailbox->email,
                // 'password'      => $mailbox->oauthGetParam('a_token'),
                // 'protocol'      => $mailbox->getInProtocolName(),
                // 'authentication' => 'oauth',
                'username'      => $mailbox->in_username,
                'password'      => $mailbox->in_password,
                'protocol'      => $mailbox->getInProtocolName(),
            ]);
        }
        // To enable debug: /vendor/webklex/php-imap/src/Connection/Protocols
        // Debug in console
        if (app()->runningInConsole()) {
            \Config::set('imap.options.debug', config('app.debug'));
        }

        $cm = new \Webklex\PHPIMAP\ClientManager(config('imap'));

        // Refresh Access Token.
        if ($oauth) {
            if ((strtotime($mailbox->oauthGetParam('issued_on')) + (int)$mailbox->oauthGetParam('expires_in')) < time()) {
                // Try to get an access token (using the authorization code grant)
                $token_data = \MailHelper::oauthGetAccessToken(\MailHelper::OAUTH_PROVIDER_MICROSOFT, [
                    'client_id' => $mailbox->in_username,
                    'client_secret' => $mailbox->in_password,
                    'refresh_token' => $mailbox->oauthGetParam('r_token'),
                ]);

                if (!empty($token_data['a_token'])) {
                    $mailbox->setMetaParam('oauth', $token_data, true);
                } elseif (!empty($token_data['error'])) {
                    $error_message = 'Error occurred refreshing oAuth Access Token: '.$token_data['error'];
                    \Helper::log(\App\ActivityLog::NAME_EMAILS_FETCHING, 
                        \App\ActivityLog::DESCRIPTION_EMAILS_FETCHING_ERROR, [
                        'error'   => $error_message,
                        'mailbox' => $mailbox->name,
                    ]);
                    throw new \Exception($error_message, 1);
                }
            }
        }

        // This makes it authenticate two times.
        //$cm->setTimeout(60);

        return $cm->account('default');
        //}
    }

    /**
     * Generate artificial Message-ID.
     */
    public static function generateMessageId($email_address, $raw_body = '')
    {
        $hash = str_random(16);
        if ($raw_body) {
            $hash = md5(strval($raw_body));
        }

        return 'fs-'.$hash.'@'.preg_replace("/.*@/", '', $email_address);
    }

    /**
     * Fetch IMAP message by Message-ID.
     */
    public static function fetchMessage($mailbox, $message_id, $message_date = null)
    {
        $no_charset = false;

        if (!$message_id) {
            return null;
        }

        try {
            $client = \MailHelper::getMailboxClient($mailbox);
            $client->connect();
        } catch (\Exception $e) {
            \Helper::logException($e, '('.$mailbox->name.') Could not fetch specific message by Message-ID:');
            return null;
        }

        $imap_folders = \Eventy::filter('mail.fetch_message.imap_folders', $mailbox->getInImapFolders(), $mailbox);

        foreach ($imap_folders as $folder_name) {
            try {
                $folder = self::getImapFolder($client, $folder_name);

                if (!$folder) {
                    \Log::error('('.$mailbox->name.') Show Original - folder not found: '.$folder_name);
                    continue;
                }
                // Message-ID: <123@123.com>
                $search_message_id = addcslashes($message_id, '\"');
                $query = $folder->query()
                    //->text('<'.$message_id.'>')
                    ->whereMessageId('"<'.$search_message_id.'>"')
                    ->leaveUnread()
                    ->limit(1);

                // Limit using date to speed up the search.
                if ($message_date) {
                   $query->since($message_date->subDays(7));
                   // Here we should add 14 days, as previous line subtracts 7 days.
                   $query->before($message_date->addDays(14));
                }

                if ($no_charset) {
                    $query->setCharset(null);
                }

                $messages = $query->get();

                $last_error = '';
                if (method_exists($client, 'getLastError')) {
                    $last_error = $client->getLastError();
                }

                if ($last_error && stristr($last_error, 'The specified charset is not supported')) {
                    // Solution for MS mailboxes.
                    // https://github.com/freescout-helpdesk/freescout/issues/176
                    //$query = $folder->query()->text('<'.$message_id.'>')->leaveUnread()->limit(1)->setCharset(null);
                    $query = $folder->query()->whereMessageId('"<'.$search_message_id.'>"')->leaveUnread()->limit(1)->setCharset(null);
                    if ($message_date) {
                       $query->since($message_date->subDays(7));
                       $query->before($message_date->addDays(14));
                    }
                    $messages = $query->get();
                    $no_charset = true;
                }

                if (count($messages)) {
                    return $messages->first();
                }

            } catch (\Exception $e) {
                \Helper::logException($e, '('.$mailbox->name.') Could not fetch specific message by Message-ID via IMAP:');
            }
        }

        return null;
    }

    public static function oauthGetAuthorizationUrl($provider_code, $params)
    {
        $args = [];

        switch ($provider_code) {
            case self::OAUTH_PROVIDER_MICROSOFT:
                // https://docs.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth
                $args = [
                    'scope' => 'offline_access https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/SMTP.Send',
                    'response_type' => 'code',
                    'approval_prompt' => 'auto',
                    'redirect_uri' => route('mailboxes.oauth_callback'),
                ];
                $args = array_merge($args, $params);
                $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?'.http_build_query($args);
                break;
        }

        return $url;
    }

    public static function oauthGetAccessToken($provider_code, $params)
    {
        $token_data = [];
        $post_params = [];

        switch ($provider_code) {
            case self::OAUTH_PROVIDER_MICROSOFT:
                $post_params = [
                    'scope' => 'offline_access https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/SMTP.Send',
                    "grant_type" => "authorization_code",
                    'redirect_uri' => route('mailboxes.oauth_callback'),
                ];

                $post_params = array_merge($post_params, $params);

                // Refreshing Access Token.
                if (!empty($post_params['refresh_token'])) {
                    $post_params['grant_type'] = 'refresh_token';
                }
                
                // $postUrl = "/common/oauth2/token";
                // $hostname = "login.microsoftonline.com";
                $full_url = "https://login.microsoftonline.com/common/oauth2/v2.0/token";

                // $headers = array(
                //     // "POST " . $postUrl . " HTTP/1.1",
                //     // "Host: login.windows.net",
                //     "Content-type: application/x-www-form-urlencoded",
                // );

                $curl = curl_init($full_url);

                curl_setopt($curl, CURLOPT_POST, true);
                //curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $post_params);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("application/x-www-form-urlencoded"));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                \Helper::setCurlDefaultOptions($curl);
                curl_setopt($curl, CURLOPT_TIMEOUT, 180);

                $response = curl_exec($curl);

                if ($response) {
                    $result = json_decode($response, true);

                    // [token_type] => Bearer
                    // [scope] => IMAP.AccessAsUser.All offline_access SMTP.Send User.Read
                    // [expires_in] => 4514
                    // [ext_expires_in] => 4514
                    // [expires_on] => 1646122657
                    // [not_before] => 1646117842
                    // [resource] => 00000002-0000-0000-c000-000000000000
                    // [access_token] => dd
                    // [refresh_token] => dd
                    // [id_token] => dd
                    if (!empty($result['access_token'])) {
                        $token_data['provider'] = self::OAUTH_PROVIDER_MICROSOFT;
                        $token_data['a_token'] = $result['access_token'];
                        $token_data['r_token'] = $result['refresh_token'];
                        //$token_data['id_token'] = $result['id_token'];
                        $token_data['issued_on'] = now()->toDateTimeString();
                        $token_data['expires_in'] = $result['expires_in'];
                    } elseif ($response) {
                        $token_data['error'] = $response;
                    } else {
                        $token_data['error'] = 'Response code: '.curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    }
                }
                curl_close($curl);

                break;
        }

        return $token_data;
    }

    public static function oauthDisconnect($provider_code, $redirect_uri)
    {
        switch ($provider_code) {
            case self::OAUTH_PROVIDER_MICROSOFT:
                return redirect()->away('https://login.microsoftonline.com/common/oauth2/v2.0/logout?post_logout_redirect_uri='.urlencode($redirect_uri));
            break;
        }
    }

    public static function prepareMailable($mailable)
    {
        $custom_headers_str = config('app.custom_mail_headers');

        if (empty($custom_headers_str)) {
            return;
        }

        $custom_headers = explode(';', $custom_headers_str);

        $mailable->withSwiftMessage(function ($swiftmessage) use ($custom_headers) {
            $headers = $swiftmessage->getHeaders();

            foreach ($custom_headers as $custom_header) {
                $header_parts = explode(':', $custom_header);

                $header_name = trim($header_parts[0] ?? '');
                $header_value = trim($header_parts[1] ?? '');
                if ($header_name && $header_value) {
                    $headers->addTextHeader($header_name, $header_value);
                }
            }
            return $swiftmessage;
        });
    }

    public static function getImapFolder($client, $folder_name)
    {
        // https://github.com/freescout-helpdesk/freescout/issues/3502
        $folder_name = mb_convert_encoding($folder_name, "UTF7-IMAP","UTF-8");

        if (method_exists($client, 'getFolderByPath')) {
            return $client->getFolderByPath($folder_name);
        } else {
            return $client->getFolder($folder_name);
        }
    }

    /**
     * This function is used to decode email subjects and attachment names in Webklex libraries.
     */
    public static function decodeSubject($subject)
    {
        // Sometimes trying to decode non-encoded strings leads
        // to loosing accents.
        // https://github.com/freescout-help-desk/freescout/issues/4506
        if (!strstr($subject, '=?')) {
            return $subject;
        }
        // Remove new lines as iconv_mime_decode() may loose a part separated by new line:
        // =?utf-8?Q?Gesch=C3=A4ftskonto?= erstellen =?utf-8?Q?f=C3=BCr?=
        //  249143
        $subject = preg_replace("/[\r\n]/", '', $subject);
        // https://github.com/freescout-helpdesk/freescout/issues/3185
        //$subject = str_ireplace('=?iso-2022-jp?', '=?iso-2022-jp-ms?', $subject);
        $subject = self::substituteMimeEncoding($subject);

        // Sometimes imap_utf8() can't decode the subject, for example:
        // =?iso-2022-jp?B?GyRCIXlCaBsoQjEzMhskQjlmISEhViUsITwlRyVzGyhCJhskQiUoJS8lOSVGJWolIiFXQGxMZ0U5JE4kPyRhJE4jURsoQiYbJEIjQSU1JW0lcyEhIVo3bjQpJSglLyU5JUYlaiUiISYlbyE8JS8hWxsoQg==?=
        // and sometimes iconv_mime_decode() can't decode the subject.
        // So we are using both.
        // 
        // We are trying iconv_mime_decode() first because imap_utf8()
        // decodes umlauts into two symbols:
        // https://github.com/freescout-helpdesk/freescout/issues/2965

        // Sometimes subject is split into parts and each part is base63 encoded.
        // And sometimes it's first encoded and after that split.
        // https://github.com/freescout-helpdesk/freescout/issues/3066      

        // Step 1. Abnormal way - text is encoded and split into parts.
  
        // Only one type of encoding should be used.
        preg_match_all("/(=\?[^\?]+\?[BQ]\?)([^\?]+)(\?=)/i", $subject, $m);
        $encodings = $m[1] ?? [];
        array_walk($encodings, function($value) {
            $value = strtolower($value);
        });
        $one_encoding = count(array_unique($encodings)) == 1;

        if ($one_encoding) {
            // First try to join all lines and parts.
            // Keep in mind that there can be non-encoded parts also:
            // =?utf-8?Q?Gesch=C3=A4ftskonto?= erstellen =?utf-8?Q?f=C3=BCr?=
            preg_match_all("/(=\?[^\?]+\?[BQ]\?)([^\?]+)(\?=)[\r\n\t ]*/i", $subject, $m);

            $joined_parts = '';
            if (count($m[1]) > 1 && !empty($m[2]) && !preg_match("/[\r\n\t ]+[^=]/i", $subject)) {
                // Example: GyRCQGlNVTtZRTkhIT4uTlMbKEI=
                $joined_parts = $m[1][0].implode('', $m[2]).$m[3][0];

                // Base64 and URL encoded string can't contain "=" in the middle
                // https://stackoverflow.com/questions/6916805/why-does-a-base64-encoded-string-have-an-sign-at-the-end
                $has_equal_in_the_middle = preg_match("#=+([^$\? =])#", $joined_parts);

                if (!$has_equal_in_the_middle) {
                    $subject_decoded = iconv_mime_decode($joined_parts, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8");

                    if ($subject_decoded 
                        && trim($subject_decoded) != trim($joined_parts)
                        && trim($subject_decoded) != trim(rtrim($joined_parts, '='))
                        && !self::isNotYetFullyDecoded($subject_decoded)
                    ) {
                        return $subject_decoded;
                    }

                    // Try imap_utf8().
                    // =?iso-2022-jp?B?IBskQiFaSEcyPDpuQ?= =?iso-2022-jp?B?C4wTU1qIVs3Mkp2JSIlLyU3JSItahsoQg==?=
                    $subject_decoded = self::imapUtf8($joined_parts);

                    if ($subject_decoded 
                        && trim($subject_decoded) != trim($joined_parts)
                        && trim($subject_decoded) != trim(rtrim($joined_parts, '='))
                        && !self::isNotYetFullyDecoded($subject_decoded)
                    ) {
                        return $subject_decoded;
                    }
                }
            }
        }

        // Step 2. Standard way - each part is encoded separately.

        // iconv_mime_decode() can't decode:
        // =?iso-2022-jp?B?IBskQiFaSEcyPDpuQC4wTU1qIVs3Mkp2JSIlLyU3JSItahsoQg==?=
        $subject_decoded = iconv_mime_decode($subject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8");

        // Sometimes iconv_mime_decode() can't decode some parts of the subject:
        // =?iso-2022-jp?B?IBskQiFaSEcyPDpuQC4wTU1qIVs3Mkp2JSIlLyU3JSItahsoQg==?=
        // =?iso-2022-jp?B?GyRCQGlNVTtZRTkhIT4uTlMbKEI=?=
        if (self::isNotYetFullyDecoded($subject_decoded)) {
            $subject_decoded = self::imapUtf8($subject);
        }

        // All previous functions could not decode text.
        // mb_decode_mimeheader() properly decodes umlauts into one unice symbol.
        // But we use mb_decode_mimeheader() as a last resort as it may garble some symbols.
        // Example: =?ISO-8859-1?Q?Vorgang 538336029: M=F6chten Sie Ihre E-Mail-Adresse =E4ndern??=
        if (self::isNotYetFullyDecoded($subject_decoded)) {
            $subject_decoded = mb_decode_mimeheader($subject);
        }

        if (!$subject_decoded) {
            $subject_decoded = $subject;
        }

        return $subject_decoded;
    }

    public static function isNotYetFullyDecoded($subject_decoded) {
        // https://stackoverflow.com/questions/15276191/why-does-a-diamond-with-a-questionmark-in-it-appear-in-my-html
        $invalid_utf_symbols = ['�'];

        return preg_match_all("/=\?[^\?]+\?[BQ]\?/i", $subject_decoded)
            || !mb_check_encoding($subject_decoded, 'UTF-8')
            || \Str::contains($subject_decoded, $invalid_utf_symbols);
    }

    public static function getHashedReplySeparator($message_id) {
        $separator = \MailHelper::REPLY_SEPARATOR_HTML;

        if ($message_id) {
            $separator .= substr(md5($message_id.config('app.key')), 0, 8);
        }

        return $separator;
    }

    // Sanitize status message - remove SMTP username and password.
    public static function sanitizeSmtpStatusMessage($status_message) {
        $status_message = preg_replace('#(username ")[^"]+(")#', '$1***$2', $status_message ?? '');
        $status_message = preg_replace("#(Swift_Transport_Esmtp_Auth_LoginAuthenticator\->authenticate\(Object\(Swift_SmtpTransport\), ')[^\']+(', ')[^\']+('\))#", '$1***$2***$3', $status_message ?? '');

        return $status_message;
    }

    public static function parseEml($content, $mailbox) {
        if (!str_contains($content, "\r\n")){
            $content = str_replace("\n", "\r\n", $content);
        }

        $raw_header = substr($content, 0, strpos($content, "\r\n\r\n"));
        $raw_body = substr($content, strlen($raw_header)+8);

        //\Config::set('app.new_fetching_library', 'true');

        $client = \MailHelper::getMailboxClient($mailbox);
        $client->openFolder("INBOX");
        
        return \Webklex\PHPIMAP\Message::make(null, null, $client, $raw_header, $raw_body, [], \Webklex\PHPIMAP\IMAP::ST_UID);
    }

    // Substitue encoding during mail body decoding.
    // https://github.com/freescout-help-desk/freescout/issues/4282
    public static function substituteEncoding($encoding)
    {
        $encoding = strtolower($encoding);

        if (!empty(self::$encoding_substitution[$encoding])) {
            return self::$encoding_substitution[$encoding];
        } else {
            return $encoding;
        }
    }

    public static function substituteMimeEncoding($string)
    {
        foreach (self::$mime_encoding_substitution as $from => $into) {
            $string = str_ireplace('=?'.$from.'?', '=?'.$into.'?', $string);
        }
        return $string;
    }

    // public static function oauthGetProvider($provider_code, $params)
    // {
    //     $provider = null;

    //     switch ($provider_code) {
    //         case self::OAUTH_PROVIDER_MICROSOFT:
    //             $provider = new \Stevenmaguire\OAuth2\Client\Provider\Microsoft([
    //                 // Required
    //                 'clientId'                  => $params['client_id'],
    //                 'clientSecret'              => $params['client_secret'],
    //                 'redirectUri'               => route('mailboxes.oauth_callback'),
    //                 //https://login.microsoftonline.com/common/oauth2/authorize';
    //                 'urlAuthorize'              => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
    //                 'urlAccessToken'            => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
    //                 'urlResourceOwnerDetails'   => 'https://outlook.office.com/api/v1.0/me'
    //             ]);
    //             break;
    //     }

    //     return $provider;
    // }
}
