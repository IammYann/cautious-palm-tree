<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use Illuminate\Http\Request;
use Throwable;

class ProductController extends Controller
{
    /**
     * Display a listing of products (public view)
     */
    public function index()
    {
        $products = Cache::remember('all_products', 3600, function() {
            return Product::where('is_available', true)->with('user')->get();
        });
    
        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product (admin only)
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Store a newly created product (admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            Product::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'user_id' => auth()->id(),
            ]);

            // Invalidate related caches
            Cache::forget('all_products');
            Cache::forget('admin_products');

            DB::commit();

            return redirect()->route('admin.products.index')->with('success', 'Product created successfully!');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            return redirect()->back()->withInput()
                ->with('error', 'Failed to create product. Please try again.');
        }
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
    // Cache individual product for 60 minutes
        $product = Cache::remember('product_' . $product->id, 3600, function() use ($product) {
            return $product->load('user');
        });
        
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing a product (admin only)
     */
    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified product (admin only)
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $product->update($validated);

            // Invalidate related caches
            Cache::forget('all_products');
            Cache::forget('admin_products');
            Cache::forget('product_' . $product->id);

            DB::commit();

            return redirect()->route('admin.products.index')->with('success', 'Product updated successfully!');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Product update failed', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
                'user_id' => auth()->id(),
            ]);
            return redirect()->back()->withInput()
                ->with('error', 'Failed to update product. Please try again.');
        }
    }

    /**
     * Remove the specified product (admin only)
     */
    public function destroy(Product $product)
    {
        try {
            // Check if product has active orders
            if ($product->orders()->where('status', '!=', 'completed')->exists()) {
                return redirect()->back()
                    ->with('error', 'Cannot delete product with pending or active orders. Please complete or cancel all orders first.');
            }

            DB::beginTransaction();

            $productId = $product->id;
            $product->delete();

            // Invalidate related caches
            Cache::forget('all_products');
            Cache::forget('admin_products');
            Cache::forget('product_' . $productId);

            DB::commit();

            return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully!');
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Product deletion failed', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
                'user_id' => auth()->id(),
            ]);
            return redirect()->back()
                ->with('error', 'Failed to delete product. Please try again.');
        }
    }
    /**
     * Display admin product listing
     */
    public function adminIndex()
    {
    // Cache for 30 minutes
        $products = Cache::remember('admin_products', 1800, function() {
            return Product::all();
        });
        
        return view('admin.products.index', compact('products'));
    }
}
