<?php

use App\Conversation;
use Faker\Generator as Faker;

$factory->define(Conversation::class, function (Faker $faker, $params) {
    if (!empty($params['created_by'])) {
        $created_by = $params['created_by'];
    } else {
        // Pick random user
        $created_by = App\User::inRandomOrder()->first()->id;
    }
    $folder_id = null;
    if (!empty($params['folder_id'])) {
        $folder_id = $params['folder_id'];
    } elseif (!empty($params['mailbox_id'])) {
        // Pick folder
        $folder = App\Folder::where(['mailbox_id' => $params['mailbox_id'], 'type' => App\Folder::TYPE_UNASSIGNED])->first();
        if ($folder) {
            $folder_id = $folder->id;
        } else {
            $folder_id = factory(App\Folder::class)->create()->id;
        }
    }

    return [
        'type'      => $faker->randomElement([Conversation::TYPE_EMAIL, Conversation::TYPE_PHONE]),
        'folder_id' => $folder_id,
        'state'     => $faker->randomElement(array_keys(Conversation::$states)),
        'subject'   => $faker->sentence(7),
        // todo: cc and bcc must be equal to first (or last?) thread of conversation
        'cc'          => json_encode([$faker->unique()->safeEmail]),
        'bcc'         => json_encode([$faker->unique()->safeEmail]),
        'preview'     => $faker->text(Conversation::PREVIEW_MAXLENGTH),
        'imported'    => true,
        'created_by'  => $created_by,
        'source_via'  => Conversation::PERSON_CUSTOMER,
        'source_type' => Conversation::SOURCE_TYPE_EMAIL,
    ];
});
