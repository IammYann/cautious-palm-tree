<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        'deliverer_id',
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
     * Get the deliverer that shipped/delivered this order
     */
    public function deliverer()
    {
        return $this->belongsTo(User::class, 'deliverer_id');
    }

    /**
     * Check if order is completed
     */
    public function isCompleted()
    {
        return in_array($this->status, ['notdelivered', 'shipped', 'delivered']);
    }

    /**
     * Mark order as completed
     */
    public function markAsCompleted($transactionId)
    {
        try {
            $this->update([
                'transaction_id' => $transactionId,
                'status' => 'notdelivered',
                'payment_date' => now(),
            ]);
            Log::info('Order marked as notdelivered', ['order_id' => $this->id, 'transaction_id' => $transactionId]);
        } catch (Throwable $e) {
            Log::error('Failed to mark order as notdelivered', [
                'order_id' => $this->id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Mark order as failed
     */
    public function markAsFailed()
    {
        try {
            $this->update(['status' => 'failed']);
            Log::info('Order marked as failed', ['order_id' => $this->id]);
        } catch (Throwable $e) {
            Log::error('Failed to mark order as failed', [
                'order_id' => $this->id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
