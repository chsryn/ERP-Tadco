<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $stockBalances = StockBalance::with(['product', 'warehouse'])
            ->when($search, function ($query, $search) {
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('product_code', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('segment', 'like', "%{$search}%");
                });
            })
            ->orderBy('product_id')
            ->paginate(10)
            ->withQueryString();

        return view('inventory.index', compact('stockBalances', 'search'));
    }

    public function show(StockBalance $stockBalance)
    {
        $stockBalance->load(['product', 'warehouse']);

        $movements = StockMovement::with(['product', 'warehouse'])
            ->where('warehouse_id', $stockBalance->warehouse_id)
            ->where('product_id', $stockBalance->product_id)
            ->latest('movement_date')
            ->paginate(10);

        return view('inventory.show', compact('stockBalance', 'movements'));
    }

    public function createAdjustment()
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('product_name')
            ->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('warehouse_name')
            ->get();

        return view('inventory.adjustment', compact('products', 'warehouses'));
    }

    public function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'movement_type' => ['required', 'in:in,out,return,damage,adjustment_in,adjustment_out'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'movement_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $stockBalance = StockBalance::firstOrCreate(
                [
                    'warehouse_id' => $validated['warehouse_id'],
                    'product_id' => $validated['product_id'],
                ],
                [
                    'qty_available' => 0,
                    'qty_reserved' => 0,
                ]
            );

            $qty = (float) $validated['qty'];
            $movementType = $validated['movement_type'];

            $increaseTypes = ['in', 'return', 'adjustment_in'];
            $decreaseTypes = ['out', 'damage', 'adjustment_out'];

            if (in_array($movementType, $increaseTypes, true)) {
                $stockBalance->qty_available += $qty;
            }

            if (in_array($movementType, $decreaseTypes, true)) {
                if ($stockBalance->qty_available < $qty) {
    throw \Illuminate\Validation\ValidationException::withMessages([
        'qty' => 'Stok tidak cukup untuk dikurangi. Stok tersedia saat ini: ' . $stockBalance->qty_available,
    ]);
}

                $stockBalance->qty_available -= $qty;
            }

            $stockBalance->save();

            StockMovement::create([
                'warehouse_id' => $validated['warehouse_id'],
                'product_id' => $validated['product_id'],
                'movement_date' => $validated['movement_date'],
                'movement_type' => $movementType,
                'qty' => $qty,
                'source_type' => 'manual_adjustment',
                'source_id' => null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('inventory.index')
            ->with('success', 'Stok berhasil diperbarui dan riwayat pergerakan stok tersimpan.');
    }
}
