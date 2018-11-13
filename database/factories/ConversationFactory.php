<?php

use App\Conversation;
use Faker\Generator as Faker;

$factory->define(Conversation::class, function (Faker $faker, $params) {
    if (!empty($params['created_by_user_id'])) {
        $created_by_user_id = $params['created_by_user_id'];
    } else {
        // Pick random user
        $created_by_user_id = App\User::inRandomOrder()->first()->id;
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
    $customer_email = $faker->unique()->safeEmail;
    if (!empty($params['customer_email'])) {
        $customer_email = $params['customer_email'];
    }

    return [
        'type'                => $faker->randomElement([Conversation::TYPE_EMAIL, Conversation::TYPE_PHONE]),
        'folder_id'           => $folder_id,
        'state'               => Conversation::STATE_PUBLISHED, // $faker->randomElement(array_keys(Conversation::$states)),
        'subject'             => $faker->sentence(7),
        'customer_email'      => $customer_email,
        'cc'                  => json_encode([$faker->unique()->safeEmail]),
        'bcc'                 => json_encode([$faker->unique()->safeEmail]),
        'preview'             => $faker->text(Conversation::PREVIEW_MAXLENGTH),
        'imported'            => true,
        'created_by_user_id'  => $created_by_user_id,
        'source_via'          => Conversation::PERSON_CUSTOMER,
        'source_type'         => Conversation::SOURCE_TYPE_EMAIL,
    ];
});
