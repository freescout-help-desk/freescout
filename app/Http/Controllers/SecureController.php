<?php

namespace App\Http\Controllers;

use App\ActivityLog;
use App\Conversation;
use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use Illuminate\Http\Request;

class SecureController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        $mailboxes = auth()->user()->mailboxesCanView();

        return view('secure/dashboard', ['mailboxes' => $mailboxes]);
    }

    /**
     * Logs.
     *
     * @return \Illuminate\Http\Response
     */
    public function logs(Request $request)
    {
        //->where('preferences->dining->meal', 'salad')
        //->get();
        $names = ActivityLog::select('log_name')->distinct()->get()->pluck('log_name');

        $activities = [];
        $cols = [];
        $current_name = '';
        if (!empty($request->name)) {
            $activities = ActivityLog::inLog($request->name)->get();
            $current_name = $request->name;
        } elseif (count($names)) {
            $activities = ActivityLog::inLog($names[0])->get();
            $current_name = $names[0];
        }

        $logs = [];
        foreach ($activities as $activity) {
            $log = [
                'date'  => $activity->created_at,
                'user'  => $activity->causer,
                'event' => $activity->getEventDescription(),
            ];
            $cols = ['date', 'user', 'event'];

            foreach ($activity->properties as $property_name => $property_value) {
                if (!is_string($property_value)) {
                    $property_value = json_encode($property_value);
                }
                $log[$property_name] = $property_value;
                $cols[] = $property_name;
            }

            $logs[] = $log;
        }

        return view('secure/logs', ['logs' => $logs, 'names' => $names, 'current_name' => $current_name, 'cols' => $cols]);
    }

    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '',
        ];

        $user = auth()->user();

        switch ($request->action) {

            // Change conversation user
            case 'conversation_change_user':
                $conversation = Conversation::find($request->conversation_id);

                $new_user_id = (int) $request->user_id;

                if (!$conversation) {
                    $response['msg'] = 'Conversation not found';
                }
                if (!$response['msg'] && $conversation->user_id == $new_user_id) {
                    $response['msg'] = 'Assignee already set';
                }
                if (!$response['msg'] && !$user->can('update', $conversation)) {
                    $response['msg'] = 'Not enough permissions';
                }
                if (!$response['msg'] && (int) $new_user_id != -1 && !in_array($new_user_id, $conversation->mailbox->userIdsHavingAccess())) {
                    $response['msg'] = 'Incorrect user';
                }
                if (!$response['msg']) {
                    // Next conversation has to be determined before updating status for current one
                    $next_conversation = $conversation->getNearby();

                    $conversation->setUser($new_user_id);
                    $conversation->save();

                    event(new ConversationUserChanged($conversation));

                    $response['status'] = 'success';

                    // Flash
                    $flash_message = __('Assignee updated');
                    if ($new_user_id != $user->id) {
                        $flash_message .= ' <a href="'.route('conversations.view', ['id' => $conversation->id]).'">'.__('View').'</a>';

                        if ($next_conversation) {
                            $response['redirect_url'] = route('conversations.view', ['id' => $next_conversation->id]);
                        } else {
                            // Show conversations list
                            $response['redirect_url'] = route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $conversation->folder_id]);
                        }
                    }
                    \Session::flash('flash_success_floating', $flash_message);

                    $response['msg'] = __('Assignee updated');
                }
                break;

            // Change conversation status
            case 'conversation_change_status':
                $conversation = Conversation::find($request->conversation_id);

                $new_status = (int) $request->status;

                if (!$conversation) {
                    $response['msg'] = 'Conversation not found';
                }
                if (!$response['msg'] && $conversation->status == $new_status) {
                    $response['msg'] = 'Status already set';
                }
                if (!$response['msg'] && !$user->can('update', $conversation)) {
                    $response['msg'] = 'Not enough permissions';
                }
                if (!$response['msg'] && !in_array((int) $request->status, array_keys(Conversation::$statuses))) {
                    $response['msg'] = 'Incorrect status';
                }
                if (!$response['msg']) {
                    // Next conversation has to be determined before updating status for current one
                    $next_conversation = $conversation->getNearby();

                    $conversation->setStatus($new_status, $user);
                    $conversation->save();

                    event(new ConversationStatusChanged($conversation));

                    $response['status'] = 'success';

                    // Flash
                    $flash_message = __('Status updated');
                    if ($new_status != Conversation::STATUS_ACTIVE) {
                        $flash_message .= ' <a href="'.route('conversations.view', ['id' => $conversation->id]).'">'.__('View').'</a>';

                        if ($next_conversation) {
                            $response['redirect_url'] = route('conversations.view', ['id' => $next_conversation->id]);
                        } else {
                            // Show conversations list
                            $response['redirect_url'] = route('mailboxes.view.folder', ['id' => $conversation->mailbox_id, 'folder_id' => $conversation->folder_id]);
                        }
                    }
                    \Session::flash('flash_success_floating', $flash_message);

                    $response['msg'] = __('Status updated');
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
}
