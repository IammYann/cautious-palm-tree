@extends('layouts.app')

@section('title', 'Payment Successful')

@section('content')
<div style="display: flex; justify-content: center; align-items: center; min-height: 60vh;">
    <div style="background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; max-width: 550px; width: 100%;">
        
        {{-- Success Icon --}}
        <div style="margin-bottom: 1.5rem;">
            <div class="success-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
        </div>

        <h1 style="color: #27ae60; margin-bottom: 0.5rem; font-size: 1.8rem;">Payment Successful! 🎉</h1>
        <p style="color: #7f8c8d; margin-bottom: 2rem;">Your order has been confirmed.</p>

        {{-- Order Details --}}
        <div style="background: #f8f9fa; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; text-align: left;">
            <h3 style="color: #2c3e50; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e0e0e0;">
                📦 Order Details
            </h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div>
                    <span style="color: #95a5a6; font-size: 0.85rem;">Order ID</span>
                    <p style="color: #2c3e50; font-weight: 600; margin: 0.2rem 0 0;">#{{ $order->id }}</p>
                </div>
                <div>
                    <span style="color: #95a5a6; font-size: 0.85rem;">Transaction Code</span>
                    <p style="color: #2c3e50; font-weight: 600; margin: 0.2rem 0 0;">{{ $transactionCode }}</p>
                </div>
                <div>
                    <span style="color: #95a5a6; font-size: 0.85rem;">Product</span>
                    <p style="color: #2c3e50; font-weight: 600; margin: 0.2rem 0 0;">{{ $order->product->name }}</p>
                </div>
                <div>
                    <span style="color: #95a5a6; font-size: 0.85rem;">Quantity</span>
                    <p style="color: #2c3e50; font-weight: 600; margin: 0.2rem 0 0;">{{ $order->quantity }}</p>
                </div>
                <div>
                    <span style="color: #95a5a6; font-size: 0.85rem;">Amount Paid</span>
                    <p style="color: #27ae60; font-weight: 700; font-size: 1.2rem; margin: 0.2rem 0 0;">
                        Rs. {{ number_format($order->amount, 2) }}
                    </p>
                </div>
                <div>
                    <span style="color: #95a5a6; font-size: 0.85rem;">Status</span>
                    <p style="margin: 0.2rem 0 0;">
                        <span style="background: #d4edda; color: #155724; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                            ✅ Completed
                        </span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="{{ route('products.index') }}" class="btn" style="padding: 0.75rem 1.5rem;">
                🛍️ Continue Shopping
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-success" style="padding: 0.75rem 1.5rem;">
                📊 Dashboard
            </a>
        </div>
    </div>
</div>

<style>
    .success-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        animation: success-pop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes success-pop {
        0% { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
</style>
@endsection
