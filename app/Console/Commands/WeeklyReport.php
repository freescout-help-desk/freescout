<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Conversation;
use App\Mailbox;
use App\Misc\Mail;
use App\User;
use Carbon\Carbon;

class WeeklyReport extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to release weekly report though email';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    /**
     * Class constants from export conversation service provider
     * 
     */
    const CUSTOM_FIELD_PREFIX = 'ccf_';
    const MODULE_PREFIX = 'module_';
    const BUNCH_SIZE = 1000;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $fields = [
            'id',
            'number',
            'type',
            'user_id',
            'status',
            'state',
            'mailbox_id',
            'customer_name',
            'customer_email',
            'threads_count',
            'subject',
            'cc',
            'bcc',
            'has_attachments',
            'channel',
            'created_at',
            'last_reply_at',
            'last_reply_from',
            'closed_at',
            'closed_by_user_id',
            'module_ccf_1',
        ];
        $downloadType = 'pdf';
        $filters['type'] = '';
        $filters['mailbox'] = '';
        $filters['after'] = Carbon::now()->subDays(7)->format('Y-m-d');
        $filters['before'] = Carbon::now()->format('Y-m-d');
        $filters['state'] = [2];
        $filters['status'] = [1,2,3];
        
        // if (!empty($request->f['mailbox'])) {
            //     $mailbox_ids[] = $request->f['mailbox'];
            // }
        // Get all mailbox ids
        $mailbox_id_data = Mailbox::select('id')->get();
        $mailbox_ids = [];
        foreach ($mailbox_id_data as $key => $mb_id) {
            $mailbox_ids[$key] = $mb_id->id;
        }
        
        $exportable_fields = $this->getExportableFields($mailbox_ids);
        $users = [];
        $mailboxes = [];
        // Fields without custom fields.
        $fields_regular = [];

        $ccf_active = \Module::isActive('customfields');
        $crm_active = \Module::isActive('crm');

        foreach ($fields as $i => $field_name) {
            if (array_key_exists($field_name, $exportable_fields)) {
                if (!preg_match("/^".\ExportConversations::CUSTOM_FIELD_PREFIX."/", $field_name)
                    && !preg_match("/^".\ExportConversations::MODULE_PREFIX.'/', $field_name)
                ) {
                    $fields_regular[] = 'conversations.'.$field_name;
                }
            } else {
                unset($fields[$i]);
            }
        }

        // Add custom fields.
        // $fields_ccf = [];
        // foreach ($fields as $field_name) {
        //     if (preg_match("/^".\ExportConversations::CUSTOM_FIELD_PREFIX."/", $field_name)) {
        //         $fields_ccf[] = str_replace(\ExportConversations::CUSTOM_FIELD_PREFIX, '', $field_name);
        //     }
        // }

        $results = [];
        $select = $fields_regular;

        $query = Conversation::query();

        // Join customers.
        if (in_array('conversations.customer_name', $fields_regular)) {
            $select[] = 'customers.first_name as customer_first_name';
            $select[] = 'customers.last_name as customer_last_name';

            $query->leftJoin('customers', function ($join) {
                $join->on('customers.id', '=', 'conversations.customer_id');
            });
            foreach ($select as $i => $field) {
                if ($field == 'conversations.customer_name') {
                    $select[$i] = 'conversations.customer_id as customer_name';
                    break;
                }
            }
        }

        // Filter.
        $query = Conversation::search('', $filters, null, $query, $mailbox_ids);

        

        // Join tags.
        if (in_array(\ExportConversations::MODULE_PREFIX.'tags', $fields)
            && \Module::isActive('tags')
        ) {

            if (!strstr($query->toSql(), 'conversation_tag')) {
                $query->leftJoin('conversation_tag', function ($join) {
                        $join->on('conversations.id', '=', 'conversation_tag.conversation_id');
                    })
                    ->leftJoin('tags', function ($join) {
                        $join->on('tags.id', '=', 'conversation_tag.tag_id');
                    });
            }
            
            if (\Helper::isMySql()) {
                $select[] = \DB::raw("GROUP_CONCAT(DISTINCT tags.name SEPARATOR ', ') as tags");
            } else {
                $select[] = \DB::raw("string_agg(tags.name, ', ') as tags");
            }
        }

        // Time tracking.
        $tt_active = \Module::isActive('timetracking');
        if (in_array(\ExportConversations::MODULE_PREFIX.'time_spent', $fields) && $tt_active) {
            // This does not work when threads are joined.
            // $query->leftJoin('timelogs', function ($join) {
            //     $join->on('timelogs.conversation_id', '=', 'conversations.id')
            //         ->where('timelogs.finished', '=', true);
            // });
            // $select[] = \DB::raw("SUM(timelogs.time_spent) as time_spent");
            $select[] = \DB::raw("(SELECT SUM(timelogs.time_spent) from timelogs WHERE timelogs.conversation_id = conversations.id) as time_spent");
        }

        // Sat. ratings. Join threads
        if (in_array(\ExportConversations::MODULE_PREFIX.'sat_ratings', $fields)
            && \Module::isActive('satratings')
        ) {
            if (!strstr($query->toSql(), '`threads`.`conversation_id`')) {
                $query->join('threads', function ($join) {
                    $join->on('conversations.id', '=', 'threads.conversation_id');
                });
            }
            if (\Helper::isMySql()) {
                $select[] = \DB::raw("GROUP_CONCAT(threads.rating SEPARATOR ', ') as sat_ratings");
            } else {
                $select[] = \DB::raw("string_agg(threads.rating, ', ') as sat_ratings");
            }
        }


        // Conversation custom fields.
        $ccf_ids = [];
        foreach ($fields as $field) {
            preg_match("/^".\ExportConversations::MODULE_PREFIX."ccf_(\d+)/", $field, $m);
            if (!empty($m[1])) {
                $ccf_ids[] = $m[1];
            }
        }

        // Customer fields.
        $crm_ids = [];
        foreach ($fields as $field) {
            preg_match("/^".\ExportConversations::MODULE_PREFIX."crm_(\d+)/", $field, $m);
            if (!empty($m[1])) {
                $crm_ids[] = $m[1];
            }
        }
        if (count($crm_ids) && !in_array('conversations.customer_id', $fields_regular)) {
            $select[] = 'conversations.customer_id';
        }

        // This includes groupBy().
        $query = $query->select($select);
        // print_r($select); die();
        
        $results = $query->get();

        // Preload data.
        if ($results) {
            // Users.
            if (in_array('conversations.user_id', $fields_regular) || in_array('conversations.closed_by', $fields_regular)) {
                $users_collection = User::select(['id', 'first_name', 'last_name'])->get();
                foreach ($users_collection as $user) {
                    $users[$user->id] = $user->getFullName();
                }
                unset($users_collection);
            }

            // Mailboxes.
            if (in_array('conversations.mailbox_id', $fields_regular)) {
                $ids = $results->pluck('mailbox_id')->unique()->toArray();
                if ($ids) {
                    $mailboxes_collection = Mailbox::select('id', 'name')
                        ->whereIn('id', $ids)
                        ->get();
                    foreach ($mailboxes_collection as $mailbox) {
                        $mailboxes[$mailbox->id] = $mailbox->name;
                    }
                    unset($mailboxes_collection);
                }
            }

            $results = $results->toArray();
        }

        // Format fields.
        foreach ($results as $i => $row) {
            if (!empty($row['type'])) {
                $results[$i]['type'] = Conversation::typeToName($row['type']);
            }
            if (!empty($row['user_id'])) {
                $results[$i]['user_id'] = $users[$row['user_id']] ?? '';
            }
            if (!empty($row['status'])) {
                $results[$i]['status'] = Conversation::statusCodeToName($row['status']);
            }
            if (!empty($row['state'])) {
                $results[$i]['state'] = Conversation::stateCodeToName($row['state']);
            }
            if (!empty($row['mailbox_id'])) {
                $results[$i]['mailbox_id'] = $mailboxes[$row['mailbox_id']] ?? '';
            }
            if (!empty($row['customer_name'])) {
                $results[$i]['customer_name'] = implode(' ', [$row['customer_first_name'], $row['customer_last_name']]);
            }
            if (!empty($row['cc'])) {
                $results[$i]['cc'] = implode(', ', \Helper::jsonToArray($row['cc']));
            }
            if (!empty($row['bcc'])) {
                $results[$i]['bcc'] = implode(', ', \Helper::jsonToArray($row['bcc']));
            }
            if (!empty($row['channel'])) {
                $results[$i]['channel'] = Conversation::channelCodeToName($row['channel']);
            }
            if (!empty($row['last_reply_from'])) {
                $results[$i]['last_reply_from'] = ucfirst(Conversation::$persons[$row['last_reply_from']] ?? '');
            }
            if (!empty($row['closed_by_user_id'])) {
                $results[$i]['closed_by_user_id'] = $users[$row['closed_by_user_id']] ?? '';
            }
            if (isset($row['has_attachments'])) {
                $results[$i]['has_attachments'] = ($row['has_attachments'] == 1 ? 'Yes' : 'No');
            }
            if (!empty($row['tags'])) {
                $results[$i]['tags'] = implode(', ', array_unique(explode(', ', $results[$i]['tags'])));
            }
            // Add custom fields.
            if (!empty($row['time_spent'])) {
                if ($tt_active) {
                    $results[$i]['time_spent'] = \TimeTracking::formatTime($row['time_spent']);
                }
            } else {
                $row['time_spent'] = '';
            }
            if (!empty($row['sat_ratings'])) {
                $ratings = explode(', ', $row['sat_ratings']);
                foreach ($ratings as $r => $rating_id) {
                    $rating_name = '';
                    switch ($rating_id) {
                        case \SatRatingsHelper::RATING_GREAT:
                            $rating_name = __('Great');
                            break;
                        case \SatRatingsHelper::RATING_OKAY:
                            $rating_name = __('Okay');
                            break;
                        case \SatRatingsHelper::RATING_BAD:
                            $rating_name = __('Not Good');
                            break;
                    }
                    $ratings[$r] = $rating_name;
                }

                $results[$i]['sat_ratings'] = implode(', ', $ratings);
            }

            // Add conversation custom fields.
            if (count($ccf_ids) && $ccf_active) {
                foreach ($ccf_ids as $custom_field_id) {
                    $results[$i][\ExportConversations::MODULE_PREFIX.'ccf_'.$custom_field_id] = '';
                }
            }

            // Add customer fields.
            if (count($crm_ids) && $crm_active) {
                foreach ($crm_ids as $customer_field_id) {
                    $results[$i][\ExportConversations::MODULE_PREFIX.'crm_'.$customer_field_id] = '';
                }
            }

            if (array_key_exists('customer_first_name', $row)) {
                unset($results[$i]['customer_first_name']);
            }
            if (array_key_exists('customer_last_name', $row)) {
                unset($results[$i]['customer_last_name']);
            }
        }

        // Get conversation custom fields.
        if (count($ccf_ids) && $ccf_active) {
            // Get custom fields.
            $custom_fields = \CustomField::whereIn('id', $ccf_ids)->get();

            $all_conv_ids = array_column($results, 'id');

            for ($bunch = 0; $bunch < ceil(count($results) / \ExportConversations::BUNCH_SIZE); $bunch++) {
                $conv_ids = array_slice($all_conv_ids, $bunch*\ExportConversations::BUNCH_SIZE, \ExportConversations::BUNCH_SIZE);

                $ccf_values = \Modules\CustomFields\Entities\ConversationCustomField::whereIn('conversation_id', $conv_ids)
                    ->select(['conversation_id', 'custom_field_id', 'value'])
                    ->whereIn('conversation_id', $conv_ids)
                    ->whereIn('custom_field_id', $ccf_ids)
                    ->get()
                    ->toArray();

                // Add to results.
                $conv_ids = array_unique(array_column($ccf_values, 'conversation_id'));
                foreach ($conv_ids as $conv_id) {
                    foreach ($results as $i => $row) {
                        if ($row['id'] == $conv_id) {
                            foreach ($ccf_values as $c => $ccf_row) {
                                if ($ccf_row['conversation_id'] == $conv_id) {
                                    // Create dummy custom field.
                                    $custom_field = $custom_fields->find($ccf_row['custom_field_id']);
                                    $custom_field->value = str_replace("\n", ' ', $ccf_row['value']);
                                    $results[$i][\ExportConversations::MODULE_PREFIX.'ccf_'.$ccf_row['custom_field_id']] = $custom_field->getAsText();
                                }
                            }
                            break;
                        }
                    }
                }
                unset($ccf_values);
            }
        }
        
        
        // Get customer fields
        if (count($crm_ids) && $crm_active) {
            // Get customer fields.
            $customer_fields = \CustomerField::whereIn('id', $crm_ids)->get();

            $all_conv_ids = array_column($results, 'id');

            for ($bunch = 0; $bunch < ceil(count($results) / \ExportConversations::BUNCH_SIZE); $bunch++) {
                $conv_ids = array_slice($all_conv_ids, $bunch*\ExportConversations::BUNCH_SIZE, \ExportConversations::BUNCH_SIZE);

                $customer_ids = Conversation::whereIn('id', $conv_ids)->pluck('customer_id');

                $crm_values = \Modules\Crm\Entities\CustomerCustomerField::select(['customer_id', 'customer_field_id', 'value'])
                    ->whereIn('customer_id', $customer_ids)
                    ->whereIn('customer_field_id', $crm_ids)
                    ->get()
                    ->toArray();

                // Add to results.
                foreach ($conv_ids as $conv_id) {
                    foreach ($results as $i => $row) {
                        if ($row['id'] == $conv_id) {
                            foreach ($crm_values as $c => $crm_row) {
                                if ($crm_row['customer_id'] == $row['customer_id']) {
                                    // Create dummy custom field.
                                    $customer_field = $customer_fields->find($crm_row['customer_field_id']);
                                    $customer_field->value = str_replace("\n", ' ', $crm_row['value']);
                                    // getAsText.
                                    if ($customer_field->type == \CustomerField::TYPE_DROPDOWN) {
                                        $customer_field_text = $customer_field->options[$customer_field->value] ?? $customer_field->value;
                                    } else {
                                        $customer_field_text = $customer_field->value;
                                    }
                                    $results[$i][\ExportConversations::MODULE_PREFIX.'crm_'.$crm_row['customer_field_id']] = $customer_field_text;
                                }
                            }
                            break;
                        }
                    }
                }
                unset($customer_ids);
                unset($crm_values);
            }
        }

        // Custom fields.
        // if (count($fields_ccf)) {
        //     $customer_fields = CunversationCustomField::whereIn('customer_field_id', $fields_ccf)
        //                         ->get();

        //     foreach ($results as $i => $row) {
        //         foreach ($fields_ccf as $cf_id) {
        //             $results[$i][\ExportConversations::CUSTOM_FIELD_PREFIX.$cf_id] = '';
        //             foreach ($customer_fields as $cf_row) {
        //                 if ($cf_row->customer_id == $row['id'] && $cf_row->customer_field_id == $cf_id) {
        //                     $results[$i][\ExportConversations::CUSTOM_FIELD_PREFIX.$cf_id] = $cf_row->value;
        //                     break;
        //                 }
        //             }
        //         }
        //     }
        // }

        $filename = 'conversations_'.date('Y-m-d').'.csv';
        
        $encoding = 'UCS-2LE';
        $separator = 'TAB';

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

            // Remove some fields.
            if (isset($row['customer_id'])) {
                unset($row['customer_id']);
            }

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
            $out = iconv("UTF-8", $encoding.'//IGNORE', $out);
            if ($encoding == 'UCS-2LE') {
                $out = "\xFF\xFE".$out;
            }
        }
        if($downloadType == 'pdf') {
            $table_data = str_replace('"','', $out);
            // return view('system.pdf_render', compact('table_data'));
            $filepath = view('system.pdf_render', compact('table_data'))->render();


            $pdf = new \TCPDF('P', 'mm', 'A3', true, 'UTF-8', false);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetCreator('Canidesk - by Canaris');
            $pdf->SetAuthor('Canaris');
            $pdf->SetTitle('Conversation report for '. date('d-m-Y H:i'));

            // Add a page
            $pdf->AddPage('L');
            // $pdf->SetAutoPageBreak(TRUE, 100);
            $html =  preg_replace('/\s\s+/', '', $filepath);
            $pdf->writeHTML($html, true, true, true, true, '');
            $pdf->setEqualColumns(21, 500);
            $filename = 'conversations_'.date('Y-m-d').'.pdf';
            $pdf->Output(public_path().'/'.$filename, 'F');
            
        }else{
            header("Cache-Control: must-revalidate, no-cache, no-store, private");
            header("Content-Length: " . strlen($out));
            header("Content-type: application/csv; charset=UCS-2LE");
            header("Content-Disposition: attachment; filename=$filename");
            echo $out;
        }
        $data = array('name'=>'Weekly Report - '.date('Y-m-d'));
        $mail = \Mail::send('emails.user.weekly_report', $data, function($message) use ($filename){
            // Change to this email in production rekha@manndeshibank.com for mann bank
            $message->to('support@canaris.in', 'Canidesk Test')->subject
            ('Weekly Report for '. Carbon::now()->subDays(7)->format('Y-m-d') . ' ~ '. Carbon::now()->format('Y-m-d'));
         $message->from('support@canaris.in','Canidesk');
         $message->attach(public_path().'/'.$filename);
        });
        dd($mail);
    }

    private function getExportableFields($mailbox_ids = [])
    {
        $exportable_fields = [
            'id' => 'ID',
            'number' => 'Conversation Number',
            'type' => 'Type',
            'user_id' => 'Assignee',
            'status' => 'Status',
            'state' => 'State',
            'mailbox_id' => 'Mailbox',
            'customer_name' => 'Customer Name',
            'customer_email' => 'Customer Email',
            'threads_count' => 'Threads Count',
            'subject' => 'Subject',
            'cc' => 'CC',
            'bcc' => 'BCC',
            'has_attachments' => 'Has Attachments',
            'channel' => 'Channel',
            'created_at' => 'Created On',
            'last_reply_at' => 'Last Reply On',
            'last_reply_from' => 'Last Reply From',
            'closed_at' => 'Closed On',
            'closed_by_user_id' => 'Closed By',
        ];

        if (\Module::isActive('tags')) {
            $exportable_fields[self::MODULE_PREFIX.'tags'] = __('Tags');
        }
        if (\Module::isActive('timetracking')) {
            $exportable_fields[self::MODULE_PREFIX.'time_spent'] = __('Time Spent');
        }
        if (\Module::isActive('satratings')) {
            $exportable_fields[self::MODULE_PREFIX.'sat_ratings'] = __('Sat. Ratings');
        }
        if (\Module::isActive('customfields')) {
            if (empty($mailbox_ids)) {
                $mailbox_ids = auth()->user()->mailboxesIdsCanView();
            }
            $custom_fields = \CustomField::whereIn('mailbox_id', $mailbox_ids)
                ->orderBy('sort_order')
                ->get();
            foreach ($custom_fields as $custom_field) {
                $exportable_fields[self::MODULE_PREFIX.'ccf_'.$custom_field->id] = $custom_field->name;
            }
        }

        if (\Module::isActive('crm')) {
            $customer_fields = \CustomerField::getCustomerFields();
            foreach ($customer_fields as $customer_field) {
                $exportable_fields[self::MODULE_PREFIX.'crm_'.$customer_field->id] = '('.__('Customer').') '.$customer_field->name;
            }
        }

        $exportable_fields = \Eventy::filter('exportconversations.exportable_fields', $exportable_fields);

        return $exportable_fields;
    }
}
