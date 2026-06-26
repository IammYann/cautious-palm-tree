<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseThankYouMail;
use App\Mail\PurchaseNotificationMail;

class SendPurchaseNotificationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * Send two emails:
     *   1. Buyer thank-you  → dispatched immediately to the queue.
     *   2. Seller notification → delayed by 5 seconds so Mailtrap's
     *      "1 email per second" free-tier limit is never hit.
     */
    public function handle(object $event): void
    {
        $order   = $event->order;
        $buyer   = $order->user;
        $product = $order->product;
        $seller  = $product->user;


        Mail::to($buyer->email)
            ->queue(new PurchaseThankYouMail($buyer, $product, $order));

        Mail::to($seller->email)
            ->later(now()->addSeconds(15), new PurchaseNotificationMail($seller, $buyer, $product, $order));
    }
}
