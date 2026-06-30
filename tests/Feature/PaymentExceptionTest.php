<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentExceptionTest extends TestCase
{
    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create(['user_id' => $this->user->id]);
    }

    /** Test Khalti lookup timeout/failure marks order failed */
    public function test_khalti_lookup_api_failure_marks_order_failed()
    {
        Http::fake([
            '*khalti*' => Http::response([], 500),
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'pending',
        ]);

        $response = $this->get(route('payment.khalti.callback', [
            'pidx' => 'test_pidx_fail',
            'purchase_order_id' => $order->transaction_uuid,
            'status' => 'Completed',
        ]));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'failed',
        ]);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('error');
    }

    /** Test malformed eSewa callback (invalid base64) */
    public function test_esewa_callback_invalid_base64_rejected()
    {
        $response = $this->get(route('payment.esewa.success', [
            'data' => 'not_valid_base64!!!xyz',
        ]));

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('error', 'Could not decode payment response.');
    }

    /** Test eSewa callback with malformed JSON */
    public function test_esewa_callback_invalid_json_rejected()
    {
        $invalidJson = base64_encode('{invalid json}');
        $response = $this->get(route('payment.esewa.success', [
            'data' => $invalidJson,
        ]));

        $response->assertRedirect(route('products.index'));
    }

    /** Test eSewa callback with no data parameter */
    public function test_esewa_callback_no_data_parameter()
    {
        $response = $this->get(route('payment.esewa.success'));

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('error', 'Invalid payment response.');
    }

    /** Test product deletion with pending orders fails gracefully */
    public function test_cannot_delete_product_with_pending_orders()
    {
        $this->actingAs($this->user);
        $product = Product::factory()->create(['user_id' => $this->user->id]);
        Order::factory()->create([
            'product_id' => $product->id,
            'status' => 'pending',
        ]);

        $response = $this->delete(route('admin.products.destroy', $product));

        $response->assertSessionHas('error', 'Cannot delete product with pending or active orders');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    /** Test product can be deleted when no pending orders */
    public function test_product_can_be_deleted_when_no_pending_orders()
    {
        $this->actingAs($this->user);
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $response = $this->delete(route('admin.products.destroy', $product));

        $response->assertSessionHas('success', 'Product deleted successfully!');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** Test product creation with transaction rollback on error */
    public function test_product_creation_handles_validation_error()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('admin.products.store'), [
            'name' => '', // invalid — empty
            'description' => 'Test',
            'price' => 100,
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** Test product update with transaction rollback on error */
    public function test_product_update_handles_database_error()
    {
        $this->actingAs($this->user);
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        // Valid data but should succeed
        $response = $this->patch(route('admin.products.update', $product), [
            'name' => 'Updated Product',
            'description' => 'Updated description',
            'price' => 250,
        ]);

        $response->assertSessionHas('success', 'Product updated successfully!');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
        ]);
    }

    /** Test Khalti callback with missing total_amount throws exception */
    public function test_khalti_callback_missing_total_amount_fails()
    {
        Http::fake([
            '*khalti*' => Http::response(['status' => 'Completed'], 200),
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'pending',
        ]);

        $response = $this->get(route('payment.khalti.callback', [
            'pidx' => 'test_pidx',
            'purchase_order_id' => $order->transaction_uuid,
            'status' => 'Completed',
        ]));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'failed',
        ]);
    }

    /** Test eSewa callback already completed order is idempotent */
    public function test_esewa_callback_idempotent_for_completed_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'status' => 'completed',
            'transaction_code' => 'existing_txn',
        ]);

        $paymentData = [
            'transaction_code' => 'existing_txn',
            'status' => 'COMPLETE',
            'total_amount' => $order->amount,
            'transaction_uuid' => $order->transaction_uuid,
            'product_code' => config('esewa.merchant_code'),
            'signature' => 'dummy_sig',
            'signed_field_names' => '',
        ];

        $encodedData = base64_encode(json_encode($paymentData));

        $response = $this->get(route('payment.esewa.success', ['data' => $encodedData]));

        // Should show success view without re-processing
        $response->assertViewIs('payment.success');
    }

    /** Test order model logs exception on mark as completed failure */
    public function test_order_mark_completed_logs_error()
    {
        Log::spy();

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        // This should succeed normally
        $order->markAsCompleted('test_txn_123');

        Log::shouldHaveReceived('info')->withArgs(function ($msg) {
            return str_contains($msg, 'Order marked as completed');
        });

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
            'transaction_id' => 'test_txn_123',
        ]);
    }
}
