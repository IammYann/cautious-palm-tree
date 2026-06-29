@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div style="background: white; padding: 2rem; border-radius: 8px;">
    <h1>{{ $product->name }}</h1>

    <div style="margin: 2rem 0; padding: 2rem; background: #f9f9f9; border-left: 4px solid #3498db;">
        <p style="line-height: 1.8; margin-bottom: 1rem;">
            <strong>Description:</strong><br>
            {{ $product->description }}
        </p>

        <p style="font-size: 1.8rem; color: #27ae60; font-weight: bold; margin-bottom: 1rem;">
            Price: Rs. {{ number_format($product->price, 2) }}
        </p>

        <p style="color: #7f8c8d;">
            <strong>Posted by:</strong> {{ $product->user->name }}
        </p>

        <p style="color: #95a5a6; font-size: 0.9rem;">
            <strong>Date:</strong> {{ $product->created_at->format('M d, Y') }}
        </p>
    </div>

    {{-- eSewa Purchase Section --}}
    <div style="background: linear-gradient(135deg, #f0faf0, #e8f5e9); border: 2px solid #60bb46; border-radius: 12px; padding: 2rem; margin-bottom: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #60bb46, #4a9e35); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <span style="color: white; font-weight: bold; font-size: 1rem;">e</span>
            </div>
            <h3 style="color: #2c3e50; margin: 0;">Pay with eSewa</h3>
        </div>

        @if(!$product->is_available)
            <div style="background: #ffebee; border: 1px solid #ffcdd2; color: #c62828; padding: 1rem; border-radius: 8px; font-weight: 600; text-align: center; font-size: 1.1rem;">
                ❌ SOLD OUT (This product is no longer available)
            </div>
        @else
            @auth
                <form action="{{ route('payment.esewa.initiate', $product->id) }}" method="POST" style="background: none; box-shadow: none; padding: 0; margin: 0;">
                    @csrf
                    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <button type="submit" id="esewa-buy-btn" style="
                            background: linear-gradient(135deg, #60bb46, #4a9e35);
                            color: white;
                            border: none;
                            padding: 0.75rem 2rem;
                            border-radius: 8px;
                            font-size: 1.1rem;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            box-shadow: 0 4px 15px rgba(96, 187, 70, 0.3);
                            display: flex;
                            align-items: center;
                            gap: 0.5rem;
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(96, 187, 70, 0.4)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(96, 187, 70, 0.3)'">
                            🛒 Buy with eSewa — Rs. {{ number_format($product->price, 2) }}
                        </button>
                    </div>
                </form>
            @else
                <p style="color: #7f8c8d; margin-bottom: 1rem;">You need to be logged in to purchase this product.</p>
                <a href="{{ route('login') }}" class="btn btn-success" style="
                    background: linear-gradient(135deg, #60bb46, #4a9e35);
                    padding: 0.75rem 2rem;
                    border-radius: 8px;
                    font-size: 1.1rem;
                    font-weight: 600;
                    box-shadow: 0 4px 15px rgba(96, 187, 70, 0.3);
                ">Login to Buy</a>
            @endauth
        @endif
    </div>

    {{-- Khalti Purchase Section --}}
    <div style="background: linear-gradient(135deg, #f3e5f5, #e1bee7); border: 2px solid #5c2d91; border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <div style="width: 36px; height: 36px; background: #5c2d91; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <span style="color: white; font-weight: bold; font-size: 1rem;">K</span>
            </div>
            <h3 style="color: #2c3e50; margin: 0;">Pay with Khalti</h3>
        </div>

        @if(!$product->is_available)
            <div style="background: #ffebee; border: 1px solid #ffcdd2; color: #c62828; padding: 1rem; border-radius: 8px; font-weight: 600; text-align: center; font-size: 1.1rem;">
                ❌ SOLD OUT (This product is no longer available)
            </div>
        @else
            @auth
                <form action="{{ route('payment.khalti.initiate', $product->id) }}" method="POST" style="background: none; box-shadow: none; padding: 0; margin: 0;">
                    @csrf
                    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <button type="submit" id="khalti-buy-btn" style="
                            background: #5c2d91;
                            color: white;
                            border: none;
                            padding: 0.75rem 2rem;
                            border-radius: 8px;
                            font-size: 1.1rem;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            box-shadow: 0 4px 15px rgba(92, 45, 145, 0.3);
                            display: flex;
                            align-items: center;
                            gap: 0.5rem;
                        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(92, 45, 145, 0.4)'; this.style.background='#4e247d';"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(92, 45, 145, 0.3)'; this.style.background='#5c2d91';">
                            🛒 Buy with Khalti — Rs. {{ number_format($product->price, 2) }}
                        </button>
                    </div>
                </form>
            @else
                <p style="color: #7f8c8d; margin-bottom: 1rem;">You need to be logged in to purchase this product.</p>
                <a href="{{ route('login') }}" class="btn" style="
                    background: #5c2d91;
                    color: white;
                    padding: 0.75rem 2rem;
                    border-radius: 8px;
                    font-size: 1.1rem;
                    font-weight: 600;
                    box-shadow: 0 4px 15px rgba(92, 45, 145, 0.3);
                ">Login to Buy</a>
            @endauth
        @endif
    </div>

    <a href="{{ route('products.index') }}" class="btn">← Back to Products</a>
    
    
</div>
@endsection
