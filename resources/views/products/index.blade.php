@extends('layouts.app')

@section('title', 'Products')

@section('content')
<h1>Available Products</h1>

@if ($products->count() > 0)
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem;">
        @foreach($products as $product)
            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s;">
                <h3 style="color: #2c3e50; margin-bottom: 0.5rem;">{{ $product->name }}</h3>
                
                <p style="color: #7f8c8d; margin-bottom: 1rem;">
                    {{ Str::limit($product->description, 100) }}{{ Str::length($product->description) > 100 ? '...' : '' }}
                </p>

                <p style="font-size: 1.5rem; color: #27ae60; font-weight: bold; margin-bottom: 0.5rem;">
                    ${{ number_format($product->price, 2) }}
                </p>

                <p style="color: #95a5a6; font-size: 0.9rem; margin-bottom: 1rem;">
                    Posted by: <strong>{{ $product->user->name }}</strong>
                </p>

                <a href="{{ route('products.show', $product->id) }}" class="btn" style="display: block; text-align: center;">
                    View Details
                </a>
            </div>
        @endforeach
    </div>
@else
    <div style="background: white; padding: 2rem; border-radius: 8px; text-align: center;">
        <p style="font-size: 1.1rem; color: #7f8c8d;">No products available yet. Check back soon!</p>
    </div>
@endif
@endsection
