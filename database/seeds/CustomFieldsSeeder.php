<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data=[
            [
                'id'=>1,
                'mailbox_id'=>'1',
                'name'=>'Ticket Category',
                'type'=>'1',
                'options'=>'{"1":"General Query","2":"Technical support","3":"Sales & Billing related","4":"Change request","5":"Other"}',
                'required'=>'1',
                'sort_order'=>'1',
                'show_in_list'=>'1',
            ],
            [
                'id'=>2,
                'mailbox_id'=>'1',
                'name'=>'Product',
                'type'=>'1',
                'options'=>'{"1":"Caniasset - Asset management","2":"Canidesk - Ticketing tool","3":"SIEM","4":"NMS - Network monitoring system","5":"Multiple products"}',
                'required'=>'1',
                'sort_order'=>'1',
                'show_in_list'=>'1',
            ],
            [
                'id'=>3,
                'mailbox_id'=>'1',
                'name'=>'Escalated',
                'type'=>'1',
                'options'=>'{"1":"TRUE","2":"FALSE"}',
                'required'=>'1',
                'sort_order'=>'1',
                'show_in_list'=>'1',
            ],
            [
                'id'=>4,
                'mailbox_id'=>'1',
                'name'=>'Priority',
                'type'=>'1',
                'options'=>'{"1":"Normal","2":"High","3":"Urgent"}',
                'required'=>'1',
                'sort_order'=>'1',
                'show_in_list'=>'1',
            ],
        ];
        foreach ($data as $item) {
            DB::table('custom_fields')->insert([
                $item
            ]);
        }

    }
}
