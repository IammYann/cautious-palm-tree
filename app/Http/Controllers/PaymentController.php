<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentController extends Controller
{
    /**
     * Generate HMAC-SHA256 signature for eSewa
     */
    private function generateSignature(string $message): string
    {
        $secret = config('esewa.secret_key');
        $hash = hash_hmac('sha256', $message, $secret, true);
        return base64_encode($hash);
    }

    /**
     * Initiate eSewa payment for a product
     */
    public function initiate(Request $request, Product $product)
    {
        // Check if the product is available
        if (!$product->is_available) {
            return redirect()->route('products.index')
                ->with('error', 'This product is no longer available for purchase.');
        }

        DB::beginTransaction();
        try {
            $quantity = 1;
            $amount = $product->price * $quantity;
            $taxAmount = 0;
            $serviceCharge = 0;
            $deliveryCharge = 0;
            $totalAmount = $amount + $taxAmount + $serviceCharge + $deliveryCharge;

            // Create a pending order
            $order = Order::create([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
                'amount' => $totalAmount,
                'quantity' => $quantity,
                'status' => 'pending',
            ]);

            // Generate unique transaction UUID (alphanumeric and hyphen only)
            $transactionUuid = $order->id . '-' . now()->format('ymdHis');
            $order->update(['transaction_uuid' => $transactionUuid]);

            $productCode = config('esewa.merchant_code');

            // eSewa requires total_amount to be formatted to 2 decimal places
            $formattedTotalAmount = number_format($totalAmount, 2, '.', '');
            $formattedAmount = number_format($amount, 2, '.', '');
            $formattedTaxAmount = number_format($taxAmount, 2, '.', '');
            $formattedServiceCharge = number_format($serviceCharge, 2, '.', '');
            $formattedDeliveryCharge = number_format($deliveryCharge, 2, '.', '');

            // Generate HMAC-SHA256 signature
            // Input format: total_amount={total},transaction_uuid={uuid},product_code={code}
            $signatureMessage = "total_amount={$formattedTotalAmount},transaction_uuid={$transactionUuid},product_code={$productCode}";
            $signature = $this->generateSignature($signatureMessage);

            $paymentData = [
                'amount' => $formattedAmount,
                'tax_amount' => $formattedTaxAmount,
                'total_amount' => $formattedTotalAmount,
                'transaction_uuid' => $transactionUuid,
                'product_code' => $productCode,
                'product_service_charge' => $formattedServiceCharge,
                'product_delivery_charge' => $formattedDeliveryCharge,
                'success_url' => route('payment.esewa.success'),
                'failure_url' => route('payment.esewa.failure'),
                'signed_field_names' => 'total_amount,transaction_uuid,product_code',
                'signature' => $signature,
            ];

            $paymentUrl = config('esewa.payment_url');

            \Illuminate\Support\Facades\Log::info('eSewa payment initiated', [
                'order_id'       => $order->id,
                'success_url'    => $paymentData['success_url'],
                'failure_url'    => $paymentData['failure_url'],
                'total_amount'   => $formattedTotalAmount,
                'transaction_uuid' => $transactionUuid,
                'signature'      => $signature,
                'payment_url'    => $paymentUrl,
            ]);

            DB::commit();

            return view('payment.esewa-form', compact('paymentData', 'paymentUrl', 'product'));
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('eSewa payment initiation failed', [
                'message' => $e->getMessage(),
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);
            if (isset($order) && $order->exists) {
                try {
                    $order->markAsFailed();
                } catch (Throwable $_) {
                    // ignore
                }
            }

            return redirect()->route('products.index')
                ->with('error', 'Unable to start payment. Please try again later.');
        }
    }

    /**
     * Handle successful payment callback from eSewa
     */
    public function success(Request $request)
    {
        $encodedData = $request->query('data');

        \Illuminate\Support\Facades\Log::info('eSewa success callback received', [
            'has_data' => !empty($encodedData),
            'raw_data' => $encodedData,
        ]);

        if (!$encodedData) {
            \Illuminate\Support\Facades\Log::warning('eSewa callback: no data parameter');
            return redirect()->route('products.index')
                ->with('error', 'Invalid payment response.');
        }

        try {
            // Safely decode the Base64 response
            $decoded = @base64_decode($encodedData, true);
            if ($decoded === false) {
                throw new \Exception('Invalid base64 encoding');
            }

            $decodedData = json_decode($decoded, true);
            if (!is_array($decodedData)) {
                throw new \Exception('Invalid JSON in callback data');
            }
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::error('eSewa callback decode failed', [
                'error' => $e->getMessage(),
                'raw_data' => substr($encodedData, 0, 100),
            ]);
            return redirect()->route('products.index')
                ->with('error', 'Could not decode payment response.');
        }

        \Illuminate\Support\Facades\Log::info('eSewa decoded response', [
            'decoded' => $decodedData,
        ]);

        if (!$decodedData) {
            \Illuminate\Support\Facades\Log::warning('eSewa callback: failed to decode data');
            return redirect()->route('products.index')
                ->with('error', 'Could not decode payment response.');
        }

        $transactionCode = $decodedData['transaction_code'] ?? null;
        $status = $decodedData['status'] ?? null;
        $totalAmount = $decodedData['total_amount'] ?? null;
        $transactionUuid = $decodedData['transaction_uuid'] ?? null;
        $productCode = $decodedData['product_code'] ?? null;
        $responseSignature = $decodedData['signature'] ?? null;
        $signedFieldNames = $decodedData['signed_field_names'] ?? null;

        // Verify the response signature
        if ($signedFieldNames && $responseSignature) {
            $fields = explode(',', $signedFieldNames);
            $signatureParts = [];
            foreach ($fields as $field) {
                $signatureParts[] = $field . '=' . ($decodedData[$field] ?? '');
            }
            $signatureMessage = implode(',', $signatureParts);
            $expectedSignature = $this->generateSignature($signatureMessage);

            \Illuminate\Support\Facades\Log::info('eSewa signature check', [
                'message' => $signatureMessage,
                'expected' => $expectedSignature,
                'received' => $responseSignature,
                'match' => $expectedSignature === $responseSignature,
            ]);

            if ($expectedSignature !== $responseSignature) {
                \Illuminate\Support\Facades\Log::error('eSewa signature MISMATCH - payment rejected');
                return redirect()->route('products.index')
                    ->with('error', 'Payment verification failed. Signature mismatch.');
            }
        }

        // Find the order by transaction_uuid
        $order = Order::where('transaction_uuid', $transactionUuid)->first();

        \Illuminate\Support\Facades\Log::info('eSewa order lookup', [
            'transaction_uuid' => $transactionUuid,
            'order_found' => !is_null($order),
            'order_id' => $order?->id,
            'order_amount' => $order?->amount,
            'esewa_total_amount' => $totalAmount,
            'status' => $status,
        ]);

        if (!$order) {
            \Illuminate\Support\Facades\Log::error('eSewa order not found', ['uuid' => $transactionUuid]);
            return redirect()->route('products.index')
                ->with('error', 'Order not found for this transaction.');
        }

        // Idempotency: if order already completed, show success and skip re-processing
        if ($order->status === 'completed') {
            \Illuminate\Support\Facades\Log::info('eSewa callback for already completed order', ['order_id' => $order->id]);
            return view('payment.success', [
                'order' => $order->load('product'),
                'transactionCode' => $transactionCode ?? $order->transaction_id ?? $order->transaction_uuid,
            ]);
        }

        // Verify the amount matches
        // Strip commas from eSewa amount (e.g. "1,299.21" -> "1299.21")
        $cleanedTotalAmount = str_replace(',', '', $totalAmount);
        if ((float) $cleanedTotalAmount != (float) $order->amount) {
            \Illuminate\Support\Facades\Log::error('eSewa amount MISMATCH', [
                'esewa_amount' => $cleanedTotalAmount,
                'order_amount' => $order->amount,
            ]);
            $order->markAsFailed();
            return redirect()->route('products.index')
                ->with('error', 'Payment amount mismatch. Potential fraud detected.');
        }

        // Mark order as completed
        if ($status === 'COMPLETE') {
            if ($order->status !== 'pending') {
                \Illuminate\Support\Facades\Log::info('eSewa callback received for non-pending order', ['order_id' => $order->id, 'status' => $order->status]);
                return view('payment.success', [
                    'order' => $order->load('product'),
                    'transactionCode' => $transactionCode ?? $order->transaction_id ?? $order->transaction_uuid,
                ]);
            }

            $order->markAsCompleted($transactionCode);

            // Mark the product as unavailable (sold)
            $order->product->update(['is_available' => false]);

            // Clear cache
            \Illuminate\Support\Facades\Cache::forget('all_products');
            \Illuminate\Support\Facades\Cache::forget('admin_products');
            \Illuminate\Support\Facades\Cache::forget('product_' . $order->product_id);

            // Dispatch event to send emails to buyer and seller
            \App\Events\productpurchase::dispatch($order);

            \Illuminate\Support\Facades\Log::info('eSewa payment completed successfully', [
                'order_id' => $order->id,
                'transaction_code' => $transactionCode,
            ]);

            return view('payment.success', [
                'order' => $order->load('product'),
                'transactionCode' => $transactionCode,
            ]);
        }

        \Illuminate\Support\Facades\Log::warning('eSewa payment status not COMPLETE', ['status' => $status]);
        return redirect()->route('products.index')
            ->with('error', 'Payment was not completed.');
    }

    /**
     * Handle failed payment callback from eSewa
     */
    public function failure(Request $request)
    {
        // Log ALL incoming data from eSewa failure callback
        $encodedData = $request->query('data');
        $decodedData = $encodedData ? json_decode(base64_decode($encodedData), true) : null;

        \Illuminate\Support\Facades\Log::warning('eSewa FAILURE callback received', [
            'has_data'    => !empty($encodedData),
            'raw_data'    => $encodedData,
            'decoded'     => $decodedData,
            'all_query'   => $request->query(),
        ]);

        if ($decodedData) {
            $transactionUuid = $decodedData['transaction_uuid'] ?? null;

            if ($transactionUuid) {
                $order = Order::where('transaction_uuid', $transactionUuid)->first();
                if ($order && $order->status === 'pending') {
                    $order->markAsFailed();
                }
            }
        }

        return redirect()->route('products.index')
            ->with('error', 'Payment was cancelled or failed. Please try again.');
    }

    /**
     * Initiate Khalti payment for a product
     */
    public function khaltiInitiate(Request $request, Product $product)
    {
        // Check if the product is available
        if (!$product->is_available) {
            return redirect()->route('products.index')
                ->with('error', 'This product is no longer available for purchase.');
        }

        $quantity = 1;
        $amount = $product->price * $quantity;
        $totalAmount = $amount; 

        // Create a pending order
        $order = Order::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'amount' => $totalAmount,
            'quantity' => $quantity,
            'status' => 'pending',
        ]);

        // Generate unique transaction UUID
        $transactionUuid = $order->id . '-' . now()->format('ymdHis');
        $order->update(['transaction_uuid' => $transactionUuid]);

        $amountInPaisa = (int) round($totalAmount * 100);
        $baseUrl = config('khalti.base_url');
        $secretKey = trim((string) config('khalti.secret_key'));

        if (!$secretKey || str_contains(strtolower($secretKey), 'default') || str_contains(strtolower($secretKey), 'your_')) {
            Log::error('Khalti payment initiation aborted: secret key is missing or still a placeholder.', [
                'secret_key' => $secretKey,
            ]);

            $order->markAsFailed();
            return redirect()->route('products.index')
                ->with('error', 'Khalti is not configured correctly. Please set a real KHALTI_SECRET_KEY in your .env file.');
        }

        try {
            $response = Http::asJson()
                ->withHeaders([
                    'Authorization' => 'key ' . $secretKey,
                ])
                ->timeout(20)
                ->post($baseUrl . '/epayment/initiate/', [
                    'return_url' => route('payment.khalti.callback'),
                    'website_url' => config('app.url'),
                    'amount' => $amountInPaisa,
                    'purchase_order_id' => $transactionUuid,
                    'purchase_order_name' => substr($product->name, 0, 99),
                    'customer_info' => [
                        'name' => auth()->user()->name ?? 'Guest',
                        'email' => auth()->user()->email ?? 'guest@example.com',
                        'phone' => '9800000000',
                    ]
                ]);

            $responseBody = $response->json();

            Log::info('Khalti initiation response', [
                'status' => $response->status(),
                'body' => $responseBody,
            ]);

            if ($response->successful()) {
                $data = $responseBody;
                if (isset($data['payment_url'])) {
                    // Store pidx on the order
                    $order->update(['transaction_id' => $data['pidx'] ?? null]);
                    return redirect()->away($data['payment_url']);
                }
            }

            $order->markAsFailed();
            $detail = data_get($responseBody, 'detail', 'Unable to initiate Khalti payment. Please try again.');

            return redirect()->route('products.index')
                ->with('error', 'Khalti payment initiation failed: ' . $detail);
        } catch (Throwable $e) {
            Log::error('Khalti initiation HTTP error', [
                'message' => $e->getMessage(),
                'product_id' => $product->id,
                'user_id' => auth()->id(),
            ]);
            try {
                $order->markAsFailed();
            } catch (Throwable $_) {
                // ignore
            }
            return redirect()->route('products.index')
                ->with('error', 'Payment service is currently unavailable. Try again later.');
        }
    }

    /**
     * Handle successful payment callback from Khalti
     */
    public function khaltiCallback(Request $request)
    {
        $pidx = $request->query('pidx');
        $status = $request->query('status');
        $transactionUuid = $request->query('purchase_order_id');
        $transactionId = $request->query('transaction_id');

        Log::info('Khalti callback received', [
            'pidx' => $pidx,
            'status' => $status,
            'transaction_uuid' => $transactionUuid,
            'query' => $request->query(),
        ]);

        if (!$pidx) {
            return redirect()->route('products.index')
                ->with('error', 'Invalid payment response from Khalti.');
        }

        // Call lookup API to verify the transaction status
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

            Log::info('Khalti lookup verification response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if (!$response->successful()) {
                throw new \Exception('Khalti lookup API returned status ' . $response->status());
            }
        } catch (Throwable $e) {
            Log::error('Khalti lookup API error', [
                'error' => $e->getMessage(),
                'pidx' => $pidx,
                'transaction_uuid' => $transactionUuid,
            ]);

            if (isset($transactionUuid)) {
                $order = Order::where('transaction_uuid', $transactionUuid)->first();
                if ($order && $order->status === 'pending') {
                    try {
                        $order->markAsFailed();
                    } catch (Throwable $_) {
                        // ignore
                    }
                }
            }

            return redirect()->route('products.index')
                ->with('error', 'Payment verification service unavailable. Please contact support.');
        }

        if ($response->successful()) {
            $data = $response->json();
            
            // Find order by transaction_uuid
            $order = Order::where('transaction_uuid', $transactionUuid)->first();
            
            if (!$order) {
                // Try finding by pidx stored in transaction_id
                $order = Order::where('transaction_id', $pidx)->first();
            }

            if (!$order) {
                return redirect()->route('products.index')
                    ->with('error', 'Order not found for this transaction.');
            }

            // Idempotency: if already completed, show success
            if ($order->status === 'completed') {
                Log::info('Khalti callback for already completed order', ['order_id' => $order->id]);
                return view('payment.success', [
                    'order' => $order->load('product'),
                    'transactionCode' => $order->transaction_id ?? $order->transaction_uuid ?? $pidx,
                ]);
            }

            // Verify status is Completed or Complete
            if (isset($data['status']) && (strtolower($data['status']) === 'completed' || strtolower($data['status']) === 'complete')) {
                // Verify amount matches (convert paisa to rupees)
                $khaltiAmount = $data['total_amount'] / 100;
                if ((float)$khaltiAmount != (float)$order->amount) {
                    $order->markAsFailed();
                    return redirect()->route('products.index')
                        ->with('error', 'Payment amount mismatch. Potential fraud detected.');
                }

                // Mark order as completed
                $actualTxnId = $data['transaction_id'] ?? $transactionId ?? $pidx;
                $order->markAsCompleted($actualTxnId);

                // Mark product as unavailable
                $order->product->update(['is_available' => false]);

                // Clear cache
                Cache::forget('all_products');
                Cache::forget('admin_products');
                Cache::forget('product_' . $order->product_id);

                // Dispatch event
                \App\Events\productpurchase::dispatch($order);

                return view('payment.success', [
                    'order' => $order->load('product'),
                    'transactionCode' => $actualTxnId,
                ]);
            }
        }

        // Mark as failed if not completed
        if (isset($transactionUuid)) {
            $order = Order::where('transaction_uuid', $transactionUuid)->first();
            if ($order && $order->status === 'pending') {
                $order->markAsFailed();
            }
        }

        return redirect()->route('products.index')
            ->with('error', 'Khalti payment verification failed or cancelled.');
    }
}
