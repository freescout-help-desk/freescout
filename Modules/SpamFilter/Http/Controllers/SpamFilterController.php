<?php

namespace Modules\SpamFilter\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Mailbox;
use Modules\SpamFilter\Providers\SpamFilterServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SpamFilterController extends Controller
{
    /**
     * Spam filter action on customer.
     * @return Response
     */
    public function action(Request $request, $id, $action, $conversation_id)
    {
    	$customer = Customer::find($id);
    	$conversation = Conversation::find($conversation_id);
    	$user = auth()->user();

    	if ($customer && $conversation && $user) {

	    	// Perform action.
	    	switch ($action) {
	    		case 'blacklist':
	    			\SpamFilterHelper::setCustomerSpamStatus($customer, $conversation->mailbox_id, \SpamFilterHelper::SPAM_STATUS_EMAIL_BLACKLISTED_HARD, $user->id, []);
	    			break;
	    		case 'unblacklist':
	    			\SpamFilterHelper::setCustomerSpamStatus($customer, $conversation->mailbox_id, \SpamFilterHelper::SPAM_STATUS_DEFAULT, $user->id, []);
	    			break;
	    		case 'whitelist':
	    			\SpamFilterHelper::setCustomerSpamStatus($customer, $conversation->mailbox_id, \SpamFilterHelper::SPAM_STATUS_EMAIL_WHITELISTED_HARD, $user->id, []);
	    			break;
	    		case 'unwhitelist':
	    			\SpamFilterHelper::setCustomerSpamStatus($customer, $conversation->mailbox_id, \SpamFilterHelper::SPAM_STATUS_DEFAULT, $user->id, []);
	    			break;
	    	}
	    	$customer->save();
	    }

        // Reload the page.
        $url = '';
        if ($conversation) {
        	$url = $conversation->url($conversation->folder_id);
        } else {
        	$url = $request->server('HTTP_REFERER');
        }
        return redirect()->away($url);
    }

/**
     * Ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {

            // Pause timer
            case 'reset':

                $mailbox = Mailbox::find($request->mailbox_id);
                if (!$mailbox) {
                    $response['msg'] .= __('Mailbox not found');
                }

                if (!$response['msg']) {
                    \SpamFilterHelper::setStatisticalDb('', $request->mailbox_id);
                    \Session::flash('flash_success_floating', __('Learning memory has been reset for the mailbox'));
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
