<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    /**
     * Automatically converted into Carbon dates.
     */
    protected $dates = ['failed_at'];
}
