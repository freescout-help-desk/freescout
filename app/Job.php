<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
	const UPDATED_AT = null;

    /**
     * Automatically converted into Carbon dates.
     */
    protected $dates = ['created_at', 'available_at', 'reserved_at'];
}
