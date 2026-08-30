<?php

namespace App\Services;

use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function processShipmentDeduction($deliveryOrder, $shipment, $shipmentDate, $userId)
    {
        foreach ($deliveryOrder->items as $item) {
            $stockBalance = StockBalance::query()
                ->where('warehouse_id', $deliveryOrder->warehouse_id) // Menggunakan warehouse dari DO
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if (!$stockBalance) {
                throw ValidationException::withMessages(['delivery_order_id' => 'Stok produk ' . ($item->product->product_name ?? '-') . ' tidak ditemukan.']);
            }

            $qty = (float) $item->qty;
            if ($stockBalance->qty_reserved < $qty || $stockBalance->qty_available < $qty) {
                throw ValidationException::withMessages(['delivery_order_id' => 'Stok/Reserved tidak mencukupi untuk dikirim.']);
            }

            $stockBalance->qty_reserved -= $qty;
            $stockBalance->qty_available -= $qty;
            $stockBalance->save();

            StockMovement::query()->create([
                'warehouse_id' => $stockBalance->warehouse_id,
                'product_id' => $item->product_id,
                'movement_date' => $shipmentDate,
                'movement_type' => 'out',
                'qty' => $qty,
                'source_type' => 'shipment',
                'source_id' => $shipment->id,
                'notes' => 'Pengiriman dari DO ' . $deliveryOrder->do_number,
                'created_by' => $userId,
            ]);
        }
    }

    public function reverseShipmentDeduction($deliveryOrder, $shipment, $userId)
    {
        foreach ($deliveryOrder->items as $item) {
            $stockBalance = StockBalance::query()
                ->where('warehouse_id', $deliveryOrder->warehouse_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($stockBalance) {
                $qty = (float) $item->qty;
                // Kembalikan stok fisik dan kembalikan status reserve (booking)
                $stockBalance->qty_reserved += $qty;
                $stockBalance->qty_available += $qty;
                $stockBalance->save();

                // Catat pergerakan pembalik (In) untuk audit
                StockMovement::query()->create([
                    'warehouse_id' => $stockBalance->warehouse_id,
                    'product_id' => $item->product_id,
                    'movement_date' => now(),
                    'movement_type' => 'in',
                    'qty' => $qty,
                    'source_type' => 'shipment',
                    'source_id' => $shipment->id,
                    'notes' => 'Pembatalan Pengiriman DO ' . $deliveryOrder->do_number,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}
