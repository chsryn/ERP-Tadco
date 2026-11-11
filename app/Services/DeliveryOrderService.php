<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Product;
use App\Models\StockBalance;
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
        if (empty($items)) {
            throw ValidationException::withMessages([
                'product_id' => 'Minimal harus ada 1 item produk pada Delivery Order.',
            ]);
        }

        // Validasi seluruh item sebelum ada data yang ditulis. Ini mencegah DO,
        // item, atau reservasi stok parsial saat produk belum punya harga valid.
        $preparedItems = [];

        foreach ($items as $item) {
            $product = Product::query()
                ->with('discounts')
                ->find($item['product_id'] ?? null);

            if (!$product) {
                throw ValidationException::withMessages([
                    'product_id' => 'Produk pada Delivery Order tidak ditemukan.',
                ]);
            }

            $productLabel = "{$product->product_code} - {$product->product_name}";

            if (!$product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => "Produk {$productLabel} tidak aktif dan tidak bisa dipakai untuk transaksi.",
                ]);
            }

            $rawQty = $item['qty'] ?? null;
            if (!is_numeric($rawQty)) {
                throw ValidationException::withMessages([
                    'qty' => "Qty untuk produk {$productLabel} wajib berupa angka.",
                ]);
            }

            $numericQty = (float) $rawQty;
            if ($numericQty <= 0 || floor($numericQty) !== $numericQty) {
                throw ValidationException::withMessages([
                    'qty' => "Qty untuk produk {$productLabel} harus bilangan bulat lebih dari 0. "
                        . 'Qty desimal tidak didukung karena kolom qty DO/invoice bertipe integer.',
                ]);
            }

            $qty = (int) $numericQty;

            $basePrice = (float) $product->base_price;
            if ($basePrice <= 0) {
                throw ValidationException::withMessages([
                    'price' => "Produk {$productLabel} belum memiliki harga dasar (base_price) yang valid. "
                        . 'Lengkapi harga produk terlebih dahulu sebelum membuat transaksi.',
                ]);
            }

            $matchedDiscount = $product->discountForQty($qty);
            $discountPercentage = $matchedDiscount
                ? (float) $matchedDiscount->discount_percentage
                : 0.0;

            if ($discountPercentage < 0 || $discountPercentage > 100) {
                throw ValidationException::withMessages([
                    'discount' => "Diskon produk {$productLabel} untuk qty {$qty} tidak valid "
                        . "({$discountPercentage}%). Diskon harus berada di antara 0 dan 100.",
                ]);
            }

            $finalPrice = round($basePrice * (1 - ($discountPercentage / 100)), 2);
            if ($finalPrice <= 0) {
                throw ValidationException::withMessages([
                    'price' => "Harga bersih produk {$productLabel} untuk qty {$qty} adalah 0. "
                        . 'Transaksi dengan harga nol tidak diizinkan.',
                ]);
            }

            $preparedItems[] = [
                'product' => $product,
                'qty' => $qty,
                'base_price' => $basePrice,
                'discount_percentage' => $discountPercentage,
                'final_price' => $finalPrice,
                'line_total' => round($qty * $finalPrice, 2),
            ];
        }

        return DB::transaction(function () use ($validatedData, $preparedItems, $salesId) {
            $totalAmount = 0;

            $deliveryOrder = DeliveryOrder::query()->create([
                'do_number' => $this->generateDoNumber(),
                'customer_id' => $validatedData['customer_id'],
                'sales_id' => $salesId,
                'do_date' => $validatedData['do_date'],
                'planned_delivery_date' => $validatedData['planned_delivery_date'] ?? null,
                'total_amount' => 0,
                'status' => 'validated',
                'notes' => $validatedData['notes'] ?? null,
            ]);

            foreach ($preparedItems as $item) {
                /** @var Product $product */
                $product = $item['product'];

                /** @var StockBalance|null $stockBalance */
                $stockBalance = StockBalance::query()
                    ->where('product_id', '=', $product->id)
                    ->lockForUpdate()
                    ->first();

                if (!$stockBalance) {
                    $stockBalance = StockBalance::create([
                        'product_id' => $product->id,
                        'qty_available' => 0,
                        'qty_reserved' => 0,
                    ]);
                }

                $qty = $item['qty'];
                $availableStock = (float) $stockBalance->qty_available - (float) $stockBalance->qty_reserved;

                if ($availableStock < $qty) {
                    $availFormatted = number_format(max(0, $availableStock), 2, ',', '.');
                    $requestedFormatted = number_format($qty, 0, ',', '.');
                    throw ValidationException::withMessages([
                        'qty' => "Stok {$product->product_name} tidak mencukupi. Stok tersedia: {$availFormatted}, Jumlah diminta: {$requestedFormatted}.",
                    ]);
                }

                DeliveryOrderItem::query()->create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'base_price' => $item['base_price'],
                    'discount_percentage' => $item['discount_percentage'],
                    'final_price' => $item['final_price'],
                    'line_total' => $item['line_total'],
                ]);

                $stockBalance->qty_reserved = (float) $stockBalance->qty_reserved + $qty;
                $stockBalance->save();

                $totalAmount += $item['line_total'];
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
     * Mark delivery order as delivered and deduct stock.
     */
    public function markAsDelivered(DeliveryOrder $deliveryOrder, string $receivedDate, ?int $userId = null): DeliveryOrder
    {
        if ($deliveryOrder->status === 'delivered') {
            throw ValidationException::withMessages([
                'status' => 'Delivery Order sudah dalam status delivered.',
            ]);
        }

        if ($deliveryOrder->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'Delivery Order yang dibatalkan tidak bisa ditandai sebagai delivered.',
            ]);
        }

        return DB::transaction(function () use ($deliveryOrder, $receivedDate) {
            $deliveryOrder->load('items');

            foreach ($deliveryOrder->items as $item) {
                /** @var StockBalance|null $stockBalance */
                $stockBalance = StockBalance::query()
                    ->where('product_id', '=', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stockBalance) {
                    throw ValidationException::withMessages([
                        'qty' => 'Stok produk tidak ditemukan.',
                    ]);
                }

                $qty = (float) $item->qty;
                if ((float) $stockBalance->qty_reserved < $qty || (float) $stockBalance->qty_available < $qty) {
                    throw ValidationException::withMessages([
                        'qty' => 'Stok/Reserved tidak mencukupi untuk dikirim.',
                    ]);
                }

                $stockBalance->qty_reserved = (float) $stockBalance->qty_reserved - $qty;
                $stockBalance->qty_available = (float) $stockBalance->qty_available - $qty;
                $stockBalance->save();
            }

            $deliveryOrder->update([
                'status' => 'delivered',
                'received_date' => $receivedDate,
            ]);

            return $deliveryOrder->fresh();
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
