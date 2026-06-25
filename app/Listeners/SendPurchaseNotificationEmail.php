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
     * The number of times the job may be attempted.
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 10;

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $order = $event->order;
        $buyer = $order->user;
        $product = $order->product;
        $seller = $product->user;

        // Add a 2-second delay to prevent Mailtrap rate-limiting "Too many emails per second" errors
        sleep(2);

        // Queue the thank you email to buyer (sent immediately)
        Mail::to($buyer->email)->queue(new PurchaseThankYouMail($buyer, $product, $order));

        // Queue the notification email to seller with a 3-second delay to respect Mailtrap limits
        Mail::to($seller->email)->later(now()->addSeconds(3), new PurchaseNotificationMail($seller, $buyer, $product, $order));
    }
}
