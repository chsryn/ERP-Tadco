<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\StockBalance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\DB;

class DailySalesImport implements ToCollection, WithStartRow, SkipsEmptyRows
{
    public function startRow(): int
    {
        return 7;
    }

    public function collection(Collection $rows)
    {
        // Tambahkan query() agar Intelephense mengerti
        $warehouse = Warehouse::query()->first();
        if (!$warehouse) return;

        // Gunakan Database Transaction agar aman jika terjadi error di tengah jalan
        DB::transaction(function () use ($rows, $warehouse) {

            foreach ($rows as $row) {
                $customerCode = trim($row[2] ?? '');
                $noFaktur     = trim($row[4] ?? '');
                $itemCode     = trim($row[13] ?? '');

                if ($customerCode === '' || $noFaktur === '' || $itemCode === '') {
                    continue;
                }

                $customer = Customer::query()->where('customer_code', $customerCode)->first();
                $product = Product::query()->where('product_code', $itemCode)->first();

                if (!$customer || !$product) continue;

                $doDate = $this->parseDate($row[6]);
                $dueDate = $this->parseDate($row[8]); // Kolom DUE DATE (Bisa jadi string 'TUNAI')

                // Jika DUE DATE tertulis 'TUNAI' atau kosong, samakan dengan hari itu
                if (!$dueDate || strtolower(trim($row[8] ?? '')) === 'tunai') {
                    $dueDate = $doDate;
                }

                $qty = $this->toDecimal($row[16]);
                $price = $this->toDecimal($row[15]);
                $discountRate = $this->toDecimal($row[14]);
                $amount = $this->toDecimal($row[17]);
                $cash = $this->toDecimal($row[18]);
                $transfer = $this->toDecimal($row[19]);
                $receivable = $this->toDecimal($row[21]);

                // ==========================================
                // 1. BUAT DELIVERY ORDER & POTONG STOK
                // ==========================================
                $deliveryOrder = DeliveryOrder::query()->firstOrCreate(
                    ['do_number' => $noFaktur],
                    [
                        'customer_id'  => $customer->id,
                        'warehouse_id' => $warehouse->id,
                        'do_date'      => $doDate ?? now()->format('Y-m-d'),
                        'status'       => 'shipped',
                        'total_amount' => 0
                    ]
                );

                $existingItem = DeliveryOrderItem::query()
                    ->where('delivery_order_id', $deliveryOrder->id)
                    ->where('product_id', $product->id)
                    ->first();

                $oldQty = $existingItem ? (float) $existingItem->qty : 0;
                $qtyDifference = $qty - $oldQty;

                if ($qtyDifference != 0) {
                    $stockBalance = StockBalance::query()->firstOrCreate(
                        ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
                        ['qty_available' => 0, 'qty_reserved' => 0]
                    );
                    $stockBalance->qty_available -= $qtyDifference;
                    $stockBalance->save();
                }

                DeliveryOrderItem::query()->updateOrCreate(
                    ['delivery_order_id' => $deliveryOrder->id, 'product_id' => $product->id],
                    [
                        'tier_code'     => trim($row[12] ?? ''),
                        'qty'           => $qty,
                        'unit_price'    => $price,
                        'discount_rate' => $discountRate,
                        'line_total'    => $amount,
                    ]
                );

                $deliveryOrder->update(['total_amount' => $deliveryOrder->items()->sum('line_total')]);

                // ==========================================
                // 2. BUAT INVOICE
                // ==========================================
                $invoiceNumber = 'INV-' . $noFaktur;

                $invoice = Invoice::query()->firstOrCreate(
                    ['invoice_number' => $invoiceNumber],
                    [
                        'delivery_order_id' => $deliveryOrder->id,
                        'customer_id'       => $customer->id,
                        'invoice_date'      => $doDate ?? now()->format('Y-m-d'),
                        'due_date'          => $dueDate ?? now()->format('Y-m-d'),
                        'subtotal'          => 0,
                        'grand_total'       => 0,
                        'paid_total'        => 0,
                        'receivable_amount' => 0,
                        'status'            => 'unpaid'
                    ]
                );

                InvoiceItem::query()->updateOrCreate(
                    ['invoice_id' => $invoice->id, 'product_id' => $product->id],
                    [
                        'tier_code'     => trim($row[12] ?? ''),
                        'qty'           => $qty,
                        'unit_price'    => $price,
                        'discount_rate' => $discountRate,
                        'line_total'    => $amount,
                    ]
                );

                $newGrandTotal = $invoice->items()->sum('line_total');

                // ==========================================
                // 3. BUAT PAYMENT JIKA ADA PEMBAYARAN
                // ==========================================
                $totalDibayarSaatIni = 0;

                // Cek Pembayaran Cash
                if ($cash > 0) {
                    Payment::query()->firstOrCreate(
                        ['payment_number' => 'PAY-C-' . $noFaktur . '-' . $product->id],
                        [
                            'invoice_id'     => $invoice->id,
                            'payment_date'   => $doDate ?? now()->format('Y-m-d'),
                            'payment_method' => 'cash',
                            'amount'         => $cash,
                        ]
                    );
                    $totalDibayarSaatIni += $cash;
                }

                // Cek Pembayaran Transfer
                if ($transfer > 0) {
                    Payment::query()->firstOrCreate(
                        ['payment_number' => 'PAY-T-' . $noFaktur . '-' . $product->id],
                        [
                            'invoice_id'     => $invoice->id,
                            'payment_date'   => $doDate ?? now()->format('Y-m-d'),
                            'payment_method' => 'transfer',
                            'amount'         => $transfer,
                            'bank_name'      => trim($row[20] ?? ''),
                        ]
                    );
                    $totalDibayarSaatIni += $transfer;
                }

                // Update Total Invoice dan Statusnya
                $totalPaidSistem = Payment::query()->where('invoice_id', $invoice->id)->sum('amount');
                $newReceivable = $newGrandTotal - $totalPaidSistem;

                $newStatus = 'unpaid';
                if ($newReceivable <= 0) {
                    $newStatus = 'paid';
                }

                $invoice->update([
                    'subtotal'          => $newGrandTotal,
                    'grand_total'       => $newGrandTotal,
                    'paid_total'        => $totalPaidSistem,
                    'receivable_amount' => $newReceivable,
                    'status'            => $newStatus
                ]);
            }
        });
    }

    private function toDecimal(mixed $value): float
    {
        if ($value === null || $value === '') return 0;
        if (is_string($value)) $value = str_replace(',', '.', $value);
        return (float) $value;
    }

    // Tambahkan kata tipe 'mixed' untuk memuaskan VS Code (Error P1132)
    private function parseDate(mixed $value)
    {
        if (empty($value)) return null;
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }
        return date('Y-m-d', strtotime($value));
    }
}
