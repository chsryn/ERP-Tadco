<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Shipment;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $shipments = Shipment::query()
            ->with(['deliveryOrder.customer', 'deliveryOrder.warehouse'])
            ->when($search, function ($query, $search) {
                $query->where('shipment_number', 'like', "%{$search}%")
                    ->orWhereHas('deliveryOrder', function ($doQuery) use ($search) {
                        $doQuery->where('do_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                $customerQuery->where('customer_name', 'like', "%{$search}%")
                                    ->orWhere('customer_code', 'like', "%{$search}%");
                            });
                    });
            })
            ->orderByDesc('shipment_date')
            ->paginate(10)
            ->withQueryString();

        return view('shipments.index', compact('shipments', 'search'));
    }

    public function create()
    {
        $deliveryOrders = DeliveryOrder::query()
            ->with(['customer', 'warehouse', 'items.product'])
            ->where('status', '=', 'validated')
            ->doesntHave('shipment')
            ->orderByDesc('do_date')
            ->get();

        return view('shipments.create', compact('deliveryOrders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_order_id' => ['required', 'exists:delivery_orders,id'],
            'shipment_date' => ['required', 'date'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $deliveryOrder = DeliveryOrder::query()
                ->where('id', '=', $validated['delivery_order_id'])
                ->lockForUpdate()
                ->first();

            if (!$deliveryOrder) {
                throw ValidationException::withMessages([
                    'delivery_order_id' => 'Delivery Order tidak ditemukan.',
                ]);
            }

            if ($deliveryOrder->status !== 'validated') {
                throw ValidationException::withMessages([
                    'delivery_order_id' => 'Hanya DO dengan status validated yang bisa dikirim.',
                ]);
            }

            $existingShipment = Shipment::query()
                ->where('delivery_order_id', '=', $deliveryOrder->id)
                ->first();

            if ($existingShipment) {
                throw ValidationException::withMessages([
                    'delivery_order_id' => 'DO ini sudah pernah dibuatkan shipment.',
                ]);
            }

            $deliveryOrder->load(['items.product']);

            if ($deliveryOrder->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'delivery_order_id' => 'DO tidak memiliki item.',
                ]);
            }

            $shipment = Shipment::query()->create([
                'shipment_number' => $this->generateShipmentNumber(),
                'delivery_order_id' => $deliveryOrder->id,
                'shipment_date' => $validated['shipment_date'],
                'driver_name' => $validated['driver_name'] ?? null,
                'vehicle_no' => $validated['vehicle_no'] ?? null,
                'status' => 'delivered',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($deliveryOrder->items as $item) {
                $stockBalance = StockBalance::query()
                    ->where('warehouse_id', '=', $deliveryOrder->warehouse_id)
                    ->where('product_id', '=', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stockBalance) {
                    throw ValidationException::withMessages([
                        'delivery_order_id' => 'Stok produk ' . ($item->product->product_name ?? '-') . ' tidak ditemukan.',
                    ]);
                }

                $qty = (float) $item->qty;
                $qtyAvailable = (float) $stockBalance->qty_available;
                $qtyReserved = (float) $stockBalance->qty_reserved;

                if ($qtyReserved < $qty) {
                    throw ValidationException::withMessages([
                        'delivery_order_id' => 'Reserved stock tidak cukup untuk produk ' . ($item->product->product_name ?? '-') .
                            '. Reserved saat ini: ' . $qtyReserved,
                    ]);
                }

                if ($qtyAvailable < $qty) {
                    throw ValidationException::withMessages([
                        'delivery_order_id' => 'Stok tersedia tidak cukup untuk produk ' . ($item->product->product_name ?? '-') .
                            '. Stok tersedia saat ini: ' . $qtyAvailable,
                    ]);
                }

                $stockBalance->qty_reserved = $qtyReserved - $qty;
                $stockBalance->qty_available = $qtyAvailable - $qty;
                $stockBalance->save();

                StockMovement::query()->create([
                    'warehouse_id' => $deliveryOrder->warehouse_id,
                    'product_id' => $item->product_id,
                    'movement_date' => $validated['shipment_date'],
                    'movement_type' => 'out',
                    'qty' => $qty,
                    'source_type' => 'shipment',
                    'source_id' => $shipment->id,
                    'notes' => 'Pengiriman dari DO ' . $deliveryOrder->do_number,
                    'created_by' => auth()->id(),
                ]);
            }

            $deliveryOrder->update([
                'status' => 'shipped',
            ]);
        });

        return redirect()
            ->route('shipments.index')
            ->with('success', 'Shipment berhasil dibuat, stok available dan reserved berhasil dikurangi.');
    }

    public function show(Shipment $shipment)
    {
        $shipment->load([
            'deliveryOrder.customer',
            'deliveryOrder.warehouse',
            'deliveryOrder.items.product',
        ]);

        $stockMovements = StockMovement::query()
            ->with(['product', 'warehouse'])
            ->where('source_type', '=', 'shipment')
            ->where('source_id', '=', $shipment->id)
            ->orderBy('movement_date')
            ->get();

        return view('shipments.show', compact('shipment', 'stockMovements'));
    }

    public function edit(Shipment $shipment)
    {
        return redirect()
            ->route('shipments.show', $shipment)
            ->with('success', 'Shipment yang sudah dibuat tidak diedit pada versi ini.');
    }

    public function update(Request $request, Shipment $shipment)
    {
        return redirect()
            ->route('shipments.show', $shipment)
            ->with('success', 'Update shipment belum diaktifkan.');
    }

    public function destroy(Shipment $shipment)
    {
        return redirect()
            ->route('shipments.show', $shipment)
            ->withErrors([
                'shipment' => 'Shipment tidak bisa dihapus karena sudah memengaruhi stok. Buat fitur retur jika barang dikembalikan.',
            ]);
    }

    private function generateShipmentNumber(): string
    {
        return 'SHP-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
