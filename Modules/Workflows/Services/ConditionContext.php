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

    /**
     * Fixed clock for date conditions. The evaluator must not call Carbon::now().
     *
     * @var string|null
     */
    public $now;

    /**
     * Conversation::PERSON_CUSTOMER or Conversation::PERSON_USER.
     *
     * @var int|null
     */
    public $last_reply_from;

    /**
     * @var bool
     */
    public $last_reply_from_workflow = false;

    /**
     * @var string|null
     */
    public $last_customer_reply_at;

    /**
     * @var string|null
     */
    public $last_user_reply_at;

    /**
     * @var string|null
     */
    public $created_at;

    /**
     * Tag added by the triggering event. When set, tag checks ignore $tags.
     *
     * @var string|null
     */
    public $added_tag;

    /**
     * @var array
     */
    public $tags = [];

    /**
     * @var string|null
     */
    public $channel;

    /**
     * @var string|null
     */
    public $custom_field_value;

    /**
     * @var mixed
     */
    public $conversation;

    /**
     * @var mixed
     */
    public $workflow;
}
