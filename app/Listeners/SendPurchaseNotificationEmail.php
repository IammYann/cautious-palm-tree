<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PurchaseThankYouMail;
use App\Mail\PurchaseNotificationMail;
use Throwable;

class SendPurchaseNotificationEmail implements ShouldQueue
{
    use InteractsWithQueue;
    
    public function handle(object $event): void
    {
        try {
            $order   = $event->order;
            $buyer   = $order->user;
            $product = $order->product;
            $seller  = $product->user;

            // Validate required email addresses
            if (!$buyer || !$buyer->email) {
                throw new \Exception('Buyer email missing for order ' . $order->id);
            }
            if (!$seller || !$seller->email) {
                throw new \Exception('Seller email missing for product ' . $product->id);
            }

            Mail::to($buyer->email)
                ->queue(new PurchaseThankYouMail($buyer, $product, $order));

            Mail::to($seller->email)
                ->later(now()->addSeconds(15), new PurchaseNotificationMail($seller, $buyer, $product, $order));

            Log::info('Purchase notification emails queued successfully', [
                'order_id' => $order->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to send purchase notification emails', [
                'order_id' => $event->order?->id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            // Don't rethrow — let the event complete even if emails fail
        }
    }
}
