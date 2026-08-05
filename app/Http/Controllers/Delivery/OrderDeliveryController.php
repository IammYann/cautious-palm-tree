<?php

namespace App\Http\Controllers\Delivery;

use App\Events\OrderDelivered;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderDeliveryController extends Controller
{
    public function deliver(Request $request, Order $order)
    {
        $action = $request->input('action');
        $delivererId = auth()->id();

        if ($action === 'toggle_shipped') {
            $is_shipped = $request->has('shipped');
            
            if ($is_shipped && in_array($order->status, ['notdelivered', 'completed', 'pending'])) {
                $order->update(['status' => 'shipped', 'deliverer_id' => $delivererId]);
                return back()->with('success', 'Order marked as shipped.');
            } elseif (!$is_shipped && $order->status === 'shipped') {
                $order->update(['status' => 'notdelivered', 'deliverer_id' => null]);
                return back()->with('success', 'Order status reverted to notdelivered.');
            }
        } elseif ($action === 'toggle_delivered') {
            $is_delivered = $request->has('delivered');

            if ($is_delivered && $order->status !== 'delivered') {
                $order->update(['status' => 'delivered', 'deliverer_id' => $delivererId]);
                event(new OrderDelivered($order));
                return back()->with('success', 'Order marked as delivered.');
            } elseif (!$is_delivered && $order->status === 'delivered') {
                // Revert status to 'shipped' if unchecked
                $order->update(['status' => 'shipped']);
                return back()->with('success', 'Order status reverted to shipped.');
            }
        }

        return back();
    }
}
