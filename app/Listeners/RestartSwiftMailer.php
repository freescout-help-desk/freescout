<?php

namespace App\Listeners;

class RestartSwiftMailer
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event)
    {
        // Destroy Swift mailer to make it clean temp files.
        // https://github.com/freescout-helpdesk/freescout/issues/2949
        // https://github.com/swiftmailer/swiftmailer/issues/1287#issuecomment-833505298        
        \App::forgetInstance('mailer');
        \App::forgetInstance('swift.mailer');
        \App::forgetInstance('swift.transport');
    }
}
