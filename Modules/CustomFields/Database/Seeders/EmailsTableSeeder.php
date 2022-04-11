<?php

namespace Modules\CustomFields\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmailsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        function (){
            for ($i=0;$i<200;$i++){
                DB::table('emails')->insert([
                    'user_id'=>1,
                    'email'=>'a@a.a',
                    'text'=>'jdfgdjkf dfj, sdhsdkh shifksdjgh lorem ,cghjxdfih djfhsdukhf
                    fldighjdf,g fklgjdfkl sdklriweo dsyis  isu kdyf sbose7r b s lieu kisdy ksdfh 
                    s kuyrisdyr i8huse'
                ]);
            }
        };

    }
}
