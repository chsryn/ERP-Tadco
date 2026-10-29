<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\Supplier;
use App\Services\ReorderService;
use Illuminate\Validation\ValidationException;

class ReorderController extends Controller
{
    public function __construct(
        protected ReorderService $reorderService
    ) {}

    public function index()
    {
        $recommendations = $this->reorderService->recommendations();

        return view('purchase_orders.reorder', [
            'recommendations' => $recommendations,
            'suppliers' => Supplier::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'reorderCount' => $recommendations->filter(fn ($row) => $row->suggested_qty > 0)->count(),
            'suggestedTotal' => $recommendations->sum('suggested_qty'),
        ]);
    }

    /**
     * Validasi memakai StorePurchaseOrderRequest (bentuk payload sama dengan create PO).
     * Pembuatan PO diserahkan ke ReorderService → PurchaseOrderService (SCM-03).
     */
    public function generate(StorePurchaseOrderRequest $request)
    {
        try {
            $purchaseOrder = $this->reorderService->generateDraft(
                $request->validated(),
                auth()->id()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Draft PO dari rekomendasi Auto PO berhasil dibuat. Review lalu konfirmasi bila sudah sesuai.');
    }
}
