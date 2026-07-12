<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $deliveryOrders = DeliveryOrder::query()
            ->with(['customer', 'warehouse'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('do_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('customer_name', 'like', "%{$search}%")
                                ->orWhere('customer_code', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('do_date')
            ->paginate(10)
            ->withQueryString();

        return view('delivery_orders.index', compact('deliveryOrders', 'search'));
    }

    public function create()
    {
        $customers = Customer::query()
            ->where('is_active', '=', true)
            ->orderBy('customer_name')
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', '=', true)
            ->orderBy('warehouse_name')
            ->get();

        $products = Product::query()
            ->where('is_active', '=', true)
            ->orderBy('product_name')
            ->get();

        return view('delivery_orders.create', compact('customers', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'do_date' => ['required', 'date'],
            'planned_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],

            'product_id' => ['required', 'array'],
            'product_id.*' => ['nullable', 'exists:products,id'],

            'qty' => ['required', 'array'],
            'qty.*' => ['nullable', 'numeric', 'min:0.01'],

            'tier_code' => ['required', 'array'],
            'tier_code.*' => ['nullable', 'in:S1,S2,S3,S4'],
        ]);

        $rows = [];

        foreach ($request->product_id as $index => $productId) {
            $qty = $request->qty[$index] ?? null;
            $tierCode = $request->tier_code[$index] ?? null;

            if ($productId && $qty && $tierCode) {
                $rows[] = [
                    'product_id' => $productId,
                    'qty' => (float) $qty,
                    'tier_code' => $tierCode,
                ];
            }
        }

        if (count($rows) === 0) {
            throw ValidationException::withMessages([
                'product_id' => 'Minimal harus ada 1 item produk pada Delivery Order.',
            ]);
        }

        DB::transaction(function () use ($validated, $rows) {
            $totalAmount = 0;

            $deliveryOrder = DeliveryOrder::query()->create([
                'do_number' => $this->generateDoNumber(),
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'sales_id' => auth()->id(),
                'do_date' => $validated['do_date'],
                'planned_delivery_date' => $validated['planned_delivery_date'] ?? null,
                'total_amount' => 0,
                'status' => 'validated',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($rows as $row) {
                $stockBalance = StockBalance::query()
                    ->where('warehouse_id', '=', $validated['warehouse_id'])
                    ->where('product_id', '=', $row['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$stockBalance) {
                    throw ValidationException::withMessages([
                        'product_id' => 'Stok produk belum tersedia di gudang yang dipilih.',
                    ]);
                }

                $availableStock = (float) $stockBalance->qty_available - (float) $stockBalance->qty_reserved;

                if ($availableStock < $row['qty']) {
                    $product = Product::query()->find($row['product_id']);

                    throw ValidationException::withMessages([
                        'qty' => 'Stok tidak cukup untuk produk ' . ($product->product_name ?? '-') .
                            '. Stok bisa dipakai: ' . $availableStock,
                    ]);
                }

                $priceData = ProductPrice::query()
                    ->where('product_id', '=', $row['product_id'])
                    ->where('tier_code', '=', $row['tier_code'])
                    ->first();

                $unitPrice = (float) ($priceData?->price ?? 0);
                $discountRate = (float) ($priceData?->discount_rate ?? 0);
                $lineTotal = $row['qty'] * $unitPrice * (1 - $discountRate);

                DeliveryOrderItem::query()->create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'product_id' => $row['product_id'],
                    'tier_code' => $row['tier_code'],
                    'qty' => $row['qty'],
                    'unit_price' => $unitPrice,
                    'discount_rate' => $discountRate,
                    'line_total' => $lineTotal,
                ]);

                $stockBalance->qty_reserved = (float) $stockBalance->qty_reserved + $row['qty'];
                $stockBalance->save();

                $totalAmount += $lineTotal;
            }

            $deliveryOrder->update([
                'total_amount' => $totalAmount,
            ]);
        });

        return redirect()
            ->route('delivery_orders.index')
            ->with('success', 'Delivery Order berhasil dibuat dan stok berhasil di-reserve.');
    }

    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load([
            'customer',
            'warehouse',
            'items.product',
        ]);

        return view('delivery_orders.show', compact('deliveryOrder'));
    }

    public function edit(DeliveryOrder $deliveryOrder)
    {
        return redirect()
            ->route('delivery_orders.show', $deliveryOrder)
            ->with('success', 'DO yang sudah dibuat tidak diedit langsung. Batalkan DO lalu buat ulang jika ada kesalahan.');
    }

    public function update(Request $request, DeliveryOrder $deliveryOrder)
    {
        return redirect()
            ->route('delivery_orders.show', $deliveryOrder)
            ->with('success', 'Update DO belum diaktifkan. Gunakan pembatalan DO jika diperlukan.');
    }

    public function destroy(DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status === 'cancelled') {
            return back()->with('success', 'Delivery Order sudah dibatalkan sebelumnya.');
        }

        if ($deliveryOrder->status === 'shipped') {
            return back()->withErrors([
                'status' => 'Delivery Order yang sudah dikirim tidak bisa dibatalkan.',
            ]);
        }

        DB::transaction(function () use ($deliveryOrder) {
            $deliveryOrder->load('items');

            foreach ($deliveryOrder->items as $item) {
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
        });

        return redirect()
            ->route('delivery_orders.index')
            ->with('success', 'Delivery Order berhasil dibatalkan dan stok reserved dikembalikan.');
    }

    private function generateDoNumber(): string
    {
        return 'DO-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
