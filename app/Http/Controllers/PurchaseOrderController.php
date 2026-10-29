<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService
    ) {}

    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');

        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                        ->orWhere('period', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('purchase_orders.index', compact('purchaseOrders', 'search', 'status'));
    }

    public function create()
    {
        return view('purchase_orders.create', $this->formData());
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        try {
            $purchaseOrder = $this->purchaseOrderService->generateDraft(
                $request->validated(),
                auth()->id()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order draft berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'creator', 'items.product']);

        return view('purchase_orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->isDraft(), 403, 'Hanya Purchase Order berstatus draft yang dapat diubah.');

        $purchaseOrder->load('items');

        return view('purchase_orders.edit', ['purchaseOrder' => $purchaseOrder] + $this->formData());
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $purchaseOrder = $this->purchaseOrderService->updateDraft(
                $purchaseOrder,
                $request->validated()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order draft berhasil diperbarui.');
    }

    public function confirm(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->purchaseOrderService->confirm($purchaseOrder);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order berhasil dikonfirmasi.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->purchaseOrderService->cancel($purchaseOrder);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order dibatalkan.');
    }

    private function formData(): array
    {
        return [
            'suppliers' => Supplier::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'products' => Product::query()
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('product_name')
                ->get(),
        ];
    }
}
