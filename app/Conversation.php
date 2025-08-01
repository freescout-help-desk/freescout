<?php

namespace App;

use App\Attachment;
use App\Customer;
use App\Mailbox;
use App\Folder;
use App\Follower;
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
     * Default subject length.
     */
    const SUBJECT_LENGTH = 80;

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
    const TYPE_CHAT = 3;
    const TYPE_CUSTOM = 4;

    public static $types = [
        self::TYPE_EMAIL => 'email',
        self::TYPE_PHONE => 'phone',
        self::TYPE_CHAT  => 'chat',
        self::TYPE_CUSTOM => 'custom',
    ];

    /**
     * Conversation statuses (code must be equal to thread statuses).
     */
    const STATUS_ACTIVE = 1;
    const STATUS_PENDING = 2;
    const STATUS_CLOSED = 3;
    const STATUS_SPAM = 4;
    // Not used
    //const STATUS_OPEN = 5;

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
    // const EMAIL_HISTORY_GLOBAL = 0;
    // const EMAIL_HISTORY_NONE = 1;
    // const EMAIL_HISTORY_LAST = 2;
    // const EMAIL_HISTORY_FULL = 3;

    public static $email_history_codes = [
        'global',
        'none',
        'last',
        'full',
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
        'state',
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
     * Default size of the chats list.
     */
    const CHATS_LIST_SIZE = 50;

    /**
     * Cache of the conversations starred by user.
     *
     * @var array
     */
    public static $starred_conversation_ids = [];

    /**
     * Cache of the app.custom_number option.
     */
    public static $custom_number_cache = null;

    /**
     * Automatically converted into Carbon dates.
     */
    protected $dates = ['created_at', 'updated_at', 'last_reply_at', 'closed_at', 'user_updated_at'];

    /**
     * Attributes which are not fillable using fill() method.
     */
    protected $guarded = ['id', 'folder_id'];

    /**
     * Convert to array.
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Default values.
     */
    protected $attributes = [
        'preview' => '',
    ];

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
     * Cached mailbox.
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
     * Get all published conversation threads in desc order.
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
    public function getLastReply($include_phone_replies = false)
    {
        $types = [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE];
        if ($include_phone_replies && $this->isPhone()) {
            $types[] = Thread::TYPE_NOTE;
        }
        return $this->threads()
            ->whereIn('type', $types)
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

        $this->preview = \Helper::textPreview($text, self::PREVIEW_MAXLENGTH);

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

    public function isPending()
    {
        return $this->status == self::STATUS_PENDING;
    }

    public function isSpam()
    {
        return $this->status == self::STATUS_SPAM;
    }

    public function isClosed()
    {
        return $this->status == self::STATUS_CLOSED;
    }

    public function isPublished()
    {
        return $this->state == self::STATE_PUBLISHED;
    }

    public function isDraft()
    {
        return $this->state == self::STATE_DRAFT;
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

            // case self::STATUS_OPEN:
            //     return __('Open');
            //     break;

            default:
                return '';
                break;
        }
    }

    /**
     * Convert state code to name.
     *
     * @param int $status
     *
     * @return string
     */
    public static function stateCodeToName($status)
    {
        switch ($status) {
            case self::STATE_DRAFT:
                return __('Draft');
                break;

            case self::STATE_PUBLISHED:
                return __('Published');
                break;

            case self::STATE_DELETED:
                return __('Deleted');
                break;

            default:
                return '';
                break;
        }
    }

    public function getStatus()
    {
        if (array_key_exists($this->status, self::$statuses)) {
            return $this->status;
        } else {
            return self::STATUS_ACTIVE;
        }
    }

    /**
     * Set conversation status and all related fields.
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
		
        // If user was previously following the conversation then unfollow
        if (!is_null($user_id)) {
            $follower = Follower::where('conversation_id', $this->id)
                ->where('user_id', $user_id)
                ->first();
            if ($follower) {
                $follower->delete();
            }
        }
    }

    /**
     * Get next active conversation.
     *
     * @param string $mode next|prev|closest
     *
     * @return Conversation
     */
    public function getNearby($mode = 'closest', $folder_id = null, $status = null, $prev_if_no_next = false)
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

        $status_applied = \Eventy::filter('conversation.get_nearby_status', false, $query, $status, $this, $folder);

        if (!$status_applied && $status) {
            $query->where('status', $status);
        }

        $order_bys = $folder->getOrderByArray();

        // Next.
        if ($mode != 'prev') {
            // Try to get next conversation
            $query_next = clone $query;
            foreach ($order_bys as $order_by) {
                foreach ($order_by as $field => $sort_order) {
                    if (!$this->$field) {
                        continue;
                    }
                    $field_value = $this->$field;
                    if ($field == 'status' && $status !== null) {
                        $field_value = $status;
                    }
                    if ($sort_order == 'asc') {
                        $query_next->where($field, '>=', $field_value);
                    } else {
                        $query_next->where($field, '<=', $field_value);
                    }
                    $query_next->orderBy($field, $sort_order);
                }
            }
            $conversation = $query_next->first();
        }

        // https://github.com/freescout-helpdesk/freescout/issues/3486
        if ($conversation || ($mode == 'next' && !$prev_if_no_next)) {
            return $conversation;
        }

        // Prev.
        $query_prev = $query;
        foreach ($order_bys as $order_by) {
            foreach ($order_by as $field => $sort_order) {
                if (!$this->$field) {
                    continue;
                }
                $field_value = $this->$field;
                if ($field == 'status' && $status !== null) {
                    $field_value = $status;
                }
                if ($sort_order == 'asc') {
                    $query_prev->where($field, '<=', $field_value);
                } else {
                    $query_prev->where($field, '>=', $field_value);
                }
                $query_prev->orderBy($field, $sort_order == 'asc' ? 'desc' : 'asc');
            }
        }

        return $query_prev->first();
    }

    /**
     * Get URL of the next conversation.
     */
    public function urlNext($folder_id = null, $status = null, $prev_if_no_next = false)
    {
        $next_conversation = $this->getNearby('next', $folder_id, $status, $prev_if_no_next);
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

            if ($mailbox->id != $this->mailbox_id) {
                $this->load('mailbox');
                $mailbox = $this->mailbox;
            }
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
                preg_match("/^(.+)\s+([^\s]+)$/", $email ?? '', $m);
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
            return \Eventy::filter('conversation.is_in_folder_allowed', false, $folder, $this);
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
        $mailbox_id = $this->mailbox_id;

        // Get ids of all the conversations starred by user and cache them
        if (!isset(self::$starred_conversation_ids[$mailbox_id])) {
            
            self::$starred_conversation_ids[$mailbox_id] = self::getUserStarredConversationIds($mailbox_id, $user_id);
        }

        if (self::$starred_conversation_ids[$mailbox_id]) {
            return in_array($this->id, self::$starred_conversation_ids[$mailbox_id]);
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
        } elseif (($user && $this->user_id == $user->id) || (!$user && auth()->user() && $this->user_id == auth()->user()->id)) {
            if ($ucfirst) {
                return __('Me');
            } else {
                return __('me');
            }
        } elseif ($this->user) {
            return $this->user->getFullName();
        } else {
            return '';
        }
    }

    /**
     * Get query to fetch conversations by folder.
     */
    public static function getQueryByFolder($folder, $user_id)
    {
        // Get conversations from personal folder
        if ($folder->type == Folder::TYPE_MINE) {
            $query_conversations = self::where('mailbox_id', $folder->mailbox_id)
                ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PENDING])
                ->where('state', self::STATE_PUBLISHED);

                // Applied below.
                //where('user_id', $user_id)

        // Assigned - do not show my conversations.
        } elseif ($folder->type == Folder::TYPE_ASSIGNED) {
            $query_conversations = $folder->conversations()
                // This condition also removes from result records with user_id = null
                ->where('user_id', '<>', $user_id)
                ->where('state', self::STATE_PUBLISHED);

        // Starred by user conversations.
        } elseif ($folder->type == Folder::TYPE_STARRED) {
            $starred_conversation_ids = self::getUserStarredConversationIds($folder->mailbox_id, $user_id);
            $query_conversations = self::whereIn('id', $starred_conversation_ids);

        // Conversations are connected to folder via conversation_folder table.
        } elseif ($folder->isIndirect()) {
            $query_conversations = self::select('conversations.*')
                //->where('conversations.mailbox_id', $folder->mailbox_id)
                ->join('conversation_folder', 'conversations.id', '=', 'conversation_folder.conversation_id')
                ->where('conversation_folder.folder_id', $folder->id);
            if ($folder->type != Folder::TYPE_DRAFTS) {
                $query_conversations->where('state', self::STATE_PUBLISHED);
            }

        // Deleted.
        } elseif ($folder->type == Folder::TYPE_DELETED) {
            $query_conversations = $folder->conversations()->where('state', self::STATE_DELETED);

        // Everything else.
        } else {
            $query_conversations = $folder->conversations()->where('state', self::STATE_PUBLISHED);
        }

        $assignee_condition_applied = false;

        // If show only assigned to the current user conversations.
        if (!\Helper::isConsole()
            && $user_id
            && $user = auth()->user()
        ) {
            if ($user->id == $user_id && $user->canSeeOnlyAssignedConversations()) {
                if ($folder->type != Folder::TYPE_DRAFTS) {
                    $assignee_condition_applied = \Eventy::filter('folder.only_assigned_condition', false, $query_conversations, $user_id);
                    if (!$assignee_condition_applied) {
                        $query_conversations->where('user_id', '=', $user_id);
                        $assignee_condition_applied = true;
                    }
                } else {
                    $query_conversations->where('user_id', '=', $user_id)
                        ->orWhere('created_by_user_id', '=', $user_id);
                }
            }
        }

        if ($folder->type == Folder::TYPE_MINE && !$assignee_condition_applied) {
            $query_conversations->where('user_id', $user_id);
        }

        return \Eventy::filter('folder.conversations_query', $query_conversations, $folder, $user_id);
    }

    /**
     * Replace vars in signature.
     * `data` contains extra info which can be used to build signature.
     */
    public function getSignatureProcessed($data = [], $escape = false)
    {
        $replaced_text = $this->replaceTextVars($this->mailbox->signature, $data, $escape);

        // https://github.com/freescout-helpdesk/freescout/security/advisories/GHSA-fffc-phh8-5h4v
        $replaced_text = \Helper::stripDangerousTags($replaced_text);

        return \Eventy::filter('conversation.signature_processed', $replaced_text, $this, $data, $escape);
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

        if (!$customer_email) {
            $customer_email = $customer->getMainEmail();
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

        foreach ($this->folders as $folder) {
            // Process indirect folders.
            if (!in_array($folder->type, Folder::$indirect_types)) {
                continue;
            }
            // Remove conversation from the folder.
            $this->removeFromFolder($folder->type, $folder->user_id);
            if ($folder->type == Folder::TYPE_STARRED) {
                self::clearStarredByUserCache($folder->user_id, $this->mailbox_id);
            }
        }

        // Remember original mailbox ID.
        $this->setMeta('orig_mailbox_id', $this->mailbox_id);
        // We don't know how to replace $this->mailbox object.
        $this->mailbox_id = $mailbox->id;
        // Check assignee.
        if ($this->user_id && !in_array($this->user_id, $mailbox->userIdsHavingAccess())) {
            // Assign conversation to the user who moved it.
            $this->user_id = $user->id;
        }
        $this->updateFolder($mailbox);
        $this->save();

        foreach ($this->folders as $folder) {
            // Process indirect folders.
            if (!in_array($folder->type, Folder::$indirect_types)) {
                continue;
            }
            // If user has access to the new mailbox,
            // move conversation to the same folder in the new mailbox.
            if ($folder->user_id) {
                if ($folder->user->hasAccessToMailbox($mailbox->id)) {
                    foreach ($mailbox->folders as $mailbox_folder) {
                        if ($mailbox_folder->type == $folder->type) {
                            $this->addToFolder($folder->type, $folder->user_id);
                            if ($folder->type == Folder::TYPE_STARRED) {
                                self::clearStarredByUserCache($folder->user_id, $mailbox->id);
                            }
                            break;
                        }
                    }
                }
            } else {
                foreach ($mailbox->folders as $mailbox_folder) {
                    if ($mailbox_folder->type == $folder->type) {
                        $this->addToFolder($folder->type, $folder->user_id);
                        break;
                    }
                }
            }
        }

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
     * Merge conversations
     */
    public function mergeConversations($second_conversation, $user)
    {
        // Move all threads from old to new conversation.
        foreach ($second_conversation->threads as $thread) {
            $thread->conversation_id = $this->id;
            $thread->setMeta(Thread::META_PREV_CONVERSATION, $second_conversation->id);
            $thread->save();
        }

        // Add record to the new conversation.
        Thread::create($this, Thread::TYPE_LINEITEM, '', [
            'created_by_user_id' => $user->id,
            'user_id'     => $this->user_id,
            'state'       => Thread::STATE_PUBLISHED,
            'action_type' => Thread::ACTION_TYPE_MERGED,
            'source_via'  => Thread::PERSON_USER,
            'source_type' => Thread::SOURCE_TYPE_WEB,
            'customer_id' => $this->customer_id,
            'meta'        => [Thread::META_MERGED_WITH_CONV => $second_conversation->id],
        ]);

        // Add record to the old conversation.
        Thread::create($second_conversation, Thread::TYPE_LINEITEM, '', [
            'created_by_user_id' => $user->id,
            'user_id'     => $second_conversation->user_id,
            'state'       => Thread::STATE_PUBLISHED,
            'action_type' => Thread::ACTION_TYPE_MERGED,
            'source_via'  => Thread::PERSON_USER,
            'source_type' => Thread::SOURCE_TYPE_WEB,
            'customer_id' => $second_conversation->customer_id,
            'meta'        => [Thread::META_MERGED_INTO_CONV => $this->id],
        ]);

        if ($second_conversation->has_attachments && !$this->has_attachments) {
            $this->has_attachments = true;
            $this->save();
        }

        // Move star mark.
        $mailbox_star_folders = Folder::where('mailbox_id', $second_conversation->mailbox_id)
            ->where('type', Folder::TYPE_STARRED)
            ->get();

        $conv_star_folder_ids = ConversationFolder::select('folder_id')
            ->whereIn('folder_id', $mailbox_star_folders->pluck('id'))
            ->where('conversation_id', $second_conversation->id)
            ->pluck('folder_id');

        foreach ($conv_star_folder_ids as $conv_star_folder_id) {
            $folder = $mailbox_star_folders->find($conv_star_folder_id);
            if ($folder->user) {
                $this->star($folder->user);
                $second_conversation->unstar($folder->user);
            }
        }

        // Delete old conversation.
        $second_conversation->deleteToFolder($user);

        // Update counters.
        $this->mailbox->updateFoldersCounters();
        if ($this->mailbox_id != $second_conversation->mailbox_id) {
            $second_conversation->mailbox->updateFoldersCounters();
        }

        \Eventy::action('conversation.merged', $this, $second_conversation, $user);

        return true;
    }

    public function star($user)
    {
        $this->addToFolder(Folder::TYPE_STARRED, $user->id);
        self::clearStarredByUserCache($user->id, $this->mailbox_id);
        $this->mailbox->updateFoldersCounters(Folder::TYPE_STARRED);
    }

    public function unstar($user)
    {
        $this->removeFromFolder(Folder::TYPE_STARRED, $user->id);
        self::clearStarredByUserCache($user->id, $this->mailbox_id);
        $this->mailbox->updateFoldersCounters(Folder::TYPE_STARRED);
    }

    /**
     * Get all users for conversations in one query.
     */
    public static function loadUsers($conversations)
    {
        $user_ids = $conversations->pluck('user_id')->unique()->toArray();
        if (!$user_ids || (count($user_ids) == 1 && empty($user_ids[0]))) {
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

    /**
     * Load mailboxes.
     */
    public static function loadMailboxes($conversations)
    {
        $ids = $conversations->pluck('mailbox_id')->unique()->toArray();
        if (!$ids) {
            return;
        }

        $mailboxes = Mailbox::whereIn('id', $ids)->get();
        if (!$mailboxes) {
            return;
        }

        foreach ($conversations as $conversation) {
            if (empty($conversation->mailbox_id)) {
                continue;
            }
            foreach ($mailboxes as $mailbox) {
                if ($mailbox->id == $conversation->mailbox_id) {
                    $conversation->mailbox = $mailbox;

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
    public function addToFolder($folder_type, $user_id = null)
    {
        // Find folder.
        $folder_query = Folder::where('mailbox_id', $this->mailbox_id)
                    ->where('type', $folder_type);
        if ($user_id) {
            $folder_query->where('user_id', $user_id);
        }
        $folder = $folder_query->first();

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
        
        return true;
    }

    /**
     * When removing from Starred folder, don't forget to clear cache using clearStarredByUserCache()
     */
    public function removeFromFolder($folder_type, $user_id = null)
    {
        // Find folder
        $folder_query = Folder::where('mailbox_id', $this->mailbox_id)
                    ->where('type', $folder_type);
        
        if ($user_id) {
            $folder_query->where('user_id', $user_id);
        }
        $folder = $folder_query->first();

        if (!$folder) {
            return false;
        }

        $this->folders()->detach($folder->id);
        $folder->updateCounters();

        return true;
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
            // For phone conversations.
            if (empty($this->$waiting_since_field)) {
                $waiting_since_field = 'updated_at';
            }
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
     * Get emails which should be excluded from CC and BCC.
     */
    public function getExcludeArray($mailbox = null)
    {
        if (!$mailbox) {
            $mailbox = $this->mailbox;
        }
        $customer_emails = [$this->customer_email];
        if (strstr($this->customer_email ?? '', ',')) {
            // customer_email contains mutiple addresses (when new conversation for multiple recipients created)
            $customer_emails = explode(',', $this->customer_email);
        }
        return array_merge($mailbox->getEmails(), $customer_emails);
    }

    /**
     * Is it an email conversation.
     */
    public function isEmail()
    {
        return ($this->type == self::TYPE_EMAIL);
    }

    /**
     * Is it as phone conversation.
     */
    public function isPhone()
    {
        return ($this->type == self::TYPE_PHONE);
    }

    /**
     * Is it as custom conversation.
     */
    public function isCustom()
    {
        return ($this->type == self::TYPE_CUSTOM);
    }

    /**
     * Is it as chat conversation.
     */
    public function isChat()
    {
        return ($this->type == self::TYPE_CHAT);
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

    public function changeSubject($new_subject, $user = null)
    {
        $prev_subject = $this->subject;

        $this->subject = $new_subject;
        $this->save();

        \Eventy::action('conversation.subject_changed', $this, $user, $prev_subject);
    }

    public function changeState($new_state, $user = null)
    {
        if (!array_key_exists($new_state, self::$states)) {
            return;
        }
        
        $prev_state = $this->state;

        $this->state = $new_state;
        $this->save();

        \Eventy::action('conversation.state_changed', $this, $user, $prev_state);
    }

    public function changeStatus($new_status, $user, $create_thread = true)
    {
        if (!array_key_exists($new_status, self::$statuses)) {
            return;
        }
        
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

        if ($create_thread) {
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
        }

        event(new ConversationUserChanged($this, $user));
        \Eventy::action('conversation.user_changed', $this, $user, $prev_user_id);
    }

    public function deleteToFolder($user, $update_folders_counters = true)
    {
        //$folder_id = $this->getCurrentFolder();

        $prev_state = $this->state;
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

        // Recalculate only old and new folders.
        if ($update_folders_counters) {
            $this->mailbox->updateFoldersCounters();
        }

        \Eventy::action('conversation.deleted', $this, $user);
        \Eventy::action('conversation.state_changed', $this, $user, $prev_state);
    }

    public function deleteForever()
    {
        self::deleteConversationsForever([$this->id]);
    }

    public static function deleteConversationsForever($conversation_ids)
    {
        \Eventy::action('conversations.before_delete_forever', $conversation_ids);

        //$conversation_ids = $conversations->pluck('id')->toArray();
        for ($i=0; $i < ceil(count($conversation_ids) / \Helper::IN_LIMIT); $i++) { 

            $ids = array_slice($conversation_ids, $i*\Helper::IN_LIMIT, \Helper::IN_LIMIT);

            // Delete attachments.
            $thread_ids = Thread::whereIn('conversation_id', $ids)->pluck('id')->toArray();
            Attachment::deleteByThreadIds($thread_ids);

            // Observers do not react on this kind of deleting.

            // Delete threads.
            Thread::whereIn('conversation_id', $ids)->delete();

            // Delete followers.
            Follower::whereIn('conversation_id', $ids)->delete();

            // Delete conversations.
            Conversation::whereIn('id', $ids)->delete();
            ConversationFolder::whereIn('conversation_id', $ids)->delete();
        }
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

    public function forward($user, $body, $to = '', $data = [], $include_attachments = false)
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
        $forwarded_conversation->updateFolder();
        $forwarded_conversation->save();

        $forwarded_thread = $thread->replicate();

        // Set forwarding meta data.
        $thread->subtype = Thread::SUBTYPE_FORWARD;
        $thread->setMeta(Thread::META_FORWARD_CHILD_CONVERSATION_NUMBER, $forwarded_conversation->number);
        $thread->setMeta(Thread::META_FORWARD_CHILD_CONVERSATION_ID, $forwarded_conversation->id);

        $thread->save();

        // Save forwarded thread.
        $forwarded_thread->conversation_id = $forwarded_conversation->id;
        $forwarded_thread->type = Thread::TYPE_MESSAGE;
        $forwarded_thread->subtype = null;
        $forwarded_thread->setTo($to);
        // if ($attachments_info['has_attachments']) {
        //     $forwarded_thread->has_attachments = true;
        // }
        $forwarded_thread->setMeta(Thread::META_FORWARD_PARENT_CONVERSATION_NUMBER, $this->number);
        $forwarded_thread->setMeta(Thread::META_FORWARD_PARENT_CONVERSATION_ID, $this->id);
        $forwarded_thread->setMeta(Thread::META_FORWARD_PARENT_THREAD_ID, $thread->id);
        $forwarded_thread->save();

        // Add attachments if needed.
        if ($include_attachments) {

            $replies = $this->getReplies();

            $has_attachments = false;
            foreach ($replies as $reply_thread) {
                
                $thread_has_attachments = false;
                foreach ($reply_thread->attachments as $attachment) {
                    $new_attachment = $attachment->replicate();
                    $new_attachment->thread_id = $forwarded_thread->id;
                    // We need to copy attachment file, because conversations
                    // can be deleted along with attachments.
                    $new_attachment->save();

                    try {
                        $attachment_file = new \Illuminate\Http\UploadedFile(
                            $attachment->getLocalFilePath(), $attachment->file_name,
                            null, null, true
                        );

                        $file_info = Attachment::saveFileToDisk($new_attachment, $new_attachment->file_name, '', $attachment_file);

                        if (!empty($file_info['file_dir'])) {
                            $new_attachment->file_dir = $file_info['file_dir'];
                            $new_attachment->save();

                            $has_attachments = true;
                            $thread_has_attachments = true;
                        }
                    } catch (\Exception $e) {
                        \Helper::logException($e);
                    }
                }
                if ($thread_has_attachments) {
                    $forwarded_thread->has_attachments = true;
                    $forwarded_thread->save();
                }
            }
            if ($has_attachments) {
                $forwarded_conversation->has_attachments = true;
                $forwarded_conversation->save();
            }
        }

        // Update folders counters
        $this->mailbox->updateFoldersCounters();

        // Notifications to users not sent.
        event(new UserAddedNote($this, $thread));
        // To send email with forwarded conversation.
        event(new UserReplied($forwarded_conversation, $forwarded_thread));
        \Eventy::action('conversation.user_forwarded', $this, $thread, $forwarded_conversation, $forwarded_thread);
    }

    // public function getEmailHistoryCode()
    // {
    //     return self::$email_history_codes[(int)$this->email_history] ?? 'global';
    // }

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

    /**
     * Create conversation.
     *
     * $threads should go from old to new.
     */
    public static function create($data, $threads, $customer)
    {
        // Detect source_via.
        $source_via = $data['source_via'] ?? 0;
        if (!$source_via && !empty($threads[0])) {
            if (!empty($threads[0]['type']) && $threads[0]['type'] == Thread::TYPE_CUSTOMER) {
                $source_via = self::PERSON_CUSTOMER;
            } else {
                $source_via = self::PERSON_USER;
            }
        }

        $conversation = new Conversation();
        $conversation->type = $data['type'];
        $conversation->subject = $data['subject'];
        $conversation->mailbox_id = $data['mailbox_id'];
        $conversation->source_via = $source_via;
        $conversation->source_type = $data['source_type'];
        $conversation->customer_id = $customer->id;
        $conversation->customer_email = $customer->getMainEmail().'';
        $conversation->state = $data['state'] ?? Conversation::STATE_PUBLISHED;
        $conversation->imported = (int)($data['imported'] ?? false);
        $conversation->closed_at = $data['closed_at'] ?? null;
        $conversation->channel = $data['channel'] ?? null;
        $conversation->preview = '';

        // Phone conversation is always pending.
        if ($conversation->isPhone()) {
            $conversation->status = Conversation::STATUS_PENDING;
        }

        // Set assignee
        $conversation->user_id = null;
        if (!empty($data['user_id'])) {
            $user_assignee = User::find($data['user_id']);
            if ($user_assignee) {
                $conversation->user_id = $user_assignee->id;
            }
        }

        $conversation->updateFolder();
        $conversation->save();

        // Create threads.
        $threads = array_reverse($threads);
        $thread_created = false;
        $last_customer_id = null;
        $thread_result = null;
        foreach ($threads as $thread) {

            $thread['conversation_id'] = $conversation->id;

            if ($conversation->imported) {
                $thread['imported'] = true;
            }
            if (!empty($data['status'])) {
                $thread['status'] = $data['status'];
            }

            $thread_result = Thread::createExtended($thread, $conversation, $customer, false);
            if ($thread_result) {
                $thread_created = true;
            }
        }

        // If no threads created, delete conversation
        if (!$thread_created) {
            $conversation->delete();
            return false;
        }

        // Restore customer if needed.
        // if ($last_customer_id && $last_customer_id != $customer->id) {
        //     // Otherwise it does not save.
        //     $conversation = $conversation->fresh();
        //     $conversation->customer_id = $customer->id;
        //     $conversation->customer_email = $customer->getMainEmail();
        //     $conversation->save();
        // }

        // Update folders counters
        $conversation->mailbox->updateFoldersCounters();

        return [
            'conversation' => $conversation,
            'thread' => $thread_result
        ];
    }

    public function getChannelName()
    {
        return self::channelCodeToName($this->channel);
    }

    public static function channelCodeToName($channel)
    {
        return \Eventy::filter('channel.name', '', $channel);
    }

    public static function subjectFromText($text)
    {
        return \Helper::textPreview($text, self::SUBJECT_LENGTH);
    }

    public static function refreshConversations($conversation, $thread)
    {
        \App\Events\RealtimeConvNewThread::dispatchSelf($thread);
        \App\Events\RealtimeMailboxNewThread::dispatchSelf($conversation->mailbox_id);
        \App\Events\RealtimeChat::dispatchSelf($conversation->mailbox_id);
    }

    public static function getConvTableSorting($request = null)
    {
        if (!$request) {
            $request = request();
        }

        $result = [
            'sort_by' => 'date',
            'order' => 'desc',
        ];

        $result = \Eventy::filter('conversations.table_sorting', $result);

        if (
            !empty($request->sorting['sort_by']) && !empty($request->sorting['order']) &&
            in_array($request->sorting['sort_by'], ['subject', 'number', 'date']) &&
            in_array($request->sorting['order'], ['asc', 'desc'])
        ) {
            $result['sort_by'] = $request->sorting['sort_by'];
            $result['order'] = $request->sorting['order'];
        }

        return $result;
    }

    public static function search($q, $filters, $user = null, $query_conversations = null, $group_by = [])
    {
        $mailbox_ids = [];

        // Like is case insensitive.
        $like = '%'.mb_strtolower($q).'%';

        if (!$query_conversations) {
            $query_conversations = Conversation::select('conversations.*');
        }
		
        // https://github.com/laravel/framework/issues/21242
        // https://github.com/laravel/framework/pull/27675
        $query_conversations->groupBy(array_merge(['conversations.id'], $group_by));

        if (!empty($filters['mailbox'])) {
            // Check if the user has access to the mailbox.
            if ($user->hasAccessToMailbox($filters['mailbox'])) {
                $mailbox_ids[] = $filters['mailbox'];
            } else {
                unset($filters['mailbox']);
                $mailbox_ids = $user->mailboxesIdsCanView();
            }
        } else {
            // Get IDs of mailboxes to which user has access
            $mailbox_ids = $user->mailboxesIdsCanView();
        }

        $query_conversations->whereIn('conversations.mailbox_id', $mailbox_ids);
        
        $like_op = 'like';
        if (\Helper::isPgSql()) {
            $like_op = 'ilike';
        }

        if ($q) {
            $query_conversations->where(function ($query) use ($like, $filters, $q, $like_op) {

                // It needs to be sanitized to avoid "Numeric value out of range" on PostgreSQL.
                $q_int = (int)$q;
                $q_int = $q_int > \Helper::DB_INT_MAX ? \Helper::DB_INT_MAX : $q_int;

                $query->where('conversations.subject', $like_op, $like)
                    ->orWhere('conversations.customer_email', $like_op, $like)
                    ->orWhere('conversations.'.self::numberFieldName(), $q_int)
                    ->orWhere('conversations.id', $q_int)
					->orWhere('customers.first_name', $like_op, $like)
                    ->orWhere('customers.last_name', $like_op, $like)
                    ->orWhere(\Helper::isPgSql() ? \DB::raw('(customers.first_name || \' \' || customers.last_name)') : \DB::raw('CONCAT(customers.first_name, " ", customers.last_name)'), $like_op, $like)
                    ->orWhere('threads.body', $like_op, $like)
                    ->orWhere('threads.from', $like_op, $like)
                    ->orWhere('threads.to', $like_op, $like)
                    ->orWhere('threads.cc', $like_op, $like)
                    ->orWhere('threads.bcc', $like_op, $like);

                $query = \Eventy::filter('search.conversations.or_where', $query, $filters, $q);
            });
        }

        // Apply search filters.
        if (!empty($filters['assigned'])) {
            if ($filters['assigned'] == self::USER_UNASSIGNED) {
                $filters['assigned'] = null;
            }
            $query_conversations->where('conversations.user_id', $filters['assigned']);
        }
        if (!empty($filters['customer'])) {
            $customer_id = $filters['customer'];
            $query_conversations->where(function ($query) use ($customer_id) {
                $query->where('conversations.customer_id', '=', $customer_id)
                    ->orWhere('threads.created_by_customer_id', '=', $customer_id);
            });
        }
        if (!empty($filters['status'])) {
            if (count($filters['status']) == 1) {
                // = is faster than IN.
                $query_conversations->where('conversations.status', '=', $filters['status'][0]);
            } else {
                $query_conversations->whereIn('conversations.status', $filters['status']);
            }
        }
        if (!empty($filters['state'])) {
            if (count($filters['state']) == 1) {
                // = is faster than IN.
                $query_conversations->where('conversations.state', '=', $filters['state'][0]);
            } else {
                $query_conversations->whereIn('conversations.state', $filters['state']);
            }
        }
        if (!empty($filters['subject'])) {
            $query_conversations->where('conversations.subject', $like_op, '%'.mb_strtolower($filters['subject']).'%');
        }
        if (!empty($filters['attachments'])) {
            $has_attachments = ($filters['attachments'] == 'yes' ? true : false);
            $query_conversations->where('conversations.has_attachments', '=', $has_attachments);
        }
        if (!empty($filters['type'])) {
            $query_conversations->where('conversations.type', '=', $filters['type']);
        }
        if (!empty($filters['body'])) {
            $query_conversations->where('threads.body', $like_op, '%'.mb_strtolower($filters['body']).'%');
        }
        if (!empty($filters['number'])) {
            $query_conversations->where('conversations.'.self::numberFieldName(), '=', $filters['number']);
        }
        if (!empty($filters['following'])) {
            if ($filters['following'] == 'yes') {
                $query_conversations->join('followers', function ($join) {
                    $join->on('followers.conversation_id', '=', 'conversations.id');
                    $join->where('followers.user_id', auth()->user()->id);
                });
            }
        }
        if (!empty($filters['id'])) {
            $query_conversations->where('conversations.id', '=', $filters['id']);
        }
        if (!empty($filters['after'])) {
            $query_conversations->where('conversations.created_at', '>=', date('Y-m-d 00:00:00', strtotime($filters['after'])));
        }
        if (!empty($filters['before'])) {
            $query_conversations->where('conversations.created_at', '<=', date('Y-m-d 23:59:59', strtotime($filters['before'])));
        }

        // Join tables if needed.
        $query_sql = $query_conversations->toSql();
        if (!self::queryContainsStr($query_sql, '`threads`.`conversation_id`')) {
            $query_conversations->join('threads', function ($join) {
                $join->on('conversations.id', '=', 'threads.conversation_id');
            });
        }

        if (!self::queryContainsStr($query_sql, '`customers`.`id`')) {
            $query_conversations->leftJoin('customers', 'conversations.customer_id', '=' ,'customers.id');
        }

        $query_conversations = \Eventy::filter('search.conversations.apply_filters', $query_conversations, $filters, $q);

        $sorting = Conversation::getConvTableSorting();
        if ($sorting['sort_by'] == 'date') {
            $sorting['sort_by'] = 'last_reply_at';
        }
        $query_conversations->orderBy($sorting['sort_by'], $sorting['order']);

        return $query_conversations;
    }

    public function getNumberAttribute($value)
    {
        if (self::$custom_number_cache === null) {
            self::$custom_number_cache = config('app.custom_number');
        }
        if (self::$custom_number_cache) {
            return $value;
        } else {
            return $this->id;
        }
    }

    public static function numberFieldName()
    {
        if (self::$custom_number_cache === null) {
            self::$custom_number_cache = config('app.custom_number');
        }
        if (self::$custom_number_cache) {
            return 'number';
        } else {
            return 'id';
        }
    }

    /**
     * Get meta value.
     */
    public function getMeta($key, $default = null)
    {
        if (isset($this->meta[$key])) {
            return $this->meta[$key];
        } else {
            return $default;
        }
    }

    /**
     * Set meta value.
     */
    public function setMeta($key, $value, $save = false)
    {
        $meta = $this->meta;
        $meta[$key] = $value;
        $this->meta = $meta;

        if ($save) {
            $this->save();
        }
    }

    public static function updatePreview($conversation_id)
    {
        // Get last suitable thread.
        $thread = Thread::where('conversation_id', $conversation_id)
            ->whereIn('type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE, Thread::TYPE_NOTE])
            ->where('state', Thread::STATE_PUBLISHED)
            ->where(function ($query) {
                $query->where('subtype', null)
                    ->orWhere('subtype', '!=', Thread::SUBTYPE_FORWARD);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($thread) {
            $thread->conversation->setPreview($thread->body);
            $thread->conversation->save();
        }
    }

    public function isInChatMode()
    {
        return $this->isChat() && \Helper::isChatMode() && \Route::is('conversations.view');
    }

    public static function getChats($mailbox_id, $offset = 0, $limit = self::CHATS_LIST_SIZE+1)
    {
        $chats = Conversation::where('type', self::TYPE_CHAT)
            ->where('mailbox_id', $mailbox_id)
            ->where('state', self::STATE_PUBLISHED)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PENDING])
            ->orderBy('last_reply_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Preload customers.
        if (count($chats)) {
            self::loadCustomers($chats);
        }

        return $chats;
    }

    public static function queryContainsStr($query_str, $substr)
    {
        $regex = preg_quote($substr);
        $regex = str_replace('`', '[`"]', $regex);

        return preg_match('#'.$regex.'#', $query_str);
    }

    public function userHasAccessToMailbox($user_id)
    {
        return MailboxUser::where('mailbox_id', $this->mailbox_id)
            ->where('user_id', $user_id)
            ->exists();
    }

    public function chatShouldStartNew($mailbox = null)
    {
        if (!$mailbox) {
            $mailbox = $this->mailbox;
        }
        if (!empty($mailbox->meta['chat_start_new'])
            && ($this->status == Conversation::STATUS_CLOSED
                || $this->state == Conversation::STATE_DELETED)
        ) {
            return true;
        } else {
            return false;
        }
    }
}
