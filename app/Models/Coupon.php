<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'discount_percentage',
        'is_active',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Check if the coupon is valid and active
     */
    public function isValid(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope to only include active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate discount amount for a given price
     */
    public function calculateDiscount(float $price): float
    {
        return ($price * $this->discount_percentage) / 100;
    }

    /**
     * Calculate final price after discount
     */
    public function calculateFinalPrice(float $price): float
    {
        return $price - $this->calculateDiscount($price);
    }
}
