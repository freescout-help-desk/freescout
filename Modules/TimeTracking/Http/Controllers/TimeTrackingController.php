<?php

namespace Modules\TimeTracking\Http\Controllers;

use App\Conversation;
use Modules\TimeTracking\Entities\Timelog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TimeTrackingController extends Controller
{
    /**
     * Ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        $user = auth()->user();

        $conversation_id = $request->conversation_id;

        $conversation = Conversation::find($conversation_id);

        if (!$conversation) {
            $response['msg'] = __('Conversation not found');
        }
        if ($user->id != $conversation->user_id) {
            $response['msg'] = __('Conversation is assigned to another user');
        }

        switch ($request->action) {

            // Pause timer
            case 'pause':

                $timelog = null;
                if (!$response['msg']) {
                    $timelog = Timelog::where('conversation_id', $conversation_id)
                        ->where('finished', false)
                        ->where('user_id', $user->id)
                        ->first();
                    if (!$timelog) {
                        // Create timelog
                        // $timelog = new Timelog();
                        // $timelog->conversation_id = $conversation_id;
                        // $timelog->user_id = $user->id;
                        // $timelog->conversation_status = $conversation->status;
                        // $timelog->save();
                        $timelog = \TimeTracking::startTimer($conversation);
                    }
                }

                if (!$response['msg']) {
                    $timelog->paused = true;
                    $timelog->time_spent += $timelog->calcTimeSpent();
                    $timelog->save();

                    $response['status'] = 'success';
                }
                break;

            // Start timer
            case 'start':

                $timelog = null;
                if (!$response['msg']) {
                    $timelog = Timelog::where('conversation_id', $conversation_id)
                        ->where('finished', false)
                        ->where('user_id', $user->id)
                        ->first();
                    if (!$timelog) {
                        // Create timelog
                        $timelog = \TimeTracking::startTimer($conversation);
                    }
                }

                if (!$response['msg']) {
                    $timelog->paused = false;
                    $timelog->save();

                    $response['status'] = 'success';
                }
                break;

            // Reset timer
            case 'reset':

                $timelog = null;
                if (!$response['msg']) {
                    $timelog = Timelog::where('conversation_id', $conversation_id)
                        ->where('finished', false)
                        ->where('user_id', $user->id)
                        ->first();
                }

                if (!$response['msg']) {
                    if ($timelog) {
                        $timelog->time_spent = 0;
                        $timelog->paused = false;
                        $timelog->save();

                        $response['time_spent'] = $timelog->time_spent;
                    } else {
                        $response['time_spent'] = 0;
                    }
                    $response['status'] = 'success';
                }
                break;

            // Cancel timelog
            case 'cancel':

                $timelog = null;
                if (!$response['msg']) {
                    $timelog = Timelog::where('conversation_id', $conversation_id)
                        ->where('finished', false)
                        ->where('user_id', $user->id)
                        ->first();
                }

                if (!$response['msg']) {
                    if ($timelog) {
                        $timelog->delete();
                    }
                    $response['status'] = 'success';
                }
                break;

            // Submit time
            case 'submit_time':

                $timelog = null;
                if (!$response['msg']) {
                    $timelog = Timelog::where('conversation_id', $conversation_id)
                        ->where('finished', false)
                        ->where('user_id', $user->id)
                        ->first();
                }

                if (!$response['msg']) {
                    if ($timelog) {
                        $timelog->time_spent = abs((int)$request->hours*3600) + abs((int)$request->minutes*60) + abs((int)$request->seconds);
                        //$timelog->conversation_status = $conversation->status;
                        $timelog->finished = true;
                        $timelog->paused = false;
                        $timelog->save();
                    }
                    $response['status'] = 'success';
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
