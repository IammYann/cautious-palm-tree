<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    /**
     * Initiate purchase - create order and generate eSewa payment form
     * Following eSewa ePay documentation: http://developer.esewa.com.np/pages/Epay#transactionflow
     */
    public function initiate(Product $product)
    {
        // Only authenticated users can purchase
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to purchase products');
        }

        // Create pending order with unique transaction UUID
        $transactionUuid = $this->generateTransactionUuid();
        
        $order = Order::create([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
            'amount' => $product->price,
            'quantity' => 1,
            'status' => 'pending',
            'transaction_id' => $transactionUuid,
        ]);

        // Get eSewa configuration
        $productCode = config('esewa.product_code');
        $secretKey = config('esewa.secret_key');
        $successUrl = route('purchase.success');
        $failureUrl = route('purchase.failure');

        // Calculate amounts (no tax, service charge or delivery charge for this simple example)
        $amount = (float)$product->price;
        $taxAmount = 0.00;
        $serviceCharge = 0.00;
        $deliveryCharge = 0.00;
        $totalAmount = $amount + $taxAmount + $serviceCharge + $deliveryCharge;

        // Generate signature following eSewa documentation
        $signature = $this->generateSignature(
            $totalAmount,
            $transactionUuid,
            $productCode,
            $secretKey
        );

        // Prepare eSewa payment form data as per documentation
        // NOTE: All string values per eSewa docs
        $esewaData = [
            'amount' => (string)$amount,
            'tax_amount' => (string)(int)$taxAmount,
            'total_amount' => (string)$totalAmount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
            'product_service_charge' => (string)(int)$serviceCharge,
            'product_delivery_charge' => (string)(int)$deliveryCharge,
            'success_url' => $successUrl,
            'failure_url' => $failureUrl,
            'signed_field_names' => 'total_amount,transaction_uuid,product_code',
            'signature' => $signature,
        ];

        Log::info('eSewa Payment initiated', [
            'order_id' => $order->id,
            'transaction_uuid' => $transactionUuid,
            'total_amount' => $totalAmount,
            'product_code' => $productCode,
            'signature' => $signature,
            'message' => "{$totalAmount},{$transactionUuid},{$productCode}",
        ]);

        return view('purchases.checkout', [
            'order' => $order,
            'product' => $product,
            'esewaData' => $esewaData,
            'esewaUrl' => config('esewa.sandbox') 
                ? 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'
                : 'https://epay.esewa.com.np/api/epay/main/v2/form',
        ]);
    }

    /**
     * Handle eSewa success callback
     * eSewa redirects here after successful payment with Base64 encoded response
     */
    public function success(Request $request)
    {
        // eSewa sends response as Base64 encoded JSON in 'data' parameter
        $encodedData = $request->query('data');

        if (!$encodedData) {
            Log::error('eSewa success: No data received');
            return redirect()->route('products.index')->with('error', 'No payment data received');
        }

        // Decode Base64 response as per eSewa documentation
        $decodedData = base64_decode($encodedData);
        $responseData = json_decode($decodedData, true);

        Log::info('eSewa success response', $responseData);

        if (!$responseData || !isset($responseData['transaction_uuid'])) {
            Log::error('eSewa success: Invalid response data', ['data' => $decodedData]);
            return redirect()->route('products.index')->with('error', 'Invalid payment response');
        }

        $transactionUuid = $responseData['transaction_uuid'];
        $order = Order::where('transaction_id', $transactionUuid)->first();

        if (!$order || $order->user_id !== Auth::id()) {
            Log::error('eSewa success: Order not found', ['uuid' => $transactionUuid]);
            return redirect()->route('products.index')->with('error', 'Order not found');
        }

        // Verify signature as per eSewa documentation
        if (!$this->verifySignature($responseData)) {
            Log::error('eSewa success: Signature verification failed', $responseData);
            $order->markAsFailed();
            return redirect()->route('products.index')->with('error', 'Signature verification failed');
        }

        // Check payment status
        if ($responseData['status'] === 'COMPLETE') {
            $order->markAsCompleted($responseData['transaction_code'] ?? $transactionUuid);
            Log::info('Payment completed', ['order_id' => $order->id, 'status' => $responseData['status']]);
            
            // Dispatch event to send emails to buyer and seller
            \App\Events\productpurchase::dispatch($order);
            
            return redirect()->route('purchase.invoice', $order)->with('success', 'Payment successful! Thank you for your purchase.');
        }

        // If not complete, mark as failed
        $order->markAsFailed();
        Log::warning('Payment not complete', ['order_id' => $order->id, 'status' => $responseData['status']]);
        return redirect()->route('products.index')->with('error', 'Payment failed with status: ' . $responseData['status']);
    }

    /**
     * Handle eSewa failure callback
     * eSewa redirects here if payment fails or user cancels
     */
    public function failure(Request $request)
    {
        $encodedData = $request->query('data');

        if ($encodedData) {
            $decodedData = base64_decode($encodedData);
            $responseData = json_decode($decodedData, true);

            Log::warning('eSewa failure response', $responseData);

            if (isset($responseData['transaction_uuid'])) {
                $order = Order::where('transaction_id', $responseData['transaction_uuid'])->first();
                if ($order) {
                    $order->markAsFailed();
                }
            }
        }

        return redirect()->route('products.index')->with('error', 'Payment failed or was cancelled');
    }

    /**
     * Generate HMAC SHA256 signature as per eSewa documentation
     * Message format: total_amount,transaction_uuid,product_code
     * Algorithm: HMAC SHA256 -> Base64 encode
     * 
     * IMPORTANT: Amount must be formatted consistently for signature generation
     */
    private function generateSignature($totalAmount, $transactionUuid, $productCode, $secretKey)
    {
        // Format total_amount as string
        // Use simple string conversion to avoid precision issues
        $amountStr = (string)$totalAmount;
        
        // If the amount is a whole number, it will be like "260" or "260.0" from float cast
        // Let's ensure it's consistent by converting through number_format then removing trailing zeros
        $formattedAmount = rtrim(number_format($totalAmount, 2, '.', ''), '0');
        // If it ends with '.', keep one decimal place
        if (substr($formattedAmount, -1) === '.') {
            $formattedAmount = $formattedAmount . '0';
        }
        
        // Build message in exact order: total_amount,transaction_uuid,product_code
        // This is critical - the order and format must match exactly what eSewa expects
        $message = "{$formattedAmount},{$transactionUuid},{$productCode}";

        Log::debug('eSewa Signature Generation', [
            'total_amount_raw' => $totalAmount,
            'total_amount_formatted' => $formattedAmount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
            'message' => $message,
            'secret_key_length' => strlen($secretKey),
        ]);

        // Generate HMAC SHA256 with binary output
        $signature = hash_hmac('sha256', $message, $secretKey, true);

        // Return Base64 encoded signature
        $base64Signature = base64_encode($signature);
        
        Log::debug('eSewa Signature Generated', [
            'signature' => $base64Signature,
            'message' => $message,
        ]);

        return $base64Signature;
    }

    /**
     * Verify signature from eSewa response
     * As per eSewa documentation, verify the signature matches
     */
    private function verifySignature($responseData)
    {
        // Extract data needed for signature verification
        $transactionCode = $responseData['transaction_code'] ?? '';
        $status = $responseData['status'] ?? '';
        $totalAmount = $responseData['total_amount'] ?? '';
        $transactionUuid = $responseData['transaction_uuid'] ?? '';
        $productCode = $responseData['product_code'] ?? '';
        $signedFieldNames = $responseData['signed_field_names'] ?? '';
        $receivedSignature = $responseData['signature'] ?? '';

        if (!$receivedSignature) {
            return false;
        }

        $secretKey = config('esewa.secret_key');

        // Reconstruct message using the same order as signed_field_names
        $fieldNames = explode(',', $signedFieldNames);
        $messageArray = [];

        foreach ($fieldNames as $field) {
            $field = trim($field);
            switch ($field) {
                case 'transaction_code':
                    $messageArray[] = $transactionCode;
                    break;
                case 'status':
                    $messageArray[] = $status;
                    break;
                case 'total_amount':
                    $messageArray[] = $totalAmount;
                    break;
                case 'transaction_uuid':
                    $messageArray[] = $transactionUuid;
                    break;
                case 'product_code':
                    $messageArray[] = $productCode;
                    break;
            }
        }

        $message = implode(',', $messageArray);

        // Generate expected signature
        $signature = hash_hmac('sha256', $message, $secretKey, true);
        $expectedSignature = base64_encode($signature);

        // Compare signatures using timing-safe comparison
        return hash_equals($expectedSignature, $receivedSignature);
    }

    /**
     * Generate unique transaction UUID
     * Format: YYYYMMDD-HHmmss-randomhex (alphanumeric and hyphen only)
     */
    private function generateTransactionUuid()
    {
        return date('Ymd-His') . '-' . substr(uniqid(), -4);
    }

    /**
     * Show purchase invoice
     */
    public function invoice(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('purchases.invoice', compact('order'));
    }

    /**
     * Show user's purchase history
     */
    public function history()
    {
        $orders = Auth::user()->orders()
            ->with('product')
            ->latest()
            ->paginate(10);

        return view('purchases.history', compact('orders'));
    }
}