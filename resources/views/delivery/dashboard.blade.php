@extends('layouts.app')

@section('title', 'Delivery Dashboard')

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

    .badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-delivered {
        background-color: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #c8e6c9;
    }

    .badge-shipped {
        background-color: #fff3e0;
        color: #ff9358;
        border: 1px solid #ffe0b2;
    }

    .badge-pending {
        background-color: #e3f2fd;
        color: #1565c0;
        border: 1px solid #bbdefb;
    }

    .badge-notdelivered {
        background-color: #e8f5e9;
        color: #e30000;
        border: 1px solid #c8e6c9;
    }

    .badge-failed {
        background-color: #ffebee;
        color: #c62828;
        border: 1px solid #ffcdd2;
    }

    .badge-cancelled {
        background-color: #fafafa;
        color: #757575;
        border: 1px solid #e0e0e0;
    }

    .deliver-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .badge-completed {
        background-color: #fff3e0;
        color: #b71c1c;
        border: 1px solid #ffccbc;
    }
</style>

<div class="admin-header">
    <h1>Purchased Products</h1>
</div>

@if ($orders->count() > 0)
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Buyer</th>
                    <th>Status</th>
                    <th style="text-align: center;">Shipped?</th>
                    <th style="text-align: center;">Delivered?</th>
                    <th>Handled By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->product->name }}</td>
                        <td>{{ $order->user->name }}</td>
                        <td>
                            @if($order->status === 'completed')
                                <span class="badge badge-completed">Not Delivered</span>
                            @else
                                <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <form action="{{ route('delivery.orders.deliver', $order->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="toggle_shipped">
                                <input
                                    type="checkbox"
                                    name="shipped"
                                    class="deliver-checkbox"
                                    value="1"
                                    {{ in_array($order->status, ['shipped', 'delivered']) ? 'checked' : '' }}
                                    onchange="this.form.submit()"
                                >
                            </form>
                        </td>
                        <td style="text-align: center;">
                            <form action="{{ route('delivery.orders.deliver', $order->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="toggle_delivered">
                                <input
                                    type="checkbox"
                                    name="delivered"
                                    class="deliver-checkbox"
                                    value="1"
                                    {{ $order->status === 'delivered' ? 'checked' : '' }}
                                    onchange="this.form.submit()"
                                >
                            </form>
                        </td>
                        <td>{{ $order->deliverer ? $order->deliverer->name : 'Na' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="panel" style="text-align: center; padding: 40px 20px;">
        <p style="font-size: 16px; color: var(--grey-color); margin-bottom: 20px;">No purchased products yet.</p>
    </div>
@endif
@endsection
