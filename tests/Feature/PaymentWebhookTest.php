<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->product = Product::factory()->create([
            'user_id' => $this->user->id,
            'is_available' => true,
        ]);
    }

    // ─── eSewa Webhook Tests ─────────────────────────────────────────

    /** Test eSewa webhook completes an order successfully */
    public function test_esewa_webhook_completes_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $payload = $this->buildEsewaWebhookPayload($order, 'COMPLETE');

        $response = $this->postJson(route('payment.webhook.esewa'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'completed']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
            'transaction_id' => 'TXN_123456',
        ]);
    }

    /** Test eSewa webhook is idempotent — processing an already completed order returns success */
    public function test_esewa_webhook_idempotent_for_completed_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'completed',
            'transaction_id' => 'existing_txn',
        ]);

        $payload = $this->buildEsewaWebhookPayload($order, 'COMPLETE');

        $response = $this->postJson(route('payment.webhook.esewa'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'completed']);

        // transaction_id should remain unchanged
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'transaction_id' => 'existing_txn',
        ]);
    }

    /** Test eSewa webhook detects signature mismatch */
    public function test_esewa_webhook_rejects_bad_signature()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'pending',
        ]);

        $payload = $this->buildEsewaWebhookPayload($order, 'COMPLETE');
        $payload['signature'] = 'tampered_signature';

        $response = $this->postJson(route('payment.webhook.esewa'), $payload);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Signature mismatch']);
    }

    /** Test eSewa webhook validates amount with integer paisa comparison */
    public function test_esewa_webhook_rejects_amount_mismatch()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $payload = $this->buildEsewaWebhookPayload($order, 'COMPLETE');
        $payload['total_amount'] = '200.00'; // mismatched amount
        // Re-sign with the wrong amount
        $message = "total_amount={$payload['total_amount']},transaction_uuid={$payload['transaction_uuid']},product_code={$payload['product_code']}";
        $payload['signature'] = $this->generateEsewaSignature($message);

        $response = $this->postJson(route('payment.webhook.esewa'), $payload);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Amount mismatch']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    /** Test eSewa webhook rejects non-COMPLETE status */
    public function test_esewa_webhook_rejects_non_complete_status()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'pending',
        ]);

        $payload = $this->buildEsewaWebhookPayload($order, 'FAILED');

        $response = $this->postJson(route('payment.webhook.esewa'), $payload);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Payment not completed']);
    }

    /** Test eSewa webhook handles race condition: product sold out -> refund_required */
    public function test_esewa_webhook_sets_refund_required_when_product_unavailable()
    {
        $this->product->update(['is_available' => false]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $payload = $this->buildEsewaWebhookPayload($order, 'COMPLETE');

        $response = $this->postJson(route('payment.webhook.esewa'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'refund_required']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refund_required',
        ]);
    }

    /** Test eSewa webhook rejects missing fields */
    public function test_esewa_webhook_rejects_missing_fields()
    {
        $response = $this->postJson(route('payment.webhook.esewa'), []);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Missing required fields']);
    }

    // ─── Khalti Webhook Tests ─────────────────────────────────────────

    /** Test Khalti webhook verifies via lookup API and completes order */
    public function test_khalti_webhook_completes_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $pidx = 'test_pidx_webhook';

        Http::fake([
            '*khalti*' => Http::response([
                'status' => 'Completed',
                'total_amount' => 10000, // 100.00 NPR in paisa
                'transaction_id' => 'khalti_txn_1',
                'purchase_order_id' => $order->transaction_uuid,
                'pidx' => $pidx,
            ], 200),
        ]);

        $payload = [
            'pidx' => $pidx,
            'status' => 'Completed',
            'purchase_order_id' => $order->transaction_uuid,
            'transaction_id' => 'khalti_txn_1',
        ];

        $response = $this->postJson(route('payment.webhook.khalti'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'completed']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
            'transaction_id' => 'khalti_txn_1',
        ]);
    }

    /** Test Khalti webhook is idempotent */
    public function test_khalti_webhook_idempotent_for_completed_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'completed',
            'transaction_id' => 'existing_txn',
        ]);

        $pidx = 'test_pidx_idempotent';

        Http::fake([
            '*khalti*' => Http::response([
                'status' => 'Completed',
                'total_amount' => 10000,
                'transaction_id' => 'new_txn',
                'purchase_order_id' => $order->transaction_uuid,
                'pidx' => $pidx,
            ], 200),
        ]);

        $payload = [
            'pidx' => $pidx,
            'status' => 'Completed',
            'purchase_order_id' => $order->transaction_uuid,
            'transaction_id' => 'new_txn',
        ];

        $response = $this->postJson(route('payment.webhook.khalti'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'completed']);

        // transaction_id should NOT have been overwritten
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'transaction_id' => 'existing_txn',
        ]);
    }

    /** Test Khalti webhook validates amount via paisa comparison */
    public function test_khalti_webhook_rejects_amount_mismatch()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $pidx = 'test_pidx_amount';

        Http::fake([
            '*khalti*' => Http::response([
                'status' => 'Completed',
                'total_amount' => 50000, // 500.00 NPR — mismatch
                'transaction_id' => 'khalti_txn_2',
                'purchase_order_id' => $order->transaction_uuid,
                'pidx' => $pidx,
            ], 200),
        ]);

        $payload = [
            'pidx' => $pidx,
            'status' => 'Completed',
            'purchase_order_id' => $order->transaction_uuid,
        ];

        $response = $this->postJson(route('payment.webhook.khalti'), $payload);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Amount mismatch']);
    }

    /** Test Khalti webhook fails when lookup API is down */
    public function test_khalti_webhook_handles_lookup_api_failure()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'pending',
        ]);

        Http::fake([
            '*khalti*' => Http::response([], 500),
        ]);

        $payload = [
            'pidx' => 'test_pidx_fail',
            'status' => 'Completed',
            'purchase_order_id' => $order->transaction_uuid,
        ];

        $response = $this->postJson(route('payment.webhook.khalti'), $payload);

        $response->assertStatus(502);
        $response->assertJson(['error' => 'Lookup API unavailable']);
    }

    /** Test Khalti webhook handles race condition */
    public function test_khalti_webhook_sets_refund_required_when_product_unavailable()
    {
        $this->product->update(['is_available' => false]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $pidx = 'test_pidx_race';

        Http::fake([
            '*khalti*' => Http::response([
                'status' => 'Completed',
                'total_amount' => 10000,
                'transaction_id' => 'khalti_txn_3',
                'purchase_order_id' => $order->transaction_uuid,
                'pidx' => $pidx,
            ], 200),
        ]);

        $payload = [
            'pidx' => $pidx,
            'status' => 'Completed',
            'purchase_order_id' => $order->transaction_uuid,
        ];

        $response = $this->postJson(route('payment.webhook.khalti'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'refund_required']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refund_required',
        ]);
    }

    // ─── FonePay Webhook Tests ────────────────────────────────────────

    /** Test FonePay webhook verifies signature and completes order */
    public function test_fonepay_webhook_completes_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $payload = $this->buildFonepayWebhookPayload($order);

        $response = $this->postJson(route('payment.webhook.fonepay'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'completed']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    /** Test FonePay webhook rejects signature mismatch */
    public function test_fonepay_webhook_rejects_bad_signature()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'pending',
        ]);

        $payload = $this->buildFonepayWebhookPayload($order);
        $payload['DV'] = 'tampered_signature';

        $response = $this->postJson(route('payment.webhook.fonepay'), $payload);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Signature mismatch']);
    }

    /** Test FonePay webhook rejects amount mismatch */
    public function test_fonepay_webhook_rejects_amount_mismatch()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $payload = $this->buildFonepayWebhookPayload($order);
        $payload['P_AMT'] = '200.00'; // mismatched amount
        // Re-sign with the wrong amount so signature check passes
        $message = "{$order->transaction_uuid},{$payload['PID']},true,200,{$payload['UID']},{$payload['BC']},{$payload['INI']},200.00,0.00";
        $payload['DV'] = $this->generateFonepaySignature($message);

        $response = $this->postJson(route('payment.webhook.fonepay'), $payload);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Amount mismatch']);
    }

    /** Test FonePay webhook handles race condition */
    public function test_fonepay_webhook_sets_refund_required_when_product_unavailable()
    {
        $this->product->update(['is_available' => false]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.00,
            'status' => 'pending',
        ]);

        $payload = $this->buildFonepayWebhookPayload($order);

        $response = $this->postJson(route('payment.webhook.fonepay'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'refund_required']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'refund_required',
        ]);
    }

    // ─── Refund Command Tests ────────────────────────────────────────

    /** Test orders:process-refunds dispatches jobs for refund_required orders */
    public function test_process_refunds_command_dispatches_jobs()
    {
        Cache::spy();

        $order1 = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'refund_required',
        ]);
        $order2 = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'refund_required',
        ]);
        // This one should NOT be picked up
        Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'pending',
        ]);

        $this->artisan('orders:process-refunds')
            ->assertSuccessful()
            ->expectsOutputToContain('Found 2 order(s) requiring refunds')
            ->expectsOutputToContain('Dispatched 2 refund job(s) successfully');
    }

    /** Test orders:process-refunds shows zero when no refunds needed */
    public function test_process_refunds_command_no_refunds_needed()
    {
        $this->artisan('orders:process-refunds')
            ->assertSuccessful()
            ->expectsOutputToContain('No orders requiring refunds found');
    }

    // ─── Integer Paisa Precision Tests ────────────────────────────────

    /** Test integer paisa comparison prevents decimal mismatch false positives */
    public function test_integer_paisa_comparison_prevents_decimal_mismatch()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.25,
            'status' => 'pending',
        ]);

        $payload = $this->buildEsewaWebhookPayload($order, 'COMPLETE');
        // Simulate eSewa returning "100.25" which could have float comparison issues
        $payload['total_amount'] = '100.25';

        $message = "total_amount={$payload['total_amount']},transaction_uuid={$payload['transaction_uuid']},product_code={$payload['product_code']}";
        $payload['signature'] = $this->generateEsewaSignature($message);

        $response = $this->postJson(route('payment.webhook.esewa'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'completed']);
    }

    /** Test integer paisa comparison correctly catches actual mismatches */
    public function test_integer_paisa_comparison_detects_actual_mismatch()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'amount' => 100.25,
            'status' => 'pending',
        ]);

        $payload = $this->buildEsewaWebhookPayload($order, 'COMPLETE');
        // Very close but still wrong — 100.26 vs 100.25
        $payload['total_amount'] = '100.26';

        $message = "total_amount={$payload['total_amount']},transaction_uuid={$payload['transaction_uuid']},product_code={$payload['product_code']}";
        $payload['signature'] = $this->generateEsewaSignature($message);

        $response = $this->postJson(route('payment.webhook.esewa'), $payload);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Amount mismatch']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    /**
     * Build an eSewa webhook payload with a valid signature for testing.
     */
    private function buildEsewaWebhookPayload(Order $order, string $status): array
    {
        $totalAmount = number_format($order->amount, 2, '.', '');
        $productCode = config('esewa.merchant_code');

        $signedFieldNames = 'total_amount,transaction_uuid,product_code';
        $message = "total_amount={$totalAmount},transaction_uuid={$order->transaction_uuid},product_code={$productCode}";
        $signature = $this->generateEsewaSignature($message);

        return [
            'transaction_code' => 'TXN_123456',
            'status' => $status,
            'total_amount' => $totalAmount,
            'transaction_uuid' => $order->transaction_uuid,
            'product_code' => $productCode,
            'signature' => $signature,
            'signed_field_names' => $signedFieldNames,
        ];
    }

    /**
     * Build a FonePay webhook payload with a valid signature for testing.
     */
    private function buildFonepayWebhookPayload(Order $order): array
    {
        $paidAmount = number_format($order->amount, 2, '.', '');
        $pid = config('fonepay.merchant_code');
        $uid = 'FP_UID_' . uniqid();

        // DV signature: PRN,PID,PS,RC,UID,BC,INI,P_AMT,R_AMT
        $message = "{$order->transaction_uuid},{$pid},true,200,{$uid},00,ONLINE,{$paidAmount},0.00";
        $dv = $this->generateFonepaySignature($message);

        return [
            'PRN' => $order->transaction_uuid,
            'PID' => $pid,
            'PS' => 'true',
            'RC' => '200',
            'UID' => $uid,
            'BC' => '00',
            'INI' => 'ONLINE',
            'P_AMT' => $paidAmount,
            'R_AMT' => '0.00',
            'DV' => $dv,
        ];
    }

    /**
     * Generate HMAC-SHA256 signature for eSewa
     */
    private function generateEsewaSignature(string $message): string
    {
        $secret = config('esewa.secret_key');
        return base64_encode(hash_hmac('sha256', $message, $secret, true));
    }

    /**
     * Generate HMAC-SHA512 signature for FonePay
     */
    private function generateFonepaySignature(string $message): string
    {
        $secret = config('fonepay.secret_key');
        return hash_hmac('sha512', $message, $secret);
    }
}
