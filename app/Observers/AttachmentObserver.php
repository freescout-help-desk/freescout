<?php

namespace App\Observers;

use App\Attachment;

class AttachmentObserver
{
    public function created(Attachment $attachment)
    {
        \Eventy::action('attachment.created', $attachment);
    }

    public function deleted(Attachment $attachment)
    {
        \Eventy::action('attachment.deleted', $attachment);
    }
}
