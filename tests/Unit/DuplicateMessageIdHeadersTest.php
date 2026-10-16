<?php

namespace Tests\Unit;

use App\Console\Commands\FetchEmails;
use Tests\TestCase;

/**
 * An email fetched twice with the same Message-ID but a different set of headers
 * must not create a second conversation unless its Date differs too.
 * https://github.com/freescout-help-desk/freescout/issues/5638
 */
class DuplicateMessageIdHeadersTest extends TestCase
{
    const SENT_COPY = "Message-ID: <abc@example.com>\r\nDate: Mon, 12 Oct 2026 10:00:00 +0000\r\n";
    const DELIVERED_COPY = "Received: from mx.example.com\r\nMessage-ID: <abc@example.com>\r\nDate: Mon, 12 Oct 2026 10:00:00 +0000\r\nSubject: Hi\r\n";
    const OTHER_EMAIL = "Message-ID: <abc@example.com>\r\nDate: Tue, 13 Oct 2026 09:30:00 +0000\r\nSubject: Another\r\n";

    public function testSameEmailWithDifferentHeaderSetIsNotNew()
    {
        $this->assertFalse(FetchEmails::isNewEmailWithSameMessageId(self::SENT_COPY, self::DELIVERED_COPY));
    }

    public function testIdenticalHeadersAreNotNew()
    {
        $this->assertFalse(FetchEmails::isNewEmailWithSameMessageId(self::DELIVERED_COPY, self::DELIVERED_COPY));
    }

    public function testDifferentDateIsNew()
    {
        $this->assertTrue(FetchEmails::isNewEmailWithSameMessageId(self::DELIVERED_COPY, self::OTHER_EMAIL));
    }
}
