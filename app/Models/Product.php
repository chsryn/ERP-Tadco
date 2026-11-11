<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

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
        // SCM-02 purchasing data
        'dynamics_code',
        'purchase_price',
        'weight_kg',
        'target_stock',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
        'purchase_price' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'target_stock' => 'decimal:2',
    ];

    public function discounts()
    {
        return $this->hasMany(ProductDiscount::class);
    }

    /**
     * Cari baris product_discounts yang berlaku untuk qty tertentu.
     *
     * Aturan ini sama dengan yang dipakai DeliveryOrderService: min_qty <= qty
     * <= max_qty (max_qty null = tanpa batas atas). Pemilihan tidak bergantung
     * pada urutan database. Jika lebih dari satu strata cocok, data master
     * dianggap tidak valid dan transaksi harus ditolak (tidak memilih acak).
     *
     * @throws ValidationException
     */
    public function discountForQty(float $qty): ?ProductDiscount
    {
        $matches = $this->discounts
            ->filter(function (ProductDiscount $discount) use ($qty) {
                $minQty = (float) $discount->min_qty;
                $maxQty = $discount->max_qty === null ? null : (float) $discount->max_qty;

                return $qty >= $minQty && ($maxQty === null || $qty <= $maxQty);
            })
            ->values();

        if ($matches->count() > 1) {
            $levels = $matches->pluck('strata_level')->implode(', ');

            throw ValidationException::withMessages([
                'discount' => "Produk {$this->product_code} - {$this->product_name} memiliki lebih dari satu strata diskon "
                    . "yang cocok untuk qty {$qty} ({$levels}). Perbaiki rentang min_qty/max_qty agar tidak tumpang tindih.",
            ]);
        }

        return $matches->first();
    }

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
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
