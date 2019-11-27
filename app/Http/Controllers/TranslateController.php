<?php

namespace App\Http\Controllers;

use Barryvdh\TranslationManager\Controller as BaseController;

class TranslateController extends BaseController
{
    /**
     * Send translations to FreeScout team.
     *
     * @return [type] [description]
     */
    public function postSend()
    {
        $result = false;

        $this->manager->exportTranslations('*', false);

        // Archive langs folder
        try {
            $archive_path = \Helper::createZipArchive(base_path().DIRECTORY_SEPARATOR.'resources/lang', 'lang.zip', 'lang');
        } catch (\Exception $e) {
            return [
                'status'  => 'error',
                'error_msg' => $e->getMessage(),
            ];
        }

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

    /**
     * Remove all translations which has not been published yet.
     *
     * @return [type] [description]
     */
    public function postRemoveUnpublished()
    {
        \Barryvdh\TranslationManager\Models\Translation::truncate();

        return ['status' => 'ok'];
    }

    /**
     * Download as ZIP.
     *
     * @return [type] [description]
     */
    public function postDownload()
    {
        $this->manager->exportTranslations('*', false);
        $file_name = 'lang.zip';
        // Archive langs folder
        $archive_path = \Helper::createZipArchive(base_path().DIRECTORY_SEPARATOR.'resources/lang', $file_name, 'lang');
        $public_path = storage_path('app/public/'.$file_name);

        \File::copy($archive_path, $public_path);

        $headers = [
            'Content-Type: application/zip',
        ];

        return \Response::download($public_path, $file_name, $headers);
    }
}
