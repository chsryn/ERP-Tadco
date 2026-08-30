<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    /**
     * Create a new Invoice from a shipped Delivery Order.
     *
     * @param array $validatedData
     * @param int|null $userId
     * @return Invoice
     * @throws ValidationException
     */
    public function createInvoice(array $validatedData, ?int $userId): Invoice
    {
        return DB::transaction(function () use ($validatedData, $userId) {
            /** @var DeliveryOrder|null $deliveryOrder */
            $deliveryOrder = DeliveryOrder::query()
                ->where('id', '=', $validatedData['delivery_order_id'])
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

            $subtotal = 0.0;

            foreach ($deliveryOrder->items as $item) {
                $subtotal += round((float) $item->line_total, 2);
            }

            $subtotal = round($subtotal, 2);
            $discountTotal = 0.0;
            $grandTotal = round($subtotal - $discountTotal, 2);

            $invoiceDate = Carbon::parse($validatedData['invoice_date']);
            $paymentTermDays = (int) ($validatedData['payment_term_days'] ?? 0);
            $dueDate = $invoiceDate->copy()->addDays($paymentTermDays);

            $invoice = Invoice::query()->create([
                'invoice_number' => $validatedData['invoice_number'] ?? null,
                'delivery_order_id' => $deliveryOrder->id,
                'customer_id' => $deliveryOrder->customer_id,
                'invoice_date' => $invoiceDate->format('Y-m-d'),
                'payment_term_days' => $paymentTermDays,
                'subtotal' => $subtotal,
                'grand_total' => $grandTotal,
                'paid_total' => 0.0,
                'receivable_amount' => $grandTotal,
                'status' => 'unpaid',
                'notes' => $validatedData['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($deliveryOrder->items as $item) {
                $basePrice = round((float) ($item->base_price ?? 0), 2);
                $discPerc = round((float) ($item->discount_percentage ?? 0), 2);
                $finalPrice = round((float) ($item->final_price ?? 0), 2);

                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'qty' => (int) $item->qty,
                    'base_price' => $basePrice,
                    'discount_percentage' => $discPerc,
                    'final_price' => $finalPrice,
                    'line_total' => round((float) $item->line_total, 2),
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Cancel an existing Invoice if it has no payments.
     *
     * @param Invoice $invoice
     * @return Invoice
     * @throws ValidationException
     */
    public function cancelInvoice(Invoice $invoice): Invoice
    {
        if (round((float) $invoice->paid_total, 2) > 0) {
            throw ValidationException::withMessages([
                'invoice' => 'Invoice yang sudah memiliki pembayaran tidak bisa dibatalkan.',
            ]);
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => 'cancelled',
            ]);

            return $invoice;
        });
    }

    /**
     * Generate unique Invoice number.
     *
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        return 'INV-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
