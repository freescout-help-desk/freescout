<?php

namespace App\Http\Controllers;

use Barryvdh\TranslationManager\Controller as BaseController;

class TranslateController extends BaseController
{
    public function postSend()
    {
        $result = false;

        $this->manager->exportTranslations('*', false);

        // Archive langs folder
        $archive_path = \Helper::createZipArchive(base_path().DIRECTORY_SEPARATOR.'resources/lang', 'lang.zip', 'lang');

        if ($archive_path) {
            $attachments[] = $archive_path;

            // Send archive to developers
            $result = \MailHelper::sendEmailToDevs('Translations', '', $attachments, auth()->user());
        }

        if ($result) {
            return ['status' => 'ok'];
        } else {
            abort(500);
        }
    }
}
