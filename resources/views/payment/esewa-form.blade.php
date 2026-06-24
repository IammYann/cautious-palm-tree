@extends('layouts.app')

@section('title', 'Paying with eSewa')

@section('content')
<div style="display: flex; justify-content: center; align-items: center; min-height: 60vh;">
    <div style="background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; max-width: 500px; width: 100%;">
        
        {{-- eSewa Logo --}}
        <div style="margin-bottom: 1.5rem;">
            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #60bb46, #4a9e35); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; box-shadow: 0 4px 15px rgba(96, 187, 70, 0.3);">
                <span style="color: white; font-size: 1.8rem; font-weight: bold;">e</span>
            </div>
        </div>

        <h2 style="color: #2c3e50; margin-bottom: 0.5rem;">Redirecting to eSewa</h2>
        
        <p style="color: #7f8c8d; margin-bottom: 0.5rem;">
            Purchasing: <strong>{{ $product->name }}</strong>
        </p>
        <p style="font-size: 1.5rem; color: #60bb46; font-weight: bold; margin-bottom: 1.5rem;">
            Rs. {{ number_format($paymentData['total_amount'], 2) }}
        </p>

        {{-- Loading spinner --}}
        <div style="margin-bottom: 1.5rem;">
            <div class="esewa-spinner"></div>
        </div>

        <p style="color: #95a5a6; font-size: 0.9rem;">
            Please wait while we redirect you to eSewa for payment...
        </p>

        {{-- Auto-submit form --}}
        <form id="esewa-payment-form" action="{{ $paymentUrl }}" method="POST" style="background: none; box-shadow: none; padding: 0; margin: 0;">
            @foreach($paymentData as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
        </form>
    </div>
</div>

<style>
    .esewa-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #e0e0e0;
        border-top-color: #60bb46;
        border-radius: 50%;
        animation: esewa-spin 0.8s linear infinite;
        margin: 0 auto;
    }
    @keyframes esewa-spin {
        to { transform: rotate(360deg); }
    }
</style>

<script>
    // Auto-submit the form after a short delay for UX
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            document.getElementById('esewa-payment-form').submit();
        }, 1500);
    });
</script>
@endsection
