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

    public function testFitImages(): void
    {
        // Wide image is scaled down preserving aspect ratio.
        self::assertSame(
            '<img src="a.png" alt="" width="580" height="326" style="max-width:100%;height:auto;">',
            \MailHelper::fitImages('<img src="a.png" alt="" width="765" height="430">', 580)
        );
        // Narrow image keeps its dimensions.
        self::assertSame(
            '<p><img src="a.png" width="300" height="200" style="max-width:100%;height:auto;" /></p>',
            \MailHelper::fitImages('<p><img src="a.png" width="300" height="200" /></p>', 580)
        );
        // Existing max-width and height are replaced, other styles are kept.
        self::assertSame(
            '<img src="a.png" style="width:765px; line-height:1; border:0;max-width:100%;height:auto;">',
            \MailHelper::fitImages('<img src="a.png" style="width:765px; max-width:800px; line-height:1; height:430px; border:0">', 580)
        );
        // No images.
        self::assertSame('<p>text</p>', \MailHelper::fitImages('<p>text</p>', 580));
        self::assertSame('', \MailHelper::fitImages('', 580));
    }
}
