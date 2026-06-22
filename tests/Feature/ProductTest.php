<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test anyone can view products
     */
    public function test_anyone_can_view_products(): void
    {
        // Create a product
        Product::create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'user_id' => User::factory()->create(['role' => 'admin'])->id,
        ]);

        $response = $this->get('/products');

        $response->assertStatus(200);
        $response->assertViewIs('products.index');
        $response->assertViewHas('products');
    }

    /**
     * Test anyone can view product details
     */
    public function test_anyone_can_view_product_details(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'user_id' => User::factory()->create(['role' => 'admin'])->id,
        ]);

        $response = $this->get("/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertViewIs('products.show');
        $response->assertSee('Test Product');
    }

    /**
     * Test only admins can create products
     */
    public function test_only_admins_can_create_products(): void
    {
        // Regular user cannot create
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->post('/admin/products', [
            'name' => 'New Product',
            'description' => 'Description',
            'price' => 99.99,
        ]);

        $response->assertStatus(403); // Forbidden

        // Admin can create
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'New Product',
            'description' => 'Description',
            'price' => 99.99,
        ]);

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseHas('products', ['name' => 'New Product']);
    }

    /**
     * Test admin can update product
     */
    public function test_admin_can_update_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Original Name',
            'description' => 'Original Description',
            'price' => 99.99,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->put("/admin/products/{$product->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'price' => 149.99,
        ]);

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseHas('products', ['name' => 'Updated Name']);
    }

    /**
     * Test admin can delete product
     */
    public function test_admin_can_delete_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'name' => 'Product to Delete',
            'description' => 'Description',
            'price' => 99.99,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/products/{$product->id}");

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}