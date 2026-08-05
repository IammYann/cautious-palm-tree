<?php

namespace App\Observers;

use App\Models\User;
use App\Services\SafeCache;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        SafeCache::flushTags(['users']);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        SafeCache::flushTags(['users']);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        SafeCache::flushTags(['users']);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        SafeCache::flushTags(['users']);
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        SafeCache::flushTags(['users']);
    }
}
