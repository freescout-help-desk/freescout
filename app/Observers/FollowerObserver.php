<?php

namespace App\Observers;

use App\Follower;

class FollowerObserver
{
    public function created(Follower $follower)
    {
        \Eventy::action('follower.created', $follower);
    }

    public function deleted(Follower $follower)
    {
        \Eventy::action('follower.deleted', $follower);
    }
}
