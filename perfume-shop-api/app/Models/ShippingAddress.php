<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingAddress extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'full_name',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'phone',
    ];

    /**
     * Get the user that owns the shipping address.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the orders for the shipping address.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
