<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_code',
        'product_name',
        'base_price',
        'uom',
        'net_weight',
        'segment',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function discounts()
    {
        return $this->hasMany(ProductDiscount::class);
    }

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getMaxStrataLevelAttribute(): ?string
    {
        return match (strtoupper($this->uom ?? '')) {
            'SACK' => 'S3',
            'BOX' => 'S5',
            default => 'S5',
        };
    }

    public function getAllowedTiersAttribute(): array
    {
        return match (strtoupper($this->uom ?? '')) {
            'SACK' => ['S1', 'S2', 'S3'],
            'BOX' => ['S1', 'S2', 'S3', 'S4', 'S5'],
            default => ['S1', 'S2', 'S3', 'S4', 'S5'],
        };
    }

    public function hasStrataLevel(string $strataLevel): bool
    {
        $allowedTiers = $this->allowed_tiers;
        return in_array(strtoupper($strataLevel), $allowedTiers, true);
    }

    /**
     * Helper to get discount percentage for a given strata level.
     */
    public function getDiscountPercentage(string $strataLevel): float
    {
        $discount = $this->discounts->firstWhere('strata_level', strtoupper($strataLevel));
        return $discount ? (float) $discount->discount_percentage : 0.0;
    }

    /**
     * Helper to calculate net unit price after strata discount.
     */
    public function getNetPriceForStrata(string $strataLevel): float
    {
        $basePrice = (float) $this->base_price;
        $discountPercentage = $this->getDiscountPercentage($strataLevel);
        return round($basePrice - ($basePrice * ($discountPercentage / 100)), 2);
    }
}
