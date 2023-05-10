<?php

namespace Modules\Workflows\Entities;

use Carbon\Carbon;
use Modules\Workflows\Entities\ConversationWorkflow;
use App\Conversation;
use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class Workflow extends Model
{
    use Rememberable;
    // This is obligatory.
    public $rememberCacheDriver = 'array';
    
    const TYPE_AUTOMATIC = 1;
	const TYPE_MANUAL    = 2;

    // Action type.
    const ACTION_TYPE_AUTOMATIC_WORKFLOW = 201;
    const ACTION_TYPE_MANUAL_WORKFLOW    = 202;

    // Workflow user email.
    const WF_USER_EMAIL = 'fsworkflow@example.org';

    const ASSIGNEE_CURRENT = -10;

    // User permission.
    const PERM_EDIT_WORKFLOWS = 6;

    public $validateErrors = null;

    protected $fillable = [
    	'name', 'type', 'apply_to_prev', 'active', 'conditions', 'actions'
    ];

	protected $attributes = [
        'type' => self::TYPE_AUTOMATIC,
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
    ];

    protected $dates = [
        'created_at',
    ];

    public static $date_conditions = [
        'created', 'waiting', 'user_reply', 'customer_reply'
    ];

    public static $conditions_config = [];

    public static $actions_config = [];

    /**
     * We have to store the last thread, as while processing workflows,
     * new last thread may be added.
     */
    public static $cond_last_thread = [];

    /**
     * Cached user.
     */
    public static $wf_user = null;

    public static function conditionsConfig($mailbox_id)
    {
        if (!empty(self::$conditions_config[$mailbox_id])) {
            return self::$conditions_config[$mailbox_id];
        }
        self::$conditions_config[$mailbox_id] = [
            'people' => [
                'title' => __('People'),
                'items' => [
                    'customer_name' => [
                        'title' => __('Customer Name'),
                        'operators' => [
                            'equal' => __('Is equal to'),
                            'contains' => __('Contains'),
                            'not_contains' => __('Does not contain'),
                            'not_equal' => __('Is not equal to'),
                            'starts' => __('Starts with'),
                            'ends' => __('Ends with'),
                            'regex' => __('Matches regex pattern'),
                        ],
                        'triggers' => [
                            'conversation.created_by_user',
                            'conversation.created_by_customer',
                            'conversation.moved',
                        ]
                    ],
                    'customer_email' => [
                        'title' => __('Customer Email'),
                        'operators' => [
                            'equal' => __('Is equal to'),
                            'contains' => __('Contains'),
                            'not_contains' => __('Does not contain'),
                            'not_equal' => __('Is not equal to'),
                            'starts' => __('Starts with'),
                            'ends' => __('Ends with'),
                            'regex' => __('Matches regex pattern'),
                        ],
                        'triggers' => [
                            'conversation.created_by_user',
                            'conversation.created_by_customer',
                            'conversation.moved',
                        ]
                    ],
                    'user_action' => [
                        'title' => __('User Action'),
                        'operators' => [
                            'replied' => __('Replied'),
                            'noted' => __('Added a note'),
                        ],
                        'triggers' => [
                            'conversation.created_by_user',
                            'conversation.user_replied',
                            'conversation.note_added',
                        ]
                    ],
                ]
            ],
            'conversation' => [
                'title' => __('Conversation'),
                'items' => [
                    'type' => [
                        'title' => __('Type'),
                        'operators' => [
                            'equal' => __('Is equal to'),
                            'not_equal' => __('Is not equal to'),
                        ],
                        'values' => [
                            Conversation::TYPE_EMAIL => __('Email'),
                            Conversation::TYPE_PHONE => __('Phone'),
                            Conversation::TYPE_CHAT  => __('Chat'),
                        ],
                        'triggers' => [
                            'conversation.created_by_user',
                            'conversation.created_by_customer',
                            'conversation.moved',
                        ]
                    ],
                    'status' => [
                        'title' => __('Status'),
                        'operators' => [
                            'equal' => __('Is equal to'),
                            'not_equal' => __('Is not equal to'),
                        ],
                        'values' => [
                            Conversation::STATUS_ACTIVE => __('Active'),
                            Conversation::STATUS_PENDING => __('Pending'),
                            Conversation::STATUS_CLOSED => __('Closed'),
                            Conversation::STATUS_SPAM => __('Spam'),
                        ],
                        'triggers' => [
                            'conversation.created_by_user',
                            'conversation.created_by_customer',
                            'conversation.status_changed',
                            'conversation.moved',
                        ]
                    ],
                    'state' => [
                        'title' => __('State'),
                        'operators' => [
                            'equal' => __('Is equal to'),
                            'not_equal' => __('Is not equal to'),
                        ],
                        'values' => [
                            Conversation::STATE_DRAFT => __('Draft'),
                            Conversation::STATE_PUBLISHED => __('Published'),
                            Conversation::STATE_DELETED => __('Deleted'),
                        ],
                        'triggers' => [
                            'conversation.state_changed',
                        ]
                    ],
                    'user' => [
                        'title' => __('Assigned to User'),
                        'operators' => [
                            'equal' => __('Is equal to'),
                            'not_equal' => __('Is not equal to'),
                        ],
                        'triggers' => [
                            'conversation.created_by_user',
                            'conversation.created_by_customer',
                            'conversation.user_changed',
                            'conversation.moved',
                        ]
                    ],
                    'to' => [
                        'title' => __('To'),
                        'operators' => [
                            'equal' => __('Is equal to'),
                            'contains' => __('Contains'),
                            'not_contains' => __('Does not contain'),
                            'not_equal' => __('Is not equal to'),
                            'starts' => __('Starts with'),
                            'ends' => __('Ends with'),
                            'regex' => __('Matches regex pattern'),
                        ],
                        'triggers' => [
                            'conversation.created_by_user',
                            'conversation.created_by_customer',
                            'conversation.moved',
                        ]
                    ],
                    'cc' => [
                        'title' => __('Cc'),
                        'operators' => [
                            'equal' => __('Is equal to'),
                            'contains' => __('Contains'),
                            'not_contains' => __('Does not contain'),
                            'not_equal' => __('Is not equal to'),
                            'starts' => __('Starts with'),
                            'ends' => __('Ends with'),
                            'regex' => __('Matches regex pattern'),
                        ],
                        'triggers' => [
                            'conversation.created_by_user',
                            'conversation.created_by_customer',
                            'conversation.moved',
                        ]
                    ],
                    'subject' => [
                        'title' => __('Subject'),
                        'operators' => [
                            'equal' => __('Is equal to'),
                            'contains' => __('Contains'),
                            'not_contains' => __('Does not contain'),
                            'not_equal' => __('Is not equal to'),
                            'starts' => __('Starts with'),
                            'ends' => __('Ends with'),
                            'regex' => __('Matches regex pattern'),
                        ],
                        'triggers' => [
                            'conversation.created_by_user',
                            'conversation.created_by_customer',
                            'conversation.moved',
                        ]
                    ],
                    'body' => [
                        'title' => __('Body'),
                        'operators' => [
                            'customer' => __('Customer message contains'),
                            'note' => __('Note contains'),
                            'regex' => __('Matches regex pattern'),
                        ],
                        'triggers' => [
                            'conversation.created_by_customer',
                            'conversation.customer_replied',
                            'conversation.note_added',
                            'conversation.moved',
                        ]
                    ],
                    'headers' => [
                        'title' => __('Headers'),
                        'operators' => [
                            'contains' => __('Contains'),
                            'not_contains' => __('Does not contain'),
                            'regex' => __('Matches regex pattern'),
                        ],
                        'triggers' => [
                            'conversation.created_by_customer',
                            'conversation.customer_replied',
                        ]
                    ],
                    'attachment' => [
                        'title' => __('Attachment'),
                        'operators' => [
                            'yes' => __('Has an attachment'),
                            'no' => __('Does not have an attachment'),
                        ],
                        'values' => [],
                        'triggers' => [
                            'conversation.created_by_user',
                            'conversation.created_by_customer',
                            'conversation.user_replied',
                            'conversation.customer_replied',
                            'conversation.moved',
                        ]
                    ],
                    'bounce' => [
                        'title' => __('Is Bounce'),
                        'operators' => [
                            'yes' => __('Yes'),
                            'no' => __('No'),
                        ],
                        'values' => [],
                        'triggers' => [
                            'conversation.created_by_customer',
                        ]
                    ],
                    'customer_viewed' => [
                        'title' => __('Customer Viewed'),
                        'operators' => [
                            'yes' => __('Yes'),
                            'no' => __('No'),
                        ],
                        'values' => [],
                        'triggers' => [
                            'thread.opened',
                        ]
                    ],
                    'new_or_reply' => [
                        'title' => __('New / Reply / Moved'),
                        'operators' => [
                            'new' => __('New conversation created'),
                            'reply' => __('User or customer replied'),
                            'moved' => __('Conversation moved from another mailbox'),
                        ],
                        'values' => [],
                        'triggers' => [
                            'conversation.created_by_customer',
                            'conversation.created_by_user',
                            'conversation.customer_replied',
                            'conversation.user_replied',
                            'conversation.moved',
                        ]
                    ],
                ],
            ],
            'dates' => [
                'title' => __('Dates'),
                'items' => [
                    // 'exact_date' => [
                    //     'title' => __('Exact Date'),
                    //     'operators' => [
                    //         'before' => __('Is before'),
                    //         'after' => __('Is after'),
                    //         //'between' => __('Is between'),
                    //     ]
                    // ],
                    'waiting' => [
                        'title' => __('Waiting Since'),
                        'operators' => [
                            'longer' => __('Is longer than'),
                            'not_longer' => __('Is not longer than'),
                        ]
                    ],
                    'user_reply' => [
                        'title' => __('Last User Reply'),
                        'operators' => [
                            'in_last' => __('Is in the last'),
                            'not_in_last' => __('Is not in the last'),
                        ],
                        'triggers' => [
                            'conversation.user_replied',
                        ]
                    ],
                    'customer_reply' => [
                        'title' => __('Last Customer Reply'),
                        'operators' => [
                            'in_last' => __('Is in the last'),
                            'not_in_last' => __('Is not in the last'),
                        ]
                    ],
                    'created' => [
                        'title' => __('Date Created'),
                        'operators' => [
                            'in_last' => __('Is in the last'),
                            'not_in_last' => __('Is not in the last'),
                        ]
                    ],
                ]
            ],
        ];

        self::$conditions_config[$mailbox_id] = \Eventy::filter('workflows.conditions_config', self::$conditions_config[$mailbox_id], $mailbox_id);

        return self::$conditions_config[$mailbox_id];
    }

    public static function actionsConfig($mailbox_id)
    {
        if (!empty(self::$actions_config[$mailbox_id])) {
            return self::$actions_config[$mailbox_id];
        }

        self::$actions_config[$mailbox_id] = [
            'dummy' => [
                'items' => [
                    'notification' => [
                        'title' => __('Send Email Notification'),
                        // 'operators' => [
                        //     'assignee' => __('Current Assignee'),
                        //     'last' => __('Last User to Reply'),
                        // ],
                        // 'values' => []
                    ],
                    'reply' => [
                        'title' => __('Reply to Conversation'),
                    ],
                    'email_customer' => [
                        'title' => __('Email the Customer'),
                    ],
                    'no_autoreply' => [
                        'title' => __('Disable Auto Reply'),
                        'values' => []
                    ],
                    'forward' => [
                        'title' => __('Forward'),
                    ],
                    'note' => [
                        'title' => __('Add a Note'),
                    ],
                    'status' => [
                        'title' => __('Change Status'),
                        'values' => [
                            Conversation::STATUS_ACTIVE => __('Active'),
                            Conversation::STATUS_PENDING => __('Pending'),
                            Conversation::STATUS_CLOSED => __('Closed'),
                            Conversation::STATUS_SPAM => __('Spam'),
                        ]
                    ],
                    'assign' => [
                        'title' => __('Assign to User'),
                        'values' => [
                            Conversation::USER_UNASSIGNED => __('Unassigned'),
                        ]
                    ],
                    'move' => [
                        'title' => __('Move to Mailbox'),
                    ],
                    'delete' => [
                        'title' => __('Move to Deleted Folder'),
                        'values' => []
                    ],
                    'delete_forever' => [
                        'title' => __('Delete Forever'),
                        'values' => []
                    ],
                ],
            ],
        ];

        self::$actions_config[$mailbox_id] = \Eventy::filter('workflows.actions_config', self::$actions_config[$mailbox_id], $mailbox_id);

        return self::$actions_config[$mailbox_id];
    }

    /**
     * Get the mailbox to which conversation belongs.
     */
    public function mailbox()
    {
        return $this->belongsTo('App\Mailbox');
    }
    
    public function setSortOrderLast()
    {
    	$this->sort_order = (int)Workflow::max('sort_order')+1;
    }

    /**
     * Get URL for editing user.
     *
     * @return string
     */
    public function url()
    {
        return route('mailboxes.workflows.update', ['mailbox_id' => $this->mailbox_id, 'id' => $this->id]);
    }

    public function isComplete()
    {
        return $this->complete;
    }

    public function isAutomatic()
    {
        return $this->type == self::TYPE_AUTOMATIC;
    }

    public function isManual()
    {
        return $this->type == self::TYPE_MANUAL;
    }

    public static function formatConditions($conditions, $mailbox_id)
    {
        $result = [];
        $row = 0;

        foreach ($conditions as $list_i => $list) {
            foreach ($list as $condition_i => $condition) {
                $config = self::getConditionConfig($condition['type'], $mailbox_id);
                if (empty($condition['type']) || empty($condition['operator']) 
                    || (!isset($condition['value']) 
                            && $config 
                            && empty($config['values_visible_if']) 
                            && (!isset($config['values']) || $config['values'] != [])
                        )
                    || (!isset($condition['value']) 
                            && $config
                            && !empty($config['values_visible_if'])
                            && in_array($condition['operator'], $config['values_visible_if'])
                        )
                ) {
                    // Miss condition.
                } else {
                    if (!isset($result[$row])) {
                        $result[$row] = [];
                    }
                    $result[$row][] = $condition;
                }
            }
            if (empty($conditions[$list_i])) {
                //unset($conditions[$list_i]);
            } else {
                $row++;
            }
        }
        return $result;
    }

    public function hasDateConditions()
    {
        foreach ($this->conditions as $ands) {
            foreach ($ands as $row) {
                if (!empty($row['type']) && in_array($row['type'], self::$date_conditions)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Run all workflows for the mailbox.
     */
    public static function processWorkflowsForMailbox($mailbox_id)
    {
        $workflows = Workflow::where('mailbox_id', $mailbox_id)
            ->where('active', true)
            ->where('type', self::TYPE_AUTOMATIC)
            ->orderBy('sort_order')
            ->get();

        // Leave only date conditions, as others are triggered when action happens.
        foreach ($workflows as $i => $workflow) {
            if (!$workflow->hasDateConditions() && !$workflow->apply_to_prev) {
                $workflows->forget($i);
            }
        }

        if (!count($workflows)) {
            return;
        }

        //$wf_ids = $workflows->pluck('id')->toArray();

        // Select unprocessed conversations.
        $executed_num = 0;

        // Fetch conversations for each workflow.
        foreach ($workflows as $workflow) {
            $executed_num += $workflow->processForMailbox();
        }
    
        return $executed_num;
    }

    public function processForMailbox()
    {
        $workflow = $this;
        $mailbox_id = $workflow->mailbox_id;
        $executed_num = 0;

        // Process conersations before or after workflow is created.
        $page = 0;
        do {
            $conversations_query = Conversation::select('conversations.*')
                ->where('mailbox_id', $mailbox_id)
                ->where('state', '!=', Conversation::STATE_DELETED)
                //->where('status', '!=', Conversation::STATUS_SPAM)
                ->leftJoin('conversation_workflow', function ($join) use ($workflow) {
                    $join->on('conversations.id', '=', 'conversation_workflow.conversation_id');
                    //$join->whereIn('conversation_workflow.workflow_id', $wf_ids);
                    $join->where('conversation_workflow.workflow_id', $workflow->id);
                })
                ->where('conversation_workflow.workflow_id', null)
                ->skip($page*1000)
                ->take(1000);

            if (!$workflow->apply_to_prev) {
                $conversations_query->where('created_at', '>=', $workflow->created_at);
            }
            $conversations = $conversations_query->get();

            foreach ($conversations as $conversation) {
                if ($workflow->checkPrevious($conversation) && $workflow->checkConditions($conversation)) {
                    $workflow->performActions($conversation);
                    $executed_num++;
                }
            }
            $page++;
        } while (count($conversations));

        return $executed_num;
    }

    public static function runAutomaticForConversation($conversation, $trigger = '')
    {
        $workflows = Workflow::where('mailbox_id', $conversation->mailbox_id)
            ->where('active', true)
            ->where('type', self::TYPE_AUTOMATIC)
            ->orderBy('sort_order')
            ->get();

        $processed_ids = ConversationWorkflow::whereIn('workflow_id', $workflows->pluck('id')->toArray())
            ->where('conversation_id', $conversation->id)
            ->pluck('workflow_id')
            ->toArray();

        // In performActions other workflows may be triggered, for example tag can be added.
        $clean_last_thread = false;
        if (empty(self::$cond_last_thread[$conversation->id])) {
            $clean_last_thread = true;
        }

        foreach ($workflows as $workflow) {
            if (!in_array($workflow->id, $processed_ids) && $workflow->checkConditions($conversation, $trigger)) {
                $workflow->performActions($conversation);
            }
        }

        if ($clean_last_thread) {
            self::$cond_last_thread[$conversation->id] = [];
        }
    }

    // public function processForAllMailboxes()
    // {
    //     $mailboxes = Mailbox::getActiveMailboxes();

    //     foreach ($mailboxes as $mailbox) {
    //         $this->processForMailbox($mailbox->id);
    //     }
    // }

    /**
     * Run manually.
     */
    public function runManually($conversation)
    {
        // $workflow = Workflow::find($workflow_id);

        // if (!$workflow) {
        //     return false;
        // }

        if (!$conversation) {
            return false;
        }

        $this->performActions($conversation);
    }

    public static function getConditionConfig($type, $mailbox_id)
    {
        $conditions_config = self::conditionsConfig($mailbox_id);
        foreach ($conditions_config as $group) {
            foreach ($group['items'] as $item_type => $item) {
                if ($item_type == $type) {
                    return $item;
                }
            }
        }
    }

    public function checkConditions($conversation, $action = '')
    {
        $and_true = true;

        // Check trigger.
        if ($action) {
            $valid_trigger = false;
            foreach ($this->conditions as $ands) {
                foreach ($ands as $row) {
                    $config = self::getConditionConfig($row['type'], $conversation->mailbox_id);
                    if (empty($config['triggers'])) {
                        continue;
                    }
                    // Check conversation.created.
                    // if ($action = 'thread.created' 
                    //     && in_array('conversation.created', $config['triggers'])
                    //     && $is_new_conversation
                    // ) {
                    //     $valid_trigger = true;
                    //     break 2;
                    // }

                    if (in_array($action, $config['triggers'])) {
                        $valid_trigger = true;
                        break 2;
                    }
                }
            }

            // If conversation has been moved from another mailbox,
            // among conditions there should be "Moved" condition.
            if ($action == 'conversation.moved') {
                $valid_trigger = false;
                foreach ($this->conditions as $ands) {
                    foreach ($ands as $row) {
                        if ($row['type'] == 'new_or_reply' && $row['operator'] == 'moved') {
                            $valid_trigger = true;
                            break 2;
                        }
                    }
                }
            }

            if (!$valid_trigger) {
                return false;
            }
        }

        foreach ($this->conditions as $ands) {
            $or_true = false;
            foreach ($ands as $row) {

                if (empty($row['type'])) {
                    continue;
                }
                // if ($only_dates && !in_array($row['type'], self::$date_conditions)) {
                //     continue;
                // }
                $operator = $row['operator'] ?? '';
                $value = $row['value'] ?? [];

                switch ($row['type']) {

                    // People.
                    case 'customer_name':
                        $customer = $conversation->customer;
                        if ($customer) {
                            $or_true = self::compareText($customer->getFullName(), $value, $operator);
                        }
                        break;

                    case 'customer_email':
                        $customer = $conversation->customer;
                        if ($customer) {
                            foreach ($customer->emails as $email) {
                                $or_true = self::compareText($email->email, $value, $operator);
                                if ($or_true) {
                                    break;
                                }
                            }
                        }
                        break;

                    case 'user_action':
                        if (empty(self::$cond_last_thread[$conversation->id]['any_type'])) {
                            $last_thread = $conversation->getLastThread();
                            self::$cond_last_thread[$conversation->id]['any_type'] = $last_thread;
                        } else {
                            $last_thread = self::$cond_last_thread[$conversation->id]['any_type'];
                        }
                        if ($last_thread && $last_thread->state == Thread::STATE_PUBLISHED) {
                            if (($operator == 'replied' && $last_thread->type == Thread::TYPE_MESSAGE)
                                || ($operator == 'noted' && $last_thread->type == Thread::TYPE_NOTE)
                            ) {
                                if ($value == Conversation::USER_UNASSIGNED 
                                    || $last_thread->created_by_user_id == $value
                                ) {
                                    $or_true = true;
                                }
                            }
                        }
                        break;

                    // Conversation.
                    case 'type':
                        if ($operator == 'equal') {
                            $or_true = ($conversation->type == $value);
                        } else {
                            $or_true = ($conversation->type != $value);
                        }
                        break;

                    case 'status':
                        if ($operator == 'equal') {
                            $or_true = ($conversation->status == $value);
                        } else {
                            $or_true = ($conversation->status != $value);
                        }
                        break;

                    case 'state':
                        if ($operator == 'equal') {
                            $or_true = ($conversation->state == $value);
                        } else {
                            $or_true = ($conversation->state != $value);
                        }
                        break;

                    case 'user':
                        if ($operator == 'equal') {
                            if ($value == Conversation::USER_UNASSIGNED) {
                                $value = 0;
                            }
                            $or_true = ($conversation->user_id == $value);
                        } else {
                            $or_true = ($conversation->user_id != $value);
                        }
                        break;

                    case 'subject':
                        $or_true = self::compareText($conversation->subject, $value, $operator);

                        // https://github.com/freescout-helpdesk/freescout/issues/1919
                        if (!$or_true) {
                            $value_imap = \imap_utf8('=?UTF-8?q?'.str_replace(' ', '_', quoted_printable_encode($value)).'?=');
                            if ($value_imap && $value_imap != $value) {
                                $or_true = self::compareText($conversation->subject, $value_imap, $operator);
                            }
                        }
                        break;

                    case 'to':
                        $last_thread = $conversation->getLastReply();
                        if ($last_thread) {
                            $or_true = self::compareArray($last_thread->getToArray(), $value, $operator);
                        }
                        break;

                    case 'cc':
                        $last_thread = $conversation->getLastReply();
                        if ($last_thread) {
                            $or_true = self::compareArray($last_thread->getCcArray(), $value, $operator);
                        }
                        break;

                    case 'body':
                        if ($action) {
                            if (empty(self::$cond_last_thread[$conversation->id]['any_type'])) {
                                $last_thread = $conversation->getLastThread();
                                self::$cond_last_thread[$conversation->id]['any_type'] = $last_thread;
                            } else {
                                $last_thread = self::$cond_last_thread[$conversation->id]['any_type'];
                            }
                            if ($last_thread) {
                                if ($last_thread->source_via == Thread::PERSON_CUSTOMER) {
                                    if ($operator == 'customer') {
                                        $or_true = self::compareText($last_thread->body, $value, 'contains');
                                    }
                                    if ($operator == 'regex') {
                                        $or_true = self::compareText($last_thread->body, $value, 'regex');
                                    }
                                }
                                if ($operator == 'note') {
                                    if ($last_thread->source_via == Thread::PERSON_USER
                                        && $last_thread->type == Thread::TYPE_NOTE
                                    ) {
                                        $or_true = self::compareText($last_thread->body, $value, 'contains');
                                    }
                                }
                            }
                        } else {
                            // We have to check all messages bodies.
                            $threads = $conversation->getThreads(null, null, [Thread::TYPE_CUSTOMER, Thread::TYPE_NOTE]);
                            foreach ($threads as $thread) {
                                if ($thread->source_via == Thread::PERSON_CUSTOMER) {
                                    if ($operator == 'customer') {
                                        $or_true = self::compareText($thread->body, $value, 'contains');
                                    }
                                    if ($operator == 'regex') {
                                        $or_true = self::compareText($thread->body, $value, 'regex');
                                    }
                                }
                                if ($operator == 'note') {
                                    if ($thread->source_via == Thread::PERSON_USER
                                        && $thread->type == Thread::TYPE_NOTE
                                    ) {
                                        $or_true = self::compareText($thread->body, $value, 'contains');
                                    }
                                }
                                if ($or_true) {
                                    break;
                                }
                            }
                        }
                        break;

                    case 'headers':
                        if ($action) {
                            if (empty(self::$cond_last_thread[$conversation->id]['any_type'])) {
                                $last_thread = $conversation->getLastThread();
                                self::$cond_last_thread[$conversation->id]['any_type'] = $last_thread;
                            } else {
                                $last_thread = self::$cond_last_thread[$conversation->id]['any_type'];
                            }
                            if ($last_thread && $last_thread->source_via == Thread::PERSON_CUSTOMER) {
                                $or_true = self::compareText($last_thread->headers, $value, $operator);
                            }
                        } else {
                            // We have to check all messages bodies.
                            $threads = $conversation->getThreads(null, null, [Thread::TYPE_CUSTOMER, Thread::TYPE_NOTE]);
                            foreach ($threads as $thread) {
                                if ($thread && $thread->source_via == Thread::PERSON_CUSTOMER) {
                                    $or_true = self::compareText($thread->headers, $value, $operator);
                                }
                                if ($or_true) {
                                    break;
                                }
                            }
                        }
                        break;

                    case 'attachment':
                        if ($operator == 'yes') {
                            $or_true = $conversation->has_attachments;
                        } else {
                            $or_true = !$conversation->has_attachments;
                        }
                        break;

                    case 'bounce':

                        if (empty(self::$cond_last_thread[$conversation->id][Thread::TYPE_CUSTOMER])) {
                            $last_customer_thread = $conversation->getLastThread([Thread::TYPE_CUSTOMER]);
                            self::$cond_last_thread[$conversation->id][Thread::TYPE_CUSTOMER] = $last_customer_thread;
                        } else {
                            $last_customer_thread = self::$cond_last_thread[$conversation->id][Thread::TYPE_CUSTOMER];
                        }

                        if ($last_customer_thread) {
                            $or_true = $last_customer_thread->isBounce();
                            if ($operator == 'no') {
                                $or_true = !$or_true;
                            }
                        }
                        break;

                    case 'customer_viewed':
                        if (empty(self::$cond_last_thread[$conversation->id][Thread::TYPE_MESSAGE])) {
                            $last_user_thread = $conversation->getLastThread([Thread::TYPE_MESSAGE]);
                            self::$cond_last_thread[$conversation->id][Thread::TYPE_MESSAGE] = $last_user_thread;
                        } else {
                            $last_user_thread = self::$cond_last_thread[$conversation->id][Thread::TYPE_MESSAGE];
                        }
                        if ($last_user_thread) {
                            if ($operator == 'yes') {
                                $or_true = !empty($last_user_thread->opened_at);
                            } else {
                                $or_true = empty($last_user_thread->opened_at);
                            }
                        }
                        break;

                    case 'new_or_reply':

                        if (!in_array($action, [
                            'conversation.created_by_customer',
                            'conversation.created_by_user',
                            'conversation.customer_replied',
                            'conversation.user_replied',
                            'conversation.moved',
                        ])) {
                            continue 2;
                        }

                        $actions_by_operator = [
                            'new' => ['conversation.created_by_customer', 'conversation.created_by_user'],
                            'reply' => ['conversation.customer_replied', 'conversation.user_replied'],
                            'moved' => ['conversation.moved'],
                        ];

                        $or_true = in_array($action, $actions_by_operator[$operator]);

                        break;

                    case 'waiting':
                        $number = $value['number'] ?? '';
                        $metric = $value['metric'] ?? '';
                        if (!$number || !$metric || !in_array($conversation->status, [Conversation::STATUS_ACTIVE, Conversation::STATUS_PENDING]) || $conversation->last_reply_from == Conversation::PERSON_USER) {
                            continue 2;
                        }
                        $now = Carbon::now();

                        if ($conversation->last_reply_at) {
                            if ($operator == 'longer') {
                                $or_true = ($conversation->last_reply_at < self::subTime($now, $metric, $number));
                            } else {
                                // not_longer
                                $or_true = ($conversation->last_reply_at > self::subTime($now, $metric, $number));
                            }
                        } else {
                            $or_true = false;
                        }
                        break;

                    case 'user_reply':
                        $number = $value['number'] ?? '';
                        $metric = $value['metric'] ?? '';
                        if (!$number || !$metric || $conversation->last_reply_from != Conversation::PERSON_USER) {
                            continue 2;
                        }
                        $now = Carbon::now();

                        if ($conversation->last_reply_at) {
                            if ($operator == 'in_last') {
                                $or_true = ($conversation->last_reply_at > self::subTime($now, $metric, $number));
                            } else {
                                // not_in_last
                                $or_true = ($conversation->last_reply_at < self::subTime($now, $metric, $number));
                            }
                        } else {
                            $or_true = false;
                        }
                        break;

                    case 'customer_reply':
                        $number = $value['number'] ?? '';
                        $metric = $value['metric'] ?? '';
                        if (!$number || !$metric || $conversation->last_reply_from != Conversation::PERSON_CUSTOMER) {
                            continue 2;
                        }
                        $now = Carbon::now();

                        if ($conversation->last_reply_at) {
                            if ($operator == 'in_last') {
                                $or_true = ($conversation->last_reply_at > self::subTime($now, $metric, $number));
                            } else {
                                // not_in_last
                                $or_true = ($conversation->last_reply_at < self::subTime($now, $metric, $number));
                            }
                        } else {
                            $or_true = false;
                        }
                        break;

                    case 'created':
                        $number = $value['number'] ?? '';
                        $metric = $value['metric'] ?? '';
                        if (!$number || !$metric) {
                            continue 2;
                        }
                        $now = Carbon::now();
                        if ($operator == 'in_last') {
                            $or_true = ($conversation->created_at > self::subTime($now, $metric, $number));
                        } else {
                            // Not in last
                            $or_true = ($conversation->created_at < self::subTime($now, $metric, $number));
                        }
                        break;
                    
                    default:
                        $or_true = \Eventy::filter('workflow.check_condition', false, $row['type'], $operator, $value, $conversation, $this);
                        break;
                }
                if ($or_true) {
                    break;
                }
            }
            if (!$or_true) {
                $and_true = false;
                break;
            }
        }

        return $and_true;
    }

    public function checkPrevious($conversation)
    {
        if ($this->apply_to_prev || $conversation->created_at > $this->created_at ) {
            return true;
        } else {
            return false;
        }
    }

    public function performActions($conversation/*, $mark_processed = true*/)
    {
        $executed = false;

        foreach ($this->actions as $ands) {
            foreach ($ands as $action) {
                $value = $action['value'] ?? '';
                $operator = $action['operator'] ?? '';

                switch ($action['type']) {

                    case 'notification':

                        if (!is_array($value)) {
                            continue 2;
                        }
                        $users = [];
                        foreach ($value as $user_id) {
                            if ($user_id == 'assignee') {
                                if ($conversation->user_id) {
                                    $users[] = $conversation->user;
                                }
                            } elseif ($user_id == 'last_user') {
                                // Last User to Reply.
                                $user_replies = $conversation->getThreads(0, 1, [Thread::TYPE_MESSAGE]);

                                if (count($user_replies) && $user_replies->first()->created_by_user_id) {
                                    $users[] = $user_replies->first()->created_by_user;
                                }
                            } else {
                                $user = User::findNonDeleted($user_id, true);
                                if ($user && $user->invite_state == User::INVITE_STATE_ACTIVATED) {
                                    $users[] = $user;
                                }
                            }
                        }
                        
                        if ($users) {
                            \App\Jobs\SendNotificationToUsers::dispatch(\Eventy::filter('users.unpack', $users), $conversation, $conversation->getThreads())
                                ->onQueue('emails');
                            $executed = true;
                        }
                        break;

                    case 'reply':
                        try {
                            $value = json_decode($action['value'] ?? '', true);
                        } catch (\Exception $e) {
                            continue 2;
                        }

                        $body = $value['body'] ?? '';
                        $cc = $value['cc'] ?? '';
                        $bcc = $value['bcc'] ?? '';

                        if ($body) {
                            $user = self::getUser();
                            $body = $conversation->replaceTextVars($body, ['user' => $user]);
                            $conversation->createUserThread(self::getUser(), $body, [
                                'cc' => $cc,
                                'bcc' => $bcc,
                                'meta' => [
                                    'workflow_id' => $this->id,
                                ]
                            ]);
                            $executed = true;
                        }
                        break;

                    case 'email_customer':
                        try {
                            $value = json_decode($action['value'] ?? '', true);
                        } catch (\Exception $e) {
                            continue 2;
                        }

                        $body = $value['body'] ?? '';
                        $cc = $value['cc'] ?? '';
                        $bcc = $value['bcc'] ?? '';

                        if ($body) {
                            $user = self::getUser();
                            $body = $conversation->replaceTextVars($body, ['user' => $user]);
                            $conversation->createUserThread(self::getUser(), $body, [
                                'cc' => $cc,
                                'bcc' => $bcc,
                                'meta' => [
                                    'workflow_id' => $this->id,
                                    Thread::META_CONVERSATION_HISTORY => 'none',
                                ]
                            ]);
                            $executed = true;
                        }
                        break;

                    case 'no_autoreply':
                        $conversation->setMeta('ar_off', true);
                        $conversation->save();
                        $executed = true;
                        break;

                    case 'forward':
                        $user = self::getUser();
                        if ($conversation->created_by_user_id == $user->id) {
                            continue 2;
                        }
                        try {
                            $value = json_decode($action['value'] ?? '', true);
                        } catch (\Exception $e) {
                            continue 2;
                        }

                        $body = $value['body'] ?? '';
                        $to = $value['to'] ?? '';
                        $cc = $value['cc'] ?? '';
                        $bcc = $value['bcc'] ?? '';

                        if ($body && $to) {
                            $body = $conversation->replaceTextVars($body, ['user' => $user]);
                            $conversation->forward($user, $body, $to, [], true);
                            $executed = true;
                        }
                        break;

                    case 'note':
                        try {
                            $value = json_decode($action['value'] ?? '', true);
                        } catch (\Exception $e) {
                            continue 2;
                        }

                        $body = $value['body'] ?? '';

                        if ($body) {
                            $user = self::getUser();
                            $body = $conversation->replaceTextVars($body, ['user' => $user]);
                            $conversation->createUserThread(self::getUser(), $body, [
                                'type' => Thread::TYPE_NOTE,
                                'meta' => [
                                    'workflow_id' => $this->id
                                ]
                            ]);
                            $executed = true;
                        }
                        break;

                    case 'status':
                        if ($conversation->status != $action['value']) {
                            $conversation->changeStatus($action['value'], self::getUser(), false);
                            $executed = true;
                        }
                        break;
                    
                    case 'assign':
                        if ($conversation->user != $action['value']) {
                            $new_user = $action['value'];
                            if ($new_user == self::ASSIGNEE_CURRENT) {
                                $auth_user = auth()->user();
                                if ($auth_user) {
                                    $new_user = $auth_user->id;
                                } else {
                                    $new_user = null;
                                }
                            }
                            if ($new_user) {
                                $conversation->changeUser($new_user, self::getUser(), false);
                                $executed = true;
                            }
                        }
                        break;

                    case 'move':
                        $mailbox = Mailbox::find($action['value']);
                        if ($mailbox) {
                            $conversation->moveToMailbox($mailbox, self::getUser());
                            $executed = true;
                        }
                        break;

                    case 'delete':
                        if ($conversation->state != Conversation::STATE_DELETED) {
                            $conversation->deleteToFolder(self::getUser());
                            $executed = true;
                        }
                        break;

                    case 'delete_forever':
                        $mailbox = $conversation->mailbox;
                        $conversation->deleteForever();
                        // Recalculate only old and new folders
                        $mailbox->updateFoldersCounters();
                        return;
                        break;

                    default:
                        \Eventy::filter('workflow.perform_action', $performed = false, $action['type'], $operator, $value, $conversation, $this);
                        break;
                }
            }
        }

        // This may lead to infinite actions execution.
        // if (!$executed) {
        //     return;
        // }

        // Create line item thread.
        $action_type = self::ACTION_TYPE_AUTOMATIC_WORKFLOW;
        $created_by_user_id = self::getUser()->id;
        if ($this->isManual()) {
            $action_type = self::ACTION_TYPE_MANUAL_WORKFLOW;
            $auth_user = auth()->user();
            if ($auth_user) {
                $created_by_user_id = $auth_user->id;
            }
        }
        Thread::create($conversation, Thread::TYPE_LINEITEM, '', [
            'user_id'       => $conversation->user_id,
            'created_by_user_id' => $created_by_user_id,
            'action_type' => $action_type,
            'source_via'    => Thread::PERSON_USER,
            'source_type'   => Thread::SOURCE_TYPE_WEB,
            'meta'          => [
                'workflow_id' => $this->id
            ]
        ]);

        //if ($mark_processed) {
        if ($this->isAutomatic()) {
            $this->markProcessed($conversation->id);
        }
    }

    /**
     * Get or create deleted user WorkfFlow.
     */
    public static function getUser()
    {
        if (!empty(self::$wf_user)) {
            return self::$wf_user;
        }
        self::$wf_user = User::where('email', self::WF_USER_EMAIL)->first();

        if (!self::$wf_user) {
            self::$wf_user = User::create([
                'first_name' => config('workflows.user_full_name'),
                'last_name'  => '',
                'email'      => self::WF_USER_EMAIL,
                'password'   => bcrypt(\Str::random(25)),
                'status'     => User::STATUS_DELETED,
                'type'       => User::TYPE_ROBOT,
            ]);
        } else {
            // Set name if needed.
            if (self::$wf_user->first_name != config('workflows.user_full_name')) {
                self::$wf_user->first_name = config('workflows.user_full_name');
                self::$wf_user->save();
            }
        }

        return self::$wf_user;
    }

    public function markProcessed($conversation_id)
    {
        try {
            $conversation_workflow = new ConversationWorkflow();
            $conversation_workflow->conversation_id = $conversation_id;
            $conversation_workflow->workflow_id = $this->id;
            $conversation_workflow->save();
        } catch (\Exception $e) {

        }
    }

    public function countConversationsApplied()
    {
        return ConversationWorkflow::where('workflow_id', $this->id)->count();
    }

    public function countConversationsToApply()
    {
        return Conversation::where('mailbox_id', $this->mailbox_id)
                    ->where('state', '!=', Conversation::STATE_DELETED)
                    //->where('created_at', '>=', $workflow->created_at);
                    //->where('status', '!=', Conversation::STATUS_SPAM)
                    ->count();
    }

    public static function compareArray($array, $text, $operator)
    {
        switch ($operator) {
            case 'equal':
            case 'contains':
            case 'starts':
            case 'ends':
            case 'regex':
                foreach ($array as $item) {
                    if (self::compareText($item, $text, $operator)) {
                        return true;
                    }
                }
                break;

            case 'not_contains':
                foreach ($array as $item) {
                    if (self::compareText($item, $text, 'contains')) {
                        return false;
                    }
                }
                return true;
                break;

            default:
                return false;
                break;
        }
        return false;
    }

    public static function subTime($date, $metric, $number)
    {
        if ($metric == 'h') {
            return $date->subHours($number);
        } elseif ($metric == 'i') {
            return $date->subMinutes($number);
        } else {
            return $date->subDays($number);
        }
    }

    public static function compareText($text1, $text2, $operator)
    {
        if (!is_string($text1)) {
            return false;
        }
        // For operators having $text2. 
        if (!in_array($operator, ['empty', 'not_empty'])) {
            if (!is_string($text2)) {
                return false;
            }
            if ($operator != 'regex') {
                $text1 = mb_strtolower($text1);
                $text2 = mb_strtolower($text2);
            }
        }
        switch ($operator) {
            case 'equal':
                return $text1 == $text2;
                break;

            case 'not_equal':
                return $text1 != $text2;
                break;

            case 'contains':
                return \Str::contains($text1, $text2);
                break;

            case 'not_contains':
                return !\Str::contains($text1, $text2);
                break;

            case 'starts':
                return \Str::startsWith($text1, $text2);
                break;
                
            case 'ends':
                return \Str::endsWith($text1, $text2);
                break;     
                           
            case 'regex':
                try {
                    // if (preg_match("#^/.*/$#", $text2)) {
                    //     $regex = $text2;
                    // } else {
                    //     $regex = '/'.$text2.'/';
                    // }
                    return preg_match($text2, $text1);
                } catch (\Exception $e) {
                    \Log::error('Invalid Workflow conditions regex: '.$text2.'. '.$e->getMessage());
                }
                break;

            case 'empty':
                return $text1 === '';
                break;

            case 'not_empty':
                return $text1 !== '';
                break;

            default:
                return false;
                break;
        }
        return false;
    }

    public static function maybeProcessInBackground($workflow)
    {
        if (!$workflow->active || !$workflow->apply_to_prev || !$workflow->id) {
            return false;
        }

        \Helper::backgroundAction('workflow.do_process', [$workflow->id]);
    }

    public static function findCached($id)
    {
        return Workflow::where('id', $id)->rememberForever()->first();
    }

    /**
     * Check if workflow is complete and deactivate if needed.
     */
    public function checkComplete($save = false)
    {
        $deactivated = false;

        $errors = $this->validate();
        if (count($errors)) {
            $this->complete = false;
            if ($this->active) {
                $deactivated = true;
            }
            $this->active = false;
        } else {
            $this->complete = true;
        }
        if ($save) {
            $this->save();
        }

        return $deactivated;
    }

    public static function canEditWorkflows($user = null, $mailbox_id = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        if (!$user) {
            return false;
        }
        if ($mailbox_id) {
            return $user->isAdmin() || ($user->hasAccessToMailbox($mailbox_id) && $user->hasPermission(\Workflow::PERM_EDIT_WORKFLOWS));
        } else {
            return $user->isAdmin() || $user->hasPermission(\Workflow::PERM_EDIT_WORKFLOWS);
        }
    }

    public static function checkAll($flash = true)
    {
        $workflows = Workflow::where('active', true)
            ->get();

        foreach ($workflows as $workflow) {
            $deactivated = $workflow->checkComplete(true);
            if ($deactivated && self::canEditWorkflows()) {
                \Helper::addFloatingFlash(__('Workflow :name deactivated', ['name' => $workflow->name]));
            }
        }
    }

    /**
     * Validate workflow.
     */
    public function validate()
    {
        $errors = [];

        if ($this->type == self::TYPE_MANUAL) {
            if (!$this->actions) {
                $errors['actions'] = [];
            }
            $errors = $this->validateActions($errors);
        } else {
            if (!$this->conditions) {
                $errors['conditions'] = [];
            }
            if (!$this->actions) {
                $errors['actions'] = [];
            }
            $errors = $this->validateConditions($errors);
            $errors = $this->validateActions($errors);
        }

        return $errors;
    }

    public function validateConditions($errors)
    {
        if (!is_array($this->conditions)) {
            $errors['conditions'] = [];
            return $errors;
        }
        foreach ($this->conditions as $and_i => $ands) {
            foreach ($ands as $or_i => $condition) {
                $has_error = false;
                if (empty($condition['type']) || empty($condition['operator'])) {
                    $has_error = true;
                }
                if (!$has_error) {
                    switch ($condition['type']) {
                        case 'customer_name':
                        case 'customer_email':
                        case 'to':
                        case 'cc':
                        case 'subject':
                        case 'body':
                            if (empty($condition['value'])) {
                                $has_error = true;
                            }
                            break;

                        case 'user_action':
                        case 'user':
                            if (empty($condition['value'])) {
                                $has_error = true;
                            } elseif ($condition['value'] != Conversation::USER_UNASSIGNED) {
                                $user = User::nonDeleted(true)->where('id' , $condition['value'])->first();
                                if (!$user) {
                                    $has_error = true;
                                }
                            }
                            break;

                        case 'waiting':
                        case 'user_reply':
                        case 'created':
                            if (empty($condition['value']) || empty($condition['value']['number'])) {
                                $has_error = true;
                            }
                            break;

                        default:
                            $has_error = \Eventy::filter('workflow.validate_condition', $has_error, $condition, $this);
                            break;
                    }
                }
                if ($has_error) {
                    $errors['conditions'][$and_i.'_'.$or_i] = 1;
                }
            }
        }

        return $errors;
    }

    public function validateActions($errors)
    {
        if (!is_array($this->actions)) {
            $errors['actions'] = [];
            return $errors;
        }
        foreach ($this->actions as $and_i => $ands) {
            foreach ($ands as $or_i => $action) {
                $has_error = false;
                if (empty($action['type'])) {
                    $has_error = true;
                }
                if (!$has_error) {
                    switch ($action['type']) {
                        
                        case 'email_customer':
                        case 'forward':
                        case 'note':
                            if (empty($action['value'])) {
                                $has_error = true;
                            }
                            break;

                        case 'assign':
                            if (empty($action['value'])) {
                                $has_error = true;
                            } elseif ($action['value'] != Conversation::USER_UNASSIGNED 
                                && $action['value'] != self::ASSIGNEE_CURRENT
                            ) {
                                $user = User::where('id' , $action['value'])->first();
                                if (!$user || !\Eventy::filter('workflow.is_user_valid', !$user->isDeleted(), $user, $this)) {
                                    $has_error = true;
                                }
                            }
                            break;

                        case 'notification':
                            if (empty($action['value'])) {
                                $has_error = true;
                            } elseif (is_array($action['value'])) {
                                $user_ids = [];
                                foreach ($action['value'] as $user_id) {
                                    if ((int)$user_id) {
                                        $user_ids[] = $user_id;
                                    }
                                }
                                if ($user_ids) {
                                    $count = User::nonDeleted(true)->whereIn('id' , $user_ids)->count();
                                    if ($count != count($user_ids)) {
                                        $has_error = true;
                                    }
                                }
                            }
                            break;

                        case 'move':
                            if (empty($action['value'])) {
                                $has_error = true;
                            } else {
                                $mailbox = Mailbox::where('id' , $action['value'])->first();
                                if (!$mailbox) {
                                    $has_error = true;
                                }
                            }
                            break;

                        default:
                            $has_error = \Eventy::filter('workflow.validate_action', $has_error, $action, $this);
                            break;
                    }
                }
                if ($has_error) {
                    $errors['actions'][$and_i.'_'.$or_i] = 1;
                }
            }
        }

        return $errors;
    }

    public function errors($cache = true)
    {
        if ($cache && $this->validateErrors !== null) {
            return $this->validateErrors;
        }
        $this->validateErrors = $this->validate();
        return $this->validateErrors;
    }
}