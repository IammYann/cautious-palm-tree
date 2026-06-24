<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'amount',
        'quantity',
        'transaction_id',
        'transaction_uuid',
        'status',
        'payment_date',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    /**
     * Get the user that created this order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product this order is for
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if order is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Mark order as completed
     */
    public function markAsCompleted($transactionId)
    {
        $this->update([
            'transaction_id' => $transactionId,
            'status' => 'completed',
            'payment_date' => now(),
        ]);
    }

    /**
     * Mark order as failed
     */
    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
    }
}
