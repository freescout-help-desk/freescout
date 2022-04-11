<?php

namespace Modules\Chat\Http\Controllers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ChatFrontendController extends Controller
{
    /**
     * Widget form.
     */
    public function widgetForm(Request $request, $mailbox_id = null)
    {        
        // Set locale if needed.
        if (!empty($request->locale)) {
            app()->setLocale($request->locale);
        }

        $mailbox = new Mailbox();
        $mailbox->id = $mailbox_id;

        $conversation = new Conversation();
        $conversation->body = '';
        $conversation->mailbox = $mailbox;

        // Redirect to Contact Us form if needed.
        $template = 'chat::frontend/widget_form';
        $form_action = '';

        if (\Module::isActive('enduserportal')) {
            $mailbox_key = 'chat.widget_mailbox_'.md5($mailbox_id);
            $mailbox_data = \Cache::get($mailbox_key);

            if (!$mailbox_data) {
                $decoded_mailbox = \Chat::decodeMailboxId($mailbox_id);
                $mailbox_data['id'] = $decoded_mailbox->id;
                $mailbox_data['hours'] = $decoded_mailbox->meta['chat.hours'] ?? [];

                \Cache::put($mailbox_key, $mailbox_data, now()->addHours(1));
            }

            if ($mailbox_data && !empty($mailbox_data['id']) && !empty($mailbox_data['hours'])) {
                $show_contact_form = false;

                $now = now();
                $day = $now->dayOfWeek;

                if (!empty($mailbox_data['hours'][$day]) && !empty($mailbox_data['hours'][$day][0])) {
                    if ((!empty($mailbox_data['hours'][$day][0]['from']) && $mailbox_data['hours'][$day][0]['from'] == 'off')
                        || (!empty($mailbox_data['hours'][$day][0]['to']) && $mailbox_data['hours'][$day][0]['to'] == 'off')
                    ) {
                        $show_contact_form = true;
                    } else {
                        try {
                            $from_time = Carbon::parse($now->format('Y-m-d').' '.$mailbox_data['hours'][$day][0]['from'].':00');
                            $to_time = Carbon::parse($now->format('Y-m-d').' '.$mailbox_data['hours'][$day][0]['to'].':00');

                            if ($now->greaterThanOrEqualTo($from_time) && $now->lessThanOrEqualTo($to_time)) {
                                // Working hours.
                            } else {
                                $show_contact_form = true;
                            }
                        } catch (\Exception $e) {
                            // Working hours.
                        }
                    }
                }

                if ($show_contact_form) {
                    // Show contact form instead of a chat.
                    $mailbox->id = \EndUserPortal::encodeMailboxId($mailbox_data['id'], \EndUserPortal::WIDGET_SALT);
                    $template = 'enduserportal::widget_form';
                    $form_action = route('enduserportal.widget_form', array_merge($request->all(), ['mailbox_id' => $mailbox->id]));   
                }
            }
        }

        return view($template, [
            'mailbox' => $mailbox,
            'conversation' => $conversation,
            'thread' => new Thread(),
            'form_action' => $form_action,
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

        if (!empty($request->locale)) {
            app()->setLocale($request->locale);
        }

        switch ($request->action) {
            case 'submit':

                $mailbox = \Chat::decodeMailboxId($request->mailbox_id_encoded);

                $text = trim($request->body);

                if ($mailbox && $text) {

                    $channel = \Chat::CHANNEL;

                    $conversation_id = '';
                    if ($request->conversation_id) {
                        $conversation_id = \Chat::decryptId($request->conversation_id);
                    }
                    $conversation = null;
                    if ($conversation_id) {
                        $conversation = Conversation::find($conversation_id);
                    }

                    $customer = null;
                    if (!$conversation) {
                        
                        // Create customer.
                        $customer_id = null;
                        if ($request->customer_id) {
                            $customer_id = \Chat::decryptId($request->customer_id);
                            $customer = Customer::find($customer_id);
                        }
                        if (!$customer) {
                            $first_name = __('Visitor');
                            $last_name = substr(crc32(time()), 0, 5);
                            if (!empty($request->visitor_name)) {
                                $name_data = Customer::parseName($request->visitor_name);
                                if (!empty($name_data['first_name'])) {
                                    $first_name = $name_data['first_name'];
                                    $last_name = $name_data['last_name'] ?? '';
                                }
                            }

                            $customer_data = [
                                'channel' => $channel,
                                'first_name' => $first_name,
                                'last_name' => $last_name,
                                'phone' => $request->visitor_phone ?? '',
                            ];

                            if (!empty($request->visitor_email)) {
                                $customer = Customer::create($request->visitor_email, $customer_data);
                            } else {
                                $customer = Customer::createWithoutEmail($customer_data);
                            }
                        }
                    } else {
                        $customer = $conversation->customer;
                    }

                    $attachments = $request->attachments ?? [];

                    // if (!empty()) {
                    //      foreach ($request->attachments as $file) {
                    //          $attachments[] = [
                    //             'file_name' => $file->getClientOriginalName(),
                    //             'mime_type' => $file->getMimeType(),
                    //             'data' => 
                    //          ];
                    //      }

                    //     $request->file->getClientOriginalName(),
                    //     $request->file->getMimeType(),
                    //     null,
                    //     '',
                    //     $request->file,
                    //     false,
                    //     null,
                    //     null
                    // } else {
                    //     $attachments = $request->attachments ?? [];
                    // }
                    
                    $thread = null;

                    if ($conversation) {
                      
                        // Create thread in existing conversation.
                        $thread = Thread::createExtended([
                                'type' => Thread::TYPE_CUSTOMER,
                                'customer_id' => $customer->id,
                                'body' => $text,
                                'attachments' => $attachments,
                            ],
                            $conversation,
                            $customer
                        );
                        if (!empty($thread)) {
                            $response['thread_id'] = \Chat::encryptId($thread->id);
                        }
                    } else {
                     
                        // Create conversation.
                        $conversation_result = Conversation::create([
                                'type' => Conversation::TYPE_CHAT,
                                'subject' => Conversation::subjectFromText($text),
                                'mailbox_id' => $mailbox->id,
                                'source_type' => Conversation::SOURCE_TYPE_WEB,
                                'channel' => $channel,
                            ], [[
                                'type' => Thread::TYPE_CUSTOMER,
                                'customer_id' => $customer->id,
                                'body' => $text,
                                'attachments' => $attachments,
                            ]],
                            $customer
                        );
                        if ($conversation_result) {
                            if (!empty($conversation_result['conversation'])) {
                                $response['conversation_id'] = \Chat::encryptId($conversation_result['conversation']->id);
                            }
                            if (!empty($conversation_result['thread'])) {
                                $thread = $conversation_result['thread'];
                                $response['thread_id'] = \Chat::encryptId($conversation_result['thread']->id);
                            }
                        }
                    }

                    if ($attachments && $thread) {

                        foreach ($thread->attachments as $attachment) {
                            $response['attachments'] = $response['attachments'] ?? [];
                            $response['attachments'][] = [
                                'name' => $attachment->file_name,
                                'url' => $attachment->url(),
                                'size' => $attachment->getSizeName()
                            ];
                        }
                    }
                    
                    $response['customer_id'] = \Chat::encryptId($customer->id);

                    $response['status'] = 'success';
                }
                break;

            case 'save_info':

                $customer_id = \Chat::decryptId($request->customer_id);
                $customer = null;

                if ($customer_id) {
                    $customer = Customer::find($customer_id);
                }
                    
                if ($customer) {
                    if ($customer->first_name == __('Visitor') && $request->name) {
                        $name = trim($request->name);
                        $name_data = Customer::parseName($name);
                        if (!empty($name_data['first_name'])) {
                            $customer->first_name = $name_data['first_name'];
                        }
                        if (!empty($name_data['last_name']) || $name_data['first_name']) {
                            $customer->last_name = $name_data['last_name'] ?? '';
                        }
                    }
                    if (!$customer->getPhones() && $request->phone) {
                        $customer->addPhone(trim($request->phone));
                    }
                    if (!$customer->getMainEmail() && $request->email) {
                        $customer->addEmail($request->email, true);
                    }
                    $customer->save();

                    $response['status'] = 'success';
                }
                break;

            case 'poll':
                $thread_id = \Chat::decryptId($request->thread_id);

                $conversation_id = \Chat::decryptId($request->conversation_id);

                $threads = \Cache::get('chat.threads_'.$thread_id, null);

                if ($threads === null) {

                    $threads = [];
                    $threads_collection = collect([]);

                    $threads_collection = Thread::where('conversation_id', $conversation_id)
                        //->where('type', Thread::TYPE_MESSAGE)
                        ->get();

                    $threads_collection = $threads_collection->sortBy('created_at');

                    $include_threads = false;

                    foreach ($threads_collection as $thread) {
                        if ((int)$thread->id == (int)$thread_id) {
                            $include_threads = true;
                            continue;
                        }
                        if ($thread->type != Thread::TYPE_MESSAGE) {
                            continue;
                        }
                        if ($thread->state != Thread::STATE_PUBLISHED) {
                            continue;
                        }
                        if (!$include_threads) {
                            continue;
                        }
                        $new_thread = [
                            'id' => \Chat::encryptId($thread->id),
                            'body' => $thread->body,
                            'user_name' => $thread->created_by_user_cached->getFullName(),
                            'user_photo' => $thread->created_by_user_cached->getPhotoUrl(false),
                        ];
                        if ($thread->has_attachments) {
                            foreach ($thread->attachments as $attachment) {
                                $new_thread['attachments'][] = [
                                    'name' => $attachment->file_name,
                                    'url' => $attachment->url(),
                                    'size' => $attachment->getSizeName()
                                ];
                            }
                        }
                        $threads[] = $new_thread;
                    }
                    \Cache::put('chat.threads_'.$thread_id, $threads, now()->addDays(1));
                }

                if (!is_array($threads)) {
                    $threads = [];
                }

                $response['threads'] = $threads;
                $response['status'] = 'success';
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
}
