<?php

namespace App;

use App\Customer;
use App\Thread;
use App\User;
use App\Events\UserAddedNote;
use App\Events\UserReplied;
use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\ConversationCustomerChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;
use Watson\Rememberable\Rememberable;

class Conversation extends Model
{
    use Rememberable;
    // This is obligatory.
    public $rememberCacheDriver = 'array';

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
        self::STATUS_PENDING => 'ok',
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
        self::STATUS_ACTIVE  => '#6ac27b',
        self::STATUS_PENDING => '#8b98a6',
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
     * Email history options.
     */
    const EMAIL_HISTORY_GLOBAL = 0;
    const EMAIL_HISTORY_NONE = 1;
    const EMAIL_HISTORY_LAST = 2;
    const EMAIL_HISTORY_FULL = 3;

    public static $email_history_codes = [
        self::EMAIL_HISTORY_GLOBAL => 'global',
        self::EMAIL_HISTORY_NONE   => 'none',
        self::EMAIL_HISTORY_LAST   => 'last',
        self::EMAIL_HISTORY_FULL   => 'full',
    ];

    /**
     * Assignee.
     */
    const USER_UNASSIGNED = -1;

    /**
     * Search filters.
     */
    public static $search_filters = [
        'assigned',
        'customer',
        'mailbox',
        'status',
        'subject',
        'attachments',
        'type',
        'body',
        'number',
        'following',
        'id',
        'after',
        'before',
        //'between',
        //'on',
    ];

    /**
     * Search mode.
     */
    const SEARCH_MODE_CONV = 'conversations';
    const SEARCH_MODE_CUSTOMERS = 'customers';

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
     * Chached mailbox.
     * @return [type] [description]
     */
    public function mailbox_cached()
    {
        return $this->mailbox()->rememberForever();
    }

    /**
     * Get the customer associated with this conversation (primaryCustomer).
     */
    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    /**
     * Cached customer.
     */
    public function customer_cached()
    {
        return $this->customer()->rememberForever();
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
     * Get conversations followers.
     */
    public function followers()
    {
        return $this->hasMany('App\Follower');
    }

    /**
     * Check if user is following this conversation.
     */
    public function isUserFollowing($user_id)
    {
        // We intentionally select all records from followers table,
        // as it is more efficient than querying a particular user record.
        foreach ($this->followers as $follower) {
            if ($follower->user_id == $user_id) {
                return true;
            }
        }

        return false;
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
    public function getThreads($skip = null, $take = null, $types = [])
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
        if ($types) {
            $query->whereIn('type', $types);
        }

        return $query->get();
    }

    /**
     * Get first thread of the conversation.
     */
    public function getFirstThread()
    {
        return $this->threads()
            ->orderBy('created_at', 'asc')
            ->first();
    }

    /**
     * Get last reply by customer or support agent.
     *
     * @param bool $last [description]
     *
     * @return [type] [description]
     */
    public function getLastReply()
    {
        return $this->threads()
            ->whereIn('type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE])
            ->where('state', Thread::STATE_PUBLISHED)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get last thread by type.
     */
    public function getLastThread($types = [])
    {
        $query = $this->threads()
            ->where('state', Thread::STATE_PUBLISHED)
            ->orderBy('created_at', 'desc');
        if ($types) {
            if (count($types) == 1 && $types[0]) {
                $query->where('type', $types[0]);
            } else {
                $query->whereIn('type', $types);
            }
        }
        return $query->first();
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
            $title = __('Created by :person', ['person' => __(ucfirst(self::$persons[$this->source_via]))]);
            $title .= '<br/>'.User::dateFormat($this->created_at, 'M j, Y H:i');
        } else {
            $person = '';
            if (!empty(self::$persons[$this->last_reply_from])) {
                $person = __(ucfirst(self::$persons[$this->last_reply_from]));
            }
            $title = __('Last reply by :person', ['person' => $person]);
            $last_reply_at = $this->created_at;
            if ($this->last_reply_at) {
                $last_reply_at = $this->last_reply_at;
            }
            $title .= '<br/>'.User::dateFormat($last_reply_at, 'M j, Y H:i');
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
        //$query = self::where('folder_id', $folder->id)->where('id', '<>', $this->id);
        $query = self::getQueryByFolder($folder, \Auth::id())
            ->where('id', '<>', $this->id);

        $query = \Eventy::filter('conversation.get_nearby_query', $query, $this, $mode, $folder);

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
            $url = route('mailboxes.view.folder', ['id' => $this->mailbox_id, 'folder_id' => $this->getCurrentFolder($this->folder_id)]);
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
            $url = route('mailboxes.view.folder', ['id' => $this->mailbox_id, 'folder_id' => $this->getCurrentFolder($this->folder_id)]);
        }

        return $url;
    }

    /**
     * Set folder according to the status, state and user of the conversation.
     */
    public function updateFolder($mailbox = null)
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

        if (!$mailbox) {
            $mailbox = $this->mailbox;
        }

        // Find folder
        $folder = $mailbox->folders()
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
            $this->cc = \Helper::jsonEncodeUtf8($emails_array);
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
            $this->bcc = \Helper::jsonEncodeUtf8($emails_array);
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
        // Create customers if needed: Test <test1@example.com>
        if (is_array($emails)) {
            foreach ($emails as $i => $email) {
                preg_match("/^(.+)\s+([^\s]+)$/", $email, $m);
                if (count($m) == 3) {
                    $customer_name = trim($m[1]);
                    $email_address = trim($m[2]);

                    if ($customer_name) {
                        preg_match("/^([^\s]+)\s+([^\s]+)$/", $customer_name, $m_customer);
                        $customer_data = [];

                        if (count($m_customer) == 3) {
                            $customer_data['first_name'] = $m_customer[1];
                            $customer_data['last_name'] = $m_customer[2];
                        } else {
                            $customer_data['first_name'] = $customer_name;
                        }

                        Customer::create($email_address, $customer_data);
                    }

                    $emails[$i] = $email_address;
                }
            }
        }
        return \MailHelper::sanitizeEmails($emails);
    }

    /**
     * Get conversation URL.
     *
     * @return string
     */
    public function url($folder_id = null, $thread_id = null, $params = [])
    {
        if (!$folder_id) {
            $folder_id = $this->getCurrentFolder();
        }
        return self::conversationUrl($this->id, $folder_id, $thread_id, $params);
    }

    /**
     * Static function for retrieving URL.
     *
     * @param  [type] $id        [description]
     * @param  [type] $folder_id [description]
     * @param  [type] $thread_id [description]
     * @param  array  $params    [description]
     * @return [type]            [description]
     */
    public static function conversationUrl($id, $folder_id = null, $thread_id = null, $params = [])
    {
        $params = array_merge($params, ['id' => $id]);

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
            return \Eventy::filter('conversation.is_in_folder_allowed', false, $folder);
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

    public static function clearStarredByUserCache($user_id, $mailbox_id)
    {
        if (!$user_id) {
            $user = auth()->user();
            if ($user) {
                $user_id = $user->id;
            } else {
                return false;
            }
        }
        \Cache::forget('user_starred_conversations_'.$user_id.'_'.$mailbox_id);
    }

    /**
     * Get IDs of the conversations starred by user.
     */
    public static function getUserStarredConversationIds($mailbox_id, $user_id = null)
    {
        return \Cache::rememberForever('user_starred_conversations_'.$user_id.'_'.$mailbox_id, function () use ($mailbox_id, $user_id) {
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

        return \Eventy::filter('folder.conversations_query', $query_conversations, $folder, $user_id);
    }

    /**
     * Replace vars in signature.
     * `data` contains extra info which can be used to build signature.
     */
    public function getSignatureProcessed($data = [], $escape = false)
    {
        return $this->replaceTextVars($this->mailbox->signature, $data, $escape);
    }

    /**
     * Replace vars in the text.
     */
    public function replaceTextVars($text, $data = [], $escape = false)
    {
        if (!\MailHelper::hasVars($text)) {
            return $text;
        }

        if (empty($data['user'])) {
            // `user` should contain a user who replies to the conversation.
            $user = auth()->user();
            if (!$user && !empty($data['thread'])) {
                $user = $data['thread']->created_by_user;
            }
        } else {
            $user = $data['user'];
        }

        $data = [
            'mailbox'      => $this->mailbox,
            'conversation' => $this,
            'customer'     => $this->customer_cached,
            'user'         => $user,
        ];

        // Set variables
        return \MailHelper::replaceMailVars($text, $data, $escape);
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
     * Move conversation to another mailbox.
     */
    public function moveToMailbox($mailbox, $user)
    {
        $prev_mailbox = $this->mailbox;

        // We don't know how to replace $this->mailbox object.
        $this->mailbox_id = $mailbox->id;
        // Check assignee.
        if ($this->user_id && !in_array($this->user_id, $mailbox->userIdsHavingAccess())) {
            // Assign conversation to the user who moved it.
            $this->user_id = $user->id;
        }
        $this->updateFolder($mailbox);
        $this->save();

        // Add record to the conversation history.
        Thread::create($this, Thread::TYPE_LINEITEM, '', [
            'created_by_user_id' => $user->id,
            'user_id'     => $this->user_id,
            'state'       => Thread::STATE_PUBLISHED,
            'action_type' => Thread::ACTION_TYPE_MOVED_FROM_MAILBOX,
            'source_via'  => Thread::PERSON_USER,
            'source_type' => Thread::SOURCE_TYPE_WEB,
            'customer_id' => $this->customer_id,
        ]);

        // Update counters.
        $prev_mailbox->updateFoldersCounters();
        $mailbox->updateFoldersCounters();

        \Eventy::action('conversation.moved', $this, $user, $prev_mailbox);

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

    /**
     * Get waiting since time for the conversation.
     *
     * @param [type] $folder [description]
     *
     * @return [type] [description]
     */
    public function getWaitingSince($folder = null)
    {
        if (!$folder) {
            $folder = $this->folder;
        }
        $waiting_since_field = $folder->getWaitingSinceField();
        if ($waiting_since_field) {
            return \App\User::dateDiffForHumans($this->$waiting_since_field);
        } else {
            return '';
        }
    }

    /**
     * Get type name.
     */
    public function getTypeName()
    {
        return self::typeToName($this->type);
    }

    /**
     * Get type name .
     */
    public static function typeToName($type)
    {
        $name = '';

        switch ($type) {
            case self::TYPE_EMAIL:
                $name = __('Email');
                break;

            case self::TYPE_PHONE:
                $name = __('Phone');
                break;

            case self::TYPE_CHAT:
                $name = __('Chat');
                break;

            default:
                $name = \Eventy::filter('conversation.type_name', $type);
                break;
        }

        return $name;
    }

    /**
     * Get emails which are excluded from CC and BCC.
     */
    public function getExcludeArray($mailbox = null)
    {
        if (!$mailbox) {
            $mailbox = $this->mailbox;
        }
        $customer_emails = [$this->customer_email];
        if (strstr($this->customer_email, ',')) {
            // customer_email contains mutiple addresses (when new conversation for multiple recipients created)
            $customer_emails = explode(',', $this->customer_email);
        }
        return array_merge($mailbox->getEmails(), $customer_emails);
    }

    /**
     * Is it as phone conversation.
     */
    public function isPhone()
    {
        return ($this->type == self::TYPE_PHONE);
    }

    /**
     * Get information on viewers for conversation table.
     */
    public static function getViewersInfo($conversations, $fields = ['id', 'first_name', 'last_name'], $exclude_user_ids = [])
    {
        $viewers_cache = \Cache::get('conv_view');
        $viewers = [];
        $first_user_id = null;
        $user_ids = [];
        foreach ($conversations as $conversation) {
            if (!empty($viewers_cache[$conversation->id])) {
                // Get replying viewers
                foreach ($viewers_cache[$conversation->id] as $user_id => $viewer) {
                    if (!$first_user_id) {
                        $first_user_id = $user_id;
                    }
                    if (!empty($viewer['r']) && !in_array($user_id, $exclude_user_ids)) {
                        $viewers[$conversation->id] = [
                            'user'     => null,
                            'user_id'  => $user_id,
                            'replying' => true
                        ];
                        $user_ids[] = $user_id;
                        break;
                    }
                }
                // Get first non-replying viewer
                if (empty($viewers[$conversation->id]) && !in_array($user_id, $exclude_user_ids)) {
                    $viewers[$conversation->id] = [
                        'user'     => null,
                        'user_id'  => $first_user_id,
                        'replying' => false
                    ];
                    $user_ids[] = $first_user_id;
                }
            }
        }
        // Get all viewing users in one query
        if ($user_ids) {
            $user_ids = array_unique($user_ids);
            $users = User::select($fields)->whereIn('id', $user_ids)->get();

            foreach ($viewers as $i => $viewer) {
                foreach ($users as $user) {
                    if ($user->id == $viewer['user_id']) {
                        $viewers[$i]['user'] = $user;
                    }
                }
            }
        }
        return $viewers;
    }

    public function changeStatus($new_status, $user, $create_thread = true)
    {
        $prev_status = $this->status;

        $this->setStatus($new_status, $user);
        $this->save();

        // Create lineitem thread
        if ($create_thread) {
            $thread = new Thread();
            $thread->conversation_id = $this->id;
            $thread->user_id = $this->user_id;
            $thread->type = Thread::TYPE_LINEITEM;
            $thread->state = Thread::STATE_PUBLISHED;
            $thread->status = $this->status;
            $thread->action_type = Thread::ACTION_TYPE_STATUS_CHANGED;
            $thread->source_via = Thread::PERSON_USER;
            // todo: this need to be changed for API
            $thread->source_type = Thread::SOURCE_TYPE_WEB;
            $thread->customer_id = $this->customer_id;
            $thread->created_by_user_id = $user->id;
            $thread->save();
        }

        event(new ConversationStatusChanged($this));
        \Eventy::action('conversation.status_changed', $this, $user, $changed_on_reply = false, $prev_status);
    }

    public function changeUser($new_user_id, $user, $create_thread = true)
    {
        $prev_user_id = $this->user_id;

        $this->setUser($new_user_id);
        $this->save();

        // Create lineitem thread
        $thread = new Thread();
        $thread->conversation_id = $this->id;
        $thread->user_id = $this->user_id;
        $thread->type = Thread::TYPE_LINEITEM;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->status = Thread::STATUS_NOCHANGE;
        $thread->action_type = Thread::ACTION_TYPE_USER_CHANGED;
        $thread->source_via = Thread::PERSON_USER;
        // todo: this need to be changed for API
        $thread->source_type = Thread::SOURCE_TYPE_WEB;
        $thread->customer_id = $this->customer_id;
        $thread->created_by_user_id = $user->id;
        $thread->save();

        event(new ConversationUserChanged($this, $user));
        \Eventy::action('conversation.user_changed', $this, $user, $prev_user_id);
    }

    public function deleteToFolder($user)
    {
        $folder_id = $this->getCurrentFolder();

        $this->state = Conversation::STATE_DELETED;
        $this->user_updated_at = date('Y-m-d H:i:s');
        $this->updateFolder();
        $this->save();

        // Create lineitem thread
        $thread = new Thread();
        $thread->conversation_id = $this->id;
        $thread->user_id = $this->user_id;
        $thread->type = Thread::TYPE_LINEITEM;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->status = Thread::STATUS_NOCHANGE;
        $thread->action_type = Thread::ACTION_TYPE_DELETED_TICKET;
        $thread->source_via = Thread::PERSON_USER;
        // todo: this need to be changed for API
        $thread->source_type = Thread::SOURCE_TYPE_WEB;
        $thread->customer_id = $this->customer_id;
        $thread->created_by_user_id = $user->id;
        $thread->save();

        // Remove conversation from drafts folder.
        $this->removeFromFolder(Folder::TYPE_DRAFTS);

        // Recalculate only old and new folders
        $this->mailbox->updateFoldersCounters();

        \Eventy::action('conversation.deleted', $this, $user);
    }

    public function deleteForever()
    {
        self::deleteConversationsForever([$this->id]);
    }

    public static function deleteConversationsForever($conversation_ids)
    {
        \Eventy::action('conversations.before_delete_forever', $conversation_ids);

        //$conversation_ids = $conversations->pluck('id')->toArray();

        // Delete attachments.
        $thread_ids = Thread::whereIn('conversation_id', $conversation_ids)->pluck('id')->toArray();
        Attachment::deleteByThreadIds($thread_ids);

        // Delete threads.
        Thread::whereIn('conversation_id', $conversation_ids)->delete();
        // Delete conversations.
        Conversation::whereIn('id', $conversation_ids)->delete();
    }

    /**
     * Create note or reply.
     */
    public function createUserThread($user, $body, $data = [])
    {
        // Create thread
        $thread = Thread::create($this, $data['type'] ?? Thread::TYPE_MESSAGE, $body, $data, false);
        $thread->source_via = Thread::PERSON_USER;
        $thread->source_type = Thread::SOURCE_TYPE_WEB;
        $thread->user_id = $this->user_id;
        $thread->status = $this->status;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->customer_id = $this->customer_id;
        $thread->created_by_user_id = $user->id;
        $thread->edited_by_user_id = null;
        $thread->edited_at = null;
        $thread->body = $body;
        $thread->setTo($this->customer_email);
        $thread->save();

        // Update folders counters
        $this->mailbox->updateFoldersCounters();

        if ($thread->type == Thread::TYPE_NOTE) {
            event(new UserAddedNote($this, $thread));
            \Eventy::action('conversation.note_added', $this, $thread);
        } else {
            event(new UserReplied($this, $thread));
            \Eventy::action('conversation.user_replied', $this, $thread);
        }
    }

    public function forward($user, $body, $to = '', $data = [])
    {
        // Create thread
        $thread = Thread::create($this, $data['type'] ?? Thread::TYPE_NOTE, $body, $data, false);
        $thread->source_via = Thread::PERSON_USER;
        $thread->source_type = Thread::SOURCE_TYPE_WEB;
        $thread->user_id = $this->user_id;
        $thread->status = $this->status;
        $thread->state = Thread::STATE_PUBLISHED;
        $thread->customer_id = $this->customer_id;
        $thread->created_by_user_id = $user->id;
        $thread->edited_by_user_id = null;
        $thread->edited_at = null;
        $thread->body = $body;
        $thread->setTo($to);

        // Create forwarded conversation.
        $now = date('Y-m-d H:i:s');
        $forwarded_conversation = $this->replicate();
        $forwarded_conversation->type = Conversation::TYPE_EMAIL;
        $forwarded_conversation->setPreview($thread->body);
        $forwarded_conversation->created_by_user_id = $user->id;
        $forwarded_conversation->source_via = Conversation::PERSON_USER;
        $forwarded_conversation->source_type = Conversation::SOURCE_TYPE_WEB;
        $forwarded_conversation->threads_count = 0; // Counter will be incremented in ThreadObserver.
        $forwarded_customer = Customer::create($to);
        $forwarded_conversation->customer_id = $forwarded_customer->id;
        $forwarded_conversation->customer_email = $to;
        $forwarded_conversation->subject = 'Fwd: '.$forwarded_conversation->subject;
        $forwarded_conversation->setCc(array_merge(Conversation::sanitizeEmails($data['cc'] ?? []), [$to]));
        $forwarded_conversation->setBcc($data['bcc'] ?? []);
        $forwarded_conversation->last_reply_at = $now;
        $forwarded_conversation->last_reply_from = Conversation::PERSON_USER;
        $forwarded_conversation->user_updated_at = $now;
        // if ($attachments_info['has_attachments']) {
        //     $forwarded_conversation->has_attachments = true;
        // }
        $forwarded_conversation->updateFolder();
        $forwarded_conversation->save();

        $forwarded_thread = $thread->replicate();

        // Set forwarding meta data.
        $thread->subtype = Thread::SUBTYPE_FORWARD;
        $thread->setMeta('forward_child_conversation_number', $forwarded_conversation->number);
        $thread->setMeta('forward_child_conversation_id', $forwarded_conversation->id);

        $thread->save();

        // Save forwarded thread.
        $forwarded_thread->conversation_id = $forwarded_conversation->id;
        $forwarded_thread->type = Thread::TYPE_MESSAGE;
        $forwarded_thread->subtype = null;
        $forwarded_thread->setTo($to);
        // if ($attachments_info['has_attachments']) {
        //     $forwarded_thread->has_attachments = true;
        // }
        $forwarded_thread->setMeta('forward_parent_conversation_number', $this->number);
        $forwarded_thread->setMeta('forward_parent_conversation_id', $this->id);
        $forwarded_thread->setMeta('forward_parent_thread_id', $thread->id);
        $forwarded_thread->save();

        // Update folders counters
        $this->mailbox->updateFoldersCounters();

        // Notifications to users not sent.
        event(new UserAddedNote($this, $thread));
        // To send email with forwarded conversation.
        event(new UserReplied($forwarded_conversation, $forwarded_thread));
        \Eventy::action('conversation.user_forwarded', $this, $thread, $forwarded_conversation, $forwarded_thread);
    }

    public function getEmailHistoryCode()
    {
        return self::$email_history_codes[(int)$this->email_history] ?? 'global';
    }

    public static function getEmailHistoryName($code) {
        $label = '';

        switch ($code) {
            case 'global':
                $label = __('Default');
                $label .= ' ('.self::getEmailHistoryName(config('app.email_conv_history')).')';
                break;
            case 'none':
                $label = __('Do not include previous messages');
                break;
            case 'last':
                $label = __('Include the last message');
                break;
            case 'full':
                $label = __('Send full conversation history');
                break;
        }

        return $label;
    }


    // /**
    //  * Get conversation meta data as array.
    //  */
    // public function getMetas()
    // {
    //     return \Helper::jsonToArray($this->meta);
    // }

    // /**
    //  * Set conversation meta value.
    //  */
    // public function setMetas($data)
    // {
    //     $this->meta = json_encode($data);
    // }

    // /**
    //  * Get conversation meta value.
    //  */
    // public function getMeta($key, $default = null)
    // {
    //     $metas = $this->getMetas();
    //     if (isset($metas[$key])) {
    //         return $metas[$key];
    //     } else {
    //         return $default;
    //     }
    // }

    // /**
    //  * Set conversation meta value.
    //  */
    // public function setMeta($key, $value)
    // {
    //     $metas = $this->getMetas();
    //     $metas[$key] = $value;
    //     $this->setMetas($metas);
    // }

    // /**
    //  * Create new conversation.
    //  */
    // public static function create($data = [], $save = true)
    // {
    //     $conversation = new Conversation();
    //     $conversation->fill($data);

    //     if ($save) {
    //         $conversation->save();
    //     }
    // }
}
