<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Remove the die-and-dump and eager load product, user, and deliverer
        $orders = Order::with(['product', 'user', 'deliverer'])
                       ->whereNotIn('status', ['failed', 'pending', 'cancelled'])
                       ->latest()
                       ->get();
        return view('delivery.dashboard', compact('orders'));
    }
}
