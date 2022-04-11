<?php

namespace Modules\TicketTranslator\Http\Controllers;

use App\Thread;
use \Dejurin\GoogleTranslateForFree;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TicketTranslatorController extends Controller
{
    /**
     * Translation modal dialog.
     */
    public function modal($thread_id)
    {
        $thread = Thread::find($thread_id);
        if (!$thread) {
            abort(404);
        }

        return view('tickettranslator::modal', [
            'thread' => $thread,
            'languages' => \Helper::$locales,
        ]);
    }

    /**
     * Ajax.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {
            case 'translate':
               
                $thread = Thread::find($request->thread_id);
                if (!$thread) {
                    $response['msg'] = __('Thread not found');
                }

                if (!$response['msg']) {
                    // Perform translation.
                    $source = $request->from;
                    $target = $request->into;
                    $text = $thread->getBodyAsText();
                    if ($thread->first) {
                        $text = '['.$thread->conversation->subject."]\n\n".$text;
                    }

                    $result = $this->translateWithGoogleFree($source, $target, $text);

                    // Save translation.
                    if ($result['translation'] && $result['translation'] != $text) {
                        $response['status'] = 'success';

                        $translations = \Helper::jsonToArray($thread->translations);
                        if (!isset($translations['i18n'])) {
                            $translations['i18n'] = [];
                        }

                        // Prepare translation.
                        $result['translation'] = preg_replace("/\n\n+/", "\n", $result['translation']);

                        $translations['i18n'][$target] = $result['translation'];
                        $translations['src_locale'] = $result['src_locale'];

                        $thread->translations = \Helper::jsonEncodeUtf8($translations);
                        $thread->save();

                        \Session::flash('flash_success_floating', __('Message Translated'));
                    } else {
                        $response['msg'] = __('Could not translate the text');
                    }
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }

    /**
     * Free Google Translate (5000 symbols).
     * https://github.com/dejurin/php-google-translate-for-free
     */
    public function translateWithGoogleFree($source, $target, $text)
    {
        $tr = new GoogleTranslateForFree();

        // Translate text by parts 5000 each.
        $parts = \Helper::strSplitKeepWords($text, 4000);

        $translations = [];
        $src_locale = '';
        foreach ($parts as $part) {
            $result = $tr->translate($source, $target, $part);
            if ($result['translation']) {
                if (!empty($result['src_locale']) && !$src_locale) {
                    $src_locale = $result['src_locale'];
                }
                $translations[] = $result['translation'];
            }
        }

        return [
            'src_locale'  => $src_locale,
            'translation' => implode(' ', $translations)
        ];
    }
}
