<?php

namespace Modules\Gdpr\Http\Controllers;

use App\Conversation;
use App\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class GdprController extends Controller
{
    /**
     * Ajax html.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {

            case 'delete_customer':

                $conv_count = Conversation::where('customer_id', $request->param)->count();

                return view('gdpr::ajax_html/delete_customer', [
                    'conv_count' => $conv_count
                ]);
        }

        abort(404);
    }

    /**
     * Ajax controller.
     */
    public function ajaxAdmin(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {

            // Delete customer.
            case 'delete_customer':
                if (!\Gdpr::canDeleteUsers()) {
                    $response['msg'] = __('Not enough permissions');
                }
                if (!$response['msg']) {
                    $customer = Customer::find($request->customer_id);
                    if ($customer) {
                        // Create background job.
                        \Helper::backgroundAction('gdpr.delete_customer_conversations', [$request->customer_id]);
                
                        $customer->setMeta('gdpr_deleting', 1);
                        $customer->save();
                        $response['msg_success'] = __("Customer and customer's conversations are being deleted in the background. It may take a few minutes.");
                        $response['status'] = 'success';
                    } else {
                        $response['msg'] = __('Customer not found');
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
}
