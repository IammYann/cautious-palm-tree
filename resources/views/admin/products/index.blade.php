@extends('layouts.app')

@section('title', 'Manage Products')

@section('content')
<style>
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        background: var(--white);
        padding: 15px 20px;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .admin-header h1 {
        font-size: 20px;
        font-weight: 500;
        color: #222;
    }

    .admin-table-container {
        background: var(--white);
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 14px;
    }

    .admin-table th {
        background-color: #fafafa;
        color: var(--grey-color);
        font-weight: 500;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-color);
        text-transform: uppercase;
        font-size: 12px;
    }

    .admin-table td {
        padding: 14px 18px;
        border-bottom: 1px solid #f2f2f2;
        color: #424242;
    }

    .admin-table tr:last-child td {
        border-bottom: none;
    }

    .price-text {
        font-weight: 500;
        color: var(--primary-color);
    }

    .actions-cell {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
</style>

<div class="admin-header">
    <h1>Manage Products</h1>
    <a href="{{ route('admin.products.create') }}" class="btn btn-success">+ Add New Product</a>
</div>

@if ($products->count() > 0)
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Product Name</th>
                    <th style="width: 45%;">Description</th>
                    <th style="width: 15%; text-align: right;">Price</th>
                    <th style="width: 15%; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr>
                        <td style="font-weight: 500;">{{ $product->name }}</td>
                        <td style="color: var(--grey-color);">{{ Str::limit($product->description, 80) }}</td>
                        <td class="price-text" style="text-align: right;">Rs. {{ number_format($product->price, 2) }}</td>
                        <td>
                            <div class="actions-cell">
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</a>

                                <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" style="margin: 0; padding: 0; background: none; box-shadow: none;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="panel" style="text-align: center; padding: 40px 20px;">
        <p style="font-size: 16px; color: var(--grey-color); margin-bottom: 20px;">No products yet.</p>
        <a href="{{ route('admin.products.create') }}" class="btn btn-success">Create Your First Product</a>
    </div>
@endif
@endsection
