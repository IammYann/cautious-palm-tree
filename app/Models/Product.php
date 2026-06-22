<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'user_id',
    ];

    /**
     * Get the user that created this product
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
