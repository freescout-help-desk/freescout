<?php

use PHPAntiSpam\Corpus\ArrayCorpus;

class CorpusTest extends PHPUnit_Framework_TestCase
{
    public function testCreatingCorpusWithMinWordLengthOption()
    {
        $messages = [
            [
                'category' => 'spam',
                'content' => 'simple text',
            ]
        ];

        $tokenizer = $this->getMockBuilder('\PHPAntiSpam\Tokenizer\TokenizerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $tokenizer->expects($this->once())
            ->method('tokenize')
            ->will($this->returnValue(['simple', 'text']));

        $corpus = new ArrayCorpus($messages, $tokenizer, ['min_word_length' => 10]);
        $lexemes = $corpus->getLexemes(['simple', 'text']);

        $this->assertCount(0, $lexemes);
        $this->assertEquals([], $lexemes);
        $this->assertEquals(['spam' => 1, 'nospam' => 0], $corpus->messagesCount);
    }

    public function testCreatingCorpusWithSpamMessage()
    {
        $messages = [
            [
                'category' => 'spam',
                'content' => 'simple text',
            ]
        ];

        $tokenizer = $this->getMockBuilder('\PHPAntiSpam\Tokenizer\TokenizerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $tokenizer->expects($this->once())
            ->method('tokenize')
            ->will($this->returnValue(['simple', 'text']));

        $corpus = new ArrayCorpus($messages, $tokenizer);
        $lexemes = $corpus->getLexemes(['simple', 'text']);

        $this->assertCount(2, $lexemes);
        $this->assertEquals([
            'simple' => ['spam'=> 1, 'nospam' => 0],
            'text' => ['spam'=> 1, 'nospam' => 0],
        ], $lexemes);
        $this->assertEquals(['spam' => 1, 'nospam' => 0], $corpus->messagesCount);
    }

    public function testCreatingCorpusWithNoSpamMessage()
    {
        $messages = [
            [
                'category' => 'nospam',
                'content' => 'simple text',
            ]
        ];

        $tokenizer = $this->getMockBuilder('\PHPAntiSpam\Tokenizer\TokenizerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $tokenizer->expects($this->once())
            ->method('tokenize')
            ->will($this->returnValue(['simple', 'text']));

        $corpus = new ArrayCorpus($messages, $tokenizer);
        $lexemes = $corpus->getLexemes(['simple', 'text']);

        $this->assertCount(2, $lexemes);
        $this->assertEquals([
            'simple' => ['spam'=> 0, 'nospam' => 1],
            'text' => ['spam'=> 0, 'nospam' => 1],
        ], $lexemes);
        $this->assertEquals(['spam' => 0, 'nospam' => 1], $corpus->messagesCount);
    }
}
