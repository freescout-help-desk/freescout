<?php

namespace Modules\CustomFields\Http\Controllers;

use App\Conversation;
use App\Mailbox;
use Modules\CustomFields\Entities\CustomField;
use Modules\CustomFields\Entities\ConversationCustomField;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomFieldsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($id)
    {
        $mailbox = Mailbox::findOrFail($id);

        $custom_fields = CustomField::getMailboxCustomFields($id);

        return view('customfields::index', [
            'mailbox'       => $mailbox,
            'custom_fields' => $custom_fields,
        ]);
    }

    /**
     * Conversations ajax controller.
     */
    public function ajaxAdmin(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {

            // Create/update saved reply
            case 'create':
            case 'update':

                // if (!$user->isAdmin()) {
                //     $response['msg'] = __('Not enough permissions');
                // }
                
                if (!$response['msg']) {
                    $name = $request->name;

                    if (!$name) {
                        $response['msg'] = __('Name is required');
                    }
                }

                if (!$response['msg']) {
                    $mailbox = Mailbox::find($request->mailbox_id);

                    if (!$mailbox) {
                        $response['msg'] = __('Mailbox not found');
                    }
                }
                
                // Check unique name.
                if (!$response['msg']) {
                    $name_exists = CustomField::where('mailbox_id', $request->mailbox_id)
                        ->where('name', $name);

                    if ($request->action == 'update') {
                        $name_exists->where('id', '!=', $request->custom_field_id);
                    }
                    $name_exists = $name_exists->first();

                    if ($name_exists) {
                        $response['msg'] = __('A Custom Field with this name already exists for this mailbox.');
                    }
                }

                if (!$response['msg']) {

                    if ($request->action == 'update') {
                        $custom_field = CustomField::find($request->custom_field_id);
                        if (!$custom_field) {
                            $response['msg'] = __('Custom Field not found');
                        }
                    } else {
                        $custom_field = new CustomField();
                        $custom_field->setSortOrderLast();
                    }

                    if (!$response['msg']) {
                        $custom_field->mailbox_id = $mailbox->id;
                        $custom_field->name = $name;
                        if ($request->action != 'update') {
                            $custom_field->type = $request->type;
                        }
                        if ($custom_field->type == CustomField::TYPE_DROPDOWN) {
                            
                            if ($request->action == 'create') {
                                $options = [];
                                $options_tmp = preg_split('/\r\n|[\r\n]/', $request->options);
                                // Remove empty
                                $option_index = 1;
                                foreach ($options_tmp as $i => $value) {
                                    $value = trim($value);
                                    if ($value) {
                                        $options[$option_index] = $value;
                                        $option_index++;
                                    }
                                }
                                if (empty($options)) {
                                    $options = [1 => ''];
                                }
                            } else {
                                $options = $request->options;
                            }

                            $custom_field->options = $options;

                            // Remove values.
                            if ($custom_field->id) {
                                ConversationCustomField::where('custom_field_id', $custom_field->id)
                                    ->whereNotIn('value', array_keys($request->options))
                                    ->delete();
                            }
                        } elseif (isset($request->options)) {
                            $custom_field->options = $request->options;
                        } else {
                            $custom_field->options = '';
                        }
                        $custom_field->required = $request->filled('required');
                        $custom_field->save();

                        $response['id']     = $custom_field->id;
                        $response['name']   = $custom_field->name;
                        $response['required']   = (int)$custom_field->required;
                        $response['status'] = 'success';

                        if ($request->action == 'update') {
                            $response['msg_success'] = __('Custom field updated');
                        } else {
                            // Flash
                            \Session::flash('flash_success_floating', __('Custom field created'));
                        }
                    }
                }
                break;

            // Delete
            case 'delete':
               
                // if (!$user->isAdmin()) {
                //     $response['msg'] = __('Not enough permissions');
                // }

                if (!$response['msg']) {
                    $custom_field = CustomField::find($request->custom_field_id);

                    if (!$custom_field) {
                        $response['msg'] = __('Custom Field not found');
                    }
                }

                if (!$response['msg']) {
                    \Eventy::action('custom_field.before_delete', $custom_field);
                    $custom_field->delete();

                    // Delete links to conversations;
                    ConversationCustomField::where('custom_field_id', $request->custom_field_id)->delete(); 

                    $response['status'] = 'success';
                    $response['msg_success'] = __('Custom Field deleted');

                    \Eventy::action('custom_field.after_delete', $request->custom_field_id);
                }
                break;

            // Update saved reply
            case 'update_sort_order':
                
                // if (!$user->isAdmin()) {
                //     $response['msg'] = __('Not enough permissions');
                // }

                if (!$response['msg']) {

                    $custom_fields = CustomField::whereIn('id', $request->custom_fields)->select('id', 'mailbox_id', 'sort_order')->get();

                    if (count($custom_fields)) {
                        foreach ($request->custom_fields as $i => $request_custom_field_id) {
                            foreach ($custom_fields as $custom_field) {
                                if ($custom_field->id != $request_custom_field_id) {
                                    continue;
                                }
                                $custom_field->sort_order = $i+1;
                                $custom_field->save();
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
     * Conversations ajax controller.
     */
    public function ajax(Request $request)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch ($request->action) {

            // Update saved reply
            case 'save_fields':
            
                // Check if user has access to the mailbox.
                // Skip.
                //$conversation = Conversation::find($request->conversation_id);

                if (!$response['msg'] && $request->conversation_id) {
                    foreach ($request->fields as $field_id => $field_value) {
                        CustomField::setValue($request->conversation_id, $field_id, $field_value);
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

    /**
     * Ajax controller.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'create':
                return view('customfields::create', [
                    'custom_field' => new CustomField,
                ]);
        }

        abort(404);
    }

    /**
     * Ajax search.
     */
    public function ajaxSearch(Request $request)
    {
        $response = [
            'results'    => [],
            'pagination' => ['more' => false],
        ];

        $query = ConversationCustomField::select('value')
            ->where('custom_field_id', $request->custom_field_id)
            ->where('value', 'like', '%'.$request->q.'%')
            ->orderBy('value')
            ->groupBy('value');

        $custom_fields = $query->paginate(20);

        foreach ($custom_fields as $row) {
            $response['results'][] = [
                'id'   => $row->value,
                'text' => $row->value,
            ];
        }

        $response['pagination']['more'] = $custom_fields->hasMorePages();

        return \Response::json($response);
    }
}
