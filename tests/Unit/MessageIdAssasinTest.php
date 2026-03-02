<?php

namespace Tests\Unit;

use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Make sure that Message-IDs generated for outgoing emails do not match Smap Assassin patterns.
 * https://github.com/freescout-help-desk/freescout/issues/5245
 */
class MessageIdAssasinTest extends TestCase
{
    use WithFaker;

    // Spam Assasin regexes.
    // https://svn.apache.org/repos/asf/spamassassin/branches/jm_re2c_hacks/rules/20_head_tests.cf
    public $assasin_regexes = [
        'MSGID_SPAM_99X9XX99' => '/^<\d\d\d\d\d\d[a-z]\d[a-z][a-z]\d\d\$[a-z][a-z][a-z]\d\d\d\d\d\$\d\d\d\d\d\d\d\d\@/',
        'MSGID_SPAM_ALPHA_NUM' => '/<[A-Z]{7}-000[0-9]{10}\@[a-z]*>/',
        'MSGID_SPAM_CAPS' => '/^\s*<?[A-Z]+\@(?!(?:mailcity|whowhere)\.com)/',
        'MSGID_SPAM_LETTERS' => '/<[a-z]{5,}\@(\S+\.)+\S+>/',
        'MSGID_NO_HOST' => '/\@>(?:$|\s)/m',
        '__MSGID_DOLLARS_OK' => '/<[0-9a-f]{4,}\$[0-9a-f]{4,}\$[0-9a-f]{4,}\@\S+>/m',
        '__MSGID_DOLLARS_MAYBE' => '/<\w{4,}\$\w{4,}\$(?!localhost)\w{4,}\@\S+>/mi',
        '__MSGID_RANDY' => '/<[a-z\d][a-z\d\$-]{10,29}[a-z\d]\@[a-z\d][a-z\d.]{3,12}[a-z\d]>/',
        '__MSGID_OK_HEX' => '/\b[a-f\d]{8}\b/',
        '__MSGID_OK_DIGITS' => '/\d{10}/',
        'MSGID_YAHOO_CAPS' => '/<[A-Z]+\@yahoo.com>/',
        '__AT_AOL_MSGID' => '/\@aol\.com\b/i',
        '__AT_EXCITE_MSGID' => '/\@excite\.com\b/i',
        '__AT_HOTMAIL_MSGID' => '/\@hotmail\.com\b/i',
        '__AT_MSN_MSGID' => '/\@msn\.com\b/i',
        '__AT_YAHOO_MSGID' => '/\@yahoo\.com\b/i',
        '__MSGID_BEFORE_OKAY' => '/\@[a-z0-9.-]+\.(?:yahoo|wanadoo)(?:\.[a-z]{2,3}){1,2}>/',
    ];

    // Reply to customer from user agent.
    public function testUserReplyMessageIdDoesNotMatchRegex()
    {
        $mailbox = factory(Mailbox::class)->make([
            'email' => 'test@example.org',
        ]);
        $thread = factory(Thread::class)->make([
            'id' => $this->faker->unique()->randomDigit,
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $message_id = $thread->getMessageId($mailbox);

        foreach ($this->assasin_regexes as $key => $regex) {
            if (preg_match($regex, $message_id)) {
                $this->fail('Message-ID ('.$message_id.') matched the following Spam Assasin pattern: '.$key.' => '.$regex);
            }
            if (preg_match($regex, '<'.$message_id.'>')) {
                $this->fail('Message-ID (<'.$message_id.'>) matched the following Spam Assasin pattern: '.$key.' => '.$regex);
            }
        }

        $this->assertTrue(true);
    }
}
