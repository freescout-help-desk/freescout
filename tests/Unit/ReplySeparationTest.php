<?php

namespace Tests\Unit;

use Tests\TestCase;

class ReplySeparationTest extends TestCase
{
    // Original body and body after separating the reply.
    public $test_bodies = [
        'Hi
<hr style="display:inline-block;width:98%" tabindex="-1">
<div id="divRplyFwdMsg" dir="ltr"><font face="Calibri, sans-serif" style="font-size:11pt" color="#000000"><b>Von:</b> ...<br>
<b>Gesendet:</b> ...<br></div>' => '<p>Hi
</p>',
    ];

    public function testIncomingMailReplySeparation()
    {
        $fetch_emails = new \App\Console\Commands\FetchEmails();
        
        foreach ($this->test_bodies as $body_original => $body_separated) {
            $separated_reply = $fetch_emails->separateReply($body_original, $is_html = true, $is_reply = true);
            $this->assertEquals($body_separated, $separated_reply);
        }
    }
}
