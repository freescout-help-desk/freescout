<?php

namespace Modules\Reports\Entities;

use Carbon\Carbon;
use Modules\Workflows\Entities\ConversationWorkflow;
use App\Conversation;
use App\Mailbox;
use App\Thread;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

class Reports extends Model
{
    use Rememberable;

    // User permission.
    const PERM_VIEW_REPORTS = 50;

    public static function canViewReports($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        if (!$user) {
            return false;
        }
        return $user->isAdmin() || $user->hasPermission(\Reports::PERM_VIEW_REPORTS);
    }
}
