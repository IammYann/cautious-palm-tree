<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     */
    protected $listen = [
        \App\Events\UserRegistered::class => [
            \App\Listeners\SendRegistrationEmail::class,
            \App\Listeners\LogRegistration::class,
        ],
        \App\Events\productpurchase::class => [
            \App\Listeners\SendPurchaseNotificationEmail::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
