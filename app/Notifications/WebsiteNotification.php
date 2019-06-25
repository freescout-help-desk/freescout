<?php
/**
 * Website notification (DB notification) to display notifications in the menu.
 */

namespace App\Notifications;

use App\Conversation;
use App\Thread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WebsiteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $conversation;

    public $thread;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($conversation, $thread)
    {
        $this->conversation = $conversation;
        $this->thread = $thread;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $user
     *
     * @return array
     */
    public function via($user)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $user
     *
     * @return array
     */
    public function toArray($user)
    {
        return [
            'thread_id'       => $this->thread->id,
            'conversation_id' => $this->conversation->id,
        ];
    }

    /**
     * Fetch data from DB for notifications list to display it.
     */
    public static function fetchNotificationsData($notifications)
    {
        $data = [];

        $threads = [];
        //$conversations = [];

        //$conversation_ids = [];
        $thread_ids = [];

        // Get threads with their customers and users
        foreach ($notifications as $notification) {
            if (!empty($notification->data['thread_id'])) {
                $thread_ids[] = $notification->data['thread_id'];
            }
        }
        if ($thread_ids) {
            $threads = Thread::whereIn('id', $thread_ids)
                ->with('conversation')
                ->with('created_by_user')
                ->with('created_by_customer')
                ->with('user')
                ->get();
        }

        // Get last reply or note of the conversation to display it's text
        if ($threads) {
            $last_threads = Thread::whereIn('conversation_id', $threads->pluck('conversation_id')->unique()->toArray())
                // Select must contain all fields from orderBy() to avoid:
                // General error: 3065 Expression #1 of ORDER BY clause is not in SELECT
                ->select(['id', 'conversation_id', 'body', 'created_at'])
                ->whereIn('type', [Thread::TYPE_CUSTOMER, Thread::TYPE_MESSAGE, Thread::TYPE_NOTE])
                ->distinct('conversation_id')
                ->orderBy('created_at')
                // We can not use groupBy because of "isn't in GROUP BY"
                //->groupBy('conversation_id')
                ->get();
        }

        // Populate all collected data into array
        foreach ($notifications as $notification) {
            $conversation_number = '';
            if (!empty($notification->data['number'])) {
                $conversation_number = $notification->data['number'];
            }

            $thread = null;
            $user = null;
            $created_by_user = null;
            $created_by_customer = null;

            if (!empty($notification->data['thread_id'])) {
                $thread = $threads->firstWhere('id', $notification->data['thread_id']);
                if (empty($thread)) {
                    continue;
                }
                if ($thread->user_id) {
                    $user = $thread->user;
                }
                if ($thread->created_by_user_id) {
                    $created_by_user = $thread->created_by_user_id;
                }
                if ($thread->created_by_customer_id) {
                    $created_by_customer = $thread->created_by_customer_id;
                }
            } else {
                continue;
            }

            $last_thread_body = '';
            $conversation = null;

            $last_thread = $last_threads->firstWhere('conversation_id', $thread->conversation_id);
            if ($last_thread) {
                $last_thread_body = $last_thread->body;
                $conversation = $last_thread->conversation;
            }
            if (empty($conversation)) {
                continue;
            }

            $data[] = [
                'notification'        => $notification,
                'created_at'          => $notification->created_at,
                'conversation'        => $conversation,
                'thread'              => $thread,
                'last_thread_body'    => $last_thread_body,
                'user'                => $user,
                'created_by_user'     => $created_by_user,
                'created_by_customer' => $created_by_customer,
            ];
        }

        return $data;
    }
}
