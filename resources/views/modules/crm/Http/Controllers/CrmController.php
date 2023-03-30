<?php

namespace Modules\Crm\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Email;
use Modules\Crm\Entities\CustomerField;
use Modules\Crm\Entities\CustomerCustomerField;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class CrmController extends Controller
{
    public function createCustomer(Request $request)
    {
        $customer = new Customer();

        return view('crm::create_customer', [
            'customer' => $customer,
            'emails' => ['']
        ]);
    }

    public function createCustomerSave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255|required_without:emails.0',
            'last_name'  => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:255',
            'state'      => 'nullable|string|max:255',
            'zip'        => 'nullable|string|max:12',
            'country'    => 'nullable|string|max:2',
            //'emails'     => 'array|required_without:first_name',
            //'emails.1'   => 'nullable|email|required_without:first_name',
            'emails.*'   => 'nullable|email|distinct|required_without:first_name',
        ]);
        $validator->setAttributeNames([
            //'emails.1'   => __('Email'),
            'emails.*'   => __('Email'),
        ]);

		// Check email uniqueness.
		$fail = false;
		foreach ($request->emails as $i => $email) {
            $sanitized_email = Email::sanitizeEmail($email);
            if ($sanitized_email) {
    			$email_exists = Email::where('email', $sanitized_email)->first();

    			if ($email_exists) {
    				$validator->getMessageBag()->add('emails.'.$i, __('A customer with this email already exists.'));
    				$fail = true;
    			}
            }
		}

		if ($fail || $validator->fails()) {
			return redirect()->route('crm.create_customer')
	                    ->withErrors($validator)
	                    ->withInput();
	    }

		$customer = new Customer();

        $customer->setData($request->all());
        $customer->save();
        $customer->syncEmails($request->emails);

        \Session::flash('flash_success_unescaped', __('Customer saved successfully.'));
        
        \Session::flash('customer.updated', 1);

		// Create customer.
		if ($customer->id) {
			return redirect()->route('customers.update', ['id' => $customer->id]);
		} else {
			// Something went wrong.
			return $this->createCustomer($request);
		}
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

            // Delete customer.
            case 'delete_customer':
                $has_conversations = Conversation::where('customer_id', $request->customer_id)->first();

                if ($has_conversations) {
                    $response['msg'] = __("This customer has conversations. In order to delete the customer you need to completely delete all customer's conversations first.");
                }
                if (!$response['msg']) {
                    $customer = Customer::find($request->customer_id);
                    if ($customer) {
                        $customer->deleteCustomer();
                        $response['msg_success'] = __('Customer deleted');
                        $response['status'] = 'success';
                    } else {
                        $response['msg'] = __('Customer not found');
                    }
                }
                break;

            case 'delete_without_conv':
                // Delete customers by bunches.
                do {
                    $customers = $this->getCustomersWithoutConvQuery()
                        ->limit(100)
                        ->get();
                
                    foreach ($customers as $customer) {
                        $customer->deleteCustomer();
                    }
                } while(count($customers));

                $response['msg_success'] = __('Customers deleted');
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

            // Create/update saved reply
            case 'customer_field_create':
            case 'customer_field_update':

                // if (!$user->isAdmin()) {
                //     $response['msg'] = __('Not enough permissions');
                // }
                
                if (!$response['msg']) {
                    $name = $request->name;

                    if (!$name) {
                        $response['msg'] = __('Name is required');
                    }
                }
                
                // Check unique name.
                if (!$response['msg']) {
                    $name_exists = CustomerField::where('name', $name);

                    if ($request->action == 'customer_field_update') {
                        $name_exists->where('id', '!=', $request->customer_field_id);
                    }
                    $name_exists = $name_exists->first();

                    if ($name_exists) {
                        $response['msg'] = __('A Customer Field with this name already exists.');
                    }
                }

                if (!$response['msg']) {

                    if ($request->action == 'customer_field_update') {
                        $customer_field = CustomerField::find($request->customer_field_id);
                        if (!$customer_field) {
                            $response['msg'] = __('Customer Field not found');
                        }
                    } else {
                        $customer_field = new CustomerField();
                        $customer_field->setSortOrderLast();
                    }

                    if (!$response['msg']) {
                        //$customer_field->mailbox_id = $mailbox->id;
                        $customer_field->name = $name;
                        if ($request->action != 'customer_field_update') {
                            $customer_field->type = $request->type;
                        }
                        if ($customer_field->type == CustomerField::TYPE_DROPDOWN) {
                            
                            if ($request->action == 'customer_field_create') {
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

                            $customer_field->options = $options;

                            // Remove values.
                            if ($customer_field->id) {
                                CustomerCustomerField::where('customer_field_id', $customer_field->id)
                                    ->whereNotIn('value', array_keys($request->options))
                                    ->delete();
                            }
                        } elseif (isset($request->options)) {
                            $customer_field->options = $request->options;
                        } else {
                            $customer_field->options = '';
                        }
                        $customer_field->required = $request->filled('required');
                        $customer_field->display = $request->filled('display');
                        $customer_field->conv_list = $request->filled('conv_list');
                        $customer_field->customer_can_view = $request->filled('customer_can_view');
                        $customer_field->customer_can_edit = $request->filled('customer_can_edit');
                        $customer_field->save();

                        $response['id']     = $customer_field->id;
                        $response['name']   = $customer_field->name;
                        $response['required']   = (int)$customer_field->required;
                        $response['display']   = (int)$customer_field->display;
                        $response['conv_list']   = (int)$customer_field->conv_list;
                        $response['customer_can_view']   = (int)$customer_field->customer_can_view;
                        $response['customer_can_edit']   = (int)$customer_field->customer_can_edit;
                        $response['status'] = 'success';

                        if ($request->action == 'customer_field_update') {
                            $response['msg_success'] = __('Customer field updated');
                        } else {
                            // Flash
                            \Session::flash('flash_success_floating', __('Customer field created'));
                        }
                    }
                }
                break;

            // Delete
            case 'customer_field_delete':
               
                // if (!$user->isAdmin()) {
                //     $response['msg'] = __('Not enough permissions');
                // }

                if (!$response['msg']) {
                    $customer_field = CustomerField::find($request->customer_field_id);

                    if (!$customer_field) {
                        $response['msg'] = __('Customer Field not found');
                    }
                }

                if (!$response['msg']) {
                    \Eventy::action('customer_field.before_delete', $customer_field);
                    $customer_field->delete();

                    // Delete links to customers;
                    CustomerCustomerField::where('customer_field_id', $request->customer_field_id)->delete(); 

                    $response['status'] = 'success';
                    $response['msg_success'] = __('Customer Field deleted');

                    \Eventy::action('customer_field.after_delete', $request->customer_field_id);
                }
                break;

            // Update saved reply
            case 'customer_field_update_sort_order':
                
                // if (!$user->isAdmin()) {
                //     $response['msg'] = __('Not enough permissions');
                // }

                if (!$response['msg']) {

                    $customer_fields = CustomerField::whereIn('id', $request->customer_fields)->select('id', 'sort_order')->get();

                    if (count($customer_fields)) {
                        foreach ($request->customer_fields as $i => $request_customer_field_id) {
                            foreach ($customer_fields as $customer_field) {
                                if ($customer_field->id != $request_customer_field_id) {
                                    continue;
                                }
                                $customer_field->sort_order = $i+1;
                                $customer_field->save();
                            }
                        }
                        $response['status'] = 'success';
                    }
                }
                break;

            // Parse CSV before importing.
            case 'import_parse':
                if (!$request->hasFile('file') || !$request->file('file')->isValid() || !$request->file) {
                    $response['msg'] = __('Error occurred uploading file');
                }

                if (!$response['msg']) {
                    try {
                        $csv = $this->readCsv($request->file('file')->getPathName(), $request->separator, $request->enclosure, $request->encoding);
                    } catch (\Exception $e) {
                        $response['msg'] = __('Error occurred').': '.$e->getMessage();
                    }

                    if (!$response['msg'] && $csv && is_array($csv)) {
                        $response['cols'] = [];
                        foreach ($csv as $r => $row) {
                            if ($request->skip_header && $r == 0) {
                                continue;
                            }
                            foreach ($row as $c => $value) {
                                if (!empty($response['cols'][$c])
                                    && $response['cols'][$c] != __('Column :number', ['number' => $c+1])
                                ) {
                                    continue;
                                }
                                if ($request->skip_header) {
                                    if ($r == 0) {
                                        if (isset($csv[1][$c]) && $csv[1][$c] != '') {
                                            $response['cols'][$c] = $value . ' ('.$csv[1][$c].')';
                                        } elseif ($value != '') {
                                            $response['cols'][$c] = $value;
                                        } else {
                                            $response['cols'][$c] = __('Column :number', ['number' => $c+1]);
                                        }
                                    } elseif (isset($csv[0][$c]) && $value) {
                                        $response['cols'][$c] = $csv[0][$c] . ' ('.$value.')';
                                    } elseif ($value != '') {
                                        $response['cols'][$c] = $value;
                                    } else {
                                        $response['cols'][$c] = __('Column :number', ['number' => $c+1]);
                                    }
                                } elseif ($value != '') {
                                    $response['cols'][$c] = $value;
                                } else {
                                    $response['cols'][$c] = __('Column :number', ['number' => $c+1]);
                                }
                            }
                        }
                    }
                    if (!$response['msg']) {
                        if (!empty($response['cols'])) {
                            $response['status'] = 'success';
                        } else {
                            $response['msg'] = __('Could not parse CSV file.');
                        }
                    }
                }
                break;

            // Import.
            case 'import_import':
                if (!$request->hasFile('file') || !$request->file('file')->isValid() || !$request->file) {
                    $response['msg'] = __('Error occurred uploading file');
                }

                if (!$response['msg']) {
                    try {
                        $csv = $this->readCsv($request->file('file')->getPathName(), $request->separator, $request->enclosure, $request->encoding);
                        $imported = 0;
                        $errors = [];
                        $email_conflicts = [];
                        if ($csv && is_array($csv)) {
                            foreach ($csv as $r => $row) {
                                if ($request->skip_header && $r == 0) {
                                    continue;
                                }
                                $data = $this->importParseRow($row, json_decode($request->mapping, true));

                                try {
                                    if (!empty($data['emails'])) {
                                        // Try to find customers with emails.
                                        // If found one - update.
                                        // If found more than one customer - it's a conflict.
                                        $customers_count = 0;
                                        $customer_email = '';
                                        $customer_customer = null;
                                        foreach ($data['emails'] as $email) {
                                            $customer = Customer::getByEmail($email);
                                            if ($customer) {
                                                $customer_email = $email;
                                                $customer_customer = $customer;
                                                $customers_count++;
                                            }
                                        }
                                        if ($customers_count > 1) {
                                            $email_conflicts[] = (int)($r+1);
                                        } elseif ($customers_count == 1 && $customer_customer) {
                                            // Update existing customer.
                                            $imported++;
                                            $customer_customer->setData($this->prepareEmails($data), false, true);
                                        } else {
                                            $customer_customer = Customer::create($data['emails'][0], $this->prepareEmails($data));
                                            if ($customer_customer) {
                                                $imported++;
                                            }
                                        }
                                    } else {
                                        // Create without email.
                                        if (!empty($data['first_name'])) {
                                            $customer_customer = Customer::createWithoutEmail($this->prepareEmails($data));
                                            if ($customer_customer) {
                                                $imported++;
                                            }
                                        } else {
                                            $errors[] = '#'.($r+1);
                                        }
                                    }
                                    // Set photo.
                                    if (!empty($data['photo_url']) && $customer_customer) {
                                        try {
                                            $customer_customer->setPhotoFromRemoteFile($data['photo_url']);
                                            $customer_customer->save();
                                        } catch (\Exception $e) {

                                        }
                                    }
                                } catch (\Exception $e) {
                                    $errors[] = '#'.($r+1);
                                }
                            }
                        }
                        // if ($imported) {
                        //     $flash_type = 'flash_success_floating';
                        // } else {
                        //     $flash_type = 'flash_error_floating';
                        // }

                        $response['result_html'] = __('Imported or updated: :imported customers.', ['imported' => $imported]);
                        if (count($errors)) {
                            $response['result_html'] .= '<br/>'.__('Could not import the following CSV rows as they contain not enough data: :errors.', ['errors' => implode(', ', $errors)]);
                        }
                        if (count($email_conflicts)) {
                            $response['result_html'] .= '<br/>'.__('Could not import the following CSV rows as emails specified in those rows belong to different existing customers: :email_conflicts.', ['email_conflicts' => implode(', ', $email_conflicts)]);
                        }
                        // \Session::flash($flash_type, __(':imported customer(s) imported, :errors error(s) occurred', ['imported' => $imported, 'errors' => $errors]));
                        // \Session::reflash();
                    } catch (\Exception $e) {
                        $response['msg'] = __('Error occurred').': '.$e->getMessage();
                    }

                    if (!$response['msg'] && $csv) {
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

    public function prepareEmails($data)
    {
        if (!empty($data['emails']) && is_array($data['emails'])) {
            $emails = [];
            foreach ($data['emails'] as $i => $email) {
                $emails[] = [
                    'value' => $email,
                    'type' => Email::TYPE_WORK,
                ];
            }
            $data['emails'] = $emails;
        }

        return $data;
    }

    public function importParseRow($row, $mapping)
    {
        $data = [];
        foreach ($mapping as $field_name => $field_row_i) {
            if (!isset($row[$field_row_i])) {
                continue;
            }
            $data[$field_name] = $row[$field_row_i];
            $data_value = $data[$field_name];

            switch ($field_name) {
                case 'emails':
                case 'phones':
                case 'websites':
                case 'social_profiles':
                    $data[$field_name] = explode(',', $data[$field_name]);
                    break;
            }

            if ($field_name == 'social_profiles' && is_array($data[$field_name])) {
                // Social profiles.
                foreach ($data[$field_name] as $i => $value) {
                    preg_match("/^([^:]+):(.*)/", $value, $m);
                    if (!empty($m[1]) && !empty($m[2])) {
                        $social_name = $m[1];
                        if (array_search($social_name, Customer::$social_type_names)) {
                            $data[$field_name][$i] = [
                                'value' => $m[2],
                                'type' => array_search($social_name, Customer::$social_type_names),
                            ];
                        }
                    }
                }
            } elseif ($field_name == 'country') {
                // Country.
                if (array_search($data[$field_name], Customer::$countries)) {
                    $data[$field_name] = array_search($data[$field_name], Customer::$countries);
                }
                $data[$field_name] = strtoupper(mb_substr($data[$field_name], 0, 2));
            } elseif (\Str::startsWith($field_name, CustomerField::NAME_PREFIX)) {
                // Custom field.
                $value = $data[$field_name];

                $field_id = preg_replace("/^".CustomerField::NAME_PREFIX."/", '', $field_name);
                $field = CustomerField::find($field_id);

                if ($field) {
                    $data[$field_name] = CustomerField::sanitizeValue($value, $field);
                }
            }
        }

        return $data;
    }

    public function readCsv($file_path, $separator, $enclosure, $encoding)
    {
        $csv = [];

        if ($separator == 'TAB') {
            $separator = "\t";
        }
        $enclosure = stripslashes($enclosure);

        $text = file_get_contents($file_path);

        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);

        $le = pack('H*','FFFE');
        $text = preg_replace("/^$le/", '', $text);

        // Convert to UTF-8.
        try {
            $text = iconv($encoding, "UTF-8//IGNORE", $text);
        } catch (\Exception $e) {

        }

        // This way there is no way to convert encoding and quotes are preserved.
        // $file = fopen($file_path, 'r');
        // // Progress file pointer and get first 3 characters to compare to the BOM string.
        // $bom = "\xef\xbb\xbf";
        // if (fgets($file, 4) !== $bom) {
        //     // BOM not found - rewind pointer to start of file.
        //     rewind($file);
        //     if (fgets($file, 3) !== "\xFF\xFE") {
        //         rewind($file);
        //     }
        // }

        // while (($line = fgetcsv($file, 0, $separator, $enclosure)) !== FALSE) {
        //     // $line is an array of the csv elements.
        //     $csv[] = $line;
        // }
        // fclose($file);

        $lines = explode(PHP_EOL, $text);

        foreach ($lines as $line) {
            $csv[] = str_getcsv($line, $separator, $enclosure);
        }

        // Convert to UTF
        // if ($csv) {
        //     array_walk_recursive($csv, function(&$value, $key) use ($encoding) {
        //         if (is_string($value)) {
        //             //$encoding = 'UTF-16LE';
        //             $encoding_detected = mb_detect_encoding($value, mb_detect_order(), false);
        //             // if ($encoding == "UTF-8") {
        //             //     $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');    
        //             // }

        //             try {
        //             //$value = iconv($encoding_detected, "UTF-8//IGNORE", $value);
        //                 $value = iconv($encoding, "UTF-8//IGNORE", $value);
        //             //     $value = iconv($encoding, "UTF-8", $value);
        //             } catch (\Exception $e) {
        //                 //$value = mb_convert_encoding($value, "UTF-8", $encoding);
        //             }

        //             //$value = iconv($encoding, "UTF-8", $value);
        //             //$value = mb_convert_encoding($value, "UTF-8", $encoding);
        //         }
        //     });
        // }

        return $csv;
    }

    /**
     * Ajax html.
     */
    public function ajaxHtml(Request $request)
    {
        switch ($request->action) {
            case 'export':
                return view('crm::ajax_html/export', [
                    // 'customers_log' => $customers_log,
                    // 'users_log'     => $users_log,
                ]);

            case 'import':
                return view('crm::ajax_html/import');

            case 'create_customer_field':
                return view('crm::ajax_html/customer_field_create', [
                    'customer_field' => new \CustomerField,
                ]);

            case 'delete_customer':

                $has_conversations = Conversation::where('customer_id', $request->param)->first();

                if ($has_conversations) {
                    return view('crm::ajax_html/delete_customer_has_conv', [
                        
                    ]);
                } else {
                    return view('crm::ajax_html/delete_customer', [
                        
                    ]);
                }

            case 'delete_without_conv':
                $count = $this->getCustomersWithoutConvQuery()->count();
                return view('crm::ajax_html/delete_customers_without_conv', ['count' => $count]);
        }

        abort(404);
    }

    public function getCustomersWithoutConvQuery()
    {
        return Customer::select('customers.*')
            ->leftJoin('conversations', function ($join) {
                $join->on('conversations.customer_id', '=', 'customers.id');
            })
            ->leftJoin('threads', function ($join) {
                $join->on('threads.created_by_customer_id', '=', 'customers.id');
            })
            ->where('conversations.customer_id', null)
            ->where('threads.created_by_customer_id', null);
    }

    /**
     * Export.
     */
    public function export(Request $request)
    {
        $fields = $request->fields ?? [];
        $export_emails = false;

        $exportable_fields = \Crm::getExportableFields();

        $fields_regular = [];

        foreach ($fields as $i => $field_name) {
            if (array_key_exists($field_name, $exportable_fields)) {
                if (!preg_match("/^".CustomerField::NAME_PREFIX."/", $field_name)) {
                    $fields_regular[] = $field_name;
                }
            } else {
                unset($fields[$i]);
            }
            if ($field_name == 'emails') {
                $export_emails = true;
                if (\Helper::isMySql()) {
                    $fields_regular[count($fields_regular)-1] = \DB::raw("GROUP_CONCAT(emails.email SEPARATOR ', ') as emails");
                } else {
                    $fields_regular[count($fields_regular)-1] = \DB::raw("string_agg(emails.email, ', ') as emails");
                }
            }
        }

        $results = [];

        if (count($fields_regular)) {

            if (!in_array('customers.id', $fields_regular)) {
                $fields_regular[] = 'customers.id';
            }

            if ($export_emails) {
                $query = Customer::select($fields_regular)
                        ->leftJoin('emails', function ($join) {
                            $join->on('emails.customer_id', '=', 'customers.id');
                        })
                        ->groupby('customers.id');
            } else {
                $query = Customer::select($fields_regular);
            }

            $results = $query->get()->toArray();
        }

        // Add customer fields.
        $fields_cf = [];
        foreach ($fields as $field_name) {
            if (preg_match("/^".CustomerField::NAME_PREFIX."/", $field_name)) {
                $fields_cf[] = str_replace(CustomerField::NAME_PREFIX, '', $field_name);
            }
        }
        if (count($fields_cf)) {
            $customer_fields = CustomerCustomerField::whereIn('customer_field_id', $fields_cf)
                                ->get();

            foreach ($results as $i => $row) {
                foreach ($fields_cf as $cf_id) {
                    $results[$i][CustomerField::NAME_PREFIX.$cf_id] = '';
                    foreach ($customer_fields as $cf_row) {
                        if ($cf_row->customer_id == $row['id'] && $cf_row->customer_field_id == $cf_id) {
                            $results[$i][CustomerField::NAME_PREFIX.$cf_id] = $cf_row->value;
                            break;
                        }
                    }
                }
            }
        }

        foreach ($results as $i => $row) {
            if (!in_array('customers.id', $fields) && isset($row['id'])) {
                unset($results[$i]['id']);
            }
            if (!empty($row['photo_url'])) {
                $results[$i]['photo_url'] = Customer::getPhotoUrlByFileName($row['photo_url']);
            }
            if (!empty($row['phones'])) {
                $phones = json_decode($row['phones'], true);
                $row['phones'] = '';
                $phones_list = [];
                if (is_array($phones) && !empty($phones)) {
                    foreach ($phones as $phone) {
                        $phones_list[] = $phone['value'];
                    }
                    $results[$i]['phones'] = implode(', ', $phones_list);
                }
            }
            if (!empty($row['websites'])) {
                $websites = json_decode($row['websites'], true);
                $results[$i]['websites'] = '';

                if (is_array($websites) && !empty($websites)) {
                    $results[$i]['websites'] = implode(', ', $websites);
                }
            }
            if (!empty($row['social_profiles'])) {
                $social_profiles = json_decode($row['social_profiles'], true);
                $row['social_profiles'] = '';
                $social_profiles_list = [];
                if (is_array($social_profiles) && !empty($social_profiles)) {
                    foreach ($social_profiles as $social_profile) {
                        $sp_formatted = Customer::formatSocialProfile($social_profile);
                        $social_profiles_list[] = $sp_formatted['type_name'].':'.$social_profile['value'];
                    }
                    $results[$i]['social_profiles'] = implode(', ', $social_profiles_list);
                }
            }
        }

        $filename = 'customers_'.date('Y-m-d').'.csv';
        
        $encoding = $request->encoding;
        $separator = $request->separator;

        if ($separator == 'TAB') {
            $separator = "\t";
        }
        
        // Rename some fields.
        foreach ($fields as $i => $field_name) {
            // if (strstr($field_name, 'as emails')) {
            //     $field_name = 'emails';
            // }

            if (!empty($exportable_fields[$field_name])) {
                $fields[$i] = $exportable_fields[$field_name];
            }
        }

        $schema_insert = '"'.implode('"'.$separator.'"', $fields).'"';
        $out = $schema_insert."\n";

        foreach($results as $row) {
            $schema_insert = '';

            foreach ($row as $row_value) {
                $value_prepared = str_replace('"', '""', $row_value ?? '');
                $value_prepared = str_replace("\t", '', $value_prepared);
                $schema_insert .= '"'.$value_prepared.'"'.$separator;
            }

            $out .= $schema_insert."\n";
        }

        if (ob_get_contents()) {
            ob_clean();
        }

        if ($encoding != 'UTF-8') {
            $out = iconv("UTF-8", $encoding, $out);
            if ($encoding == 'UCS-2LE') {
                $out = "\xFF\xFE".$out;
            }
        }

        header("Cache-Control: must-revalidate, no-cache, no-store, private");
        header("Content-Length: " . strlen($out));
        header("Content-type: application/csv; charset=UCS-2LE");
        header("Content-Disposition: attachment; filename=$filename");
        echo $out;
        exit;
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

        $query = CustomerCustomerField::select('value')
            ->where('customer_field_id', CustomerField::decodeName($request->custom_field_id))
            ->where('value', 'like', '%'.$request->q.'%')
            ->orderBy('value')
            ->groupBy('value');

        $customer_fields = $query->paginate(20);

        foreach ($customer_fields as $row) {
            $response['results'][] = [
                'id'   => $row->value,
                'text' => $row->value,
            ];
        }

        $response['pagination']['more'] = $customer_fields->hasMorePages();

        return \Response::json($response);
    }
}
