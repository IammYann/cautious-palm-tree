@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div style="max-width: 420px; margin: 40px auto;">
    <div class="panel" style="padding: 30px;">
        <h2 style="font-size: 22px; font-weight: 500; margin-bottom: 5px; text-align: center; color: #222;">Create Account</h2>
        <p style="font-size: 13px; color: var(--grey-color); text-align: center; margin-bottom: 25px;">Join Bazar to discover amazing products!</p>

        <form action="{{ route('register') }}" method="POST" style="box-shadow: none; padding: 0; background: none;">
            @csrf

            <div class="form-group">
                <label for="name">Full Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') }}"
                    required
                    class="form-control"
                    placeholder="First and Last Name"
                >
                @error('name')
                    <span style="color: var(--danger-color); font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ old('email') }}"
                    required
                    class="form-control"
                    placeholder="your@email.com"
                >
                @error('email')
                    <span style="color: var(--danger-color); font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    class="form-control"
                    placeholder="Enter a strong password"
                >
                @error('password')
                    <span style="color: var(--danger-color); font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input 
                    type="password" 
                    id="password_confirmation" 
                    name="password_confirmation" 
                    required
                    class="form-control"
                    placeholder="Confirm your password"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="height: 44px; font-size: 15px; font-weight: 500; margin-top: 10px;">SIGN UP</button>
        </form>
    </div>

    <p style="text-align: center; font-size: 14px; color: var(--grey-color); margin-top: 15px;">
        Already have an account? <a href="{{ route('login') }}" style="color: #1a9cb4; font-weight: 500;">Login here</a>.
    </p>
</div>
@endsection
