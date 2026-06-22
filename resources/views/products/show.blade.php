@extends('layouts.app')

@section('title', $product->name)

@section('content')
<style>
    .product-view-container {
        display: grid;
        grid-template-columns: 450px 1fr;
        gap: 20px;
        background: var(--white);
        padding: 25px;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    /* Left Gallery Column */
    .gallery-column {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .main-image-box {
        width: 100%;
        padding-top: 100%; /* 1:1 square ratio */
        background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        position: relative;
        border-radius: 4px;
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .main-image-placeholder {
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

    .main-image-placeholder svg {
        width: 80px;
        height: 80px;
        color: var(--primary-color);
        margin-bottom: 12px;
        opacity: 0.8;
    }

    /* Right Details Column */
    .details-column {
        display: flex;
        flex-direction: column;
    }

    .detail-title {
        font-size: 22px;
        font-weight: 500;
        color: #212121;
        margin-bottom: 10px;
        line-height: 1.3;
    }

    /* Price Section */
    .price-box {
        background-color: #fafafa;
        padding: 15px 20px;
        margin-bottom: 25px;
        border-radius: 4px;
    }

    .main-price {
        font-size: 30px;
        color: var(--primary-color);
        font-weight: 500;
    }

    /* Quantity and Buy Section */
    .quantity-section {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
        font-size: 14px;
        color: var(--grey-color);
    }

    .quantity-selector {
        display: flex;
        align-items: center;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        overflow: hidden;
        background: #fff;
    }

    .qty-btn {
        width: 32px;
        height: 32px;
        background: #fafafa;
        border: none;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .qty-btn:hover {
        background: #f0f0f0;
    }

    .qty-input {
        width: 45px;
        height: 32px;
        text-align: center;
        border: none;
        border-left: 1px solid var(--border-color);
        border-right: 1px solid var(--border-color);
        font-size: 14px;
        outline: none;
    }

    .actions-section {
        display: flex;
        gap: 12px;
        margin-bottom: 30px;
    }

    .btn-buy-now {
        flex: 1;
        background-color: #ffb300;
        color: var(--white);
        font-size: 16px;
        height: 48px;
        font-weight: 700;
    }

    .btn-buy-now:hover {
        background-color: #f59f00;
    }

    .btn-add-cart {
        flex: 1;
        background-color: var(--primary-color);
        color: var(--white);
        font-size: 16px;
        height: 48px;
        font-weight: 700;
    }

    .btn-add-cart:hover {
        background-color: var(--primary-hover);
    }

    /* Description Section */
    .description-box {
        border-top: 1px solid #f0f0f0;
        padding-top: 20px;
    }

    .description-box h3 {
        font-size: 16px;
        font-weight: 500;
        color: #212121;
        margin-bottom: 12px;
        text-transform: uppercase;
    }

    .description-text {
        font-size: 14px;
        color: #424242;
        line-height: 1.7;
    }

    .back-btn-container {
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .product-view-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="product-view-container">
    <!-- Left Column -->
    <div class="gallery-column">
        <div class="main-image-box">
            <div class="main-image-placeholder">
                <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm1 14.5h-2v-2h2zm0-4h-2v-4h2z"/></svg>
                <span style="font-size: 16px; font-weight: 500; color: var(--grey-color);">{{ $product->name }}</span>
            </div>
        </div>
        <div class="back-btn-container">
            <a href="{{ route('products.index') }}" class="btn btn-outline" style="width: 100%;">← Back to Products</a>
        </div>
    </div>

    <!-- Right Column -->
    <div class="details-column">
        <h1 class="detail-title">{{ $product->name }}</h1>

        <div style="font-size: 13px; color: var(--grey-color); margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 15px;">
            Posted by: <strong style="color: #212121;">{{ $product->user->name }}</strong>
        </div>

        <div class="price-box">
            <div class="main-price">Rs. {{ number_format($product->price, 2) }}</div>
        </div>

        <div class="quantity-section">
            <span>Quantity</span>
            <div class="quantity-selector">
                <button type="button" class="qty-btn" onclick="let input = document.getElementById('qty'); if(input.value > 1) input.value--">-</button>
                <input type="text" id="qty" class="qty-input" value="1" readonly>
                <button type="button" class="qty-btn" onclick="let input = document.getElementById('qty'); input.value++">+</button>
            </div>
        </div>

        <div class="actions-section">
            <button class="btn btn-buy-now" onclick="alert('Order Placed Successfully (Mock Checkout)!')">Buy Now</button>
            <button class="btn btn-add-cart" onclick="alert('Added to Cart!')">Add to Cart</button>
        </div>

        <div class="description-box">
            <h3>Product Details of {{ $product->name }}</h3>
            <div class="description-text">
                {!! nl2br(e($product->description)) !!}
            </div>
        </div>
    </div>
</div>
@endsection
