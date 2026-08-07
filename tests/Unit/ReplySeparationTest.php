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

    // Original body and body after separating the user's reply to the email notification.
    // Outlook places its reply header (<hr> + div#divRplyFwdMsg) above the quoted
    // notification, so it has to be removed from the extracted reply separately.
    // https://github.com/freescout-help-desk/freescout/issues/5545
    public $test_notification_bodies = [
        // Outlook mobile / OWA / new Outlook rewrite id and class of the notification
        // marker table to "x_fsNotifReplyAbove" but keep the data-fs attribute intact.
        'Hi
<hr style="display:inline-block;width:98%" tabindex="-1">
<div id="divRplyFwdMsg" dir="ltr"><font face="Calibri, sans-serif" style="font-size:11pt" color="#000000"><b>Von:</b> ...<br>
<b>Gesendet:</b> ...<br></font>
<div>&nbsp;</div>
</div>
<table bgcolor="#f8f9f9" width="100%" id="x_fsNotifReplyAbove" class="x_fsNotifReplyAbove" data-fs="fsNotifReplyAbove"><tbody><tr><td>Quoted notification</td></tr></tbody></table>' => '<p>Hi
</p>',
    ];

    public function testIncomingNotificationReplySeparation()
    {
        $fetch_emails = new \App\Console\Commands\FetchEmails();

        foreach ($this->test_notification_bodies as $body_original => $body_separated) {
            $separated_reply = $fetch_emails->separateReply($body_original, $is_html = true, $is_reply = true, $user_reply_to_notification = true);
            $this->assertEquals($body_separated, $separated_reply);
        }
    }
}
