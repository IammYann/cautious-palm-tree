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
