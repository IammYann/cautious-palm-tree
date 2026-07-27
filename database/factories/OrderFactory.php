<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'amount' => 100,
            'quantity' => 1,
            'status' => 'pending',
            'transaction_uuid' => 'TXN-' . uniqid(),
        ];
    }
}
