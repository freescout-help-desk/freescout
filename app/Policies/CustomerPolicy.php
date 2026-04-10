<?php

namespace App\Policies;

use App\Conversation;
use App\Customer;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param \App\User $user
     * @param \App\User $model
     *
     * @return mixed
     */
    public function view(User $user, Customer $customer)
    {
        if (!$customer) {
            return false;
        }
        
        $limited_visibility = config('app.limit_user_customer_visibility') && !$user->isAdmin();

        if ($limited_visibility) {
            $mailbox_ids = $user->mailboxesIdsCanView();
            
            $accesible = Conversation::where('customer_id', $customer->id)
                ->whereIn('conversations.mailbox_id', $mailbox_ids)
                ->exists();

            if (!$accesible) {
                return false;
            }
        }

        return true;
    }
}
