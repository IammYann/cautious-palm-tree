<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use Illuminate\Http\Request;

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

        Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'user_id' => auth()->id(),
        ]);

        // Invalidate related caches
        Cache::forget('all_products');
        Cache::forget('admin_products');

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully!');
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

        $product->update($validated);

        // Invalidate related caches
        Cache::forget('all_products');
        Cache::forget('admin_products');
        Cache::forget('product_' . $product->id);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified product (admin only)
     */
    public function destroy(Product $product)
    {
        $product->delete();
        Cache::forget('all_products');
        Cache::forget('admin_products');
        Cache::forget('product_' . $product->id);

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully!');
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
