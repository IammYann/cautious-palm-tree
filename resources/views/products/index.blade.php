@extends('layouts.app')

@section('title', 'All Products')

@section('content')
<style>
    .grid-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        background: var(--white);
        padding: 15px 20px;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .grid-header h2 {
        font-size: 18px;
        font-weight: 500;
        text-transform: uppercase;
        color: #424242;
    }

    .daraz-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(188px, 1fr));
        gap: 12px;
    }

    .product-card {
        background: var(--white);
        border-radius: 2px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid transparent;
        cursor: pointer;
        position: relative;
    }

    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.12);
        border-color: rgba(245, 114, 36, 0.2);
    }

    .product-img-container {
        position: relative;
        width: 100%;
        padding-top: 100%; /* 1:1 Aspect Ratio */
        background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        overflow: hidden;
    }

    .product-img-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: var(--light-grey);
    }

    .product-img-placeholder svg {
        width: 48px;
        height: 48px;
        margin-bottom: 8px;
        opacity: 0.7;
        color: var(--primary-color);
    }

    .product-details {
        padding: 10px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .product-title {
        font-size: 14px;
        color: #212121;
        line-height: 18px;
        height: 36px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        margin-bottom: 8px;
        font-weight: 400;
    }

    .product-card:hover .product-title {
        color: var(--primary-color);
    }

    .price-section {
        margin-bottom: 6px;
    }

    .product-price {
        font-size: 18px;
        color: var(--primary-color);
        font-weight: 500;
        display: inline-block;
    }
</style>

<div class="grid-header">
    <h2>Available Products</h2>
    <div style="font-size: 14px; color: var(--grey-color);">
        Showing {{ $products->count() }} items
    </div>
</div>

@if ($products->count() > 0)
    <div class="daraz-grid">
        @foreach($products as $index => $product)
            <div class="product-card" onclick="window.location='{{ route('products.show', $product->id) }}'">
                <div class="product-img-container">
                    <div class="product-img-placeholder">
                        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 14.5h-2v-2h2zm0-4h-2v-4h2z"/></svg>
                        <span style="font-size: 12px; font-weight: 500;">{{ $product->name }}</span>
                    </div>
                </div>
                
                <div class="product-details">
                    <h3 class="product-title">{{ $product->name }}</h3>
                    
                    <div class="price-section">
                        <span class="product-price">Rs. {{ number_format($product->price, 2) }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="panel" style="text-align: center; padding: 40px 20px;">
        <p style="font-size: 16px; color: var(--grey-color); margin-bottom: 20px;">No products available yet. Check back soon!</p>
        <a href="{{ route('products.index') }}" class="btn btn-primary">Refresh Products</a>
    </div>
@endif
@endsection
