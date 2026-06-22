<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bazar') - Online Shopping Mall</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #f57224;
            --primary-hover: #d0611e;
            --secondary-color: #26abd4;
            --secondary-hover: #1e8fae;
            --dark-color: #212121;
            --grey-color: #757575;
            --light-grey: #9e9e9e;
            --bg-color: #eff0f5;
            --border-color: #dadada;
            --white: #ffffff;
            --success-color: #4caf50;
            --danger-color: #f44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--bg-color);
            color: var(--dark-color);
            line-height: 1.5;
        }

        a {
            text-decoration: none;
            color: inherit;
            transition: color 0.2s;
        }

        /* Top Bar */
        .top-bar {
            background-color: #f4f4f4;
            border-bottom: 1px solid #e2e2e2;
            font-size: 12px;
            color: var(--grey-color);
            padding: 4px 0;
        }

        .top-bar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
        }

        .top-bar-links a {
            margin-right: 15px;
            font-weight: 400;
        }

        .top-bar-links a:hover {
            color: var(--primary-color);
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .top-bar-right a:hover {
            color: var(--primary-color);
        }

        /* Main Header */
        .main-header {
            background-color: var(--white);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.04);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 15px 0;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 15px;
            gap: 20px;
        }

        /* Logo styling resembling Daraz */
        .logo-area {
            flex-shrink: 0;
        }

        .logo-link {
            font-size: 32px;
            font-weight: 900;
            font-style: italic;
            letter-spacing: -1.5px;
            display: flex;
            align-items: center;
        }

        .logo-orange {
            color: var(--primary-color);
        }

        .logo-dark {
            color: #333;
        }

        /* Search Area */
        .search-area {
            flex-grow: 1;
            max-width: 700px;
        }

        .search-form {
            display: flex;
            width: 100%;
            border-radius: 4px;
            overflow: hidden;
        }

        .search-input {
            width: 100%;
            border: 1px solid var(--border-color);
            background-color: #f5f5f5;
            padding: 10px 15px;
            font-size: 14px;
            outline: none;
            border-radius: 4px 0 0 4px;
            border-right: none;
            transition: background-color 0.2s;
        }

        .search-input:focus {
            background-color: var(--white);
            border-color: var(--primary-color);
        }

        .search-btn {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            width: 45px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 0 4px 4px 0;
            transition: background-color 0.2s;
        }

        .search-btn:hover {
            background-color: var(--primary-hover);
        }

        .search-icon {
            width: 20px;
            height: 20px;
        }

        /* Cart Area */
        .cart-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* .cart-link {
            position: relative;
            color: #333;
        }

        .cart-icon {
            width: 32px;
            height: 32px;
        } */

        /* .cart-badge {
            position: absolute;
            top: -4px;
            right: -6px;
            background-color: var(--primary-color);
            color: var(--white);
            font-size: 11px;
            font-weight: 700;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--white);
        } */

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }

        /* Common Elements & Helper Styles */
        h1, h2, h3 {
            color: #222;
            font-weight: 500;
        }

        /* Card / Panel styling */
        .panel {
            background-color: var(--white);
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        /* Buttons matching Daraz */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s, box-shadow 0.2s;
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: var(--secondary-hover);
        }

        .btn-outline {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            background-color: transparent;
        }

        .btn-outline:hover {
            background-color: rgba(245, 114, 36, 0.05);
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: var(--white);
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }

        .btn-success {
            background-color: var(--success-color);
            color: var(--white);
        }

        .btn-success:hover {
            background-color: #388e3c;
        }

        .btn-block {
            width: 100%;
            display: flex;
        }

        /* Form elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            color: var(--dark-color);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Alerts styling */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .alert ul {
            list-style: none;
            padding-left: 0;
        }

        .alert li {
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <!-- Top Utility Bar -->
    <div class="top-bar">
        <div class="top-bar-container">
            <div class="top-bar-links">
                
            </div>
            <div class="top-bar-right">
                @auth
                    <span style="font-weight: 500;">👤 {{ auth()->user()->name }}</span>
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.products.index') }}" style="color: var(--primary-color);">📦 Manage Products</a>
                        <a href="{{ route('admin.users.index') }}">👥 Manage Users</a>
                    @endif
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" style="background: none; border: none; font-size: 12px; color: var(--grey-color); cursor: pointer; padding: 0; margin-left: 10px;">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}">Sign Up</a>
                @endauth
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-area">
                <a href="{{ route('products.index') }}" class="logo-link">
                    <span class="logo-orange">bazar</span>
                </a>
            </div>
            
            <div class="search-area">
                <form action="{{ route('products.index') }}" method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search in Bazar..." class="search-input" value="{{ request('search') }}">
                    <button type="submit" class="search-btn">
                        <svg viewBox="0 0 24 24" class="search-icon"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5A6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5S14 7.01 14 9.5S11.99 14 9.5 14z"/></svg>
                    </button>
                </form>
            </div>

            {{-- <div class="cart-area">
                <a href="#" class="cart-link">
                    <svg viewBox="0 0 24 24" class="cart-icon"><path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2s-.9-2-2-2zM1 2v2h2l3.6 7.59l-1.35 2.45c-.16.28-.25.61-.25.96c0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12l.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0 0 20 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2s2-.9 2-2s-.9-2-2-2z"/></svg>
                    <span class="cart-badge">0</span>
                </a>
            </div> --}}
        </div>
    </header>

    <div class="container">
        @if ($errors->any())
            <div class="alert alert-error">
                <strong>Please check the following errors:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
