<?php

namespace Modules\Workflows\Services;

/**
 * Plain values for one condition check. Not an Eloquent model.
 */
class ConditionContext
{
    /**
     * @var int|null
     */
    public $status;

    /**
     * @var int|string|null
     */
    public $user_id;

    /**
     * @var int|null
     */
    public $type;

    /**
     * @var bool
     */
    public $customer_viewed = false;

    /**
     * @var string|null
     */
    public $trigger;

    /**
     * @var string|null
     */
    public $trigger_source;

    /**
     * @var string|null
     */
    public $trigger_body;

    /**
     * @var array
     */
    public $latest_body_by_source = [];

    /**
     * @var bool
     */
    public $has_attachment = false;
}
