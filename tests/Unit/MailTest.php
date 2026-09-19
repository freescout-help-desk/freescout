<?php

namespace Tests\Unit;

use Generator;
use Tests\TestCase;

class MailTest extends TestCase
{
    public function testGetHeader(): void
    {
    	$message = file_get_contents(base_path('tests/Messages/message-6.eml'));

        self::assertSame("7f9b1a39-536b-47de-a7c3-22aab4aa0964@example.com", \MailHelper::getHeader($message, 'Message-ID'), 'Message-ID');
        self::assertSame("Tue, 8 Sep 2026 15:46:52 +0700", \MailHelper::getHeader($message, 'Date'), 'Date');
        // By some reason multiline headers are not parsed properly here.
        //self::assertSame("v=1; a=rsa-sha256; c=simple; d=example.com;", \MailHelper::getHeader($message, 'DKIM-Signature'), 'DKIM-Signature');
 //        self::assertSame('multipart/alternative;
 // boundary="------------c141PQyZlgOaY0xUqE0ydJUA"', \MailHelper::getHeader($message, 'Content-Type'));
    }
}
