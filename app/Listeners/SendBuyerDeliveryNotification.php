<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Mail\BuyerDeliveryNotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendBuyerDeliveryNotification implements ShouldQueue
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
        $buyer = $order->user;

        if ($buyer) {
            Mail::to($buyer->email)->queue(new BuyerDeliveryNotificationMail($order));
        }
    }
}
