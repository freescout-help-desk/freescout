<?php

namespace Modules\Facebook\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class FacebookController extends Controller
{
    public function webhooks(Request $request, $mailbox_id, $mailbox_secret)
    {
        if (class_exists('Debugbar')) {
            \Debugbar::disable();
        }

        $mailbox = Mailbox::find($mailbox_id);

        if (!$mailbox 
            || \Facebook::getMailboxSecret($mailbox_id) != $request->mailbox_secret
        ) {
            \Facebook::log('Incorrect webhook URL: '.url()->current(), $mailbox ?? null);
            abort(404);
        }

        $botman = \Facebook::getBotman($mailbox, $request);

        if (!$botman) {
            abort(404);
        }

        $botman->hears('(.*)', function ($bot, $text) use ($mailbox) {
            \Facebook::processIncomingMessage($bot, $text, $mailbox);
        });

        $botman->receivesFiles(function($bot, $files) use ($mailbox) {

            \Facebook::processIncomingMessage($bot, __('File(s)'), $mailbox, $files);

            // foreach ($files as $file) {

            //     $url = $file->getUrl(); // The direct url
            //     $payload = $file->getPayload(); // The original payload
            // }
        });

        $botman->receivesImages(function($bot, $images) use ($mailbox) {
            \Facebook::processIncomingMessage($bot, __('Image(s)'), $mailbox, $images);

            // foreach ($images as $image) {

            //     $url = $image->getUrl(); // The direct url
            //     $title = $image->getTitle(); // The title, if available
            //     $payload = $image->getPayload(); // The original payload
            // }
        });

        $botman->receivesVideos(function($bot, $videos) use ($mailbox) {
            \Facebook::processIncomingMessage($bot, __('Video(s)'), $mailbox, $videos);
            // foreach ($videos as $video) {

            //     $url = $video->getUrl(); // The direct url
            //     $payload = $video->getPayload(); // The original payload
            // }
        });

        $botman->receivesAudio(function($bot, $audios) use ($mailbox) {
            \Facebook::processIncomingMessage($bot, __('Audio'), $mailbox, $audios);
            // foreach ($audios as $audio) {

            //     $url = $audio->getUrl(); // The direct url
            //     $payload = $audio->getPayload(); // The original payload
            // }
        });

        $botman->receivesLocation(function($bot, $location) use ($mailbox) {
            \Facebook::processIncomingMessage($bot, __('Location: '.$location->getLatitude().','.$location->getLongitude()), $mailbox);
            // $lat = $location->getLatitude();
            // $lng = $location->getLongitude();
        });

        $botman->listen();
    }

    /**
     * Settings.
     */
    public function settings($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        if (!auth()->user()->isAdmin()) {
            \Helper::denyAccess();
        }

        $settings = $mailbox->meta['facebook'] ?? [];

        return view('facebook::settings', [
            'mailbox'   => $mailbox,
            'settings'   => $settings,
        ]);
    }

    /**
     * Settings save.
     */
    public function settingsSave(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        $mailbox->setMetaParam('facebook', $request->settings);
        $mailbox->save();

        \Session::flash('flash_success_floating', __('Settings updated'));

        return redirect()->route('mailboxes.facebook.settings', ['mailbox_id' => $mailbox_id]);
    }
}
