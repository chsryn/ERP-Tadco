<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryOrderService
{
    /**
     * Create a new Delivery Order and reserve stock with Pessimistic Locking inside DB transaction.
     *
     * @param array $validatedData
     * @param array $items
     * @param int|null $salesId
     * @return DeliveryOrder
     * @throws ValidationException
     */
    public function createDeliveryOrder(array $validatedData, array $items, ?int $salesId): DeliveryOrder
    {
        $defaultWarehouse = Warehouse::query()->first();

        if (!$defaultWarehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Gudang default belum tersedia di sistem. Harap buat data gudang terlebih dahulu.',
            ]);
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'product_id' => 'Minimal harus ada 1 item produk pada Delivery Order.',
            ]);
        }

        return DB::transaction(function () use ($validatedData, $items, $defaultWarehouse, $salesId) {
            $totalAmount = 0;

            $deliveryOrder = DeliveryOrder::query()->create([
                'do_number' => $this->generateDoNumber(),
                'customer_id' => $validatedData['customer_id'],
                'warehouse_id' => $defaultWarehouse->id,
                'sales_id' => $salesId,
                'do_date' => $validatedData['do_date'],
                'planned_delivery_date' => $validatedData['planned_delivery_date'] ?? null,
                'total_amount' => 0,
                'status' => 'validated',
                'notes' => $validatedData['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                /** @var StockBalance|null $stockBalance */
                $stockBalance = StockBalance::query()
                    ->where('warehouse_id', '=', $defaultWarehouse->id)
                    ->where('product_id', '=', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$stockBalance) {
                    $stockBalance = StockBalance::create([
                        'warehouse_id' => $defaultWarehouse->id,
                        'product_id' => $item['product_id'],
                        'qty_available' => 0,
                        'qty_reserved' => 0,
                    ]);
                }

                $product = Product::query()->find($item['product_id']);

                $qty = (float) $item['qty'];
                $availableStock = (float) $stockBalance->qty_available - (float) $stockBalance->qty_reserved;

                if ($availableStock < $qty) {
                    $availFormatted = number_format(max(0, $availableStock), 2, ',', '.');
                    $requestedFormatted = number_format($qty, 2, ',', '.');
                    $productName = $product->product_name ?? 'Produk';
                    throw ValidationException::withMessages([
                        'qty' => "Stok {$productName} tidak mencukupi di {$defaultWarehouse->warehouse_name}. Stok tersedia: {$availFormatted}, Jumlah diminta: {$requestedFormatted}.",
                    ]);
                }

                $basePrice = (float) ($product->base_price ?? 0);
                $discountPercentage = 0.0;

                if ($product->relationLoaded('discounts') || $product->discounts) {
                    $matchedDiscount = $product->discounts
                        ->filter(fn ($d) => $qty >= (int) $d->min_qty && (is_null($d->max_qty) || $qty <= (int) $d->max_qty))
                        ->first();
                    if ($matchedDiscount) {
                        $discountPercentage = (float) $matchedDiscount->discount_percentage;
                    }
                }

                $finalPrice = round($basePrice * (1 - ($discountPercentage / 100)), 2);
                $lineTotal = round($qty * $finalPrice, 2);

                DeliveryOrderItem::query()->create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'product_id' => $item['product_id'],
                    'qty' => $qty,
                    'base_price' => $basePrice,
                    'discount_percentage' => $discountPercentage,
                    'final_price' => $finalPrice,
                    'line_total' => $lineTotal,
                ]);

                $stockBalance->qty_reserved = (float) $stockBalance->qty_reserved + $item['qty'];
                $stockBalance->save();

                $totalAmount += $lineTotal;
            }

            $deliveryOrder->update([
                'total_amount' => $totalAmount,
            ]);

            return $deliveryOrder;
        });
    }

    /**
     * Cancel a Delivery Order and release reserved stock.
     *
     * @param DeliveryOrder $deliveryOrder
     * @return DeliveryOrder
     * @throws ValidationException
     */
    public function cancelDeliveryOrder(DeliveryOrder $deliveryOrder): DeliveryOrder
    {
        if ($deliveryOrder->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'Delivery Order sudah dibatalkan sebelumnya.',
            ]);
        }

        if ($deliveryOrder->status === 'shipped') {
            throw ValidationException::withMessages([
                'status' => 'Delivery Order yang sudah dikirim tidak bisa dibatalkan.',
            ]);
        }

        return DB::transaction(function () use ($deliveryOrder) {
            $deliveryOrder->load('items');

            foreach ($deliveryOrder->items as $item) {
                /** @var StockBalance|null $stockBalance */
                $stockBalance = StockBalance::query()
                    ->where('warehouse_id', '=', $deliveryOrder->warehouse_id)
                    ->where('product_id', '=', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($stockBalance) {
                    $stockBalance->qty_reserved = max(
                        0,
                        (float) $stockBalance->qty_reserved - (float) $item->qty
                    );
                    $stockBalance->save();
                }
            }

            $deliveryOrder->update([
                'status' => 'cancelled',
            ]);

            return $deliveryOrder;
        });
    }

    /**
     * Generate unique Delivery Order Number.
     *
     * @return string
     */
    private function generateDoNumber(): string
    {
        return 'DO-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
