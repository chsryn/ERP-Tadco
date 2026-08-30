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
            ->with(['deliveryOrder.customer'])
            ->when($search, function ($query, $search) {
                $query->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('driver_name', 'like', "%{$search}%")
                    ->orWhere('vehicle_no', 'like', "%{$search}%")
                    ->orWhereHas('deliveryOrder', function ($doQuery) use ($search) {
                        $doQuery->where('do_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($custQuery) use ($search) {
                                $custQuery->where('customer_name', 'like', "%{$search}%")
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
            ->with(['customer', 'items.product'])
            ->where('status', '=', 'validated')
            ->doesntHave('shipment')
            ->orderByDesc('do_date')
            ->get();

        return view('shipments.create', compact('deliveryOrders'));
    }

    public function store(Request $request, \App\Services\StockService $stockService)
    {
        $validated = $request->validate([
            'delivery_order_id' => ['required', 'exists:delivery_orders,id'],
            'shipment_date' => ['required', 'date'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $validated, $stockService) {
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

            // 1. Buat Shipment
            $shipment = Shipment::query()->create([
                'shipment_number' => $this->generateShipmentNumber(),
                'delivery_order_id' => $deliveryOrder->id,
                'shipment_date' => $validated['shipment_date'],
                'driver_name' => $validated['driver_name'] ?? null,
                'vehicle_no' => $validated['vehicle_no'] ?? null,
                'status' => 'shipped',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 2. Pemotongan stok dan pencatatan StockMovement melalui StockService
            $stockService->processShipmentDeduction($deliveryOrder, $shipment, $validated['shipment_date'], auth()->id());

            // 3. Update status DO
            $deliveryOrder->update([
                'status' => 'shipped',
            ]);
        });

        return redirect()
            ->route('shipments.index')
            ->with('success', 'Shipment berhasil diproses. Silakan tandai "Diterima" jika barang sudah sampai, lalu buat Invoice secara manual.');
    }

    public function show(Shipment $shipment)
    {
        $shipment->load([
            'deliveryOrder.customer',
            'deliveryOrder.items.product',
        ]);

        $stockMovements = StockMovement::query()
            ->with(['product'])
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

    public function destroy(Shipment $shipment, \App\Services\StockService $stockService)
    {
        if (in_array($shipment->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Shipment yang sudah diterima atau dibatalkan tidak dapat diubah.');
        }

        try {
            DB::transaction(function () use ($shipment, $stockService) {
                // 1. Kembalikan stok fisik dan reserve
                $stockService->reverseShipmentDeduction($shipment->deliveryOrder, $shipment, auth()->id());

                // 2. Kembalikan DO ke status awal (Siap Dikirim)
                if ($shipment->deliveryOrder) {
                    $shipment->deliveryOrder->update(['status' => 'validated']);
                }

                // 3. Batalkan Shipment
                $shipment->update(['status' => 'cancelled']);
            });

            return back()->with('success', 'Pengiriman berhasil dibatalkan. Stok fisik dikembalikan dan status DO kembali menjadi Siap Dikirim.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan pengiriman: ' . $e->getMessage());
        }
    }

    private function generateShipmentNumber(): string
    {
        return 'SHP-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }

    public function markReceived(\Illuminate\Http\Request $request, \App\Models\Shipment $shipment)
    {
        $request->validate(['received_date' => ['required', 'date', 'before_or_equal:today']]);
        if ($shipment->status === 'received') return back()->with('error', 'Sudah ditandai diterima.');

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $shipment) {
                // 1. Update status Shipment dan catat tanggal
                $shipment->update([
                    'status' => 'received',
                    'received_date' => $request->received_date,
                ]);

                // 2. Update status DO dan catat tanggal (TIDAK LAGI memotong stok karena sudah dipotong di awal shipment)
                if ($shipment->deliveryOrder && $shipment->deliveryOrder->status !== 'delivered') {
                    $shipment->deliveryOrder->update([
                        'status' => 'delivered',
                        'received_date' => $request->received_date,
                    ]);
                }
            });
            return back()->with('success', 'Barang berhasil diterima oleh customer! Status pengiriman dan DO telah diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui status: ' . $e->getMessage());
        }
    }
}
