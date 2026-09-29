<?php

namespace Modules\Workflows\Entities;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workflows';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'apply_to_previous' => 'boolean',
    ];

    public function conditions()
    {
        return $this->hasMany(WorkflowCondition::class)->orderBy('sort_order');
    }

    public function actions()
    {
        return $this->hasMany(WorkflowAction::class)->orderBy('sort_order');
    }
}
