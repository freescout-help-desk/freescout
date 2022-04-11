<?php

namespace Modules\Chat\Http\Controllers;

use App\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ChatController extends Controller
{
    /**
     * Settings.
     */
    public function settings($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        $widget_settings = \Chat::getWidgetSettings($mailbox_id);

        if (!empty($widget_settings)) {
            $widget_settings['id'] = \Chat::encodeMailboxId($mailbox_id);
        }

        return view('chat::admin/settings', [
            'mailbox'   => $mailbox,
            'chat_hours'   => $mailbox->meta['chat.hours'] ?? [],
            'widget_settings'   => $widget_settings,
            'locales'  => \Helper::getAllLocales(),
        ]);
    }

    /**
     * Settings save.
     */
    public function settingsSave(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);
        
        if (!empty($request->chat_action) && $request->chat_action == 'save_widget') {
            $settings = $request->widget ?? [];

            unset($settings['_token']);
            unset($settings['chat_action']);

            // if (empty($settings['title'])) {
            //     $settings['title'] = __('Contact us');
            // }

            // Remove empty.
            foreach ($settings as $i => $value) {
                if (!$value) {
                    unset($settings[$i]);
                }
            }

            if (empty($settings['color'])) {
                $settings['color'] = '#0068bd';
            }

            try {
                \Chat::saveWidgetSettings($mailbox_id, $settings);

                \Session::flash('flash_success_floating', __('Settings updated'));
            } catch (\Exception $e) {
                \Session::flash('flash_error_floating', $e->getMessage());
            }
        }

        if (!empty($request->chat_action) && $request->chat_action == 'save_chat_hours') {

            $chat_hours = [];

            foreach ($request->chat_hours as $day => $rows) {
                foreach ($rows as $i => $row) {
                    if (!empty($row['from']) && $row['from'] == 'off') {
                        $row['to'] = 'off';
                    }
                    if (!empty($row['to']) && $row['to'] == 'off') {
                        $row['from'] = 'off';
                    }

                    if (!empty($row['from']) && !empty($row['to'])) {
                        $chat_hours[$day][$i]['from'] = $row['from'];
                        $chat_hours[$day][$i]['to'] = $row['to'];
                    }
                }
            }
           
            $mailbox->setMetaParam('chat.hours', $chat_hours);
            $mailbox->save();

            \Cache::forget('chat.widget_mailbox_'.md5(\Chat::encodeMailboxId($mailbox_id)));

            \Session::flash('flash_success_floating', __('Settings updated'));
        }

        return redirect()->route('chat.settings', ['mailbox_id' => $mailbox_id]);
    }
}
