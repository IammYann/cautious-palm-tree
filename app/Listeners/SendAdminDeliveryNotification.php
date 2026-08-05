<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Mail\AdminDeliveryNotificationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendAdminDeliveryNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderDelivered $event): void
    {
        $order = $event->order;
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            if ($admin) {
                Mail::to($admin->email)->queue(new AdminDeliveryNotificationMail($order));
            }
        }
    }
}
