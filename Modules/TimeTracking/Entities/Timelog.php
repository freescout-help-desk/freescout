<?php

namespace Modules\TimeTracking\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Timelog extends Model
{
    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Automatically converted into Carbon dates.
     */
    protected $dates = ['created_at', 'updated_at'];

    const SUBTRACT_TIME = 30; // sec.

    /**
     * Get user.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function calcTimeSpent()
    {
        //if ($this->paused) {
        return Carbon::now()->diffInSeconds($this->updated_at);
        // } else {
        //     return \Carbon::now()->diffInSeconds($this->updated_at);
        // }
    }

    public function pause($subtact_time = self::SUBTRACT_TIME)
    {
        $calc_time = $this->calcTimeSpent();
        if ($calc_time > $subtact_time) {
            $calc_time = $calc_time - $subtact_time;
        }
        $this->time_spent += $calc_time;
        $this->paused = true;
        $this->save();
    }
}
