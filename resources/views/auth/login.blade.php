@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div style="max-width: 420px; margin: 50px auto;">
    <div class="panel" style="padding: 30px;">
        <h2 style="font-size: 22px; font-weight: 500; margin-bottom: 5px; text-align: center; color: #222;">Welcome to Bazar!</h2>
        <p style="font-size: 13px; color: var(--grey-color); text-align: center; margin-bottom: 25px;">Please log in to your account.</p>

        <form action="{{ route('login') }}" method="POST" style="box-shadow: none; padding: 0; background: none;">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ old('email') }}"
                    required
                    class="form-control"
                    placeholder="Please enter your email"
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
                    placeholder="Please enter your password"
                >
                @error('password')
                    <span style="color: var(--danger-color); font-size: 12px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; color: var(--grey-color);">
                    <input type="checkbox" name="remember" id="remember" style="accent-color: var(--primary-color);">
                    Remember me
                </label>
                <a href="#" style="color: #1a9cb4;">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="height: 44px; font-size: 15px; font-weight: 500;">LOGIN</button>
        </form>
    </div>

    <p style="text-align: center; font-size: 14px; color: var(--grey-color); margin-top: 15px;">
        New member? <a href="{{ route('register') }}" style="color: #1a9cb4; font-weight: 500;">Register here</a>.
    </p>
</div>
@endsection
