@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1>Welcome, {{ auth()->user()->name }}!</h1>

<div style="margin-top: 2rem;">
    @if(auth()->user()->role === 'admin')
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <!-- Admin Card -->
            <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #e74c3c;">
                <h2 style="color: #2c3e50; margin-bottom: 1rem;">📦 Product Management</h2>
                <p style="color: #7f8c8d; margin-bottom: 1rem;">Manage your products, add new ones, or edit existing products.</p>
                <a href="{{ route('admin.products.index') }}" class="btn">Go to Products</a>
            </div>

            <!-- Users Card -->
            <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #3498db;">
                <h2 style="color: #2c3e50; margin-bottom: 1rem;">👥 User Management</h2>
                <p style="color: #7f8c8d; margin-bottom: 1rem;">Promote or demote users to/from admin role.</p>
                <a href="{{ route('admin.users.index') }}" class="btn">Go to Users</a>
            </div>
        </div>

        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="color: #2c3e50;">Admin Panel</h2>
            <p style="color: #7f8c8d;">You have full access to manage products and users.</p>
        </div>
    @else
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #27ae60;">
            <h2 style="color: #2c3e50; margin-bottom: 1rem;">🛍️ Browse Products</h2>
            <p style="color: #7f8c8d; margin-bottom: 1rem;">Check out our amazing products posted by admins!</p>
            <a href="{{ route('products.index') }}" class="btn btn-success">View All Products</a>
        </div>
    @endif
</div>
@endsection
