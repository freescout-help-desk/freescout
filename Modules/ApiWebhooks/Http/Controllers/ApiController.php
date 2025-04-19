<?php

namespace Modules\ApiWebhooks\Http\Controllers;

use App\Attachment;
use App\Conversation;
use App\Customer;
use App\Email;
use App\Mailbox;
use App\Folder;
use App\Thread;
use App\User;
use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\UserAddedNote;
use App\Events\CustomerCreatedConversation;
use App\Events\UserCreatedConversation;
use App\Events\UserCreatedConversationDraft;
use App\Events\UserCreatedThreadDraft;
use App\Events\UserReplied;
use App\Events\CustomerReplied;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ApiController extends Controller
{
    /**
     * HTTP status codes.
     */
    const STATUS_CREATED = 201;
    const STATUS_BAD_REQUEST = 400;

    /**
     * Default items per page.
     */
    const PAGE_SIZE = 50;

    /**
     * Default sort order.
     */
    const SORT_ORDER = 'desc';

    /**
     * Error codes.
     */
    //const ERROR_CODE_BAD_REQUEST = 'BAD REQUEST';

    /**
     * @group Conversations
     *
     * List Conversations
     *
     * Request parameters can be used to filter conversations. By default conversations are sorted by createdAt (from newest to oldest): ?sortField=createdAt&sortOrder=desc
     *
     * @response 201 {
     *     "_embedded": {
     *         "conversations": [
     *             {
     *               "id" : 1,
     *                "number" : 3,
     *                "threads" : 2,
     *                "type" : "email",
     *                    "folderId" : 11,
     *                "status" : "closed",
     *                "state" : "published",
     *                "subject" : "Refund",
     *                "preview" : "Could you please refund my recent payment...",
     *                "mailboxId" : 15,
     *                "assignee" : {
     *                  "id" : 9,
     *                  "type" : "user",
     *                  "firstName" : "John",
     *                  "lastName" : "Doe",
     *                  "email" : "johndoe@example.org"
     *                },
     *                "createdBy" : {
     *                  "id" : 11,
     *                  "type" : "customer",
     *                  "email" : "customer@example.org"
     *                },
     *                "createdAt" : "2020-03-15T22:46:22Z",
     *                "updatedAt" : "2020-03-15T22:46:22Z",
     *                "closedBy" : 14,
     *                "closedByUser" : {
     *                  "id" : 14,
     *                  "type" : "user",
     *                  "firstName" : "John",
     *                  "lastName" : "Doe",
     *                  "photoUrl" : "https://support.example.org/storage/users/5a10629fd2bae86563892b191f6677e7.jpg",
     *                  "email" : "johndoe@example.org"
     *                },
     *                "closedAt" : "2020-03-16T14:07:23Z",
     *                "userUpdatedAt" : "2020-03-16T14:07:23Z",
     *                "customerWaitingSince" : {
     *                  "time" : "2020-07-24T20:18:33Z",
     *                  "friendly" : "10 hours ago",
     *                  "latestReplyFrom" : "customer"
     *                },
     *                "source" : {
     *                  "type" : "email",
     *                  "via" : "customer"
     *                },
     *                "cc" : [ "fox@example.org" ],
     *                "bcc" : [ "fox@example.org" ],
     *                "customer" : {
     *                  "id" : 91,
     *                  "type" : "customer",
     *                  "firstName" : "Rodney",
     *                  "lastName" : "Robertson",
     *                  "photoUrl" : "https://support.example.org/storage/customers/7a10629fd2bae86563892b191f6677e7.jpg",
     *                  "email" : "rodney@example.org"
     *                },
     *                "customFields" : [],
     *                "_embedded" : {
     *                  "threads" : []
     *                }
     *            }
     *         ]
     *     },
     *     "page": {
     *         "size": 50,
     *         "totalElements": 1,
     *         "totalPages": 1,
     *         "number": 1
     *     }
     * }
     *
     * @queryParam   embed Pass comma separated values to include extra data: threads, timelogs, tags. Example: threads
     * @queryParam   mailboxId Filter conversations from a specific mailbox. Can contain multiple comma separated IDs. Example: 123
     * @queryParam   folderId Filter conversations from a specific folder ID (filtering by Custom Folder ID provided by Custom Folders Module is not possible). Example: 57
     * @queryParam   status Filter conversation by status (defaults to active): active, pending, closed, spam. Can contain multiple comma separated values. Example: active
     * @queryParam   state Filter conversation by state: draft, published, deleted. Example: deleted
     * @queryParam   type Filter conversation by type: email, phone, chat. Example: email
     * @queryParam   assignedTo Filter conversations by assignee id. Pass an empty value in order to get unassigned conversations. Example: 35
     * @queryParam   customerEmail Filter conversations by customer email. Example: john@example.org
     * @queryParam   customerPhone Filter conversations by customer phone number. Example: 777-777-777
     * @queryParam   customerId Filter conversations by customer ID. Example: 17
     * @queryParam   number Look up conversation by conversation number. Example: 359
     * @queryParam   subject Look up conversations containing a text in the subject. Example: test
     * @queryParam   tag Look up conversations by tag. Example: overdue
     * @queryParam   createdByUserId Filter conversations by a user who created it. Example: 9
     * @queryParam   createdByCustomerId Filter conversations by a customer who created it. Example: 10
     * @queryParam   createdSince Return only conversations created after the specified date. Example: 2021-01-07T12:00:03Z
     * @queryParam   updatedSince Return only conversations modified after the specified date. Example: 2021-01-07T12:00:03Z
     * @queryParam   sortField Sort the result by specified field: createdAt, mailboxId, number, subject, updatedAt, waitingSince. Example: updatedAt
     * @queryParam   sortOrder Sort order: desc (default), asc. Example: asc
     * @queryParam   page Page number. Example: 1
     * @queryParam   pageSize Page size (defaults to 50). Example: 100
     */
    public function listConversations(Request $request)
    {
        // Laravel automatically adds HEAD to the get() routes,
        // so we need to check it manually.
        if (!$request->isMethod('get')) {
            return $this->methodNotAllowed();
        }

        $response_data = [
            '_embedded' => [
                'conversations' => []
            ]
        ];

        // sortField.
        $sort_field = 'created_at';
        $sort_fields = [
            'createdAt' => 'conversations.created_at',
            //'customerEmail' => 'customer_email',
            'mailboxId' => 'mailbox_id',
            'number' => Conversation::numberFieldName(),
            //'status' => 'status',
            'subject' => 'subject',
            'updatedAt' => 'conversations.updated_at',
            'waitingSince' => 'last_reply_at',
        ];
        if (!empty($request->sortField) && array_key_exists($request->sortField, $sort_fields)) {
            $sort_field = $sort_fields[$request->sortField];
        }
        // sortOrder.
        $sort_order = self::SORT_ORDER;
        if (!empty($request->sortOrder) && in_array($request->sortOrder, ['desc', 'asc'])) {
            $sort_order = $request->sortOrder;
        }

        $query = Conversation::select(['conversations.*'])
            ->orderBy($sort_field, $sort_order);

        if (!empty($request->mailboxId)) {
            $mailbox_ids = explode(',', $request->mailboxId);

            if (count($mailbox_ids) == 1) {
                $query->where('mailbox_id', $request->mailboxId);
            } else {
                $query->whereIn('mailbox_id', $mailbox_ids);
            }
        }
        if (!empty($request->customerEmail)) {
            $query->where('customer_email', $request->customerEmail);
        }
        if (!empty($request->customerPhone)) {
            $query->leftJoin('customers', 'conversations.customer_id', '=' ,'customers.id');
            $query->where('customers.phones', 'like', '%'.\Helper::phoneToNumeric($request->customerPhone).'%');
        }
        if (!empty($request->customerId)) {
            $query->where('customer_id', $request->customerId);
        }
        if (!empty($request->folderId)) {
            $query->where('folder_id', $request->folderId);
        }
        if (!empty($request->status)) {
            $statuses = explode(',', $request->status);

            if (count($statuses) == 1) {
                if (in_array($request->status, Conversation::$statuses)) {
                    $status = array_flip(Conversation::$statuses)[$request->status];
                    $query->where('status', $status);
                }
            } else {
                $statuses_decoded = [];
                foreach ($statuses as $status) {
                    $status = trim($status);
                    if (in_array($status, Conversation::$statuses)) {
                        $statuses_decoded[] = array_flip(Conversation::$statuses)[$status];
                    }
                }
                if (count($statuses_decoded)) {
                    $query->whereIn('status', $statuses_decoded);
                }
            }
        }
        if (!empty($request->state) && in_array($request->state, Conversation::$states)) {
            $state = array_flip(Conversation::$states)[$request->state];
            $query->where('conversations.state', $state);
        }
        if (!empty($request->type) && in_array($request->type, Conversation::$types)) {
            $type = array_flip(Conversation::$types)[$request->type];
            $query->where('type', $type);
        }
        if ($request->has('assignedTo')) {
            $query->where('user_id', $request->assignedTo);
        }
        if (!empty($request->updatedSince)) {
            $query->where('conversations.updated_at', '>=', self::utcStringToServerDate($request->updatedSince));
        }
        if (!empty($request->createdSince)) {
            $query->where('conversations.created_at', '>=', self::utcStringToServerDate($request->createdSince));
        }
        if (!empty($request->number)) {
            $query->where(Conversation::numberFieldName(), $request->number);
        }
        if (!empty($request->subject)) {
            $query->where('subject', \Helper::isPgSql() ? 'ilike' : 'like', '%'.$request->subject.'%');
        }
        if ($request->has('createdByUserId')) {
            $query->where('created_by_user_id', $request->createdByUserId);
        }
        if ($request->has('createdByCustomerId')) {
            $query->where('created_by_customer_id', $request->createdByCustomerId);
        }
        if (!empty($request->tag) && \Module::isActive('tags')) {
            $tag_name = \Modules\Tags\Entities\Tag::normalizeName($request->tag);

            if ($tag_name) {
                $tag_id = (int)\Modules\Tags\Entities\Tag::where('name', $tag_name)->value('id');

                $query->join('conversation_tag', function ($join) use ($tag_id) {
                    $join->on('conversations.id', '=', 'conversation_tag.conversation_id')
                        ->where('conversation_tag.tag_id', $tag_id);
                });
            }
        }

        $conversations = $query->paginate($request->pageSize ?: self::PAGE_SIZE);
        $extra_data = ['without_threads' => true];

        if (!empty($request->embed) ) {

            $embeds = explode(',', $request->embed);

            if (in_array('threads', $embeds)) {
                $extra_data['without_threads'] = false;
            }

            if (in_array('timelogs', $embeds)) {
                $extra_data['include_timelogs'] = true;
            }

            if (in_array('tags', $embeds)) {
                $extra_data['include_tags'] = true;
            }
        }

        if (count($conversations) > 1) {
            Conversation::loadCustomers($conversations);
            Conversation::loadUsers($conversations);
        }

        foreach ($conversations as $conversation) {
            $response_data['_embedded']['conversations'][] = \ApiWebhooks::formatEntity($conversation, true, '', $extra_data);
        }

        $response_data = self::addPageDataToResponse($response_data, $request, $conversations);

        return $this->getApiResponse($response_data);
    }

    /**
     * @group Conversations
     *
     * Get Conversation
     *
     * @response {
     *  "id" : 1,
     *  "number" : 3,
     *  "threadsCount" : 2,
     *  "type" : "email",
     *  "folderId" : 11,
     *  "status" : "closed",
     *  "state" : "published",
     *  "subject" : "Refund",
     *  "preview" : "Could you please refund my recent payment...",
     *  "mailboxId" : 15,
     *  "assignee" : {
     *    "id" : 9,
     *    "type" : "user",
     *    "firstName" : "John",
     *    "lastName" : "Doe",
     *    "email" : "johndoe@example.org"
     *  },
     *  "createdBy" : {
     *    "id" : 11,
     *    "type" : "customer",
     *    "email" : "customer@example.org"
     *  },
     *  "createdAt" : "2020-03-15T22:46:22Z",
     *  "updatedAt" : "2020-03-15T22:46:22Z",
     *  "closedBy" : 14,
     *  "closedByUser" : {
     *    "id" : 14,
     *    "type" : "user",
     *    "firstName" : "John",
     *    "lastName" : "Doe",
     *    "photoUrl" : "https://support.example.org/storage/users/5a10629fd2bae86563892b191f6677e7.jpg",
     *    "email" : "johndoe@example.org"
     *  },
     *  "closedAt" : "2020-03-16T14:07:23Z",
     *  "userUpdatedAt" : "2020-03-16T14:07:23Z",
     *  "customerWaitingSince" : {
     *    "time" : "2020-07-24T20:18:33Z",
     *    "friendly" : "10 hours ago",
     *    "latestReplyFrom" : "customer"
     *  },
     *  "source" : {
     *    "type" : "email",
     *    "via" : "customer"
     *  },
     *  "cc" : [ "fox@example.org" ],
     *  "bcc" : [ "fox@example.org" ],
     *  "customer" : {
     *    "id" : 91,
     *    "type" : "customer",
     *    "firstName" : "Rodney",
     *    "lastName" : "Robertson",
     *    "photoUrl" : "https://support.example.org/storage/customers/7a10629fd2bae86563892b191f6677e7.jpg",
     *    "email" : "rodney@example.org"
     *  },
     *  "customFields" : [ {
     *      "id": 22,
     *      "name": "Amount",
     *      "value": "7",
     *      "text": ""
     *    }, {
     *      "id": 23,
     *      "name": "Currency",
     *      "value": "1",
     *      "text": "USD"
     *  } ],
     *  "_embedded" : {
     *    "threads" : [ {
     *      "id" : 17,
     *      "type" : "customer",
     *      "status" : "active",
     *      "state" : "published",
     *      "action" : {
     *        "type" : "changed-ticket-assignee",
     *        "text" : "John Doe assigned conversation to Mark",
     *        "associatedEntities" : { }
     *      },
     *      "body" : "Thank you very much!",
     *      "source" : {
     *        "type" : "email",
     *        "via" : "customer"
     *      },
     *      "customer" : {
     *        "id" : 91,
     *        "type" : "customer",
     *        "firstName" : "Rodney",
     *        "lastName" : "Robertson",
     *        "photoUrl" : "https://support.example.org/storage/customers/7a10629fd2bae86563892b191f6677e7.jpg",
     *        "email" : "rodney@example.org"
     *      },
     *      "createdBy" : {
     *        "id" : 91,
     *        "type" : "customer",
     *        "firstName" : "Rodney",
     *        "lastName" : "Robertson",
     *        "photoUrl" : "https://support.example.org/storage/customers/7a10629fd2bae86563892b191f6677e7.jpg",
     *        "email" : "rodney@example.org"
     *      },
     *      "assignedTo" : {
     *        "id" : 14,
     *        "type" : "user",
     *        "firstName" : "John",
     *        "lastName" : "Doe",
     *        "photoUrl" : "https://support.example.org/storage/users/5a10629fd2bae86563892b191f6677e7.jpg",
     *        "email" : "johndoe@example.org"
     *      },
     *      "to" : [ "test@example.org" ],
     *      "cc" : [ "fox@example.org" ],
     *      "bcc" : [ "fox@example.org" ],
     *      "createdAt" : "2020-06-05T20:18:33Z",
     *      "openedAt" : "2020-06-07T10:01:25Z",
     *       "_embedded": {
     *           "attachments": [
     *               {
     *                   "id": 71,
     *                   "fileName": "example.pdf",
     *                   "fileUrl": "https://support.example.org/storage/attachment/7/3/1/example.pdf?id=71&token=c5135450a05cc47d7aa3333d8a3e7831",
     *                   "mimeType": "application/pdf",
     *                   "size": 2331
     *               }
     *           ]
     *       }
     *    } ],
     *    "timelogs": [
     *      {
     *          "id": 498,
     *          "conversationStatus": "pending",
     *          "userId": 1,
     *          "timeSpent": 219,
     *          "paused": false,
     *          "finished": true,
     *          "createdAt": "2021-04-21T13-24-01Z",
     *          "updatedAt": "2021-04-21T13-43-10Z"
     *      },
     *      {
     *          "id": 497,
     *          "conversationId": 1984,
     *          "conversationStatus": "active",
     *          "userId": 1,
     *          "timeSpent": 711,
     *          "paused": false,
     *          "finished": true,
     *          "createdAt": "2021-04-21T13-22-09Z",
     *          "updatedAt": "2021-04-21T13-43-10Z"
     *      }
     *    ],
     *    "tags": [
     *      {
     *          "id": 57,
     *          "name": "overdue"
     *      },
     *      {
     *          "id": 39,
     *          "name": "refund"
     *      }
     *    ]
     *  }
     * }
     *
     * @queryParam   embed Pass comma separated values to include extra data: threads, timelogs, tags. Default: threads. Example: threads
     */
    public function getConversation(Request $request, $conversationId)
    {
        // Laravel automatically adds HEAD to the get() routes,
        // so we need to check it manually.
        if (!$request->isMethod('get')) {
            return $this->methodNotAllowed();
        }

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        $extra_data = [];
        if (!empty($request->embed)) {
            $embeds = explode(',', $request->embed);

            $extra_data['without_threads'] = true;
            if (in_array('threads', $embeds)) {
                $extra_data['without_threads'] = false;
            }

            if (in_array('timelogs', $embeds)) {
                $extra_data['include_timelogs'] = true;
            }

            if (in_array('tags', $embeds)) {
                $extra_data['include_tags'] = true;
            }
        }

        $response = \ApiWebhooks::formatEntity($conversation, true, '', $extra_data);

        return $this->getApiResponse($response);
    }

    /**
     * @group Conversations
     *
     * Update Conversation
     *
     * Order of passed parameters (status, assignTo, etc.) determines the order in which changes are made.
     *
     * @response 204 {
     *   "headers": "HTTP/1.1 204 No Content"
     * }
     *
     * @bodyParam  byUser number ID of the user updating the conversation. Required when changing: "status", "assignTo" or "mailboxId". Example: 33
     * @bodyParam  status string Change conversation status: active, pending, closed, spam. Example: active
     * @bodyParam  assignTo number Change conversation assignee to the user with the specified ID. Example: 15
     * @bodyParam  mailboxId number Move conversation to the mailbox with the specified ID. Example: 1
     * @bodyParam  customerId number Change conversation customer to the customer with the specified ID. Example: 7
     * @bodyParam  subject number Change conversation subject. Example: Hi there
     */
    public function updateConversation(Request $request, $conversationId)
    {
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        $data = $request->all();
        $data = self::convertConversationCodes($data);

        $save = false;

        foreach ($data as $param => $value) {
            if ($param == 'byUser') {
                continue;
            }
            $user = null;
            if (in_array($param, ['status', 'assignTo', 'mailboxId'])) {
                if (empty($data['byUser'])) {
                    return $this->getErrorResponse('byUser parameter is required.', 'byUser');
                }
                $user = User::find($data['byUser']);
                if (!$user) {
                    return $this->getErrorResponse('User not found.', 'byUser');
                }
            }
            switch ($param) {
                case 'status':
                    if ($conversation->status != $value) {
                        $conversation->changeStatus($value, $user);
                    }
                    break;

                case 'assignTo':
                    if ($conversation->user_id != $value) {
                        $conversation->changeUser($value, $user);
                    }
                    break;

                case 'mailboxId':
                    if ($conversation->mailbox_id != $value) {
                        $mailbox = Mailbox::find($value);
                        if (!$mailbox) {
                            return $this->getErrorResponse('Mailbox not found.', 'mailboxId');
                        }
                        $conversation->moveToMailbox($mailbox, $user);
                    }
                    break;

                case 'customerId':
                    if ($conversation->customer_id != $value) {
                        $customer = Customer::find($value);
                        if (!$customer) {
                            return $this->getErrorResponse('Customer not found.', 'customerId');
                        }
                        if (empty($user) && !empty($data['byUser'])) {
                            $user = User::find($data['byUser']);
                        }
                        $conversation->changeCustomer('', $customer, $user ?: null);
                    }
                    break;

                case 'subject':
                    if ($conversation->subject != $value) {
                        $conversation->subject = $value;
                        $save = true;
                    }
                    break;
            }
        }

        if ($save) {
            $conversation->save();
        }

        return self::getApiResponse([], 204);
    }

    /**
     * @group Conversations
     *
     * Delete Conversation
     *
     * This method deletes a conversation forever.
     *
     * @response 204 {
     *   "headers": "HTTP/1.1 204 No Content"
     * }
     */
    public function deleteConversation(Request $request, $conversationId)
    {
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        $mailbox = $conversation->mailbox;
        $conversation->deleteForever();
        // Recalculate only old and new folders.
        $mailbox->updateFoldersCounters();

        return self::getApiResponse([], 204);
    }

    /**
     * @group Conversations
     *
     * Create Conversation
     *
     * This method creates a conversation in a mailbox with at least one thread.
     *
     * @response 201 {
     *   "headers": "HTTP/1.1 201 Created\nResource-ID: 35"
     * }
     *
     * @bodyParam  type string required Conversation type: email, phone, chat (after importing "chat" conversation, support agent replies will not reach customer linked to the conversation, as connection to customer's messenger can't be imported). Example: email
     * @bodyParam  mailboxId number required ID of a mailbox where the conversation is being created. Example: 1
     * @bodyParam  subject string required Conversation’s subject. Example: Hi there
     * @bodyParam  customer object required Customer associated with the conversation. Customer object must contain a valid customer id or an email address: { "id": 123 } or { "email": "mark@example.org" }. If the id field is defined, the email will be ignored. If the id is not defined, email is used to look up a customer. If a customer does not exist, a new one will be created. If a customer is matched either via id or email field, the rest of the optional fields is ignored. When creating a phone conversation "firstName" or "phone" can be passed instead of "email". Example: { "email": "mark@example.org" }
     * @bodyParam  threads object required Conversation threads. There has to be least one thread in a conversation. Newest threads go first. Example: [ { "text": "This is the message from a user", "type": "message", "user": 7 }, { "text": "This is the note from a user", "type": "note", "user": 7 }, { "text": "This is the message from a customer", "type": "customer", "cc": ["antony@example.org"], "customer": { "email": "mark@example.org", "firstName": "Mark" } } ]
     * @bodyParam  imported boolean When imported is set to true (boolean value without quotes), no outgoing emails or notifications will be generated, auto reply will not be sent to the customer. Example: false
     * @bodyParam  assignTo number User ID to assign new conversation to. Example: 15
     * @bodyParam  status string Conversation status: active, pending, closed. Example: active
     * @bodyParam  customFields object Conversation custom fields. Example: [ { "id" : 37, "value" : "Some text" }, { "id" : 18, "value" : 3 } ]
     * @bodyParam  createdAt string Conversation date (ISO 8601 date time format). Example: 2020-03-16T14:07:23Z
     * @bodyParam  closedAt string When the conversation was closed, only applicable for imported conversations (ISO 8601 date time format). Example: 2020-03-16T14:07:23Z
     */
    public function createConversation(Request $request)
    {
        $data = $request->all();

        // Required parameters.
        $check_required_params = $this->checkRequiredParams($data, [
            'subject',
            'type',
            'mailboxId',
            'customer',
            'threads',
            'ip',
            'source',
            'report_type',
            'path',
        ]);
        if ($check_required_params !== true) {
            return $check_required_params;
        }
        // Threads must be sequential array.
        if (!is_array($data['threads'])
            || count(array_filter(array_values($data['threads']), 'is_array')) != count($data['threads'])
        ) {
            return $this->getErrorResponse("'threads' parameter must be an array", 'threads');
        }

        $data = self::toSnake($data);

        // Convert codes into integers.
        $data = self::convertConversationCodes($data);

        // Create or get Customer.
        // Fix for Zapier.
        if (!empty($data['customer']) && is_array($data['customer']) && !empty($data['customer'][0])) {
            $data['customer'] = $data['customer'][0];
        }
        $customer = null;
        if (!empty($data['customer']['id'])) {
            $customer = Customer::find($data['customer']['id']);
            if (!$customer) {
                return $this->getErrorResponse('Customer with the following ID not found: '.$data['customer']['id'], 'customer');
            }
        }
        if (!$customer && !empty($data['customer']['email'])) {
            $customer = Customer::getByEmail($data['customer']['email']);
        }
        if (!$customer && !empty($data['customer']['phone'])) {
            $customer = Customer::findByPhone($data['customer']['phone']);
        }

        if (!$customer) {
            if (!is_array($data['customer'])) {
                $data['customer'] = [];
            }
            $customer_request = new Request();
            $customer_request->merge($data['customer']);
            $customer_response = $this->createCustomer($customer_request, true);

            if ($customer_response->headers->get('Resource-ID')) {
                $customer = Customer::find($customer_response->headers->get('Resource-ID'));
            } else {
                return $customer_response;
            }
        }

        // Create conversation.
        $mailbox = Mailbox::find($data['mailbox_id']);
        if (!$mailbox) {
            return $this->getErrorResponse('Mailbox not found', 'mailboxId');
        }

        $conversation = null;
        $conversation = new Conversation();
        $conversation->type = $data['type'];
        $conversation->subject = $data['subject'];
        $conversation->mailbox_id = $mailbox->id;
        $conversation->ip = $data['ip'];
        $conversation->source = $data['source'];
        $conversation->report_type = $data['report_type'];
        $conversation->path = $data['path'];
        $conversation->preview = '';
        // Preset source_via here to avoid error in PostgreSQL.
        $conversation->source_via = Conversation::PERSON_CUSTOMER;
        $conversation->source_type = Conversation::SOURCE_TYPE_API;
        $conversation->customer_id = $customer->id;
        $conversation->customer_email = $customer->getMainEmail().'';
        $conversation->status = $data['status'] ?? Conversation::STATUS_ACTIVE;
        $conversation->state = $data['state'] ?? Conversation::STATE_PUBLISHED;
        $conversation->imported = (int)($data['imported'] ?? false);
        if (!empty($data['created_at'])) {
            $conversation->created_at = self::utcStringToServerDate($data['created_at']);
        }
        if ($conversation->imported && !empty($data['closed_at'])) {
            $conversation->closed_at = self::utcStringToServerDate($data['closed_at']);
        }

        // Set assignee
        $conversation->user_id = null;
        if (!empty($data['assign_to'])) {
            $user_assignee = User::find($data['assign_to']);
            if ($user_assignee) {
                $conversation->user_id = $user_assignee->id;
            }
        }

        $conversation->updateFolder();
        $conversation->save();

        // Create threads.
        $threads = array_reverse($data['threads']);
        $thread_created = false;
        $thread_response = null;
        $last_customer_id = null;
        foreach ($threads as $thread) {
            $thread_request = new Request();
            if ($conversation->imported) {
                $thread['imported'] = true;
            }
            if (!empty($data['status'])) {
                $thread['status'] = $data['status'];
            }
            $thread_request->merge($thread);
            $thread_response = $this->createThread($thread_request, $conversation->id, true);

            if ($thread_response->headers->get('Resource-ID')) {
                $thread_created = true;

                if ($thread['type'] == Thread::$types[Thread::TYPE_CUSTOMER] && $thread_response->headers->get('Customer-ID')) {
                    $last_customer_id = $thread_response->headers->get('Customer-ID');
                }
            }
        }

        // If no threads created, delete conversation
        if (!$thread_created) {
            $conversation->delete();
            if ($thread_response) {
                return $thread_response;
            } else {
                return $this->getErrorResponse('Could no create thread(s)', 'threads');
            }
        }

        // Restore customer if needed.
        if ($last_customer_id && $last_customer_id != $customer->id) {
            // Otherwise it does not save.
            $conversation = $conversation->fresh();
            $conversation->customer_id = $customer->id;
            $conversation->customer_email = $customer->getMainEmail();
            $conversation->save();
        }

        // Update folders counters
        $conversation->mailbox->updateFoldersCounters();

        // Custom fields.
        if (!empty($data['custom_fields']) && is_array($data['custom_fields']) && \Module::isActive('customfields')) {
            foreach ($data['custom_fields'] as $custom_field) {
                if (!is_numeric($custom_field['id']) || !isset($custom_field['value'])) {
                    continue;
                }
                \CustomField::setValue($conversation->id, $custom_field['id'], $custom_field['value']);
            }
        }

        // Events.
        // event(new UserCreatedConversation($conversation, $thread));
        // \Eventy::action('conversation.created_by_user_can_undo', $conversation, $thread);
        // // After Conversation::UNDO_TIMOUT period trigger final event.
        // \Helper::backgroundAction('conversation.created_by_user', [$conversation, $thread], now()->addSeconds(Conversation::UNDO_TIMOUT));

        // We need to refresh the conversation as otherwise created_by_customer_id is empty.
        // https://github.com/freescout-helpdesk/freescout/issues/3802
        $conversation->refresh();

        if ($conversation) {
            $response_data = \ApiWebhooks::formatEntity($conversation);

            $response = $this->getApiResponse($response_data, self::STATUS_CREATED);

            $response->header('Resource-ID', $conversation->id);
            //$response->header('Location', $conversation->url());
        } else {
            $response = $this->getApiResponse(self::getErrorBody('Error occurred'), self::STATUS_BAD_REQUEST);
        }

        return $response;
    }

    /**
     * @group Threads
     *
     * Create Thread
     *
     * This method adds a new customer reply, user reply or user note to a conversation.
     *
     * @response 201 {
     *   "headers": "HTTP/1.1 201 Created\nResource-ID: 25"
     * }
     *
     * @bodyParam  type string required Thread type: customer (customer reply), message (user reply), note (user note). Example: message
     * @bodyParam  text string required The message text. Example: Plese let us know if you have any other questions.
     * @bodyParam  customer object Customer adding the thread (required if thread 'type' is 'customer'). Customer object must contain a valid customer id or an email address: { "id": 123 } or { "email": "mark@example.org" }. If the id field is defined, the email will be ignored. If the id is not defined, email is used to look up a customer. If a customer does not exist, a new one will be created. If a customer is matched either via id or email field, the rest of the optional fields is ignored. Example: { "email": "mark@example.org" }
     * @bodyParam  user number ID of the user who is adding the thread (required if thread 'type' is 'message' or 'note'). Example: 33
     * @bodyParam  imported boolean When imported is set to 'true', no outgoing emails or notifications will be generated. Example: false
     * @bodyParam  status string Conversation status: active, pending, closed. Use this field to change conversation status when adding a thread. If not explicitly set, a customer reply will reactivate the conversation and support agent reply will make it pending. Example: closed
     * @bodyParam  state string Thread state: draft, published (default). Example: published
     * @bodyParam  cc array List of CC email addresses. Example: [ "anna@example.org", "bill@example.org" ]
     * @bodyParam  bcc array List of BCC email addresses. Example: [ "bob@example.org", "andrea@example.org" ]
     * @bodyParam  createdAt string Creation date to be used when importing conversations and threads in ISO 8601 date time format (can be used only when 'imported' field is set to true). Example: 2020-03-16T14:07:23Z
     * @bodyParam  attachments array List of attachments to be attached to the thread. Attachment content can be passed in "data" parameter as BASE64 encoded string or as URL in "fileUrl" parameter.  Example: [ { "fileName" : "file.txt", "mimeType" : "plain/text", "data" : "ZmlsZQ==" }, { "fileName" : "file2.txt", "mimeType" : "plain/text", "fileUrl" : "https://example.org/uploads/file2.txt" } ]
     */
    public function createThread(Request $request, $conversationId, $internal = false)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        $data = $request->all();

        // Required parameters.
        $check_required_params = $this->checkRequiredParams($data, [
            'text',
            'type',
        ]);
        if ($check_required_params !== true) {
            return $check_required_params;
        }

        $data = self::toSnake($data);

        // Convert codes into integers.
        $data = self::convertThreadCodes($data);

        // Create or get Customer.
        $customer = null;
        $is_customer = ($data['type'] == Thread::TYPE_CUSTOMER);
        if (!empty($data['customer'])) {
            if (!is_array($data['customer'])) {
                $data['customer'] = [];
            }
            if (!empty($data['customer']['id'])) {
                $customer = Customer::find($data['customer']['id']);
                if (!$customer) {
                    return $this->getErrorResponse('Customer with the following ID not found: '.$data['customer']['id'], 'customer');
                }
            }
            if (!$customer && !empty($data['customer']['email'])) {
                $customer = Customer::getByEmail($data['customer']['email']);
            }
            if (!$customer && !empty($data['customer']['phone'])) {
                $customer = Customer::findByPhone($data['customer']['phone']);
            }
            if (!$customer) {
                $customer_request = new Request();
                $customer_request->merge($data['customer']);
                $customer_response = $this->createCustomer($customer_request, true);

                if ($customer_response->headers->get('Resource-ID')) {
                    $customer = Customer::find($customer_response->headers->get('Resource-ID'));
                } else {
                    return $customer_response;
                }
            }
        }
        if (empty($customer)) {
            $customer = $conversation->customer;
        }

        // User.
        $user = null;
        if (!$is_customer && !empty($data['user'])) {
            $user = User::find($data['user']);
        }

        // Check type.
        if ($data['type'] == Thread::TYPE_CUSTOMER && empty($customer)) {
            return $this->getErrorResponse('`customer` parameter is required', 'customer');
        }
        if (($data['type'] == Thread::TYPE_MESSAGE || $data['type'] == Thread::TYPE_NOTE) && empty($user)) {
            return $this->getErrorResponse('`user` parameter is required', 'user');
        }

        // New conversation.
        $new = !$conversation->threads_count;

        $thread = new Thread();
        $thread->conversation_id = $conversation->id;
        $thread->type = $data['type'];
        if ($is_customer) {
            $thread->source_via = Thread::PERSON_CUSTOMER;
            $thread->created_by_customer_id = $customer->id;
        } else {
            $thread->source_via = Thread::PERSON_USER;
            $thread->created_by_user_id = $user->id;
            $thread->edited_by_user_id = null;
            $thread->edited_at = null;
        }
        $thread->source_type = Thread::SOURCE_TYPE_API;
        $thread->user_id = $conversation->user_id;
        //$thread->status = $request->status;
        $thread->state = $data['state'] ?? Thread::STATE_PUBLISHED;
        $thread->customer_id = $customer->id ?? $conversation->customer_id ?? null;
        $thread->body = $data['text'];
        if (!$is_customer) {
            $thread->setTo([$customer->getMainEmail()]);
        }

        $cc = \MailHelper::sanitizeEmails($data['cc'] ?? []);
        $thread->setCc($cc);

        $bcc = \MailHelper::sanitizeEmails($data['bcc'] ?? []);
        $thread->setBcc($bcc);
        $thread->imported = (int)($data['imported'] ?? false);
        if ($thread->imported && !empty($data['created_at'])) {
            $created_at = self::utcStringToServerDate($data['created_at']);
            $thread->created_at = $created_at;
        } else {
            $created_at = date('Y-m-d H:i:s');
        }
        if ($new) {
            $thread->first = true;
        }

        // Process attachments.
        if (!empty($data['attachments'])) {
            $has_attachments = false;
            foreach ($data['attachments'] as $attachment) {

                if (empty($attachment['file_name'])
                    || empty($attachment['mime_type'])
                    || (empty($attachment['data']) && empty($attachment['file_url']))
                ) {
                    continue;
                }
                $content = null;
                $uploaded_file = null;
                if (!empty($attachment['data'])) {
                    // BASE64 string.
                    $content = base64_decode($attachment['data']);
                    if (!$content) {
                        continue;
                    }
                } else {
                    // URL.
                    $uploaded_file = \Helper::downloadRemoteFileAsTmpFile($attachment['file_url']);
                    if (!$uploaded_file) {
                        continue;
                    }
                }
                if (!$has_attachments) {
                    $thread->save();
                }
                $attachment = Attachment::create(
                    $attachment['file_name'],
                    $attachment['mime_type'],
                    null,
                    $content,
                    $uploaded_file = $uploaded_file,
                    $embedded = false,
                    $thread->id,
                    $user->id ?? null
                );

                if ($attachment) {
                    $has_attachments = true;
                }
            }
            if ($has_attachments) {
                $thread->has_attachments = true;
                $conversation->has_attachments = true;
            }
        }

        $thread->save();

        if ($new) {
            if ($is_customer) {
                $conversation->source_via = Conversation::PERSON_CUSTOMER;
                $conversation->created_by_customer_id = $customer->id;
            } else {
                $conversation->source_via = Conversation::PERSON_USER;
                $conversation->created_by_user_id = $user->id;
            }
        }

        $conversation->setCc($cc);
        // BCC should keep BCC of the first email,
        // so we change BCC only if it contains emails.
        if ($bcc) {
            $conversation->setBcc($bcc);
        }

        $update_folder = false;

        if ($thread->isReply()) {
            $conversation->last_reply_at = $created_at;
            if ($is_customer) {
                $conversation->last_reply_from = Conversation::PERSON_CUSTOMER;
                // Set specific status
                if (!empty($data['status'])) {
                    if (!$internal && (int)$conversation->status != (int)$data['status']) {
                        $update_folder = true;
                    }
                    $conversation->status = $data['status'];
                } else {
                    if (!$internal && (int)$conversation->status != Conversation::STATUS_ACTIVE) {
                        $update_folder = true;
                    }
                    // Reply from customer makes conversation active
                    $conversation->status = Conversation::STATUS_ACTIVE;
                }

                // Reply from customer to deleted conversation should undelete it.
                if ($conversation->state == Conversation::STATE_DELETED) {
                    $conversation->state = Conversation::STATE_PUBLISHED;
                    if (!$internal) {
                        $update_folder = true;
                    }
                }
            } else {
                $conversation->last_reply_from = Conversation::PERSON_USER;
                $conversation->user_updated_at = $created_at;

                if (!empty($data['status'])) {
                    if (!$internal && (int)$conversation->status != (int)$data['status']) {
                        $update_folder = true;
                    }
                    $conversation->status = $data['status'];
                } else {
                    if (!$internal && (int)$conversation->status != Conversation::STATUS_PENDING) {
                        $update_folder = true;
                    }
                    $conversation->status = Conversation::STATUS_PENDING;
                }
            }
        } else {
            // Note.
            // Set specific status.
            if (!empty($data['status'])) {
                if ((int)$conversation->status != (int)$data['status']) {
                    $conversation->status = $data['status'];
                    $conversation->user_updated_at = $created_at;
                    $update_folder = true;
                }
            }
        }
        // Maybe we need to trigger status change events here
        // from $conversation->setStatus().
        // For now it does not cause any issues.

        $conversation->customer_id = $customer->id;
        if ($is_customer) {
            $conversation->customer_email = $customer->getMainEmail();
        }

        // Update conversation here if needed.
        if ($is_customer) {
            if ($new) {
                $conversation = \Eventy::filter('conversation.created_by_customer', $conversation, $thread, $customer);
            } else {
                $conversation = \Eventy::filter('conversation.customer_replied', $conversation, $thread, $customer);
            }
        }

        if ($update_folder) {
            $conversation->updateFolder();
        }

        // save() will check if something in the model has changed. If it hasn't it won't run a db query.
        $conversation->save();

        // Update folders counters
        if (!$internal) {
            // Update folders counters
            $conversation->mailbox->updateFoldersCounters();
        }

        // Events.

        // Conversation customer changed
        // Not used anywhere
        // if ($prev_customer_id) {
        //     event(new ConversationCustomerChanged($conversation, $prev_customer_id, $prev_customer_email, null, $customer));
        // }

        if ($new) {
            if ($is_customer) {
                event(new CustomerCreatedConversation($conversation, $thread));
                \Eventy::action('conversation.created_by_customer', $conversation, $thread, $customer);
            } else {
                // New conversation.
                event(new UserCreatedConversation($conversation, $thread));
                \Eventy::action('conversation.created_by_user_can_undo', $conversation, $thread);
                // After Conversation::UNDO_TIMOUT period trigger final event.
                \Helper::backgroundAction('conversation.created_by_user', [$conversation, $thread], now()->addSeconds(Conversation::UNDO_TIMOUT));
            }
        } elseif ($data['type'] == Thread::TYPE_NOTE) {
            // Note.
            event(new UserAddedNote($conversation, $thread));
            \Eventy::action('conversation.note_added', $conversation, $thread);
        } else {
            // Reply.
            if ($is_customer) {
                event(new CustomerReplied($conversation, $thread));
                \Eventy::action('conversation.customer_replied', $conversation, $thread, $customer);
            } else {
                event(new UserReplied($conversation, $thread));
                \Eventy::action('conversation.user_replied_can_undo', $conversation, $thread);
                // After Conversation::UNDO_TIMOUT period trigger final event.
                \Helper::backgroundAction('conversation.user_replied', [$conversation, $thread], now()->addSeconds(Conversation::UNDO_TIMOUT));
            }
        }

        if ($thread) {
            $response_data = \ApiWebhooks::formatEntity($thread);

            $response = $this->getApiResponse($response_data, self::STATUS_CREATED);

            $response->header('Resource-ID', $thread->id);
            if ($internal) {
                $response->header('Customer-ID', $conversation->customer_id);
            }
            //$response->header('Location', $thread->conversation->url());
        } else {
            $response = $this->getApiResponse(self::getErrorBody('Error occurred'), self::STATUS_BAD_REQUEST);
        }

        return $response;
    }

    /**
     * @group Customers
     *
     * List Customers
     *
     * Request parameters can be used to filter customers. By default customers are sorted by createdAt (from newest to oldest): ?sortField=createdAt&sortOrder=desc
     *
     * @response 201 {
     *     "_embedded": {
     *         "customers": [
     *             {
     *                 "id" : 75,
     *                 "firstName" : "Mark",
     *                 "lastName" : "Morrison",
     *                 "company" : "Example, Inc",
     *                 "jobTitle" : "Secretary",
     *                 "photoType" : "unknown",
     *                 "photoUrl" : "https://support.example.org/storage/customers/7a10629fd2bae86563892b191f6677e7.jpg",
     *                 "createdAt" : "2020-07-23T12:34:12Z",
     *                 "updatedAt" : "2020-07-24T20:18:33Z",
     *                 "notes" : "Nothing special to say.",
     *                 "_embedded": {
     *                     "emails": [],
     *                     "phones": [],
     *                     "social_profiles": [],
     *                     "websites": [],
     *                     "address": {
     *                         "city": null,
     *                         "state": null,
     *                         "zip": null,
     *                         "country": null,
     *                         "address": null
     *                     }
     *                 }
     *             }
     *         ]
     *     },
     *     "page": {
     *         "size": 50,
     *         "totalElements": 1,
     *         "totalPages": 1,
     *         "number": 1
     *     }
     * }
     *
     * @queryParam  firstName Filter customers by first name. Example: John
     * @queryParam  lastName Filter customers by last name. Example: Doe
     * @queryParam  phone Filter customers by phone number. Example: 777-777-777
     * @queryParam  email Filter customers by email. Example: john@example.org
     * @queryParam  updatedSince Return only customers modified after the specified date. Example: 2021-01-07T12:00:03Z
     * @queryParam  sortField Sort the result by specified field: createdAt (default), firstName, lastName, updatedAt. Example: firstName
     * @queryParam  sortOrder Sort order: desc (default), asc. Example: asc
     * @queryParam   page Page number. Example: 1
     * @queryParam   pageSize Page size (defaults to 50). Example: 100
     */
    public function listCustomers(Request $request)
    {
        // Laravel automatically adds HEAD to the get() routes,
        // so we need to check it manually.
        if (!$request->isMethod('get')) {
            return $this->methodNotAllowed();
        }

        $response_data = [
            '_embedded' => [
                'customers' => []
            ]
        ];

        // sortField.
        $sort_field = 'customers.id';
        $sort_fields = [
            'createdAt' => 'customers.id',
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'updatedAt' => 'updated_at',
        ];
        if (!empty($request->sortField) && array_key_exists($request->sortField, $sort_fields)) {
            $sort_field = $sort_fields[$request->sortField];
        }
        // sortOrder.
        $sort_order = self::SORT_ORDER;
        if (!empty($request->sortOrder) && in_array($request->sortOrder, ['desc', 'asc'])) {
            $sort_order = $request->sortOrder;
        }

        $query = Customer::select(['customers.*'])
            ->orderBy($sort_field, $sort_order);

        if (!empty($request->email)) {
            $query->join('emails', function ($join) {
                $join->on('emails.customer_id', 'customers.id');
            })->where('emails.email', $request->email);
        }
        if (!empty($request->firstName)) {
            $query->where('first_name', $request->firstName);
        }
        if (!empty($request->lastName)) {
            $query->where('last_name', $request->lastName);
        }
        if (!empty($request->phone)) {
            $query->where('phones', 'like', '%'.\Helper::phoneToNumeric($request->phone).'%');
        }
        if (!empty($request->updatedSince)) {
            $query->where('updated_at', '>=', self::utcStringToServerDate($request->updatedSince));
        }

        $customers = $query->paginate($request->pageSize ?: self::PAGE_SIZE);

        foreach ($customers as $customer) {
            $response_data['_embedded']['customers'][] = \ApiWebhooks::formatEntity($customer);
        }

        $response_data = self::addPageDataToResponse($response_data, $request, $customers);

        return $this->getApiResponse($response_data);
    }

    public function addPageDataToResponse($response_data, $request, $list)
    {
        $response_data['page'] = [
            "size" => (int)$request->pageSize ?: self::PAGE_SIZE,
            "totalElements" => $list->total(),
            "totalPages" => $list->lastPage(),
            "number" => $list->currentPage()
        ];

        return $response_data;
    }

    /**
     * @group Customers
     *
     * Get Customer
     *
     * @response {
     *  "id" : 75,
     *  "firstName" : "Mark",
     *  "lastName" : "Morrison",
     *  "company" : "Example, Inc",
     *  "jobTitle" : "Secretary",
     *  "photoType" : "unknown",
     *  "photoUrl" : "https://support.example.org/storage/customers/7a10629fd2bae86563892b191f6677e7.jpg",
     *  "createdAt" : "2020-07-23T12:34:12Z",
     *  "updatedAt" : "2020-07-24T20:18:33Z",
     *  "notes" : "Nothing special to say.",
     *  "customerFields": [
     *           {
     *               "id": 11,
     *               "name": "Age",
     *               "value": "25",
     *               "text": ""
     *           },
     *           {
     *               "id": 2,
     *               "name": "Gender",
     *               "value": "1",
     *               "text": "Male"
     *           }
     *  ],
     *  "_embedded" : {
     *    "emails" : [ {
     *      "id" : 1,
     *      "value" : "mark@example.org",
     *      "type" : "home"
     *    } ],
     *    "phones" : [ {
     *      "id" : 0,
     *      "value" : "777-777-777",
     *      "type" : "home"
     *    } ],
     *    "social_profiles": [ {
     *      "id" : 0,
     *      "value" : "@markexample",
     *      "type" : "twitter"
     *    } ],
     *    "websites" : [ {
     *      "id" : 0,
     *      "value" : "https://example.org"
     *    } ],
     *    "address" : {
     *      "city" : "Los Angeles",
     *      "state" : "California",
     *      "zip" : "123123",
     *      "country" : "US",
     *      "address" : "1419 Westwood Blvd"
     *    }
     *  }
     * }
     */
    public function getCustomer(Request $request, $customerId)
    {
        // Laravel automatically adds HEAD to the get() routes,
        // so we need to check it manually.
        if (!$request->isMethod('get')) {
            return $this->methodNotAllowed();
        }

        $customer = Customer::find($customerId);

        if (!$customer) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        $response_data = \ApiWebhooks::formatEntity($customer);

        return $this->getApiResponse($response_data);
    }

    /**
     * @group Customers
     *
     * Create Customer
     *
     * This method does not update existing customers. Method makes sure that the email address is unique and does not check uniqueness of other parameters. If the request contains email(s) and customers with all these emails already exist, no customer will be created.
     *
     * If want to avoid creating duplicate customers with same "firstName", "lastName" and "phone", before executing this method use "List Customers" method to check if the customer already exists.
     *
     * @response 201 {
     *   "headers": "HTTP/1.1 201 Created\nResource-ID: 17"
     * }
     *
     * @bodyParam  firstName string First name of the customer (max 40 characters). Example: Mark
     * @bodyParam  lastName string Last name of the customer (max 40 characters). Example: Morrison
     * @bodyParam  phone string Phone number. Example: 777-777-777
     * @bodyParam  photoUrl string URL of the customer’s photo (max 200 characters). Example: https://example.org/upload/customer.jpg
     * @bodyParam  jobTitle string Job title (max 60 characters). Example: Secretary
     * @bodyParam  photoType string Type of photo: unknown, gravatar, twitter, facebook, googleprofile, googleplus, linkedin. Example: unknown
     * @bodyParam  address object Customer's address (country contains <a href="https://en.wikipedia.org/wiki/ISO_3166-1#Current_codes" target="_blank" rel="nofollow">two-letter country code</a>): { "city": "Los Angeles", "state": "California", "zip": "123123", "country": "US", "address": "1419 Westwood Blvd" }. Example: { "city": "LA", "state": "California", "zip": "123123", "country": "US", "address": "1419 Westwood Blvd" }
     * @bodyParam  notes string Notes. Example: Nothing special to say
     * @bodyParam  company string Company (max 60 characters). Example: Example, Inc
     * @bodyParam  emails object List of email entries: [ { "value": "mark@example.org", "type": "home" } ]. Example: [ { "value": "mark@example.org", "type": "home" } ]
     * @bodyParam  phones object List of phones entries: [ { "value": "777-777-777", "type": "home" } ]. Example: [ { "value": "777-777-777", "type": "home" } ]
     * @bodyParam  socialProfiles object List of social profile entries: [ { "value": "@markexample", "type": "twitter" } ]. Example: [ { "value": "@markexample", "type": "twitter" } ]
     * @bodyParam  websites object List of website entries: [ { "value": "https:\/\/example.org" } ]. Example: [ { "value": "https:\/\/example.org" } ]
     */
    public function createCustomer(Request $request, $codes_converted = false)
    {
        $data = $request->all();
        $data = self::toSnake($data);

        // Convert codes into integers.
        // https://github.com/freescout-helpdesk/freescout/issues/3977
        if (!$codes_converted) {
            $data = self::convertCustomerCodes($data);
        }

        // Email can also be passed in `email` parameter.
        if (!empty($data['email'])) {
            $data['emails'] = $data['emails'] ?? [];
            $data['emails'][] = [
                'value' => $data['email'],
                'type' => Email::TYPE_WORK,
            ];
        }

        if (!empty($data['phone'])) {
            $data['phones'] = $data['phones'] ?? [];
            $data['phones'][] = [
                'value' => $data['phone'],
                'type' => Customer::PHONE_TYPE_WORK,
            ];
        }

        // If emails are present, There should be at 1 unique email.
        if (!empty($data['emails']) && is_array($data['emails'])) {
            $email_ok = false;
            foreach ($data['emails'] as $email_data) {
                if (!empty($email_data['value'])) {
                    $email_exists = Email::where('email', $email_data['value'])->first();
                    if (!$email_exists) {
                        $email_ok = true;
                        break;
                    }
                }
            }

            if (!$email_ok) {
                return $this->getApiResponse(self::getErrorBody('Error occurred', [[
                    'path'    => 'emails',
                    'message' => 'Customers with such email(s) already exist',
                    'source'  => 'JSON',
                ]]), self::STATUS_BAD_REQUEST);
            }
        }

        // Validate emails.
        if (!empty($data['emails'])) {
            foreach ($data['emails'] as $email) {
                if (!empty($email['value']) && !Email::sanitizeEmail($email['value'])) {
                    return $this->getErrorResponse('Invalid email: '.$email['value'], 'emails');
                }
            }
        }

        // Parse address.
        if (!empty($data['address'])) {
            $address = $data['address'];

            $data = array_merge($data, $address);

            if (!empty($address['lines'])) {
                $data['address'] = implode(', ', $address['lines']);
                unset($data['lines']);
            }/* else {
                unset($data['address']);
            }*/
        }

        $photo_url = $data['photo_url'] ?? '';
        if (isset($data['photo_url'])) {
            unset($data['photo_url']);
        }

        if (empty($data['first_name']) && empty($data['emails'])) {
            return $this->getErrorResponse('Customer first name or email is required.', 'customer.first_name');
        }

        $customer = Customer::createWithoutEmail($data);

        if ($customer) {
            // Save photo.
            if (!empty($photo_url)) {
                $photo_path = \Helper::downloadRemoteFileAsTmp($photo_url);
                if ($photo_path) {
                    $photo_file = $customer->savePhoto($photo_path, \File::mimeType($photo_path));
                    $customer->photo_url = $photo_file;
                    $customer->save();
                }
            }

            $response_data = \ApiWebhooks::formatEntity($customer);

            $response = $this->getApiResponse($response_data, self::STATUS_CREATED);

            $response->header('Resource-ID', $customer->id);
            //$response->header('Location', $customer->urlView());
        } else {
            $response = $this->getApiResponse(self::getErrorBody('Error occurred'), self::STATUS_BAD_REQUEST);
        }

        return $response;
    }

    /**
     * @group Customers
     *
     * Update Customer
     *
     * @response 204 {
     *   "headers": "HTTP/1.1 204 No Content"
     * }
     *
     * @bodyParam  firstName string First name of the customer (max 40 characters). Example: Mark
     * @bodyParam  lastName string Last name of the customer (max 40 characters). Example: Morrison
     * @bodyParam  phone string Phone number. Example: 777-777-777
     * @bodyParam  photoUrl string URL of the customer’s photo (max 200 characters). Example: https://example.org/upload/customer.jpg
     * @bodyParam  jobTitle string Job title (max 60 characters). Example: Secretary
     * @bodyParam  photoType string Type of photo: unknown, gravatar, twitter, facebook, googleprofile, googleplus, linkedin. Example: unknown
     * @bodyParam  address object Customer's address (country contains <a href="https://en.wikipedia.org/wiki/ISO_3166-1#Current_codes" target="_blank" rel="nofollow">two-letter country code</a>): { "city": "Los Angeles", "state": "California", "zip": "123123", "country": "US", "address": "1419 Westwood Blvd" }. Example: { "city": "LA", "state": "California", "zip": "123123", "country": "US", "address": "1419 Westwood Blvd" }
     * @bodyParam  notes string Notes. Example: Nothing special to say
     * @bodyParam  company string Company (max 60 characters). Example: Example, Inc
     * @bodyParam  emails object List of emails (when this parameter is set the 'emails_add' parameter has no effect): [ "mark@example.org", "admin@example.org" ]. Example: [ "mark@example.org", "admin@example.org" ]
     * @bodyParam  emails_add object Add emails to the customer: [ "mark.new1@example.org", "mark.new2@example.org" ]. Example: [ "mark.new1@example.org", "mark.new2@example.org" ]
     * @bodyParam  phones object List of phones entries: [ { "value": "777-777-777", "type": "home" } ]. Example: [ { "value": "777-777-777", "type": "home" } ]
     * @bodyParam  socialProfiles object List of social profile entries: [ { "value": "@markexample", "type": "twitter" } ]. Example: [ { "value": "@markexample", "type": "twitter" } ]
     * @bodyParam  websites object List of website entries: [ { "value": "https:\/\/example.org" } ]. Example: [ { "value": "https:\/\/example.org" } ]
     */
    public function updateCustomer(Request $request, $customerId)
    {
        $customer = Customer::find($customerId);

        if (!$customer) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        $data = $request->all();
        $data = self::toSnake($data);
        $data = self::convertCustomerCodes($data);

        if (!empty($data['address'])) {
            $data = array_merge($data['address'], $data);
            if (!empty($data['address']['address'])) {
                $data['address'] = $data['address']['address'];
            }
        }

        $emails_update = $data['emails'] ?? [];

        if (!empty($data['emails_add'])) {
            $data['emails'] = $data['emails_add'];
        }

        $customer->setData($data);

        // Replace emails.
        if (!empty($emails_update)) {
            // Plain list of emails.
            // Email type is ignored for now.
            $emails_to_sync = [];
            foreach ($emails_update as $email_update) {
                if (is_string($email_update)) {
                    $emails_to_sync[] = $email_update;
                } elseif (is_array($email_update) && !empty($email_update['value'])) {
                    $emails_to_sync[] = $email_update['value'];
                }
            }
            $customer->syncEmails($emails_to_sync);
        }

        $customer->save();

        // Update photo.
        if (!empty($data['photo_url'])) {
            $photo_url = $data['photo_url'];
            if (!empty($photo_url)) {
                $photo_path = \Helper::downloadRemoteFileAsTmp($photo_url);
                if ($photo_path) {
                    $photo_file = $customer->savePhoto($photo_path, \File::mimeType($photo_path));
                    $customer->photo_url = $photo_file;
                    $customer->save();
                }
            }
        }

        return self::getApiResponse([], 204);
    }

    /**
     * @group Customers
     *
     * Update Customer Fields
     *
     * @response 204 {
     *   "headers": "HTTP/1.1 204 No Content"
     * }
     *
     * @bodyParam  customerFields array required List of customer fields to be updated. Example: [{"id": 37, "value": "Test value"}]
     */
    public function updateCustomerFields(Request $request, $customerId)
    {
        $data = $request->all();

        if (!\Module::isActive('crm')) {
            return $this->getApiResponse(self::getErrorBody('Customer Fields module is not installed or not activated'), self::STATUS_BAD_REQUEST);
        }

        $customer = Customer::find($customerId);

        if (!$customer) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        // Required parameters.
        $check_required_params = $this->checkRequiredParams($data, [
            'customerFields',
        ]);
        if ($check_required_params !== true) {
            return $check_required_params;
        }

        $data = self::toSnake($data);

        foreach ($data['customer_fields'] as $customer_field) {
            if (!isset($customer_field['id']) || !isset($customer_field['value'])) {
                continue;
            }
            \CustomerField::setValue($customerId, $customer_field['id'], $customer_field['value']);
        }

        return self::getApiResponse([], 204);
    }

    /**
     * @group Users
     *
     * Create User
     *
     * This method does not update existing users. Method makes sure that the email address is unique and does not check uniqueness of other parameters. Method creates only regular Users and does not allow to create Administrators. No invitation email is being sent upon user creation. Created user does not have permissions to access any mailboxes by default.
     *
     * @response 201 {
     *   "headers": "HTTP/1.1 201 Created\nResource-ID: 17"
     * }
     *
     * @bodyParam  firstName string required First name of the user. Example: John
     * @bodyParam  lastName string required Last name of the user. Example: Doe
     * @bodyParam  email string required Email address. Example: johndoe@example.org
     * @bodyParam  password string User password. Example: 123456789
     * @bodyParam  alternateEmails string User alternate emails (comma separated). Example: johndoe777@example.org
     * @bodyParam  jobTitle string Job title. Example: Support agent
     * @bodyParam  phone string Phone number. Example: 777-777-777
     * @bodyParam  timezone string User timezone. List of timezones: https://www.php.net/manual/en/timezones.php. Example: Europe/Paris
     * @bodyParam  photoUrl string URL of the user's photo. Example: https://example.org/upload/customer.jpg
     */
    public function createUser(Request $request)
    {
        $data = $request->all();

        // Required parameters.
        $check_required_params = $this->checkRequiredParams($data, [
            'email',
            'firstName',
            'lastName',
        ]);
        if ($check_required_params !== true) {
            return $check_required_params;
        }

        $data = self::toSnake($data);

        // Convert codes into integers.
        $data = self::convertUserCodes($data);

        $email = Email::sanitizeEmail($data['email']);
        $email_exists = User::where('email', $email)->first();

        if ($email_exists) {
            return $this->getApiResponse(self::getErrorBody('Error occurred', [[
                'path'    => 'email',
                'message' => 'User with such email already exists',
                'source'  => 'JSON',
            ]]), self::STATUS_BAD_REQUEST);
        }

        if (Mailbox::where('email', $email)->first()) {
            return $this->getApiResponse(self::getErrorBody('Error occurred', [[
                'path'    => 'email',
                'message' => 'There is a mailbox with such email. Users and mailboxes can not have the same email addresses.',
                'source'  => 'JSON',
            ]]), self::STATUS_BAD_REQUEST);
        }

        if (empty($data['password'])) {
            $data['password'] = \Str::random(10);
            $data['invite_state'] = User::INVITE_STATE_NOT_INVITED;
        } else {
            $data['invite_state'] = User::INVITE_STATE_ACTIVATED;
        }

        $data['emails'] = $data['alternate_emails'] ?? '';

        if (isset($data['role'])) {
            unset($data['role']);
        }

        $photo_url = $data['photo_url'] ?? '';
        if (isset($data['photo_url'])) {
            unset($data['photo_url']);
        }

        $user = User::create($data);

        if ($user) {

            // Save photo.
            if (!empty($photo_url)) {
                $photo_path = \Helper::downloadRemoteFileAsTmp($photo_url);
                if ($photo_path) {
                    $photo_file = $user->savePhoto($photo_path, \File::mimeType($photo_path));
                    $user->photo_url = $photo_file;
                    $user->save();
                }
            }

            $response_data = \ApiWebhooks::formatEntity($user);

            $response = $this->getApiResponse($response_data, self::STATUS_CREATED);

            $response->header('Resource-ID', $user->id);
            //$response->header('Location', $user->url());
        } else {
            $response = $this->getApiResponse(self::getErrorBody('Error occurred'), self::STATUS_BAD_REQUEST);
        }

        return $response;
    }

    /**
     * @group Users
     *
     * Get User
     *
     * @response {
     *     "id": 1,
     *     "firstName": "John",
     *     "lastName": "Doe",
     *     "email": "johndoe@example.org",
     *     "role": "admin",
     *     "alternateEmails": "johndoe777@example.org",
     *     "jobTitle": "Support agent",
     *     "phone": "+1867342345",
     *     "timezone": "Etc/GMT-3",
     *     "photoUrl": "https://example.org/upload/customer.jpg",
     *     "language": "en",
     *     "createdAt": "2018-08-09T10-08-53Z",
     *     "updatedAt": "2020-12-22T14-54-35Z"
     * }
     */
    public function getUser(Request $request, $userId)
    {
        // Laravel automatically adds HEAD to the get() routes,
        // so we need to check it manually.
        if (!$request->isMethod('get')) {
            return $this->methodNotAllowed();
        }

        $user = User::find($userId);

        if (!$user) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        $response_data = \ApiWebhooks::formatEntity($user);

        return $this->getApiResponse($response_data);
    }

    /**
     * @group Users
     *
     * List Users
     *
     * Request parameters can be used to filter users.
     *
     * @response 201 {
     *     "_embedded": {
     *         "users": [
     *             {
     *                "id": 1,
     *                "firstName": "John",
     *                "lastName": "Doe",
     *                "email": "johndoe@example.org",
     *                "role": "admin",
     *                "alternateEmails": "johndoe777@example.org",
     *                "jobTitle": "Support agent",
     *                "phone": "+1867342345",
     *                "timezone": "Etc/GMT-3",
     *                "photoUrl": "https://example.org/upload/customer.jpg",
     *                "language": "en",
     *                "createdAt": "2018-08-09T10-08-53Z",
     *                "updatedAt": "2020-12-22T14-54-35Z"
     *             }
     *         ]
     *     },
     *     "page": {
     *         "size": 50,
     *         "totalElements": 1,
     *         "totalPages": 1,
     *         "number": 1
     *     }
     * }
     *
     * @queryParam  email Look up user by email. Example: johndoe@example.org
     * @queryParam  page Page number. Example: 1
     * @queryParam  pageSize Page size (defaults to 50). Example: 100
     */
    public function listUsers(Request $request)
    {
        $response_data = [
            '_embedded' => [
                'users' => []
            ]
        ];

        $query = User::orderBy('id', 'desc');

        if (!empty($request->email)) {
            $query->where('email', $request->email);
        }
        // if (!empty($request->mailboxId)) {
        //     $query->where('mailbox_id', $request->mailboxId);
        // }

        $users = $query->paginate($request->pageSize ?: self::PAGE_SIZE);

        foreach ($users as $user) {
            $response_data['_embedded']['users'][] = \ApiWebhooks::formatEntity($user);
        }

        $response_data = self::addPageDataToResponse($response_data, $request, $users);

        return $this->getApiResponse($response_data);
    }

    /**
     * @group Users
     *
     * Delete User
     *
     * This method deletes a user.
     *
     * @response 204 {
     *   "headers": "HTTP/1.1 204 No Content"
     * }
     *
     * @queryParam  byUserId required ID of the user performing the deletion of the user. Example: 1
     * @queryParam  assignTo Optional mapping array determining new assignee for conversations assigned to the user which is being deleted. Format: assignTo[MAILBOX_ID]=NEW_ASSIGNEE_ID. Example: assignTo[2]=7
     */
    public function deleteUser(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        if ($user->isDeleted()) {
            return $this->getApiResponse(self::getErrorBody('User is already deleted: '.$userId), self::STATUS_BAD_REQUEST);
        }

        // Check if the user is the only one admin
        if ($user->isAdmin()) {
            $admins_count = User::where('role', User::ROLE_ADMIN)->count();
            if ($admins_count < 2) {
                return $this->getApiResponse(self::getErrorBody('Administrator can not be deleted'), self::STATUS_BAD_REQUEST);
            }
        }

        $by_user = User::find($request->byUserId);
        if (!$by_user || $by_user->isDeleted()) {
            return $this->getApiResponse(self::getErrorBody('User (byUserId) with the following ID not found: '.$request->byUserId), self::STATUS_BAD_REQUEST);
        }

        $user->deleteUser($by_user, $request->assignTo ?? []);

        return self::getApiResponse([], 204);
    }

    /**
     * @group Mailboxes
     *
     * List Mailbox Custom Fields
     *
     * #### Response Fields
     *
     * Field | Type | Description
     * --------- | ------- | -----------
     * id | number | Custom field ID.
     * name | string | Name of the custom field.
     * type | string | Type of the custom field: singleline, multiline, dropdown, date, number
     * options | object | Contains options for dropdown custom fields.
     * required | boolean | Specifies if the custom field has to be filled.
     * sortOrder | number | Order of the custom field when displayed in the app.
     *
     * @response {
     *     "_embedded": {
     *         "custom_fields": [
     *             {
     *                 "id": 18,
     *                 "name": "Priority",
     *                 "type": "dropdown",
     *                 "options": {
     *                     "1": "Low",
     *                     "2": "Medium",
     *                     "3": "High"
     *                 },
     *                 "required": false,
     *                 "sortOrder": 1
     *             },
     *             {
     *                 "id": 19,
     *                 "name": "Purchase Date",
     *                 "type": "date",
     *                 "options": null,
     *                 "required": false,
     *                 "sortOrder": 3
     *             },
     *             {
     *                 "id": 37,
     *                 "name": "Vendor",
     *                 "type": "singleline",
     *                 "options": "",
     *                 "required": false,
     *                 "sortOrder": 6
     *             },
     *             {
     *                 "id": 38,
     *                 "name": "Comments",
     *                 "type": "multiline",
     *                 "options": "",
     *                 "required": false,
     *                 "sortOrder": 7
     *             },
     *             {
     *                 "id": 39,
     *                 "name": "Amount",
     *                 "type": "number",
     *                 "options": "",
     *                 "required": false,
     *                 "sortOrder": 8
     *             }
     *         ]
     *     },
     *     "page": {
     *         "size": 50,
     *         "totalElements": 5,
     *         "totalPages": 1,
     *         "number": 1
     *     }
     * }
     */
    public function mailboxCustomFields(Request $request, $mailboxId)
    {
        // Laravel automatically adds HEAD to the get() routes,
        // so we need to check it manually.
        if (!$request->isMethod('get')) {
            return $this->methodNotAllowed();
        }

        $response_data = [
            '_embedded' => [
                'custom_fields' => []
            ]
        ];

        $mailbox = Mailbox::find($mailboxId);

        if (!$mailbox) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        if (!\Module::isActive('customfields')) {
            return $this->getApiResponse(self::getErrorBody('Custom Fields module is not installed or not activated'), self::STATUS_BAD_REQUEST);
        }

        $query = \CustomField::where('mailbox_id', $mailbox->id);
        $custom_fields = $query->paginate($request->pageSize ?: self::PAGE_SIZE);

        foreach ($custom_fields as $custom_field) {
            $response_data['_embedded']['custom_fields'][] = \ApiWebhooks::formatEntity($custom_field, true, '', ['customfield_structure' => true]);
        }

        $response_data = self::addPageDataToResponse($response_data, $request, $custom_fields);

        return $this->getApiResponse($response_data);
    }

    /**
     * @group Mailboxes
     *
     * List Mailbox Folders
     *
     * @response {
     *    "_embedded": {
     *         "folders": [
     *          {
     *              "id": 1681,
     *              "name": "Unassigned",
     *              "type": 1,
     *              "userId": null,
     *              "totalCount": 0,
     *              "activeCount": 0,
     *              "meta": null
     *          },
     *          {
     *              "id": 1958,
     *              "name": "Mine",
     *              "type": 20,
     *              "userId": 145,
     *              "totalCount": 0,
     *              "activeCount": 0,
     *              "meta": null
     *          },
     *          {
     *              "id": 1959,
     *              "name": "Starred",
     *              "type": 25,
     *              "userId": 145,
     *              "totalCount": 0,
     *              "activeCount": 0,
     *              "meta": null
     *          },
     *          {
     *              "id": 1682,
     *              "name": "Drafts",
     *              "type": 30,
     *              "userId": null,
     *              "totalCount": 0,
     *              "activeCount": 0,
     *              "meta": null
     *          },
     *          {
     *              "id": 1683,
     *              "name": "Assigned",
     *              "type": 40,
     *              "userId": null,
     *              "totalCount": 0,
     *              "activeCount": 0,
     *              "meta": null
     *          },
     *          {
     *              "id": 1684,
     *              "name": "Closed",
     *              "type": 60,
     *              "userId": null,
     *              "totalCount": 0,
     *              "activeCount": 0,
     *              "meta": null
     *          },
     *          {
     *              "id": 1685,
     *              "name": "Spam",
     *              "type": 80,
     *              "userId": null,
     *              "totalCount": 0,
     *              "activeCount": 0,
     *              "meta": null
     *          },
     *          {
     *              "id": 1686,
     *              "name": "Deleted",
     *              "type": 110,
     *              "userId": null,
     *              "totalCount": 0,
     *              "activeCount": 0,
     *              "meta": null
     *          }
     *      ]
     *     },
     *         "page": {
     *          "size": 50,
     *          "totalElements": 24,
     *          "totalPages": 1,
     *          "number": 1
     *     }
     * }
     *
     * @queryParam  userId Get folders belonging to the specified user. Example: 7
     * @queryParam  folderId Get specific folder. Example: 3
     * @queryParam  pageSize Page size (defaults to 50). Example: 100
     */
    public function mailboxFolders(Request $request, $mailboxId)
    {
        // Laravel automatically adds HEAD to the get() routes,
        // so we need to check it manually.
        if (!$request->isMethod('get')) {
            return $this->methodNotAllowed();
        }

        $response_data = [
            '_embedded' => [
                'folders' => []
            ]
        ];

        $mailbox = Mailbox::find($mailboxId);

        if (!$mailbox) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        $query = Folder::where('mailbox_id', $mailboxId)->orderBy('type', 'ASC');

        if (!empty($request->userId)) {
            $query->where('user_id', $request->userId);
        }

        if (!empty($request->folderId)) {
            $query->where('id', $request->folderId);
        }

        $folders = $query->paginate($request->pageSize ?: self::PAGE_SIZE);

        foreach ($folders as $folder) {
            $response_data['_embedded']['folders'][] = \ApiWebhooks::formatEntity($folder);
        }

        $response_data = self::addPageDataToResponse($response_data, $request, $folders);

        return $this->getApiResponse($response_data);
    }

    /**
     * @group Mailboxes
     *
     * List Mailboxes
     *
     * Method returns mailboxes sorted by id. Request parameters can be used to filter mailboxes.
     *
     * @response 201 {
     *     "_embedded": {
     *         "mailboxes": [
     *             {
     *                  "id": 1,
     *                  "name": "Demo Mailbox",
     *                  "email": "support@support.example.org",
     *                  "createdAt": "2020-08-09T10-09-00Z",
     *                  "updatedAt": "2021-01-16T12-38-46Z"
     *             }
     *         ]
     *     },
     *     "page": {
     *         "size": 50,
     *         "totalElements": 1,
     *         "totalPages": 1,
     *         "number": 1
     *     }
     * }
     *
     * @queryParam  userId Get maiboxes to which specified user has an access. Example: 7
     * @queryParam   page Page number. Example: 1
     * @queryParam   pageSize Page size (defaults to 50). Example: 100
     */
    public function listMailboxes(Request $request)
    {
        // Laravel automatically adds HEAD to the get() routes,
        // so we need to check it manually.
        if (!$request->isMethod('get')) {
            return $this->methodNotAllowed();
        }

        $response_data = [
            '_embedded' => [
                'mailboxes' => []
            ]
        ];

        $query = Mailbox::select(['mailboxes.*'])->orderBy('mailboxes.id', 'asc');

        if (!empty($request->userId)) {
            $user = User::find($request->userId);
            if ($user) {
                if (!$user->isAdmin()) {
                    $query->join('mailbox_user', function ($join) {
                        $join->on('mailbox_user.mailbox_id', 'mailboxes.id');
                    });
                    $query->where('mailbox_user.user_id', $user->id);
                }
            }
        }

        $mailboxes = $query->paginate($request->pageSize ?: self::PAGE_SIZE);

        foreach ($mailboxes as $mailbox) {
            $response_data['_embedded']['mailboxes'][] = \ApiWebhooks::formatEntity($mailbox);
        }

        $response_data = self::addPageDataToResponse($response_data, $request, $mailboxes);

        return $this->getApiResponse($response_data);
    }

    /**
     * @group Tags
     *
     * List Tags
     *
     * Method returns tags sorted by id.
     *
     * @response 201 {
     *     "_embedded": {
     *         "tags": [
     *             {
     *                  "id": 1,
     *                  "name": "overdue",
     *                  "counter": 5,
     *                  "color": 1
     *             }
     *         ]
     *     },
     *     "page": {
     *         "size": 50,
     *         "totalElements": 1,
     *         "totalPages": 1,
     *         "number": 1
     *     }
     * }
     *
     * @queryParam  conversationId Conversation ID. Example: 7
     * @queryParam   page Page number. Example: 1
     * @queryParam   pageSize Page size (defaults to 50). Example: 100
     */
    public function listTags(Request $request)
    {
        // Laravel automatically adds HEAD to the get() routes,
        // so we need to check it manually.
        if (!$request->isMethod('get')) {
            return $this->methodNotAllowed();
        }

        $response_data = [
            '_embedded' => [
                'tags' => []
            ]
        ];

        if (!\Module::isActive('tags')) {
            return $this->getApiResponse(self::getErrorBody('Tags module is not installed or not activated'), self::STATUS_BAD_REQUEST);
        }

        $query = \Tag::select(['tags.*'])
            ->orderBy('id');

        if (!empty($request->conversationId)) {
            $query->join('conversation_tag', function ($join) {
                $join->on('conversation_tag.tag_id', 'tags.id');
            });
            $query->where('conversation_tag.conversation_id', $request->conversationId);
        }

        $tags = $query->paginate($request->pageSize ?: self::PAGE_SIZE);

        foreach ($tags as $tag) {
            $response_data['_embedded']['tags'][] = \ApiWebhooks::formatEntity($tag, true);
        }

        $response_data = self::addPageDataToResponse($response_data, $request, $tags);

        return $this->getApiResponse($response_data);
    }

    /**
     * @group Tags
     *
     * Update Conversation Tags
     *
     * This method allows to update tags for a conversation. The full list of tags must be sent in the request. If some tag specified does not exist it will be first created and then applied to the conversation. Any conversation tags which are not listed in the request will be removed. Send an empty list of tags to remove all tags.
     *
     * @response 204 {
     *   "headers": "HTTP/1.1 204 No Content"
     * }
     *
     * @bodyParam  tags array required List of tags (tag names) to be applied to the conversation. Example: ["overdue", "refund"]
     */
    public function updateTags(Request $request, $conversationId)
    {
        $data = $request->all();

        if (!\Module::isActive('tags')) {
            return $this->getApiResponse(self::getErrorBody('Tags module is not installed or not activated'), self::STATUS_BAD_REQUEST);
        }

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        // Required parameters.
        $check_required_params = $this->checkRequiredParams($data, [
            'tags',
        ]);
        if ($check_required_params !== true) {
            return $check_required_params;
        }

        $data = self::toSnake($data);

        $conv_tags = \Tag::conversationTags($conversation)->pluck('name')->toArray();

        // Add tags by name.
        foreach ($data['tags'] as $tag_name) {
            if (is_array($tag_name)) {
                continue;
            }
            \Tag::attachByName($tag_name, $conversationId);
        }

        // Remove tags.
        $request_tags_norm = [];
        foreach ($data['tags'] as $tag_name) {
            $request_tags_norm[] = \Tag::normalizeName($tag_name);
        }

        foreach ($conv_tags as $conv_tag_name) {
            if (!in_array($conv_tag_name, $request_tags_norm)) {
                \Tag::detachByName($conv_tag_name, $conversationId);
            }
        }

        return self::getApiResponse([], 204);
    }

    /**
     * @group Webhooks
     *
     * Create Webhook
     *
     * @response 201 {
     *   "headers": "HTTP/1.1 201 Created\nResource-ID: 17"
     * }
     *
     * @bodyParam  url string required URL that will be called when any of the events occur. Example: https://example.org/freescout
     * @bodyParam  events array required List of events to track: convo.assigned, convo.created, convo.deleted, convo.moved, convo.status, convo.customer.reply.created, convo.agent.reply.created, convo.note.created, customer.created, customer.updated. Example: ["convo.created"]
     */
    public function createWebhook(Request $request)
    {
        $data = $request->all();

        // Required parameters.
        $check_required_params = $this->checkRequiredParams($data, [
            'url',
            'events',
        ]);
        if ($check_required_params !== true) {
            return $check_required_params;
        }

        $data = self::toSnake($data);

        $webhook = \Webhook::create($data);

        if ($webhook) {
            $response_data = \ApiWebhooks::formatEntity($webhook);

            $response = $this->getApiResponse($response_data, self::STATUS_CREATED);

            $response->header('Resource-ID', $webhook->id);
            //$response->header('Location', $user->url());
        } else {
            $response = $this->getApiResponse(self::getErrorBody('Error occurred'), self::STATUS_BAD_REQUEST);
        }

        return $response;
    }

    /**
     * @group Webhooks
     *
     * Delete Webhook
     *
     * @response 204 {
     *   "headers": "HTTP/1.1 204 No Content"
     * }
     */
    public function deleteWebhook(Request $request, $webhookId)
    {
        $webhook = \Webhook::find($webhookId);

        if (!$webhook) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        $webhook->delete();

        \ApiWebhooks::clearWebhooksCache();

        return self::getApiResponse([], 204);
    }

    /**
     * @group Custom Fields
     *
     * Update Custom Fields
     *
     * @response 204 {
     *   "headers": "HTTP/1.1 204 No Content"
     * }
     *
     * @bodyParam  customFields array required List of custom fields to be applied to the conversation. When updating a custom filed with type  "Dropdown", in the value field you must provide a number (ID), not a textual value. Example: [{"id": 37, "value": "Test value"}]
     */
    public function updateCustomFields(Request $request, $conversationId)
    {
        $data = $request->all();

        if (!\Module::isActive('customfields')) {
            return $this->getApiResponse(self::getErrorBody('Custom Fields module is not installed or not activated'), self::STATUS_BAD_REQUEST);
        }

        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return $this->getApiResponse(self::getErrorBody('Not Found'), 404);
        }

        // Required parameters.
        $check_required_params = $this->checkRequiredParams($data, [
            'customFields',
        ]);
        if ($check_required_params !== true) {
            return $check_required_params;
        }

        $data = self::toSnake($data);

        foreach ($data['custom_fields'] as $custom_field) {
            if (!isset($custom_field['id']) || !isset($custom_field['value'])) {
                continue;
            }
            \CustomField::setValue($conversationId, $custom_field['id'], $custom_field['value']);
        }

        return self::getApiResponse([], 204);
    }

    /**
     * @group Timelogs
     *
     * List Conversation Timelogs
     *
     * Get Time Tracking Module timelogs for a conversation. Timelogs are sorted from newest to oldest.
     *
     * @response 201 {
     *     "_embedded": {
     *         "timelogs": [
     *            {
     *                "id": 498,
     *                "conversationStatus": "pending",
     *                "userId": 1,
     *                "timeSpent": 219,
     *                "paused": false,
     *                "finished": true,
     *                "createdAt": "2021-04-21T13-24-01Z",
     *                "updatedAt": "2021-04-21T13-43-10Z"
     *            },
     *            {
     *                "id": 497,
     *                "conversationStatus": "active",
     *                "userId": 1,
     *                "timeSpent": 711,
     *                "paused": false,
     *                "finished": true,
     *                "createdAt": "2021-04-21T13-22-09Z",
     *                "updatedAt": "2021-04-21T13-43-10Z"
     *            }
     *         ]
     *     },
     *     "page": {
     *         "size": 50,
     *         "totalElements": 1,
     *         "totalPages": 1,
     *         "number": 1
     *     }
     * }
     *
     * @queryParam   page Page number. Example: 1
     * @queryParam   pageSize Page size (defaults to 50). Example: 100
     */
    public function listTimelogs(Request $request, $conversationId)
    {
        // Laravel automatically adds HEAD to the get() routes,
        // so we need to check it manually.
        if (!$request->isMethod('get')) {
            return $this->methodNotAllowed();
        }

        $data = $request->all();

        if (!\Module::isActive('timetracking')) {
            return $this->getApiResponse(self::getErrorBody('Time Tracking module is not installed or not activated'), self::STATUS_BAD_REQUEST);
        }

        $response_data = [
            '_embedded' => [
                'timelogs' => []
            ]
        ];

        $query = \Modules\TimeTracking\Entities\Timelog::where('conversation_id', $conversationId)
            ->orderBy('id', 'desc');

        $timelogs = $query->paginate($request->pageSize ?: self::PAGE_SIZE);

        foreach ($timelogs as $timelog) {
            $response_data['_embedded']['timelogs'][] = \ApiWebhooks::formatEntity($timelog, false);
        }

        $response_data = self::addPageDataToResponse($response_data, $request, $timelogs);

        return $this->getApiResponse($response_data);
    }

    public function getApiResponse($data, $code = 200)
    {
        $response = \Response::json($data, $code);

        return $response;
    }

    public static function getErrorBody($message, $errors = [])
    {
        return [
            'message' => $message,
            //'errorCode' => $errorCode,
            '_embedded' => [
                'errors' => $errors
            ]
        ];
    }

    public static function toSnake($data)
    {
        $result = [];

        foreach ($data as $i => $value) {
            if (!is_array($value)) {
                $result[\Str::snake((string)$i)] = $value;
            } else {
                $result[\Str::snake((string)$i)] = self::toSnake($value);
            }
        }

        return $result;
    }

    public static function convertUserCodes($data, $parent_field = '')
    {
        foreach ($data as $field => $value) {
            if (!is_array($value)) {

                if (is_string($field)) {
                    switch ($field) {
                        case 'role':
                            $value = array_flip(User::$roles)[$value] ?? User::ROLE_USER;
                            break;
                    }

                    $data[$field] = $value;
                }
            } else {
                $data[$field] = self::convertUserCodes($value, (is_string($parent_field) && $parent_field ? $parent_field : $field));
            }
        }

        return $data;
    }

    public static function convertCustomerCodes($data, $parent_field = '')
    {
        foreach ($data as $field => $value) {
            if (!is_array($value)) {

                if (is_string($field)) {
                    switch ($field) {
                        case 'photo_type':
                            $value = array_flip(Customer::$photo_types)[$value] ?? Customer::PHOTO_TYPE_UKNOWN;
                            break;

                        case 'gender':
                            $value = array_flip(Customer::$genders)[$value] ?? Customer::GENDER_UNKNOWN;
                            break;

                        case 'country':
                            $value = strtoupper($value);
                            if (!array_key_exists($value, Customer::$countries)) {
                                $value = '';
                            }
                            break;
                    }

                    $data[$field] = $value;
                }

                if (is_string($parent_field) && is_string($field)) {
                    if ($parent_field == 'emails' && $field == 'type') {
                        $value = array_flip(Email::$types)[$value] ?? Email::TYPE_WORK;
                    }
                    if ($parent_field == 'phones' && $field == 'type') {
                        $value = array_flip(Customer::$phone_types)[$value] ?? Customer::PHONE_TYPE_WORK;
                    }
                    if ($parent_field == 'social_profiles' && $field == 'type') {
                        $value = array_flip(Customer::$social_types)[$value] ?? Customer::SOCIAL_TYPE_OTHER;
                    }

                    $data[$field] = $value;
                }
            } else {
                $data[$field] = self::convertCustomerCodes($value, (is_string($parent_field) && $parent_field ? $parent_field : $field));
            }
        }

        return $data;
    }

    public static function convertConversationCodes($data, $parent_field = '')
    {
        if ($parent_field == 'customer') {
            $data = self::convertCustomerCodes($data);
        }
        foreach ($data as $field => $value) {
            if (!is_array($value)) {

                if (is_string($field) && empty($parent_field)) {
                    switch ($field) {
                        case 'type':
                            $value = array_flip(Conversation::$types)[$value] ?? Conversation::TYPE_EMAIL;
                            break;

                        case 'status':
                            $value = array_flip(Conversation::$statuses)[$value] ?? Conversation::STATUS_ACTIVE;
                            break;

                        case 'state':
                            $value = array_flip(Conversation::$states)[$value] ?? Conversation::STATE_PUBLISHED;
                            break;
                    }

                    $data[$field] = $value;
                }

            } else {
                $data[$field] = self::convertConversationCodes($value, (is_string($parent_field) && $parent_field ? $parent_field : $field));
            }
        }

        return $data;
    }

    public static function convertThreadCodes($data, $parent_field = '')
    {
        if ($parent_field == 'customer') {
            $data = self::convertCustomerCodes($data);
        }
        foreach ($data as $field => $value) {
            if (!is_array($value)) {

                if (is_string($field) && empty($parent_field)) {
                    switch ($field) {
                        case 'type':
                            $value = array_flip(Thread::$types)[$value] ?? Thread::TYPE_CUSTOMER;
                            break;

                        case 'status':
                            if (empty(Thread::$statuses[$value])) {
                                $value = array_flip(Thread::$statuses)[$value] ?? Thread::STATUS_ACTIVE;
                            }
                            break;

                        case 'state':
                            $value = array_flip(Thread::$states)[$value] ?? Thread::STATE_PUBLISHED;
                            break;
                    }

                    $data[$field] = $value;
                }

            } else {
                $data[$field] = self::convertThreadCodes($value, (is_string($parent_field) && $parent_field ? $parent_field : $field));
            }
        }

        return $data;
    }

    public function checkRequiredParams($data, $params)
    {
        foreach ($params as $param) {
            if (!array_key_exists($param, $data)) {
                return $this->getApiResponse(self::getErrorBody('Error occurred', [[
                            'path'    => $param,
                            'message' => "`".$param.'` parameter is required',
                            'source'  => 'JSON',
                        ]]), self::STATUS_BAD_REQUEST);
            }
        }

        return true;
    }

    public function getErrorResponse($message, $param = '')
    {
        return $this->getApiResponse(self::getErrorBody('Error occurred', [[
                    'path'    => $param,
                    'message' => $message,
                    'source'  => 'JSON',
                ]]), self::STATUS_BAD_REQUEST);
    }

    public static function utcStringToServerDate($date_string)
    {
        $date = new Carbon();
        return $date->parse($date_string)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    public static function methodNotAllowed()
    {
        return response()->json("Method Not Allowed", 405);
    }

    public function all()
    {
        return '';
    }
}
