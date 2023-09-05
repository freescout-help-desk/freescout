<?php

namespace App\Misc;

class SwiftGetSmtpQueueId implements \Swift_Events_ResponseListener
{
	public static $last_smtp_queue_id = null;

    public function responseReceived(\Swift_Events_ResponseEvent $evt)
    {
        $response_text = $evt->getResponse();
        if (strpos($response_text, 'queued') !== false) {
             preg_match("#queued as ([^\$\r\n ]+)[$\r\n]#", $response_text, $m);
             if (!empty($m[1]) && trim($m[1])) {
             	self::$last_smtp_queue_id = trim($m[1]);
             }
        }
    }
}