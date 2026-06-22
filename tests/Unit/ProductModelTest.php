<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test product belongs to user
     */
    public function test_product_belongs_to_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Description',
            'price' => 99.99,
            'user_id' => $admin->id,
        ]);

        $this->assertEquals($admin->id, $product->user->id);
        $this->assertInstanceOf(User::class, $product->user);
    }

    /**
     * Test product has required attributes
     */
    public function test_product_has_required_attributes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Description',
            'price' => 99.99,
            'user_id' => $admin->id,
        ]);

        $this->assertNotNull($product->name);
        $this->assertNotNull($product->description);
        $this->assertNotNull($product->price);
        $this->assertNotNull($product->user_id);
    }

    /**
     * Test product price is decimal
     */
    public function test_product_price_is_numeric(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Description',
            'price' => 99.99,
            'user_id' => $admin->id,
        ]);

        $this->assertTrue(is_numeric($product->price));
    }
}