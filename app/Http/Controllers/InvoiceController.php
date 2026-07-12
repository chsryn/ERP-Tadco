<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $invoices = Invoice::query()
            ->with(['customer', 'deliveryOrder'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('customer_name', 'like', "%{$search}%")
                                ->orWhere('customer_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('deliveryOrder', function ($doQuery) use ($search) {
                            $doQuery->where('do_number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('invoice_date')
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'search'));
    }

    public function create()
    {
        $deliveryOrders = DeliveryOrder::query()
            ->with(['customer', 'warehouse', 'items.product'])
            ->where('status', '=', 'shipped')
            ->doesntHave('invoice')
            ->orderByDesc('do_date')
            ->get();

        return view('invoices.create', compact('deliveryOrders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'delivery_order_id' => ['required', 'exists:delivery_orders,id'],
            'invoice_date' => ['required', 'date'],
            'payment_term_days' => ['nullable', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
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

            if ($deliveryOrder->status !== 'shipped') {
                throw ValidationException::withMessages([
                    'delivery_order_id' => 'Invoice hanya bisa dibuat dari DO yang sudah shipped.',
                ]);
            }

            $existingInvoice = Invoice::query()
                ->where('delivery_order_id', '=', $deliveryOrder->id)
                ->first();

            if ($existingInvoice) {
                throw ValidationException::withMessages([
                    'delivery_order_id' => 'DO ini sudah pernah dibuatkan invoice.',
                ]);
            }

            $deliveryOrder->load(['items.product']);

            if ($deliveryOrder->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'delivery_order_id' => 'DO tidak memiliki item.',
                ]);
            }

            $subtotal = 0;

            foreach ($deliveryOrder->items as $item) {
                $subtotal += (float) $item->line_total;
            }

            $discountTotal = 0;
            $grandTotal = $subtotal - $discountTotal;

            $invoiceDate = Carbon::parse($validated['invoice_date']);

            $paymentTermDays = $validated['payment_term_days'] ?? null;

            if (!empty($validated['due_date'])) {
                $dueDate = Carbon::parse($validated['due_date']);
            } elseif ($paymentTermDays !== null) {
                $dueDate = $invoiceDate->copy()->addDays((int) $paymentTermDays);
            } else {
                $dueDate = null;
            }

            $invoice = Invoice::query()->create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'delivery_order_id' => $deliveryOrder->id,
                'customer_id' => $deliveryOrder->customer_id,
                'invoice_date' => $invoiceDate->format('Y-m-d'),
                'payment_term_days' => $paymentTermDays,
                'due_date' => $dueDate?->format('Y-m-d'),
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'grand_total' => $grandTotal,
                'paid_total' => 0,
                'receivable_amount' => $grandTotal,
                'status' => 'unpaid',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($deliveryOrder->items as $item) {
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'tier_code' => $item->tier_code,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'discount_rate' => $item->discount_rate,
                    'line_total' => $item->line_total,
                ]);
            }
        });

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice berhasil dibuat dari DO yang sudah shipped.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'deliveryOrder.warehouse',
            'items.product',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice tidak diedit langsung pada versi ini.');
    }

    public function update(Request $request, Invoice $invoice)
    {
        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Update invoice belum diaktifkan.');
    }

    public function destroy(Invoice $invoice)
    {
        if ((float) $invoice->paid_total > 0) {
            return back()->withErrors([
                'invoice' => 'Invoice yang sudah memiliki pembayaran tidak bisa dibatalkan.',
            ]);
        }

        $invoice->update([
            'status' => 'cancelled',
        ]);

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice berhasil dibatalkan.');
    }

    private function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
