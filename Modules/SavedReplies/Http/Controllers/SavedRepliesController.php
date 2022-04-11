<?php

namespace Modules\SavedReplies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Conversation;
use App\Mailbox;
use App\User;
use Modules\SavedReplies\Entities\SavedReply;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;

class SavedRepliesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index($id)
    {
        $mailbox = Mailbox::findOrFail($id);
        //$this->authorize('index', $mailbox);
        //$this->authorize('updateMailboxSavedReplies', SavedReply::class);
        
        $user = auth()->user();
        if (!SavedReply::userCanUpdateMailboxSavedReplies($user, $mailbox)) {
            \Helper::denyAccess();
        }

        // if ($user->isAdmin() || ($user->hasPermission(User::PERM_EDIT_SAVED_REPLIES) && $mailbox->userHasAccess($user->id))) {
        //     // OK
        // } else {
        //     \Helper::denyAccess();
        // }

        $saved_replies = SavedReply::where('mailbox_id', $mailbox->id)->orderby('sort_order')->get();

        return view('savedreplies::index', [
            'mailbox'       => $mailbox,
            'saved_replies' => $saved_replies,
            'categories' => $this->getCategories($mailbox->id),
        ]);
    }

    /**
     * Conversations ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        switch ($request->action) {

            // Create saved reply
            case 'create':
                
                $name = $request->name;
                $text = $request->text;

                if (!$name) {
                    $response['msg'] = __('Saved reply name is required');
                } /*elseif (!$text) {
                    $response['msg'] = __('Saved reply text is required');
                }*/

                $mailbox = Mailbox::find($request->mailbox_id);

                if (!$mailbox) {
                    $response['msg'] = __('Mailbox not found');
                }

                if (!$response['msg'] && !SavedReply::userCanUpdateMailboxSavedReplies($user, $mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }
                
                // Check unique name.
                if (!$response['msg']) {
                    $name_exists = SavedReply::where('mailbox_id', $request->mailbox_id)
                        ->where('name', $name);

                    $name_exists = $name_exists->first();

                    if ($name_exists) {
                        $response['msg'] = __('A Saved Reply with this name already exists for this mailbox.');
                    }
                }

                if (!$response['msg']) {

                    $saved_reply = new SavedReply();
                    $saved_reply->mailbox_id = $mailbox->id;
                    $saved_reply->name = $name;
                    $saved_reply->text = $text;
                    $saved_reply->user_id = $user->id;
                    $saved_reply->parent_saved_reply_id = $request->parent_saved_reply_id;
                    $saved_reply->save();

                    $response['id']     = $saved_reply->id;
                    $response['status'] = 'success';

                    if ((int)$request->from_reply) {
                        $response['msg_success'] = __('Created new Saved Reply');
                    } else {
                        // Flash
                        \Session::flash('flash_success_floating', __('Created new Saved Reply'));
                    }
                }
                break;

            // Update saved reply
            case 'update':
                
                $name = $request->name;
                $text = $request->text;

                if (!$name) {
                    $response['msg'] = __('Saved reply name is required');
                } /*elseif (!$text) {
                    $response['msg'] = __('Saved reply text is required');
                }*/

                $saved_reply = SavedReply::find($request->saved_reply_id);

                if (!$saved_reply) {
                    $response['msg'] = __('Saved reply not found');
                }

                if (!$response['msg'] && !SavedReply::userCanUpdateMailboxSavedReplies($user, $saved_reply->mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }
                
                // Check unique name.
                if (!$response['msg']) {
                    $name_exists = SavedReply::where('mailbox_id', $saved_reply->mailbox_id)
                        ->where('name', $name)
                        ->where('id', '!=', $saved_reply->id);

                    $name_exists = $name_exists->first();

                    if ($name_exists) {
                        $response['msg'] = __('A Saved Reply with this name already exists for this mailbox.');
                    }
                }

                if (!$response['msg']) {

                    $saved_reply->name = $name;
                    $saved_reply->text = $text;
                    if ((int)$saved_reply->parent_saved_reply_id != (int)$request->parent_saved_reply_id) {
                        $response['refresh'] = 1;
                    }
                    $saved_reply->parent_saved_reply_id = $request->parent_saved_reply_id;
                    $saved_reply->save();

                    $response['status'] = 'success';
                    $response['msg_success'] = __('Updated Saved Reply');
                }
                break;

            // Get saved reply
            case 'get':
               
                $saved_reply = SavedReply::find($request->saved_reply_id);

                if (!$saved_reply) {
                    $response['msg'] = __('Saved reply not found');
                }
                
                if (!$response['msg'] && !$saved_reply->mailbox->userHasAccess($user->id)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {

                    $replace_data = [];
                    if (!empty($request->conversation_id)) {
                        $conversation = Conversation::find($request->conversation_id);
                        if ($conversation) {
                            $replace_data = [
                                'conversation' => $conversation,
                                'mailbox'      => $conversation->mailbox,
                                'customer'     => $conversation->customer,
                                'user'         => $user,
                            ];
                        }
                    }

                    $response['name'] = $saved_reply->name;
                    $response['text'] = \MailHelper::replaceMailVars($saved_reply->text, $replace_data);

                    $response['status'] = 'success';
                }
                break;

            // Delete saved reply
            case 'delete':
               
                $saved_reply = SavedReply::find($request->saved_reply_id);

                if (!$saved_reply) {
                    $response['msg'] = __('Saved reply not found');
                }
                
                if (!$response['msg'] && !SavedReply::userCanUpdateMailboxSavedReplies($user, $saved_reply->mailbox)) {
                    $response['msg'] = __('Not enough permissions');
                }

                if (!$response['msg']) {
                    // Delete refereces to this save reply.
                    SavedReply::where('parent_saved_reply_id', $saved_reply->id)->update(['parent_saved_reply_id' => null]);
                    
                    $saved_reply->delete();

                    $response['status'] = 'success';
                    $response['msg_success'] = __('Deleted Saved Reply');
                }
                break;

            // Update saved reply
            case 'update_sort_order':
                
                $saved_replies = SavedReply::whereIn('id', $request->saved_replies)->select('id', 'mailbox_id', 'sort_order')->get();

                if (count($saved_replies)) {
                    if (!SavedReply::userCanUpdateMailboxSavedReplies($user, $saved_replies->first()->mailbox)) {
                        $response['msg'] = __('Not enough permissions');
                    }
                    if (!$response['msg']) {
                        foreach ($request->saved_replies as $i => $request_saved_reply_id) {
                            foreach ($saved_replies as $saved_reply) {
                                if ($saved_reply->id != $request_saved_reply_id) {
                                    continue;
                                }
                                $saved_reply->sort_order = $i+1;
                                $saved_reply->save();
                            }
                        }
                        $response['status'] = 'success';
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
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'create':
                $text = Input::get('text');

                return view('savedreplies::create', [
                    'text' => $text,
                    'categories' => $this->getCategories($request->param),
                ]);
        }

        abort(404);
    }

    /**
     * Returns a list of categories separated with an empty save reply
     * from regular saved replies.
     */
    public function getCategories($mailbox_id)
    {
        $categories = [];

        $saved_replies = SavedReply::where('mailbox_id', $mailbox_id)
            ->select('id', 'name', 'parent_saved_reply_id')
            ->get();

        $saved_replies = $saved_replies->sortBy('sort_order');

        // First add categories.
        $categories_ids = [];
        foreach ($saved_replies as $saved_reply) {
            if ($saved_reply->parent_saved_reply_id) {
                if (!in_array($saved_reply->parent_saved_reply_id, $categories_ids)) {
                    $categories_ids[] = $saved_reply->parent_saved_reply_id;
                }
            }
        }

        if ($categories_ids) {
            foreach ($saved_replies as $saved_reply) {
                if (in_array($saved_reply->id, $categories_ids)) {
                    $categories[] = $saved_reply;
                }
            }
            $categories[] = new SavedReply();
        }
        // Add the rest.
        foreach ($saved_replies as $saved_reply) {
            if (!in_array($saved_reply->id, $categories_ids)) {
                $categories[] = $saved_reply;
            }
        }

        return $categories;
    }
}
