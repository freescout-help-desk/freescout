<?php

namespace App;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class SLASetting extends Model
{
   // use HashFactory;
   protected $table = 'sla_settings';
   public $timestamps=true;
}
