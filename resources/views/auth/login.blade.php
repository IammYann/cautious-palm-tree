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

            <div style="display: flex; align-items: center; margin: 20px 0;">
                <div style="flex: 1; height: 1px; background: #ddd;"></div>
                <span style="padding: 0 12px; font-size: 12px; color: var(--grey-color, #888);">OR</span>
                <div style="flex: 1; height: 1px; background: #ddd;"></div>
            </div>

            <a href="{{ route('auth.google') }}"
               style="display: flex; align-items: center; justify-content: center; gap: 10px;
                      width: 100%; height: 44px; border: 1px solid #ddd; border-radius: 4px;
                      background: #fff; color: #444; font-size: 14px; font-weight: 500;
                      text-decoration: none; cursor: pointer; transition: background 0.2s;"
               onmouseover="this.style.background='#f7f7f7'"
               onmouseout="this.style.background='#fff'">
                <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917"/><path fill="#FF3D00" d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691"/><path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44"/><path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917"/></svg>
                Sign in with Google
            </a>

            <a href="{{ route('auth.facebook') }}"
                style="display: flex; align-items: center; justify-content: center; gap: 10px;
                        width: 100%; height: 44px; border: 1px solid #ddd; border-radius: 4px;
                        background: #1877F2; color: #fff; font-size: 14px; font-weight: 500;
                        text-decoration: none; cursor: pointer; transition: background 0.2s; margin-top: 10px;"
                onmouseover="this.style.background='#166eab'"
                onmouseout="this.style.background='#1877F2'">

                <div style="
                    width: 34px;
                    height: 34px;
                    border-radius: 100%;
                    background: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;">
                    <img src="{{ asset('images.jpg') }}"
                        alt="Facebook"
                        style="width: 28px; height: 28px; border-radius: 100%;">
                </div>

                <span>Sign in with Facebook</span>
            </a>
        </form>
    </div>

    <p style="text-align: center; font-size: 14px; color: var(--grey-color); margin-top: 15px;">
        New member? <a href="{{ route('register') }}" style="color: #1a9cb4; font-weight: 500;">Register here</a>.
    </p>
</div>
@endsection
