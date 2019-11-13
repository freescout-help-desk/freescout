<?php
/*
* File:     Message.php
* Category: -
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP;

use Carbon\Carbon;
use Webklex\IMAP\Support\AttachmentCollection;
use Webklex\IMAP\Support\FlagCollection;

/**
 * Class Message.
 */
class Message
{
    /**
     * Client instance.
     *
     * @var Client
     */
    private $client = Client::class;

    /**
     * U ID.
     *
     * @var int
     */
    public $uid = '';

    /**
     * Fetch body options.
     *
     * @var int
     */
    public $fetch_options = null;

    /**
     * Fetch body options.
     *
     * @var bool
     */
    public $fetch_body = null;

    /**
     * Fetch attachments options.
     *
     * @var bool
     */
    public $fetch_attachment = null;

    /**
     * Fetch flags options.
     *
     * @var bool
     */
    public $fetch_flags = null;

    /**
     * @var int
     */
    public $msglist = 1;

    /**
     * @var int
     */
    public $msgn = null;

    /**
     * @var string
     */
    public $header = null;

    /**
     * @var null|object
     */
    public $header_info = null;

    /** @var null|string $raw_body */
    public $raw_body = null;

    /**
     * Message header components.
     *
     * @var string
     * @var mixed  $message_no
     * @var string $subject
     * @var mixed  $references
     * @var mixed  $date
     * @var array  $from
     * @var array  $to
     * @var array  $cc
     * @var array  $bcc
     * @var array  $reply_to
     * @var string $in_reply_to
     * @var array  $sender
     * @var array  $flags
     * @var array  $priority
     */
    public $message_id = '';
    public $message_no = null;
    public $subject = '';
    public $references = null;
    public $date = null;
    public $from = [];
    public $to = [];
    public $cc = [];
    public $bcc = [];
    public $reply_to = [];
    public $in_reply_to = '';
    public $sender = [];
    public $priority = 0;

    /**
     * Message body components.
     *
     * @var array
     * @var AttachmentCollection|array $attachments
     * @var FlagCollection|array       $flags
     */
    public $bodies = [];
    public $attachments = [];
    public $flags = [];

    /**
     * Message const.
     *
     * @const integer   TYPE_TEXT
     * @const integer   TYPE_MULTIPART
     *
     * @const integer   ENC_7BIT
     * @const integer   ENC_8BIT
     * @const integer   ENC_BINARY
     * @const integer   ENC_BASE64
     * @const integer   ENC_QUOTED_PRINTABLE
     * @const integer   ENC_OTHER
     */
    const TYPE_TEXT = 0;
    const TYPE_MULTIPART = 1;

    const ENC_7BIT = 0;
    const ENC_8BIT = 1;
    const ENC_BINARY = 2;
    const ENC_BASE64 = 3;
    const ENC_QUOTED_PRINTABLE = 4;
    const ENC_OTHER = 5;

    const PRIORITY_UNKNOWN = 0;
    const PRIORITY_HIGHEST = 1;
    const PRIORITY_HIGH = 2;
    const PRIORITY_NORMAL = 3;
    const PRIORITY_LOW = 4;
    const PRIORITY_LOWEST = 5;

    /**
     * Message constructor.
     *
     * @param int      $uid
     * @param int|null $msglist
     * @param Client   $client
     * @param int|null $fetch_options
     * @param bool     $fetch_body
     * @param bool     $fetch_attachment
     * @param bool     $fetch_flags
     *
     * @throws Exceptions\ConnectionFailedException
     */
    public function __construct($uid, $msglist, Client $client, $fetch_options = null, $fetch_body = false, $fetch_attachment = false, $fetch_flags = false)
    {
        $this->setFetchOption($fetch_options);
        $this->setFetchBodyOption($fetch_body);
        $this->setFetchAttachmentOption($fetch_attachment);
        $this->setFetchFlagsOption($fetch_flags);

        $this->attachments = AttachmentCollection::make([]);
        $this->flags = FlagCollection::make([]);

        $this->msglist = $msglist;
        $this->client = $client;

        $this->uid = ($this->fetch_options == FT_UID) ? $uid : $uid;
        $this->msgn = ($this->fetch_options == FT_UID) ? imap_msgno($this->client->getConnection(), $uid) : $uid;

        $this->parseHeader();

        if ($this->getFetchFlagsOption() === true) {
            $this->parseFlags();
        }

        if ($this->getFetchBodyOption() === true) {
            $this->parseBody();
        }
    }

    /**
     * Copy the current Messages to a mailbox.
     *
     * @param $mailbox
     * @param int $options
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return bool
     */
    public function copy($mailbox, $options = 0)
    {
        return imap_mail_copy($this->client->getConnection(), $this->msglist, $mailbox, $options);
    }

    /**
     * Move the current Messages to a mailbox.
     *
     * @param $mailbox
     * @param int $options
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return bool
     */
    public function move($mailbox, $options = 0)
    {
        return imap_mail_move($this->client->getConnection(), $this->msglist, $mailbox, $options);
    }

    /**
     * Check if the Message has a text body.
     *
     * @return bool
     */
    public function hasTextBody()
    {
        return isset($this->bodies['text']);
    }

    /**
     * Get the Message text body.
     *
     * @return mixed
     */
    public function getTextBody()
    {
        if (!isset($this->bodies['text'])) {
            return false;
        }

        return $this->bodies['text']->content;
    }

    /**
     * Check if the Message has a html body.
     *
     * @return bool
     */
    public function hasHTMLBody()
    {
        return isset($this->bodies['html']);
    }

    /**
     * Get the Message html body.
     *
     * @var bool
     *
     * @return mixed
     */
    public function getHTMLBody($replaceImages = false)
    {
        if (!isset($this->bodies['html'])) {
            return false;
        }

        $body = $this->bodies['html']->content;
        if ($replaceImages) {
            $this->attachments->each(function ($oAttachment) use (&$body) {
                if ($oAttachment->id && isset($oAttachment->img_src)) {
                    $body = str_replace('cid:'.$oAttachment->id, $oAttachment->img_src, $body);
                }
            });
        }

        return $body;
    }

    /**
     * Parse all defined headers.
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return void
     */
    private function parseHeader()
    {
        $this->header = $header = imap_fetchheader($this->client->getConnection(), $this->uid, FT_UID);
        if ($this->header) {
            $header = imap_rfc822_parse_headers($this->header);
        }

        if (preg_match('/x\-priority\:.*([0-9]{1,2})/i', $this->header, $priority)) {
            $priority = isset($priority[1]) ? (int) $priority[1] : 0;
            switch ($priority) {
                case self::PRIORITY_HIGHEST:
                    $this->priority = self::PRIORITY_HIGHEST;
                    break;
                case self::PRIORITY_HIGH:
                    $this->priority = self::PRIORITY_HIGH;
                    break;
                case self::PRIORITY_NORMAL:
                    $this->priority = self::PRIORITY_NORMAL;
                    break;
                case self::PRIORITY_LOW:
                    $this->priority = self::PRIORITY_LOW;
                    break;
                case self::PRIORITY_LOWEST:
                    $this->priority = self::PRIORITY_LOWEST;
                    break;
                default:
                    $this->priority = self::PRIORITY_UNKNOWN;
                    break;
            }
        }

        if (property_exists($header, 'subject')) {
            $this->subject = mb_decode_mimeheader($header->subject);
        }

        if (property_exists($header, 'date')) {
            $date = $header->date;

            /*
             * Exception handling for invalid dates
             *
             * Currently known invalid formats:
             * ^ Datetime                                   ^ Problem                           ^ Cause
             * | Mon, 20 Nov 2017 20:31:31 +0800 (GMT+8:00) | Double timezone specification     | A Windows feature
             * | Thu, 8 Nov 2018 08:54:58 -0200 (-02)       |
             * |                                            | and invalid timezone (max 6 char) |
             * | 04 Jan 2018 10:12:47 UT                    | Missing letter "C"                | Unknown
             * | Thu, 31 May 2018 18:15:00 +0800 (added by) | Non-standard details added by the | Unknown
             * |                                            | mail server                       |
             * | Sat, 31 Aug 2013 20:08:23 +0580            | Invalid timezone                  | PHPMailer bug https://sourceforge.net/p/phpmailer/mailman/message/6132703/
             *
             * Please report any new invalid timestamps to [#45](https://github.com/Webklex/laravel-imap/issues/45)
             */
            // try {
            //     $this->date = Carbon::parse($date);
            // } catch (\Exception $e) {
            //     switch (true) {
            //         case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \(.*)\)+$/i', $date) > 0:
            //         case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{2,4}\ [0-9]{2}\:[0-9]{2}\:[0-9]{2}\ [A-Z]{2}\ \-[0-9]{2}\:[0-9]{2}\ \([A-Z]{2,3}\ \-[0-9]{2}:[0-9]{2}\))+$/i', $date) > 0:
            //         $array = explode('(', $date);
            //             $array = array_reverse($array);
            //             $date = trim(array_pop($array));
            //             break;
            //         case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
            //             $date .= 'C';
            //             break;
            //     }
            //     $date = preg_replace('/[<>]/', '', $date);
            //     try {
            //         $this->date = Carbon::parse($date);
            //     } catch (\Exception $e) {
            //         \Helper::logException($e, '[Webklex\IMAP\Message]');
            //     }
            // }
            if (preg_match('/\+0580/', $date)) {
                $date = str_replace('+0580', '+0530', $date);
            }
            $date = trim(rtrim($date));
            $date = preg_replace('/[<>]/', '', $date);
            try {
                $this->date = Carbon::parse($date);
            } catch (\Exception $e) {
                switch (true) {
                    case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ UT)+$/i', $date) > 0:
                        $date .= 'C';
                        break;
                    case preg_match('/([A-Z]{2,3}[\,|\ \,]\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}.*)+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}\,\ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \(.*)\)+$/i', $date) > 0:
                    case preg_match('/([A-Z]{2,3}\, \ [0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{4}\ [0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}\ [\-|\+][0-9]{4}\ \(.*)\)+$/i', $date) > 0:
                    case preg_match('/([0-9]{1,2}\ [A-Z]{2,3}\ [0-9]{2,4}\ [0-9]{2}\:[0-9]{2}\:[0-9]{2}\ [A-Z]{2}\ \-[0-9]{2}\:[0-9]{2}\ \([A-Z]{2,3}\ \-[0-9]{2}:[0-9]{2}\))+$/i', $date) > 0:
                        $array = explode('(', $date);
                        $array = array_reverse($array);
                        $date = trim(array_pop($array));
                        break;
                }
                try {
                    $this->date = Carbon::parse($date);
                } catch (\Exception $_e) {
                    $this->date = Carbon::now();
                    \Helper::logException($_e, '[Webklex\IMAP\Message]');
                    //throw new InvalidMessageDateException("Invalid message date. ID:".$this->getMessageId(), 1000, $e);
                }
            }
        }

        if (property_exists($header, 'from')) {
            $this->from = $this->parseAddresses($header->from);
        }
        if (property_exists($header, 'to')) {
            $this->to = $this->parseAddresses($header->to);
        }
        if (property_exists($header, 'cc')) {
            $this->cc = $this->parseAddresses($header->cc);
        }
        if (property_exists($header, 'bcc')) {
            $this->bcc = $this->parseAddresses($header->bcc);
        }
        if (property_exists($header, 'references')) {
            $this->references = $header->references;
        }

        if (property_exists($header, 'reply_to')) {
            $this->reply_to = $this->parseAddresses($header->reply_to);
        }
        if (property_exists($header, 'in_reply_to')) {
            $this->in_reply_to = str_replace(['<', '>'], '', $header->in_reply_to);
        }
        if (property_exists($header, 'sender')) {
            $this->sender = $this->parseAddresses($header->sender);
        }

        if (property_exists($header, 'message_id')) {
            $this->message_id = str_replace(['<', '>'], '', $header->message_id);
        }
        if (property_exists($header, 'Msgno')) {
            $messageNo = (int) trim($header->Msgno);
            $this->message_no = ($this->fetch_options == FT_UID) ? $messageNo : imap_msgno($this->client->getConnection(), $messageNo);
        } else {
            $this->message_no = imap_msgno($this->client->getConnection(), $this->getUid());
        }
    }

    /**
     * Parse additional flags.
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return void
     */
    private function parseFlags()
    {
        $flags = imap_fetch_overview($this->client->getConnection(), $this->uid, FT_UID);
        if (is_array($flags) && isset($flags[0])) {
            if (property_exists($flags[0], 'recent')) {
                $this->flags->put('recent', $flags[0]->recent);
            }
            if (property_exists($flags[0], 'flagged')) {
                $this->flags->put('flagged', $flags[0]->flagged);
            }
            if (property_exists($flags[0], 'answered')) {
                $this->flags->put('answered', $flags[0]->answered);
            }
            if (property_exists($flags[0], 'deleted')) {
                $this->flags->put('deleted', $flags[0]->deleted);
            }
            if (property_exists($flags[0], 'seen')) {
                $this->flags->put('seen', $flags[0]->seen);
            }
            if (property_exists($flags[0], 'draft')) {
                $this->flags->put('draft', $flags[0]->draft);
            }
        }
    }

    /**
     * Get the current Message header info.
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return object
     */
    public function getHeaderInfo()
    {
        if ($this->header_info == null) {
            $this->header_info =
            $this->header_info = imap_headerinfo($this->client->getConnection(), $this->getMessageNo());
        }

        return $this->header_info;
    }

    /**
     * Parse Addresses.
     *
     * @param $list
     *
     * @return array
     */
    private function parseAddresses($list)
    {
        $addresses = [];

        foreach ($list as $item) {
            $address = (object) $item;

            if (!property_exists($address, 'mailbox')) {
                $address->mailbox = false;
            }
            if (!property_exists($address, 'host')) {
                $address->host = false;
            }
            if (!property_exists($address, 'personal')) {
                $address->personal = false;
            }

            $personalParts = imap_mime_header_decode($address->personal);

            $address->personal = '';
            foreach ($personalParts as $p) {
                $address->personal .= $p->text;
            }

            $address->mail = ($address->mailbox && $address->host) ? $address->mailbox.'@'.$address->host : false;
            $address->full = ($address->personal) ? $address->personal.' <'.$address->mail.'>' : $address->mail;

            $addresses[] = $address;
        }

        return $addresses;
    }

    /**
     * Parse the Message body.
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return $this
     */
    public function parseBody()
    {
        $structure = imap_fetchstructure($this->client->getConnection(), $this->uid, FT_UID);

        if (property_exists($structure, 'parts')) {
            $parts = $structure->parts;

            foreach ($parts as $part) {
                foreach ($part->parameters as $parameter) {
                    if ($parameter->attribute == 'charset') {
                        $encoding = $parameter->value;
                        $parameter->value = preg_replace('/Content-Transfer-Encoding/', '', $encoding);
                    }
                }
            }
        }

        $this->fetchStructure($structure);

        return $this;
    }

    /**
     * Fetch the Message structure.
     *
     * @param $structure
     * @param mixed $partNumber
     *
     * @throws Exceptions\ConnectionFailedException
     */
    private function fetchStructure($structure, $partNumber = null)
    {
        if ($structure->type == self::TYPE_TEXT &&
            # FreeScout #320
            #($structure->ifdisposition == 0 ||
            #    ($structure->ifdisposition == 1 && !isset($structure->parts) && $partNumber == null)
            #)
            (empty($structure->disposition) || strtolower($structure->disposition) != 'attachment')
        ) {
            // FreeScout improvement
            /*if (strtolower($structure->subtype) == 'plain' || strtolower($structure->subtype) == 'csv') {
                if (!$partNumber) {
                    $partNumber = 1;
                }

                $encoding = $this->getEncoding($structure);

                $content = imap_fetchbody($this->client->getConnection(), $this->uid, $partNumber, $this->fetch_options | FT_UID);
                $content = $this->decodeString($content, $structure->encoding);
                $content = $this->convertEncoding($content, $encoding);

                $body = new \stdClass();
                $body->type = 'text';
                $body->content = $content;

                $this->bodies['text'] = $body;

                $this->fetchAttachment($structure, $partNumber);
            } elseif (strtolower($structure->subtype) == 'html') {
                if (!$partNumber) {
                    $partNumber = 1;
                }

                $encoding = $this->getEncoding($structure);

                $content = imap_fetchbody($this->client->getConnection(), $this->uid, $partNumber, $this->fetch_options | FT_UID);
                $content = $this->decodeString($content, $structure->encoding);
                $content = $this->convertEncoding($content, $encoding);

                $body = new \stdClass();
                $body->type = 'html';
                $body->content = $content;

                $this->bodies['html'] = $body;
            }*/
            if (strtolower($structure->subtype) == 'html') {
                if (!$partNumber) {
                    $partNumber = 1;
                }

                $encoding = $this->getEncoding($structure);

                $content = imap_fetchbody($this->client->getConnection(), $this->uid, $partNumber, $this->fetch_options | FT_UID);
                $content = $this->decodeString($content, $structure->encoding);
                $content = $this->convertEncoding($content, $encoding);

                $body = new \stdClass();
                $body->type = 'html';
                $body->content = $content;

                $this->bodies['html'] = $body;
            } else {
                // PLAIN.
                if (!$partNumber) {
                    $partNumber = 1;
                }

                $encoding = $this->getEncoding($structure);

                $content = imap_fetchbody($this->client->getConnection(), $this->uid, $partNumber, $this->fetch_options | FT_UID);
                $content = $this->decodeString($content, $structure->encoding);
                $content = $this->convertEncoding($content, $encoding);

                $body = new \stdClass();
                $body->type = 'text';
                $body->content = $content;

                $this->bodies['text'] = $body;

                $this->fetchAttachment($structure, $partNumber);
            }
        } elseif ($structure->type == self::TYPE_MULTIPART) {
            foreach ($structure->parts as $index => $subStruct) {
                $prefix = '';
                if ($partNumber) {
                    $prefix = $partNumber.'.';
                }
                $this->fetchStructure($subStruct, $prefix.($index + 1));
            }
        } else {
            if ($this->getFetchAttachmentOption() === true) {
                $this->fetchAttachment($structure, $partNumber);
            }
        }
    }

    /**
     * Fetch the Message attachment.
     *
     * @param object $structure
     * @param mixed  $partNumber
     *
     * @throws Exceptions\ConnectionFailedException
     */
    protected function fetchAttachment($structure, $partNumber)
    {
        $oAttachment = new Attachment($this, $structure, $partNumber);

        if ($oAttachment->getName() !== null) {
            if ($oAttachment->getId() !== null) {
                $this->attachments->put($oAttachment->getId(), $oAttachment);
            } else {
                $this->attachments->push($oAttachment);
            }
        }
    }

    /**
     * Fail proof setter for $fetch_option.
     *
     * @param $option
     *
     * @return $this
     */
    public function setFetchOption($option)
    {
        if (is_int($option) === true) {
            $this->fetch_options = $option;
        } elseif (is_null($option) === true) {
            $config = config('imap.options.fetch', FT_UID);
            $this->fetch_options = is_int($config) ? $config : 1;
        }

        return $this;
    }

    /**
     * Fail proof setter for $fetch_body.
     *
     * @param $option
     *
     * @return $this
     */
    public function setFetchBodyOption($option)
    {
        if (is_bool($option)) {
            $this->fetch_body = $option;
        } elseif (is_null($option)) {
            $config = config('imap.options.fetch_body', true);
            $this->fetch_body = is_bool($config) ? $config : true;
        }

        return $this;
    }

    /**
     * Fail proof setter for $fetch_attachment.
     *
     * @param $option
     *
     * @return $this
     */
    public function setFetchAttachmentOption($option)
    {
        if (is_bool($option)) {
            $this->fetch_attachment = $option;
        } elseif (is_null($option)) {
            $config = config('imap.options.fetch_attachment', true);
            $this->fetch_attachment = is_bool($config) ? $config : true;
        }

        return $this;
    }

    /**
     * Fail proof setter for $fetch_flags.
     *
     * @param $option
     *
     * @return $this
     */
    public function setFetchFlagsOption($option)
    {
        if (is_bool($option)) {
            $this->fetch_flags = $option;
        } elseif (is_null($option)) {
            $config = config('imap.options.fetch_flags', true);
            $this->fetch_flags = is_bool($config) ? $config : true;
        }

        return $this;
    }

    /**
     * Decode a given string.
     *
     * @param $string
     * @param $encoding
     *
     * @return string
     */
    public function decodeString($string, $encoding)
    {
        switch ($encoding) {
            case self::ENC_7BIT:
                return $string;
            case self::ENC_8BIT:
                return quoted_printable_decode(imap_8bit($string));
            case self::ENC_BINARY:
                return imap_binary($string);
            case self::ENC_BASE64:
                return imap_base64($string);
            case self::ENC_QUOTED_PRINTABLE:
                return quoted_printable_decode($string);
            case self::ENC_OTHER:
                return $string;
            default:
                return $string;
        }
    }

    /**
     * Convert the encoding.
     *
     * @param $str
     * @param string $from
     * @param string $to
     *
     * @return mixed|string
     */
    public function convertEncoding($str, $from = 'ISO-8859-2', $to = 'UTF-8')
    {

        // FreeScout fix
        // We don't need to do convertEncoding() if charset is ASCII (us-ascii):
        //     ASCII is a subset of UTF-8, so all ASCII files are already UTF-8 encoded
        //     https://stackoverflow.com/a/11303410
        //
        // us-ascii is the same as ASCII:
        //     ASCII is the traditional name for the encoding system; the Internet Assigned Numbers Authority (IANA)
        //     prefers the updated name US-ASCII, which clarifies that this system was developed in the US and
        //     based on the typographical symbols predominantly in use there.
        //     https://en.wikipedia.org/wiki/ASCII
        //
        // convertEncoding() function basically means convertToUtf8(), so when we convert ASCII string into UTF-8 it gets broken.
        if (strtolower($from) == 'us-ascii' && $to == 'UTF-8') {
            return $str;
        }

        try {
            if (function_exists('iconv') && $from != 'UTF-7' && $to != 'UTF-7') {
                // FreeScout #351
                return iconv($from, $to, $str);
            } else {
                if (!$from) {
                    return mb_convert_encoding($str, $to);
                }

                return mb_convert_encoding($str, $to, $from);
            }
        } catch (\Exception $e) {
            // FreeScout #360
            if (strstr($from, '-')) {
                $from = str_replace('-', '', $from);
                return $this->convertEncoding($str, $from, $to);
            } else {
                \Helper::logException($e, '[Webklex\IMAP\Message]');
                return $str;
            }
        }
    }

    /**
     * Get the encoding of a given abject.
     *
     * @param object|string $structure
     *
     * @return string
     */
    public function getEncoding($structure)
    {
        if (property_exists($structure, 'parameters')) {
            foreach ($structure->parameters as $parameter) {
                if (strtolower($parameter->attribute) == 'charset') {
                    return EncodingAliases::get($parameter->value);
                }
            }
        } elseif (is_string($structure) === true) {
            return mb_detect_encoding($structure);
        }

        return 'UTF-8';
    }

    /**
     * Find the folder containing this message.
     *
     * @param null|Folder $folder where to start searching from (top-level inbox by default)
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return null|Folder
     */
    public function getContainingFolder(Folder $folder = null)
    {
        $folder = $folder ?: $this->client->getFolders()->first();
        $this->client->checkConnection();

        // Try finding the message by uid in the current folder
        $client = new Client();
        $client->openFolder($folder);
        $uidMatches = imap_fetch_overview($client->getConnection(), $this->uid, FT_UID);
        $uidMatch = count($uidMatches)
            ? new self($uidMatches[0]->uid, $uidMatches[0]->msgno, $client)
            : null;
        $client->disconnect();

        // imap_fetch_overview() on a parent folder will return the matching message
        // even when the message is in a child folder so we need to recursively
        // search the children
        foreach ($folder->children as $child) {
            $childFolder = $this->getContainingFolder($child);

            if ($childFolder) {
                return $childFolder;
            }
        }

        // before returning the parent
        if ($this->is($uidMatch)) {
            return $folder;
        }

        // or signalling that the message was not found in any folder
    }

    /**
     * Move the Message into an other Folder.
     *
     * @param string $mailbox
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return bool
     */
    public function moveToFolder($mailbox = 'INBOX')
    {
        $this->client->createFolder($mailbox);

        return imap_mail_move($this->client->getConnection(), $this->uid, $mailbox, CP_UID);
    }

    /**
     * Delete the current Message.
     *
     * @param bool $expunge
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return bool
     */
    public function delete($expunge = true)
    {
        $status = imap_delete($this->client->getConnection(), $this->uid, FT_UID);
        if ($expunge) {
            $this->client->expunge();
        }

        return $status;
    }

    /**
     * Restore a deleted Message.
     *
     * @param bool $expunge
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return bool
     */
    public function restore($expunge = true)
    {
        $status = imap_undelete($this->client->getConnection(), $this->uid, FT_UID);
        if ($expunge) {
            $this->client->expunge();
        }

        return $status;
    }

    /**
     * Get all message attachments.
     *
     * @return AttachmentCollection
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Checks if there are any attachments present.
     *
     * @return bool
     */
    public function hasAttachments()
    {
        return $this->attachments->isEmpty() === false;
    }

    /**
     * Set a given flag.
     *
     * @param string|array $flag
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return bool
     */
    public function setFlag($flag)
    {
        $flag = '\\'.trim(is_array($flag) ? implode(' \\', $flag) : $flag);
        $status = imap_setflag_full($this->client->getConnection(), $this->getUid(), $flag, SE_UID);
        $this->parseFlags();

        return $status;
    }

    /**
     * Unset a given flag.
     *
     * @param string|array $flag
     *
     * @throws Exceptions\ConnectionFailedException
     *
     * @return bool
     */
    public function unsetFlag($flag)
    {
        $flag = '\\'.trim(is_array($flag) ? implode(' \\', $flag) : $flag);
        $status = imap_clearflag_full($this->client->getConnection(), $this->getUid(), $flag, SE_UID);
        $this->parseFlags();

        return $status;
    }

    /**
     * @throws Exceptions\ConnectionFailedException
     *
     * @return null|object|string
     */
    public function getRawBody()
    {
        if ($this->raw_body === null) {
            $this->raw_body = imap_fetchbody($this->client->getConnection(), $this->getUid(), '', $this->fetch_options | FT_UID);
        }

        return $this->raw_body;
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return int
     */
    public function getFetchOptions()
    {
        return $this->fetch_options;
    }

    /**
     * @return bool
     */
    public function getFetchBodyOption()
    {
        return $this->fetch_body;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return bool
     */
    public function getFetchAttachmentOption()
    {
        return $this->fetch_attachment;
    }

    /**
     * @return bool
     */
    public function getFetchFlagsOption()
    {
        return $this->fetch_flags;
    }

    /**
     * @return int
     */
    public function getMsglist()
    {
        return $this->msglist;
    }

    /**
     * @return mixed
     */
    public function getMessageId()
    {
        return $this->message_id;
    }

    /**
     * @return int
     */
    public function getMessageNo()
    {
        return $this->message_no;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return mixed
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * @return Carbon|null
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return array
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return array
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @return array
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @return array
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @return array
     */
    public function getReplyTo()
    {
        return $this->reply_to;
    }

    /**
     * @return string
     */
    public function getInReplyTo()
    {
        return $this->in_reply_to;
    }

    /**
     * @return array
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @return mixed
     */
    public function getBodies()
    {
        return $this->bodies;
    }

    /**
     * @return FlagCollection
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Does this message match another one?
     *
     * A match means same uid, message id, subject and date/time.
     *
     * @param null|static $message
     *
     * @return bool
     */
    public function is(self $message = null)
    {
        if (is_null($message)) {
            return false;
        }

        return $this->uid == $message->uid
            && $this->message_id == $message->message_id
            && $this->subject == $message->subject
            && $this->date->eq($message->date);
    }
}
