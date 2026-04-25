<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// https://github.com/freescout-help-desk/freescout/issues/5328
class AddIndexesToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Not needed.
        // Schema::table('polycast_events', function (Blueprint $table) {
        //     $table->index(['event', 'created_at']);
        // });

        Schema::table('conversations', function (Blueprint $table) {
            // Folder list with state and closed date filter; covers the most frequent UI load pattern.
            // [idx_conversations_folder_state_closed].
            // 
            // Query pattern (every folder view — "All", "Mine", "Assigned", custom folders):
            // SELECT ... FROM conversations
            // WHERE folder_id = ? AND state = ?
            // ORDER BY closed_at DESC
            // LIMIT 25;
            // 
            // Filesort eliminated; #1 most-used index.
            $table->index(['folder_id', 'state', 'closed_at']);

            // Not needed as there is [idx_conversations_mailbox_state_status_dates]
            // Mailbox inbox and folder views.
            // Mailbox inbox sorted by last reply — the main agent view.
            // [idx_conversations_mailbox_state_status_reply].
            // 
            // Query pattern (every mailbox/folder page load):
            // SELECT ... FROM conversations
            // WHERE mailbox_id = ? AND state = ? AND status = ?
            // ORDER BY last_reply_at DESC
            // LIMIT 25;
            // 
            // Filesort eliminated.
            //$table->index(['mailbox_id', 'state', 'status', 'last_reply_at']);

            // Dashboard conversation counts.
            // Cross-mailbox state/status aggregation (dashboard counts).
            // [idx_conversations_state_mailbox_status].
            // 
            // SELECT mailbox_id, status, COUNT(*) AS cnt
            // FROM conversations
            // WHERE state = ?
            // GROUP BY mailbox_id, status;
            // 
            // Dashboard counts: seek vs. full scan.
            $table->index(['state', 'mailbox_id', 'status']);

            // Mailbox reports and date-range filtered views.
            // [idx_conversations_mailbox_state_status_dates]
            // 
            // SELECT ... FROM conversations
            // WHERE mailbox_id = ? AND state = ? AND status = ?
            // ORDER BY last_reply_at DESC
            // LIMIT 25;
            // 
            // Filesort eliminated; date-range variant
            $table->index(['mailbox_id', 'state', 'status', 'last_reply_at', 'created_at'], 'conversations_mailbox_state_status_dates');

            // Customer conversation history.
            // [idx_conversations_customer_id_created_at]
            // 
            // SELECT ... FROM conversations
            // WHERE customer_id = ?
            // ORDER BY created_at DESC
            // LIMIT 25;
            // 
            // Opening a customer profile and loading their conversation history required the engine 
            // to scan all conversations matching that customer_id without a time-ordered index, 
            // often falling back to filesort. Profiles for high-volume customers (e.g. those with many past conversations)
            // were slow to open.
            // 
            // Filesort eliminated.
            $table->index(['customer_id', 'created_at']);

            // Mailbox-scoped time range queries.
            // [idx_conversations_mailbox_created]
            // [idx_conversations_mailbox_dates]
            // 
            // Query patterns (reporting — conversations opened or closed in a date range):
            // -- Date range by open date
            // SELECT ... FROM conversations
            // WHERE mailbox_id = ? AND created_at BETWEEN ? AND ?
            // ORDER BY created_at DESC LIMIT 25;
            // 
            // -- Date range by both open and close date (reporting)
            // SELECT ... FROM conversations
            // WHERE mailbox_id = ? AND created_at >= ? AND closed_at >= ?
            // ORDER BY created_at DESC LIMIT 25;
            // 
            // Filesort eliminated.
            //$table->index(['mailbox_id', 'created_at']);
            $table->index(['mailbox_id', 'created_at', 'closed_at']);

            // Email-based customer lookup.
            // [idx_conversations_customer_email_status_created]
            // 
            // Query pattern (customer search by email address with status filter):
            // SELECT ... FROM conversations
            // WHERE customer_email = ? AND status = ?
            // ORDER BY created_at DESC LIMIT 25;
            // 
            // Avoids full table scan per email search.
            $table->index(['customer_email', 'status', 'created_at']);
        });

        Schema::table('threads', function (Blueprint $table) {
            //  Workflow thread conditions.
            //  Workflow processing — scans threads by type and state within a time window.
            // [idx_threads_type_state_created]
            // 
            // SELECT t.conversation_id FROM threads t
            // WHERE t.type = 2 AND t.state = 0 AND t.created_at >= NOW() - INTERVAL 1 DAY;
            // 
            // Using index condition; Using where >> Using index condition
            $table->index(['type', 'state', 'created_at']);

            // Agent thread lookup.
            // [idx_threads_created_by_user_id] - in fact [idx_threads_created_by_user_id_created]
            // 
            // -- "Which threads did this agent write?"
            // SELECT ... FROM threads WHERE created_by_user_id = ? ORDER BY created_at DESC LIMIT 25;
            // 
            // Using where; Backward index scan >> Using filesort
            $table->index(['created_by_user_id', 'created_at']);

            // Agent-assigned thread lookup.
            // [idx_threads_user_id]
            // 
            // This index has 0 reads in the current measurement window. EXPLAIN confirms it would be chosen for WHERE user_id = ? queries on the 23.7M-row threads table. It was added as a safety net after identifying that the user_id column had no index and any such query would be a full 57 GB table scan. The zero read count reflects that no such query happened to run during the ~12-day window.
            //$table->index(['user_id']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            // Unread notification count and recent notification list per agent.
            // Notification bell, unread badge.
            // [idx_notifications_notifiable_read]
            // 
            // SELECT COUNT(*) FROM notifications
            // WHERE notifiable_id = ? AND notifiable_type = 'App\User' AND read_at IS NULL;
            $table->index(['notifiable_id', 'notifiable_type', 'read_at', 'created_at'], 'notifications_notifiable_read');
            //  The native 2-column (notifiable_id, notifiable_type) index has recorded 0 reads 
            //  over 12 days of production traffic; the optimizer always prefers the extended index.
            $table->dropIndex(['notifiable_id', 'notifiable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read');
            $table->index(['notifiable_id', 'notifiable_type']);
        });

        Schema::table('threads', function (Blueprint $table) {
            $table->dropIndex(['created_by_user_id', 'created_at']);
            $table->dropIndex(['type', 'state', 'created_at']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['customer_email', 'status', 'created_at']);
            $table->dropIndex(['mailbox_id', 'created_at', 'closed_at']);
            $table->dropIndex(['customer_id', 'created_at']);
            $table->dropIndex('conversations_mailbox_state_status_dates');
            $table->dropIndex(['state', 'mailbox_id', 'status']);
            $table->dropIndex(['folder_id', 'state', 'closed_at']);
        });
    }
}
