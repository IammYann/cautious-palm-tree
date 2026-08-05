<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Models\User;
use App\Observers\UserObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        // UserRegistered listeners are auto-discovered by Laravel in the Listeners folder
        \App\Events\productpurchase::class => [
            \App\Listeners\SendPurchaseNotificationEmail::class,
        ],

        \App\Events\OrderDelivered::class => [
            \App\Listeners\SendBuyerDeliveryNotification::class,
            \App\Listeners\SendAdminDeliveryNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        User::observe(UserObserver::class);
    }
}
