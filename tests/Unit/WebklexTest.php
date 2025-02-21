<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\FixtureWebklexMessage;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Message;

class WebklexTest extends FixtureWebklexMessage {

    /**
     * @throws RuntimeException
     * @throws MessageContentFetchingException
     * @throws ResponseException
     * @throws ImapBadRequestException
     * @throws InvalidMessageDateException
     * @throws ConnectionFailedException
     * @throws \ReflectionException
     * @throws ImapServerErrorException
     * @throws AuthFailedException
     * @throws MaskNotFoundException
     */
    public function testMessage1() {
        $message = $this->getFixture("message-1.eml");

        self::assertSame("☆第132号　「ガーデン&エクステリア」専門店のためのＱ&Ａサロン　【月刊エクステリア・ワーク】", (string)$message->subject);
        self::assertSame("------------B832AF745285AEEC6D5AEE42", $message->header->getBoundary());

        $attachments = $message->getAttachments();

        self::assertSame(1, $attachments->count());

        $attachment = $attachments->first();
        self::assertSame("☆第132号　「ガーデン&エクステリア」専門店のためのＱ&Ａサロン　【月刊エクステリア・ワーク】", $attachment->filename);
        self::assertSame("☆第132号　「ガーデン&エクステリア」専門店のためのＱ&Ａサロン　【月刊エクステリア・ワーク】", $attachment->name);

        // https://github.com/freescout-help-desk/freescout/issues/4506
        $from = $message->getFrom();
        self::assertSame(1, count($from->get()));
        self::assertSame('Análisis EC Madrid', $from[0]->personal);
    }

    /**
     * @throws RuntimeException
     * @throws MessageContentFetchingException
     * @throws ResponseException
     * @throws ImapBadRequestException
     * @throws InvalidMessageDateException
     * @throws ConnectionFailedException
     * @throws \ReflectionException
     * @throws ImapServerErrorException
     * @throws AuthFailedException
     * @throws MaskNotFoundException
     */
    public function testMessage1B() {
        $message = $this->getFixture("message-1b.eml");

        self::assertSame("386 - 400021804 - 19., Heiligenstädter Straße 80 - 0819306 - Anfrage Vergabevorschlag", (string)$message->subject);

        $attachments = $message->getAttachments();

        self::assertSame(1, $attachments->count());

        $attachment = $attachments->first();
        //self::assertSame("2021_Mängelliste_0819306.xlsx", $attachment->description);
        self::assertSame("2021_Mängelliste_0819306.xlsx", $attachment->filename);
        //self::assertSame("2021_Mängelliste_0819306.xlsx", $attachment->name);
    }

    /**
     * @throws RuntimeException
     * @throws MessageContentFetchingException
     * @throws ResponseException
     * @throws ImapBadRequestException
     * @throws ConnectionFailedException
     * @throws InvalidMessageDateException
     * @throws ImapServerErrorException
     * @throws AuthFailedException
     * @throws \ReflectionException
     * @throws MaskNotFoundException
     */
    public function testMessage1Symbols() {
        $message = $this->getFixture("message-1symbols.eml");

        $attachments = $message->getAttachments();

        self::assertSame(1, $attachments->count());

        /** @var Attachment $attachment */
        $attachment = $attachments->first();
        // self::assertSame("Checkliste 10.,DAVIDGASSE 76-80;2;2.pdf", $attachment->description);
        // self::assertSame("Checkliste 10.,DAVIDGASSE 76-80;2;2.pdf", $attachment->name);
        self::assertSame("Checkliste 10.,DAVIDGASSE 76-80;2;2.pdf", $attachment->filename);
    }

    // https://github.com/Webklex/php-imap/commit/0a9b263eb4e29c2822cf7d68bec27a9af33ced2f
    public function testMessageParts() {
        $message = $this->getFixture("message-2.eml");

        self::assertSame("Test bad boundary", (string)$message->subject);
        self::assertSame("-", $message->header->getBoundary());

        $attachments = $message->getAttachments();
        self::assertSame(1, $attachments->count());

        
        $attachment = $attachments->first();
        //self::assertSame("file.pdf", $attachment->name);
        self::assertSame("file.pdf", $attachment->filename);
        self::assertStringStartsWith("%PDF-1.4", $attachment->content);
        self::assertStringEndsWith("%%EOF\n", $attachment->content);
        self::assertSame(14938, $attachment->size);
    }

    // https://github.com/freescout-help-desk/freescout/issues/4567
    public function testIssue4567() {
        $message = $this->getFixture("issue-4567.eml");

        self::assertSame("------3f0eb27c226a6efc44713e1b8f40befd34d8d3c9199e2ad04dab9839cbbd3524", $message->header->getBoundary());
    }

    public function testRegularEmail() {
        $message = $this->getFixture("message-3.eml");

        self::assertSame("1", (string)$message->getSubject());
        self::assertSame("1\n", $message->getTextBody());
        self::assertSame('<div dir="ltr"><div>1</div></div>', $message->getHtmlBody());
    }
}