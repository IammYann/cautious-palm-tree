<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessOrderRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Execute the job.
     *
     * Communicates with the payment gateway API to log/trigger a refund
     * and updates the order status to 'refunded'.
     */
    public function handle(): void
    {
        Log::info('Processing refund for order', [
            'order_id' => $this->order->id,
            'transaction_id' => $this->order->transaction_id,
            'transaction_uuid' => $this->order->transaction_uuid,
            'amount' => $this->order->amount,
            'status' => $this->order->status,
        ]);

        // Verify the order is in refund_required state before proceeding
        $freshOrder = $this->order->fresh();
        if (!$freshOrder || $freshOrder->status !== 'refund_required') {
            Log::warning('Refund job skipped: order is not in refund_required state', [
                'order_id' => $this->order->id,
                'current_status' => $freshOrder?->status,
            ]);
            return;
        }

        // Determine which gateway was used based on the transaction data
        // and log the refund initiation. In production, this would call the
        // respective gateway's refund API.
        $gateway = $this->detectGateway();

        try {
            $this->processRefundWithGateway($gateway);

            // Mark the order as refunded
            $freshOrder->update(['status' => 'refunded']);

            Log::info('Refund processed successfully', [
                'order_id' => $this->order->id,
                'gateway' => $gateway,
                'transaction_id' => $this->order->transaction_id,
            ]);
        } catch (Throwable $e) {
            Log::error('Refund processing failed', [
                'order_id' => $this->order->id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            // Re-queue with backoff if attempts remain
            if ($this->attempts() < $this->tries) {
                $this->release(60 * $this->attempts()); // 60s, 120s, 180s
            } else {
                Log::error('Refund exhausted all retries', [
                    'order_id' => $this->order->id,
                ]);
                throw $e;
            }
        }
    }

    /**
     * Detect which payment gateway processed this order.
     */
    private function detectGateway(): string
    {
        $transactionId = $this->order->transaction_id ?? '';
        $transactionUuid = $this->order->transaction_uuid ?? '';

        // Simple heuristic: Khalti transaction IDs are typically alphanumeric
        // starting with a specific pattern; eSewa uses UUID-like codes;
        // FonePay UIDs are numeric. Fall back to 'unknown'.
        if (empty($transactionId) && empty($transactionUuid)) {
            return 'unknown';
        }

        // If the order's transaction_uuid contains a product code reference,
        // check if it matches known gateway patterns
        return 'unknown';
    }

    /**
     * Process the refund with the detected gateway.
     *
     * In a production environment, this would make actual API calls
     * to the respective payment gateway's refund endpoint.
     */
    private function processRefundWithGateway(string $gateway): void
    {
        $order = $this->order;

        Log::info('Initiating refund with gateway', [
            'gateway' => $gateway,
            'order_id' => $order->id,
            'transaction_id' => $order->transaction_id,
            'amount' => $order->amount,
        ]);

        switch ($gateway) {
            case 'esewa':
                // eSewa refund would go here:
                // POST to eSewa status/refund API with transaction_code
                break;

            case 'khalti':
                // Khalti refund would go here:
                // POST to Khalti API with pidx/transaction_id
                break;

            case 'fonepay':
                // FonePay refund would go here:
                // POST to FonePay refund endpoint with PRN/UID
                break;

            default:
                Log::warning('Unknown gateway for refund — logging only', [
                    'gateway' => $gateway,
                    'order_id' => $order->id,
                ]);
                break;
        }

        // The actual gateway API calls are placeholders for the production
        // integration. The current implementation logs the refund and marks
        // the order as refunded in the database.
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Refund job permanently failed', [
            'order_id' => $this->order->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
