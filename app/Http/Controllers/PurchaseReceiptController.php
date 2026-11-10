<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseReceiptRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Services\PurchaseOrderService;
use App\Services\PurchaseReceiptService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PurchaseReceiptController extends Controller
{
    public function __construct(
        protected PurchaseReceiptService $purchaseReceiptService,
        protected StockService $stockService
    ) {}

    public function index(Request $request)
    {
        $search = $request->get('search');

        $purchaseReceipts = PurchaseReceipt::query()
            ->with(['purchaseOrder.supplier', 'creator'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('receipt_number', 'like', "%{$search}%")
                        ->orWhereHas('purchaseOrder', fn ($po) => $po->where('po_number', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('receipt_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('purchase_receipts.index', compact('purchaseReceipts', 'search'));
    }

    public function create(PurchaseOrder $purchaseOrder)
    {
        $this->ensureReceivable($purchaseOrder);

        $purchaseOrder->load(['supplier', 'items.product', 'receipts']);

        return view('purchase_receipts.create', compact('purchaseOrder'));
    }

    public function store(StorePurchaseReceiptRequest $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $purchaseReceipt = $this->purchaseReceiptService->createReceipt(
                $purchaseOrder,
                $request->validated(),
                auth()->id()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('purchase_receipts.show', $purchaseReceipt)
            ->with('success', 'Purchase Receipt ' . $purchaseReceipt->receipt_number . ' berhasil disimpan. Stock IN belum diproses (SCM-07).');
    }

    public function show(PurchaseReceipt $purchaseReceipt)
    {
        $purchaseReceipt->load(['purchaseOrder.supplier', 'items.product', 'items.purchaseOrderItem', 'creator']);

        $stockInProcessed = $this->stockService->receiptProcessed($purchaseReceipt);

        return view('purchase_receipts.show', compact('purchaseReceipt', 'stockInProcessed'));
    }

    /**
     * Stock IN (SCM-07): qty receipt masuk ke stok lewat StockService.
     */
    public function processStockIn(PurchaseReceipt $purchaseReceipt)
    {
        try {
            $this->stockService->processPurchaseReceipt($purchaseReceipt, auth()->id());
        } catch (ValidationException $e) {
            return redirect()
                ->route('purchase_receipts.show', $purchaseReceipt)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('purchase_receipts.show', $purchaseReceipt)
            ->with('success', 'Stock IN berhasil diproses. Stok gudang sudah bertambah sesuai qty receipt.');
    }

    private function ensureReceivable(PurchaseOrder $purchaseOrder): void
    {
        abort_unless(
            in_array($purchaseOrder->status, [PurchaseOrderService::STATUS_CONFIRMED, PurchaseOrderService::STATUS_PARTIAL], true),
            403,
            'Purchase Receipt hanya untuk PO berstatus confirmed atau partial.'
        );
    }
}
