<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentWebhookController extends Controller
{
    /**
     * Generate HMAC-SHA256 signature for eSewa
     */
    private function generateEsewaSignature(string $message): string
    {
        $secret = config('esewa.secret_key');
        $hash = hash_hmac('sha256', $message, $secret, true);
        return base64_encode($hash);
    }

    /**
     * Generate HMAC-SHA512 signature for FonePay
     */
    private function generateFonepaySignature(string $message): string
    {
        $secret = config('fonepay.secret_key');
        return hash_hmac('sha512', $message, $secret);
    }

    /**
     * Handle asynchronous server-to-server webhook from eSewa
     *
     * eSewa sends a POST request with JSON body containing:
     * transaction_code, status, total_amount, transaction_uuid,
     * product_code, signature, signed_field_names
     */
    public function esewaWebhook(Request $request): JsonResponse
    {
        Log::info('eSewa webhook received', [
            'body' => $request->all(),
        ]);

        $payload = $request->all();

        $transactionCode = $payload['transaction_code'] ?? null;
        $status = $payload['status'] ?? null;
        $totalAmount = $payload['total_amount'] ?? null;
        $transactionUuid = $payload['transaction_uuid'] ?? null;
        $productCode = $payload['product_code'] ?? null;
        $responseSignature = $payload['signature'] ?? null;
        $signedFieldNames = $payload['signed_field_names'] ?? null;

        // Validate required fields
        if (!$transactionUuid || !$status || !$totalAmount) {
            Log::warning('eSewa webhook: missing required fields', ['payload' => $payload]);
            return response()->json(['error' => 'Missing required fields'], 422);
        }

        // Verify the response signature if present
        if ($signedFieldNames && $responseSignature) {
            $fields = explode(',', $signedFieldNames);
            $signatureParts = [];
            foreach ($fields as $field) {
                $signatureParts[] = $field . '=' . ($payload[$field] ?? '');
            }
            $signatureMessage = implode(',', $signatureParts);
            $expectedSignature = $this->generateEsewaSignature($signatureMessage);

            Log::info('eSewa webhook signature check', [
                'message' => $signatureMessage,
                'expected' => $expectedSignature,
                'received' => $responseSignature,
                'match' => $expectedSignature === $responseSignature,
            ]);

            if ($expectedSignature !== $responseSignature) {
                Log::error('eSewa webhook: signature MISMATCH');
                return response()->json(['error' => 'Signature mismatch'], 403);
            }
        } else {
            Log::warning('eSewa webhook: no signature to verify');
            return response()->json(['error' => 'Missing signature'], 422);
        }

        // Find the order by transaction_uuid
        $order = Order::where('transaction_uuid', $transactionUuid)->first();

        if (!$order) {
            Log::error('eSewa webhook: order not found', ['uuid' => $transactionUuid]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Check if payment was actually COMPLETE
        if ($status !== 'COMPLETE') {
            Log::warning('eSewa webhook: status not COMPLETE', ['status' => $status]);
            return response()->json(['error' => 'Payment not completed'], 422);
        }

        // Verify the amount matches using integer paisa comparison
        $cleanedTotalAmount = str_replace(',', '', $totalAmount);
        $orderPaisa = (int) round($order->amount * 100);
        $receivedPaisa = (int) round($cleanedTotalAmount * 100);

        if ($orderPaisa !== $receivedPaisa) {
            Log::error('eSewa webhook: amount MISMATCH', [
                'esewa_paisa' => $receivedPaisa,
                'order_paisa' => $orderPaisa,
            ]);
            return response()->json(['error' => 'Amount mismatch'], 409);
        }

        // Pessimistic locking to prevent race conditions
        $purchaseSuccessful = false;
        $requiresRefund = false;

        try {
            DB::transaction(function () use ($order, $transactionCode, &$purchaseSuccessful, &$requiresRefund) {
                // 1. Lock the Order to prevent duplicate webhook processing
                $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

                // Idempotency: If already completed or not pending, skip
                if ($lockedOrder->status === 'completed') {
                    $purchaseSuccessful = true;
                    return;
                }

                if ($lockedOrder->status !== 'pending') {
                    // Already processed (failed, refunded, refund_required) — still ack 200
                    return;
                }

                // 2. Lock the Product to serialize access across concurrent buyers
                $product = \App\Models\Product::where('id', $lockedOrder->product_id)->lockForUpdate()->first();

                // 3. Race Condition Check: Is the product still available?
                if (!$product->is_available) {
                    $requiresRefund = true;
                    $lockedOrder->update(['status' => 'refund_required']);
                    return;
                }

                // 4. Finalize the Purchase
                $lockedOrder->markAsCompleted($transactionCode);
                $product->update(['is_available' => false]);

                $purchaseSuccessful = true;
            });
        } catch (Throwable $e) {
            Log::error('eSewa webhook: transaction failed', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }

        if ($requiresRefund) {
            Log::warning('eSewa webhook: race condition prevented. Refund required.', [
                'order_id' => $order->id,
                'transaction_code' => $transactionCode,
            ]);
            return response()->json(['status' => 'refund_required'], 200);
        }

        if ($purchaseSuccessful) {
            Cache::forget('all_products');
            Cache::forget('admin_products');
            Cache::forget('product_' . $order->product_id);

            \App\Events\productpurchase::dispatch($order);

            Log::info('eSewa webhook: payment completed successfully', [
                'order_id' => $order->id,
                'transaction_code' => $transactionCode,
            ]);

            return response()->json(['status' => 'completed'], 200);
        }

        // Order was already completed before this webhook arrived
        return response()->json(['status' => $order->fresh()->status], 200);
    }

    /**
     * Handle asynchronous server-to-server webhook from Khalti
     *
     * Khalti sends a POST request with JSON body containing:
     * pidx, status, transaction_id, total_amount, purchase_order_id, etc.
     */
    public function khaltiWebhook(Request $request): JsonResponse
    {
        Log::info('Khalti webhook received', [
            'body' => $request->all(),
        ]);

        $payload = $request->all();
        $pidx = $payload['pidx'] ?? null;
        $status = $payload['status'] ?? null;
        $transactionUuid = $payload['purchase_order_id'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;

        if (!$pidx) {
            Log::warning('Khalti webhook: missing pidx');
            return response()->json(['error' => 'Missing pidx'], 422);
        }

        // Verify via Khalti lookup API
        $baseUrl = config('khalti.base_url');
        $secretKey = config('khalti.secret_key');

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Authorization' => 'Key ' . $secretKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($baseUrl . '/epayment/lookup/', [
                    'pidx' => $pidx,
                ]);

            Log::info('Khalti webhook lookup verification', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Khalti lookup API returned status ' . $response->status());
            }
        } catch (Throwable $e) {
            Log::error('Khalti webhook: lookup API error', [
                'error' => $e->getMessage(),
                'pidx' => $pidx,
            ]);
            return response()->json(['error' => 'Lookup API unavailable'], 502);
        }

        $data = $response->json();

        // Verify status from lookup
        if (!isset($data['status']) || (strtolower($data['status']) !== 'completed' && strtolower($data['status']) !== 'complete')) {
            Log::warning('Khalti webhook: payment not completed', ['status' => $data['status'] ?? null]);
            return response()->json(['error' => 'Payment not completed'], 422);
        }

        // Determine the transaction UUID from the payload or lookup data
        $effectiveTransactionUuid = $transactionUuid ?? $data['purchase_order_id'] ?? null;

        if (!$effectiveTransactionUuid) {
            Log::error('Khalti webhook: no purchase_order_id found');
            return response()->json(['error' => 'Missing purchase_order_id'], 422);
        }

        // Find order by transaction_uuid, falling back to pidx
        $order = Order::where('transaction_uuid', $effectiveTransactionUuid)->first();
        if (!$order) {
            $order = Order::where('transaction_id', $pidx)->first();
        }

        if (!$order) {
            Log::error('Khalti webhook: order not found', [
                'transaction_uuid' => $effectiveTransactionUuid,
                'pidx' => $pidx,
            ]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Verify amount matches using integer paisa comparison
        $khaltiTotalAmount = $data['total_amount'] ?? 0;
        $orderPaisa = (int) round($order->amount * 100);

        if ($orderPaisa !== (int) $khaltiTotalAmount) {
            Log::error('Khalti webhook: amount MISMATCH', [
                'khalti_paisa' => $khaltiTotalAmount,
                'order_paisa' => $orderPaisa,
            ]);
            return response()->json(['error' => 'Amount mismatch'], 409);
        }

        // Pessimistic locking to prevent race conditions
        $purchaseSuccessful = false;
        $requiresRefund = false;
        $actualTxnId = $data['transaction_id'] ?? $transactionId ?? $pidx;

        try {
            DB::transaction(function () use ($order, $actualTxnId, $pidx, &$purchaseSuccessful, &$requiresRefund) {
                // 1. Lock the Order
                $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

                if ($lockedOrder->status === 'completed') {
                    $purchaseSuccessful = true;
                    return;
                }

                if ($lockedOrder->status !== 'pending') {
                    return;
                }

                // 2. Lock the Product
                $product = \App\Models\Product::where('id', $lockedOrder->product_id)->lockForUpdate()->first();

                // 3. Race condition check
                if (!$product->is_available) {
                    $requiresRefund = true;
                    $lockedOrder->update(['status' => 'refund_required']);
                    return;
                }

                // 4. Finalize
                $lockedOrder->markAsCompleted($actualTxnId);
                $product->update(['is_available' => false]);

                $purchaseSuccessful = true;
            });
        } catch (Throwable $e) {
            Log::error('Khalti webhook: transaction failed', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }

        if ($requiresRefund) {
            Log::warning('Khalti webhook: race condition prevented. Refund required.', [
                'order_id' => $order->id,
                'transaction_code' => $actualTxnId,
            ]);
            return response()->json(['status' => 'refund_required'], 200);
        }

        if ($purchaseSuccessful) {
            Cache::forget('all_products');
            Cache::forget('admin_products');
            Cache::forget('product_' . $order->product_id);

            \App\Events\productpurchase::dispatch($order);

            Log::info('Khalti webhook: payment completed successfully', [
                'order_id' => $order->id,
                'transaction_code' => $actualTxnId,
            ]);

            return response()->json(['status' => 'completed'], 200);
        }

        return response()->json(['status' => $order->fresh()->status], 200);
    }

    /**
     * Handle asynchronous server-to-server webhook from FonePay
     *
     * FonePay sends a POST request with parameters:
     * PRN, PID, PS, RC, UID, BC, INI, P_AMT, R_AMT, DV
     */
    public function fonepayWebhook(Request $request): JsonResponse
    {
        Log::info('FonePay webhook received', [
            'body' => $request->all(),
        ]);

        $payload = $request->all();

        $prn = $payload['PRN'] ?? $payload['prn'] ?? null;
        $pid = $payload['PID'] ?? $payload['pid'] ?? null;
        $ps = $payload['PS'] ?? $payload['ps'] ?? null;
        $rc = $payload['RC'] ?? $payload['rc'] ?? null;
        $uid = $payload['UID'] ?? $payload['uid'] ?? null;
        $bc = $payload['BC'] ?? $payload['bc'] ?? null;
        $ini = $payload['INI'] ?? $payload['ini'] ?? null;
        $pAmt = $payload['P_AMT'] ?? $payload['p_amt'] ?? null;
        $rAmt = $payload['R_AMT'] ?? $payload['r_amt'] ?? null;
        $dv = $payload['DV'] ?? $payload['dv'] ?? null;

        if (!$prn || !$dv) {
            Log::warning('FonePay webhook: missing required fields (PRN or DV)');
            return response()->json(['error' => 'Missing required fields'], 422);
        }

        // Verify the response signature (DV)
        $verificationMessage = "{$prn},{$pid},{$ps},{$rc},{$uid},{$bc},{$ini},{$pAmt},{$rAmt}";
        $expectedDv = $this->generateFonepaySignature($verificationMessage);

        Log::info('FonePay webhook signature verification', [
            'message' => $verificationMessage,
            'expected_dv' => $expectedDv,
            'received_dv' => $dv,
            'match' => strtolower($expectedDv) === strtolower($dv),
        ]);

        if (strtolower($expectedDv) !== strtolower($dv)) {
            Log::error('FonePay webhook: signature MISMATCH');
            return response()->json(['error' => 'Signature mismatch'], 403);
        }

        // Find the order by PRN
        $order = Order::where('transaction_uuid', $prn)->first();

        if (!$order) {
            Log::error('FonePay webhook: order not found', ['PRN' => $prn]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Check if payment was successful
        if (strtolower($ps ?? '') !== 'true') {
            Log::warning('FonePay webhook: payment not successful', ['PS' => $ps, 'RC' => $rc]);
            return response()->json(['error' => 'Payment not successful'], 422);
        }

        // Verify the amount matches using integer paisa comparison
        $cleanedPaidAmount = str_replace(',', '', $pAmt ?? '0');
        $orderPaisa = (int) round($order->amount * 100);
        $receivedPaisa = (int) round($cleanedPaidAmount * 100);

        if ($orderPaisa !== $receivedPaisa) {
            Log::error('FonePay webhook: amount MISMATCH', [
                'fonepay_paisa' => $receivedPaisa,
                'order_paisa' => $orderPaisa,
            ]);
            return response()->json(['error' => 'Amount mismatch'], 409);
        }

        // Pessimistic locking to prevent race conditions
        $purchaseSuccessful = false;
        $requiresRefund = false;
        $transactionCode = $uid ?? $prn;

        try {
            DB::transaction(function () use ($order, $transactionCode, &$purchaseSuccessful, &$requiresRefund) {
                // 1. Lock the Order
                $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

                if ($lockedOrder->status === 'completed') {
                    $purchaseSuccessful = true;
                    return;
                }

                if ($lockedOrder->status !== 'pending') {
                    return;
                }

                // 2. Lock the Product
                $product = \App\Models\Product::where('id', $lockedOrder->product_id)->lockForUpdate()->first();

                // 3. Race condition check
                if (!$product->is_available) {
                    $requiresRefund = true;
                    $lockedOrder->update(['status' => 'refund_required']);
                    return;
                }

                // 4. Finalize the purchase
                $lockedOrder->markAsCompleted($transactionCode);
                $product->update(['is_available' => false]);

                $purchaseSuccessful = true;
            });
        } catch (Throwable $e) {
            Log::error('FonePay webhook: transaction failed', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }

        if ($requiresRefund) {
            Log::warning('FonePay webhook: race condition prevented. Refund required.', [
                'order_id' => $order->id,
                'transaction_code' => $transactionCode,
            ]);
            return response()->json(['status' => 'refund_required'], 200);
        }

        if ($purchaseSuccessful) {
            Cache::forget('all_products');
            Cache::forget('admin_products');
            Cache::forget('product_' . $order->product_id);

            \App\Events\productpurchase::dispatch($order);

            Log::info('FonePay webhook: payment completed successfully', [
                'order_id' => $order->id,
                'transaction_code' => $transactionCode,
            ]);

            return response()->json(['status' => 'completed'], 200);
        }

        return response()->json(['status' => $order->fresh()->status], 200);
    }
}
