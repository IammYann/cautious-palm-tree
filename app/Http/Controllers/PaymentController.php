<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        return view('payment.esewa-form', compact('paymentData', 'paymentUrl', 'product'));
    }

    /**
     * Handle successful payment callback from eSewa
     */
    public function success(Request $request)
    {
        $encodedData = $request->query('data');

        if (!$encodedData) {
            return redirect()->route('products.index')
                ->with('error', 'Invalid payment response.');
        }

        // Decode the Base64 response
        $decodedData = json_decode(base64_decode($encodedData), true);

        if (!$decodedData) {
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

            if ($expectedSignature !== $responseSignature) {
                return redirect()->route('products.index')
                    ->with('error', 'Payment verification failed. Signature mismatch.');
            }
        }

        // Find the order by transaction_uuid
        $order = Order::where('transaction_uuid', $transactionUuid)->first();

        if (!$order) {
            return redirect()->route('products.index')
                ->with('error', 'Order not found for this transaction.');
        }

        // Verify the amount matches
        if ((float) $totalAmount != (float) $order->amount) {
            $order->markAsFailed();
            return redirect()->route('products.index')
                ->with('error', 'Payment amount mismatch. Potential fraud detected.');
        }

        // Mark order as completed
        if ($status === 'COMPLETE') {
            $order->markAsCompleted($transactionCode);

            // Mark the product as unavailable (sold)
            $order->product->update(['is_available' => false]);

            // Clear cache
            \Illuminate\Support\Facades\Cache::forget('all_products');
            \Illuminate\Support\Facades\Cache::forget('admin_products');
            \Illuminate\Support\Facades\Cache::forget('product_' . $order->product_id);

            return view('payment.success', [
                'order' => $order->load('product'),
                'transactionCode' => $transactionCode,
            ]);
        }

        return redirect()->route('products.index')
            ->with('error', 'Payment was not completed.');
    }

    /**
     * Handle failed payment callback from eSewa
     */
    public function failure(Request $request)
    {
        // Try to extract transaction_uuid from the response if available
        $encodedData = $request->query('data');

        if ($encodedData) {
            $decodedData = json_decode(base64_decode($encodedData), true);
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
}
