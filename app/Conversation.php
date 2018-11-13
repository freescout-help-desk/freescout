<?php

namespace App;

use App\Events\ConversationCustomerChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class Conversation extends Model
{
    /**
     * Max length of the preview.
     */
    const PREVIEW_MAXLENGTH = 255;

    /**
     * Conversation reply undo timeout in seconds.
     * Value has to be larger than close_after in fsFloatingAlertsInit.
     */
    const UNDO_TIMOUT = 15;

    /**
     * By whom action performed (used in fields: source_via, last_reply_from).
     */
    const PERSON_CUSTOMER = 1;
    const PERSON_USER = 2;

    public static $persons = [
        self::PERSON_CUSTOMER => 'customer',
        self::PERSON_USER     => 'user',
    ];

    /**
     * Conversation types.
     */
    const TYPE_EMAIL = 1;
    const TYPE_PHONE = 2;
    const TYPE_CHAT = 3; // not used

    public static $types = [
        self::TYPE_EMAIL => 'email',
        self::TYPE_PHONE => 'phone',
        self::TYPE_CHAT  => 'chat',
    ];

    /**
     * Conversation statuses (code must be equal to thread statuses).
     */
    const STATUS_ACTIVE = 1;
    const STATUS_PENDING = 2;
    const STATUS_CLOSED = 3;
    const STATUS_SPAM = 4;
    // Present in the API, but what does it mean?
    const STATUS_OPEN = 5;

    public static $statuses = [
        self::STATUS_ACTIVE  => 'active',
        self::STATUS_PENDING => 'pending',
        self::STATUS_CLOSED  => 'closed',
        self::STATUS_SPAM    => 'spam',
        //self::STATUS_OPEN => 'open',
    ];

    /**
     * https://glyphicons.bootstrapcheatsheets.com/.
     */
    public static $status_icons = [
        self::STATUS_ACTIVE  => 'flag',
        self::STATUS_PENDING => 'hourglass',
        self::STATUS_CLOSED  => 'lock',
        self::STATUS_SPAM    => 'ban-circle',
        //self::STATUS_OPEN => 'folder-open',
    ];

    public static $status_classes = [
        self::STATUS_ACTIVE  => 'success',
        self::STATUS_PENDING => 'lightgrey',
        self::STATUS_CLOSED  => 'grey',
        self::STATUS_SPAM    => 'danger',
        //self::STATUS_OPEN => 'folder-open',
    ];

    public static $status_colors = [
        self::STATUS_ACTIVE  => '#71c171',
        self::STATUS_PENDING => '#9598a1',
        self::STATUS_CLOSED  => '#6b6b6b',
        self::STATUS_SPAM    => '#de6864',
    ];

    /**
     * Conversation states.
     */
    const STATE_DRAFT = 1;
    const STATE_PUBLISHED = 2;
    const STATE_DELETED = 3;

    public static $states = [
        self::STATE_DRAFT     => 'draft',
        self::STATE_PUBLISHED => 'published',
        self::STATE_DELETED   => 'deleted',
    ];

    /**
     * Source types (equal to thread source types).
     */
    const SOURCE_TYPE_EMAIL = 1;
    const SOURCE_TYPE_WEB = 2;
    const SOURCE_TYPE_API = 3;

    public static $source_types = [
        self::SOURCE_TYPE_EMAIL => 'email',
        self::SOURCE_TYPE_WEB   => 'web',
        self::SOURCE_TYPE_API   => 'api',
    ];

    /**
     * todo: Search filters.
     */
    public static $filters = [
        'assigned',
        'customer',
        'mailbox',
        'status',
        'subject',
        'tag',
        'type',
        'body',
        'number',
        'id',
        'after',
        'before',
        'between',
        'on',
    ];

    /**
     * Default size of the conversations table.
     */
    const DEFAULT_LIST_SIZE = 50;

    /**
     * Cache of the conversations starred by user.
     *
     * @var array
     */
    public static $starred_conversation_ids = null;

    /**
     * Automatically converted into Carbon dates.
     */
    protected $dates = ['created_at', 'updated_at', 'last_reply_at', 'closed_at', 'user_updated_at'];

    /**
     * Attributes which are not fillable using fill() method.
     */
    protected $guarded = ['id', 'folder_id'];

    protected static function boot()
    {
        parent::boot();

        self::creating(function (Conversation $model) {
            $next_ticket = (int) Option::get('next_ticket');
            $current_number = Conversation::max('number');

            if ($next_ticket) {
                Option::remove('next_ticket');
            }

            if ($next_ticket && $next_ticket >= ($current_number + 1) && !Conversation::where('number', $next_ticket)->exists()) {
                $model->number = $next_ticket;
            } else {
                $model->number = $current_number + 1;
            }
        });
    }

    /**
     * Who the conversation is assigned to (assignee).
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get the folder to which conversation belongs via folder field.
     */
    public function folder()
    {
        return $this->belongsTo('App\Folder');
    }

    /**
     * Get the folder to which conversation belongs via conversation_folder table.
     */
    public function folders()
    {
        return $this->belongsToMany('App\Folder');
    }

    /**
     * Get the mailbox to which conversation belongs.
     */
    public function mailbox()
    {
        return $this->belongsTo('App\Mailbox');
    }

    /**
     * Get the customer associated with this conversation (primaryCustomer).
     */
    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Get conversation threads.
     */
    public function threads()
    {
        return $this->hasMany('App\Thread');
    }

    /**
     * Folders containing starred conversations.
     */
    public function extraFolders()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Get user who created the conversations.
     */
    public function created_by_user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get customer who created the conversations.
     */
    public function created_by_customer()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Get user who closed the conversations.
     */
    public function closed_by_user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get only reply threads from conversations.
     *
     * @return Collection
     */
    public function getReplies()
    {
        return $this->threads()
            ->whereIn('type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE])
            ->where('state', Thread::STATE_PUBLISHED)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all published conversation thread in desc order.
     *
     * @return Collection
     */
    public function getThreads($skip = null, $take = null)
    {
        $query = $this->threads()
            ->where('state', Thread::STATE_PUBLISHED)
            ->orderBy('created_at', 'desc');

        if (!is_null($skip)) {
            $query->skip($skip);
        }
        if (!is_null($take)) {
            $query->take($take);
        }

        return $query->get();
    }

    /**
     * Set preview text.
     *
     * @param string $text
     */
    public function setPreview($text = '')
    {
        $this->preview = '';

        if (!$text) {
            $first_thread = $this->threads()->first();
            if ($first_thread) {
                $text = $first_thread->body;
            }
        }

        $this->preview = \App\Misc\Helper::textPreview($text, self::PREVIEW_MAXLENGTH);

        return $this->preview;
    }

    /**
     * Get conversation timestamp title.
     *
     * @return string
     */
    public function getDateTitle()
    {
        if ($this->threads_count == 1) {
            $title = __('Created by :person<br/>:date', ['person' => ucfirst(__(
            self::$persons[$this->source_via])), 'date' => User::dateFormat($this->created_at, 'M j, Y H:i')]);
        } else {
            $title = __('Last reply by :person<br/>:date', ['person' => ucfirst(__(self::$persons[$this->source_via])), 'date' => User::dateFormat($this->created_at, 'M j, Y H:i')]);
        }

        return $title;
    }

    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * Get status name.
     *
     * @return string
     */
    public function getStatusName()
    {
        return self::statusCodeToName($this->status);
    }

    /**
     * Convert status code to name.
     *
     * @param int $status
     *
     * @return string
     */
    public static function statusCodeToName($status)
    {
        switch ($status) {
            case self::STATUS_ACTIVE:
                return __('Active');
                break;

            case self::STATUS_PENDING:
                return __('Pending');
                break;

            case self::STATUS_CLOSED:
                return __('Closed');
                break;

            case self::STATUS_SPAM:
                return __('Spam');
                break;

            case self::STATUS_OPEN:
                return __('Open');
                break;

            default:
                return '';
                break;
        }
    }

    /**
     * Set conersation status and all related fields.
     *
     * @param int $status
     */
    public function setStatus($status, $user = null)
    {
        $now = date('Y-m-d H:i:s');

        $this->status = $status;
        $this->updateFolder();
        $this->user_updated_at = $now;

        if ($user && $status == self::STATUS_CLOSED) {
            $this->closed_by_user_id = $user->id;
            $this->closed_at = $now;
        }
    }

    /**
     * Set conversation user and all related fields.
     *
     * @param int $user_id
     */
    public function setUser($user_id)
    {
        $now = date('Y-m-d H:i:s');

        if ($user_id == -1) {
            $user_id = null;
        }

        $this->user_id = $user_id;
        $this->updateFolder();
        $this->user_updated_at = $now;
    }

    /**
     * Get next active conversation.
     *
     * @param string $mode next|prev|closest
     *
     * @return Conversation
     */
    public function getNearby($mode = 'closest', $folder_id = null)
    {
        $conversation = null;

        if ($folder_id) {
            $folder = Folder::find($folder_id);
        } else {
            $folder = $this->folder;
        }
        $query = self::where('folder_id', $folder->id)
            ->where('id', '<>', $this->id);
        $order_bys = $folder->getOrderByArray();

        if ($mode != 'prev') {
            // Try to get next conversation
            $query_next = $query;
            foreach ($order_bys as $order_by) {
                foreach ($order_by as $field => $sort_order) {
                    if (!$this->$field) {
                        continue;
                    }
                    if ($sort_order == 'asc') {
                        $query_next->where($field, '>=', $this->$field);
                    } else {
                        $query_next->where($field, '<=', $this->$field);
                    }
                    $query_next->orderBy($field, $sort_order);
                }
            }
            $conversation = $query_next->first();
        }
        // echo 'folder_id'.$folder->id.'|';
        // echo 'id'.$this->id.'|';
        // echo 'status'.self::STATUS_ACTIVE.'|';
        // echo '$this->status'.$this->status.'|';
        // echo '$this->last_reply_at'.$this->last_reply_at.'|';
        // echo $query_next->toSql();
        // exit();

        if ($conversation || $mode == 'next') {
            return $conversation;
        }

        // Try to get previous conversation
        $query_prev = $query;
        foreach ($order_bys as $order_by) {
            foreach ($order_by as $field => $sort_order) {
                if (!$this->$field) {
                    continue;
                }
                if ($sort_order == 'asc') {
                    $query_prev->where($field, '<=', $this->$field);
                } else {
                    $query_prev->where($field, '>=', $this->$field);
                }
                $query_prev->orderBy($field, $sort_order);
            }
        }

        return $query_prev->first();
    }

    /**
     * Get URL of the next conversation.
     */
    public function urlNext($folder_id = null)
    {
        $next_conversation = $this->getNearby('next', $folder_id);
        if ($next_conversation) {
            $url = $next_conversation->url();
        } else {
            // Show folder
            $url = route('mailboxes.view.folder', ['id' => $this->mailbox_id, 'folder_id' => self::getCurrentFolder($this->folder_id)]);
        }

        return $url;
    }

    /**
     * Get URL of the previous conversation.
     */
    public function urlPrev($folder_id = null)
    {
        $prev_conversation = $this->getNearby('prev', $folder_id);
        if ($prev_conversation) {
            $url = $prev_conversation->url();
        } else {
            // Show folder
            $url = route('mailboxes.view.folder', ['id' => $this->mailbox_id, 'folder_id' => self::getCurrentFolder($this->folder_id)]);
        }

        return $url;
    }

    /**
     * Set folder according to the status, state and user of the conversation.
     */
    public function updateFolder()
    {
        if ($this->state == self::STATE_DRAFT) {
            $folder_type = Folder::TYPE_DRAFTS;
        } elseif ($this->state == self::STATE_DELETED) {
            $folder_type = Folder::TYPE_DELETED;
        } elseif ($this->status == self::STATUS_SPAM) {
            $folder_type = Folder::TYPE_SPAM;
        } elseif ($this->status == self::STATUS_CLOSED) {
            $folder_type = Folder::TYPE_CLOSED;
        } elseif ($this->user_id) {
            $folder_type = Folder::TYPE_ASSIGNED;
        } else {
            $folder_type = Folder::TYPE_UNASSIGNED;
        }

        // Find folder
        $folder = $this->mailbox->folders()
            ->where('type', $folder_type)
            ->first();

        if ($folder) {
            $this->folder_id = $folder->id;
        }
    }

    /**
     * Set CC as JSON.
     */
    public function setCc($emails)
    {
        $emails_array = self::sanitizeEmails($emails);
        if ($emails_array) {
            $emails_array = array_unique($emails_array);
            $this->cc = json_encode($emails_array);
        } else {
            $this->cc = null;
        }
    }

    /**
     * Set BCC as JSON.
     */
    public function setBcc($emails)
    {
        $emails_array = self::sanitizeEmails($emails);
        if ($emails_array) {
            $emails_array = array_unique($emails_array);
            $this->bcc = json_encode($emails_array);
        } else {
            $this->bcc = null;
        }
    }

    /**
     * Get CC recipients.
     *
     * @return array
     */
    public function getCcArray($exclude_array = [])
    {
        return \App\Misc\Helper::jsonToArray($this->cc, $exclude_array);
    }

    /**
     * Get BCC recipients.
     *
     * @return array
     */
    public function getBccArray($exclude_array = [])
    {
        return \App\Misc\Helper::jsonToArray($this->bcc, $exclude_array);
    }

    /**
     * Convert list of email to array.
     *
     * @return
     */
    public static function sanitizeEmails($emails)
    {
        return \App\Misc\Mail::sanitizeEmails($emails);
    }

    /**
     * Get conversation URL.
     *
     * @return string
     */
    public function url($folder_id = null, $thread_id = null, $params = [])
    {
        $params = array_merge($params, ['id' => $this->id]);
        if (!$folder_id) {
            $folder_id = self::getCurrentFolder();
        }
        $params['folder_id'] = $folder_id;

        $url = route('conversations.view', $params);

        if ($thread_id) {
            $url .= '#thread-'.$thread_id;
        }

        return $url;
    }

    /**
     * Get CSS color of the status.
     *
     * @return string
     */
    public function getStatusColor()
    {
        return self::$status_colors[$this->status];
    }

    /**
     * Get folder ID from request or use the default one.
     */
    public function getCurrentFolder($default_folder_id = null)
    {
        $folder_id = self::getFolderParam();
        if ($folder_id) {
            return $folder_id;
        }
        if ($this->folder_id) {
            return $this->folder_id;
        } else {
            return $default_folder_id;
        }
    }

    public static function getFolderParam()
    {
        if (!empty(request()->folder_id)) {
            return request()->folder_id;
        } elseif (!empty(Input::get('folder_id'))) {
            return Input::get('folder_id');
        }

        return '';
    }

    /**
     * Check if conversation can be in the folder.
     */
    public function isInFolderAllowed($folder)
    {
        if (in_array($folder->type, Folder::$public_types)) {
            return $folder->id == $this->folder_id;
        } elseif ($folder->type == Folder::TYPE_MINE) {
            $user = auth()->user();
            if ($user && $user->id == $folder->user_id && $this->user_id == $user->id) {
                return true;
            } else {
                return false;
            }
        } else {
            // todo: check ConversationFolder here
        }

        return false;
    }

    /**
     * Check if conversation is starred.
     * For each user starred conversations are cached.
     */
    public function isStarredByUser($user_id = null)
    {
        if (!$user_id) {
            $user = auth()->user();
            if ($user) {
                $user_id = $user->id;
            } else {
                return false;
            }
        }
        // Get ids of all the conversations starred by user and cache them
        if (self::$starred_conversation_ids === null) {
            $mailbox_id = $this->mailbox_id;
            self::$starred_conversation_ids = self::getUserStarredConversationIds($mailbox_id, $user_id);
        }

        if (self::$starred_conversation_ids) {
            return in_array($this->id, self::$starred_conversation_ids);
        } else {
            return false;
        }
    }

    public static function clearStarredByUserCache($user_id)
    {
        if (!$user_id) {
            $user = auth()->user();
            if ($user) {
                $user_id = $user->id;
            } else {
                return false;
            }
        }
        \Cache::forget('user_starred_conversations_'.$user_id);
    }

    /**
     * Get IDs of the conversations starred by user.
     */
    public static function getUserStarredConversationIds($mailbox_id, $user_id = null)
    {
        return \Cache::rememberForever('user_starred_conversations_'.$user_id, function () use ($mailbox_id, $user_id) {
            // Get user's folder
            $folder = Folder::select('id')
                        ->where('mailbox_id', $mailbox_id)
                        ->where('user_id', $user_id)
                        ->where('type', Folder::TYPE_STARRED)
                        ->first();

            if ($folder) {
                return ConversationFolder::where('folder_id', $folder->id)
                    ->pluck('conversation_id')
                    ->toArray();
            } else {
                activity()
                    ->withProperties([
                        'error'    => "Folder not found (mailbox_id: $mailbox_id, user_id: $user_id)",
                     ])
                    ->useLog(\App\ActivityLog::NAME_SYSTEM)
                    ->log(\App\ActivityLog::DESCRIPTION_SYSTEM_ERROR);

                return [];
            }
        });
    }

    /**
     * Get text for the assignee.
     *
     * @return string
     */
    public function getAssigneeName($ucfirst = false, $user = null)
    {
        if (!$this->user_id) {
            if ($ucfirst) {
                return __('Anyone');
            } else {
                return __('anyone');
            }
        } elseif (($user && $this->user_id == $user->id) || (!$user && $this->user_id == auth()->user()->id)) {
            if ($ucfirst) {
                return __('Me');
            } else {
                return __('me');
            }
        } else {
            return $this->user->getFullName();
        }
    }

    /**
     * Get query to fetch conversations by folder.
     */
    public static function getQueryByFolder($folder, $user_id)
    {
        if ($folder->type == Folder::TYPE_MINE) {
            // Get conversations from personal folder
            $query_conversations = self::where('user_id', $user_id)
                ->where('mailbox_id', $folder->mailbox_id)
                ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PENDING])
                ->where('state', self::STATE_PUBLISHED);
        } elseif ($folder->type == Folder::TYPE_ASSIGNED) {

            // Assigned - do not show my conversations
            $query_conversations = $folder->conversations()
                // This condition also removes from result records with user_id = null
                ->where('user_id', '<>', $user_id)
                ->where('state', self::STATE_PUBLISHED);
        } elseif ($folder->type == Folder::TYPE_STARRED) {
            $starred_conversation_ids = self::getUserStarredConversationIds($folder->mailbox_id, $user_id);
            $query_conversations = self::whereIn('id', $starred_conversation_ids);
        } elseif ($folder->isIndirect()) {

            // Conversations are connected to folder via conversation_folder table.
            $query_conversations = self::select('conversations.*')
                //->where('conversations.mailbox_id', $folder->mailbox_id)
                ->join('conversation_folder', 'conversations.id', '=', 'conversation_folder.conversation_id')
                ->where('conversation_folder.folder_id', $folder->id);
            if ($folder->type != Folder::TYPE_DRAFTS) {
                $query_conversations->where('state', self::STATE_PUBLISHED);
            }
        } elseif ($folder->type == Folder::TYPE_DELETED) {
            $query_conversations = $folder->conversations()->where('state', self::STATE_DELETED);
        } else {
            $query_conversations = $folder->conversations()->where('state', self::STATE_PUBLISHED);
        }

        return $query_conversations;
    }

    /**
     * Replace vars in signature.
     */
    public function getSignatureProcessed()
    {
        if (!\App\Misc\Mail::hasVars($this->mailbox->signature)) {
            return $this->mailbox->signature;
        }
        $data = [
            'mailbox'      => $this->mailbox,
            'conversation' => $this,
            'customer'     => $this->customer,
        ];

        // Set variables
        return \App\Misc\Mail::replaceMailVars($this->mailbox->signature, $data);
    }

    /**
     * Change conversation customer.
     * Customer is changed using customer email, as each conversation has customer email.
     * Method also creates line item thread if customer changed by user.
     * Both by_user and by_customer can be null.
     */
    public function changeCustomer($customer_email, $customer = null, $by_user = null, $by_customer = null)
    {
        if (!$customer) {
            $email = Email::where('email', $customer_email)->first();
            if ($email) {
                $customer = $email->customer;
            } else {
                return false;
            }
        }

        $prev_customer_id = $this->customer_id;
        $prev_customer_email = $this->customer_email;

        $this->customer_email = $customer_email;
        $this->customer_id = $customer->id;
        $this->save();

        // Create line item thread
        if ($by_user) {
            $thread = new Thread();
            $thread->conversation_id = $this->id;
            $thread->user_id = $this->user_id;
            $thread->type = Thread::TYPE_LINEITEM;
            $thread->state = Thread::STATE_PUBLISHED;
            $thread->status = Thread::STATUS_NOCHANGE;
            $thread->action_type = Thread::ACTION_TYPE_CUSTOMER_CHANGED;
            $thread->action_data = $this->customer_email;
            $thread->source_via = Thread::PERSON_USER;
            $thread->source_type = Thread::SOURCE_TYPE_WEB;
            $thread->customer_id = $this->customer_id;
            $thread->created_by_user_id = $by_user->id;
            $thread->save();
        }

        event(new ConversationCustomerChanged($this, $prev_customer_id, $prev_customer_email, $by_user, $by_customer));

        return true;
    }

    /**
     * Get all users for conversations in one query.
     */
    public static function loadUsers($conversations)
    {
        $user_ids = $conversations->pluck('user_id')->unique()->toArray();
        if (!$user_ids) {
            return;
        }

        $users = User::whereIn('id', $user_ids)->get();
        if (!$users) {
            return;
        }

        foreach ($conversations as $conversation) {
            if (empty($conversation->user_id)) {
                continue;
            }
            foreach ($users as $user) {
                if ($user->id == $conversation->user_id) {
                    $conversation->user = $user;

                    continue 2;
                }
            }
        }
    }

    /**
     * Get all customers for conversations in one query.
     */
    public static function loadCustomers($conversations)
    {
        $ids = $conversations->pluck('customer_id')->unique()->toArray();
        if (!$ids) {
            return;
        }

        $customers = Customer::whereIn('id', $ids)->get();
        if (!$customers) {
            return;
        }

        foreach ($conversations as $conversation) {
            if (empty($conversation->customer_id)) {
                continue;
            }
            foreach ($customers as $customer) {
                if ($customer->id == $conversation->customer_id) {
                    $conversation->customer = $customer;

                    continue 2;
                }
            }
        }
    }

    public function getSubject()
    {
        if ($this->subject) {
            return $this->subject;
        } else {
            return __('(no subject)');
        }
    }

    /**
     * Add conversation to folder via conversation_folder table.
     */
    public function addToFolder($folder_type)
    {
        // Find folder
        $folder = Folder::where('mailbox_id', $this->mailbox_id)
                    ->where('type', $folder_type)
                    ->first();
        if (!$folder) {
            return false;
        }

        $values = [
            'folder_id'       => $folder->id,
            'conversation_id' => $this->id,
        ];
        $folder_exists = ConversationFolder::select('id')->where($values)->first();
        if (!$folder_exists) {
            // This throws an exception if record exists
            $this->folders()->attach($folder->id);
        }
        $folder->updateCounters();

        // updateOrCreate does not create properly with ManyToMany
        // $values = [
        //     'folder_id' => $folder->id,
        //     'conversation_id' => $this->id,
        // ];
        // ConversationFolder::updateOrCreate($values, $values);
    }

    public function removeFromFolder($folder_type)
    {
        // Find folder
        $folder = Folder::where('mailbox_id', $this->mailbox_id)
                    ->where('type', $folder_type)
                    ->first();
        if (!$folder) {
            return false;
        }

        $this->folders()->detach($folder->id);
        $folder->updateCounters();
    }

    /**
     * Remove conversation from drafts folder if there are no draft threads in conversation.
     */
    public function maybeRemoveFromDrafts()
    {
        $has_drafts = Thread::where('conversation_id', $this->id)
                        ->where('state', Thread::STATE_DRAFT)
                        ->select('id')
                        ->first();
        if (!$has_drafts) {
            $this->removeFromFolder(Folder::TYPE_DRAFTS);

            return true;
        }

        return false;
    }

    /**
     * Delete threads and everything connected to threads.
     */
    public function deleteThreads()
    {
        $this->threads->each(function ($thread, $i) {
            $thread->deleteThread();
        });
    }
}
