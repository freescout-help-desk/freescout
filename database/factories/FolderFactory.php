<?php

use App\Folder;
use Faker\Generator as Faker;

$factory->define(App\Folder::class, function (Faker $faker, $params) {
    $mailbox_id = null;
    if (!empty($params['mailbox_id'])) {
        $mailbox_id = $params['mailbox_id'];
    } else {
        $mailbox = App\Mailbox::inRandomOrder()->first();
        if ($mailbox) {
            $mailbox_id = $mailbox->id;
        }
    }

    return [
        'mailbox_id' => $mailbox_id,
        'type'       => Folder::TYPE_UNASSIGNED,
    ];
});
