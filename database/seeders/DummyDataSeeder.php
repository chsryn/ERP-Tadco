<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Setup Gudang & Customer
            $warehouse = Warehouse::firstOrCreate(
                ['warehouse_code' => 'WH-01'],
                ['warehouse_name' => 'Gudang Utama', 'is_active' => true]
            );

            $customer1 = Customer::firstOrCreate(
                ['customer_code' => 'CUST-01'],
                ['customer_name' => 'Toko Makmur Jaya', 'is_active' => true]
            );

            $customer2 = Customer::firstOrCreate(
                ['customer_code' => 'CUST-02'],
                ['customer_name' => 'Lapak Barito', 'is_active' => true]
            );

            // 2. Ambil Produk dari ProductSeeder
            $product1 = Product::where('product_code', 'TG-G-10B')->first();
            $product2 = Product::where('product_code', 'TG-GM-25')->first();

            if (!$product1 || !$product2) {
                $this->call(ProductSeeder::class);
                $product1 = Product::where('product_code', 'TG-G-10B')->first();
                $product2 = Product::where('product_code', 'TG-GM-25')->first();
            }

            // Inisialisasi Stok Gudang
            foreach ([$product1, $product2] as $prod) {
                if ($prod) {
                    StockBalance::updateOrCreate(
                        ['warehouse_id' => $warehouse->id, 'product_id' => $prod->id],
                        ['qty_available' => 500, 'qty_reserved' => 0]
                    );

                    StockMovement::create([
                        'warehouse_id'  => $warehouse->id,
                        'product_id'    => $prod->id,
                        'movement_date' => Carbon::now()->subDays(7),
                        'movement_type' => 'in',
                        'qty'           => 500,
                        'notes'         => 'Stok Awal Dummy',
                    ]);
                }
            }

            // Skenario 1 (Lunas/Paid): Customer 1 beli 'TG-G-10B' (BOX), Qty 15, TOP 0 (Cash), 3 hari yang lalu
            if ($product1) {
                $this->createScenarioTransaction(
                    warehouse: $warehouse,
                    customer: $customer1,
                    product: $product1,
                    qty: 15,
                    paymentTermDays: 0,
                    isPaid: true,
                    createdDate: Carbon::now()->subDays(3)
                );
            }

            // Skenario 2 (Tempo/Unpaid): Customer 2 beli 'TG-GM-25' (SACK), Qty 20, TOP 14 (Tempo), Hari ini
            if ($product2) {
                $this->createScenarioTransaction(
                    warehouse: $warehouse,
                    customer: $customer2,
                    product: $product2,
                    qty: 20,
                    paymentTermDays: 14,
                    isPaid: false,
                    createdDate: Carbon::now()
                );
            }
        });
    }

    private function createScenarioTransaction($warehouse, $customer, $product, int $qty, int $paymentTermDays, bool $isPaid, Carbon $createdDate): void
    {
        // Hitung diskon & final price otomatis berdasarkan relasi diskon produk
        $product->loadMissing('discounts');
        $basePrice = (float) $product->base_price;
        $discountPercentage = 0.0;

        $matchedDiscount = $product->discounts
            ->filter(fn ($d) => $qty >= (int) $d->min_qty && (is_null($d->max_qty) || $qty <= (int) $d->max_qty))
            ->first();

        if ($matchedDiscount) {
            $discountPercentage = (float) $matchedDiscount->discount_percentage;
        }

        $finalPrice = round($basePrice * (1 - ($discountPercentage / 100)), 2);
        $lineTotal = round($qty * $finalPrice, 2);

        $doNumber = 'DO-' . $createdDate->format('Ymd') . '-' . rand(100, 999);

        // 1. Buat Delivery Order (Status: shipped)
        $do = DeliveryOrder::create([
            'do_number'             => $doNumber,
            'customer_id'           => $customer->id,
            'warehouse_id'          => $warehouse->id,
            'do_date'               => $createdDate->format('Y-m-d'),
            'planned_delivery_date' => $createdDate->format('Y-m-d'),
            'status'                => 'shipped',
            'total_amount'          => $lineTotal,
        ]);

        DeliveryOrderItem::create([
            'delivery_order_id'   => $do->id,
            'product_id'          => $product->id,
            'qty'                 => $qty,
            'base_price'          => $basePrice,
            'discount_percentage' => $discountPercentage,
            'final_price'         => $finalPrice,
            'line_total'          => $lineTotal,
        ]);

        // Potong Stok & Buat Movement Out
        $stockBalance = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        if ($stockBalance) {
            $stockBalance->decrement('qty_available', $qty);
        }

        StockMovement::create([
            'warehouse_id'  => $warehouse->id,
            'product_id'    => $product->id,
            'movement_date' => $createdDate->format('Y-m-d H:i:s'),
            'movement_type' => 'out',
            'qty'           => $qty,
            'source_type'   => 'App\Models\DeliveryOrder',
            'source_id'     => $do->id,
            'notes'         => 'Pengiriman barang DO ' . $do->do_number,
        ]);

        // 2. Buat Invoice
        $dueDate = $createdDate->copy()->addDays($paymentTermDays);
        $status = $isPaid ? 'paid' : 'unpaid';
        $paidTotal = $isPaid ? $lineTotal : 0.0;
        $receivableAmount = $isPaid ? 0.0 : $lineTotal;

        $invoice = Invoice::create([
            'invoice_number'    => 'INV-' . $createdDate->format('Ymd') . '-' . rand(100, 999),
            'delivery_order_id' => $do->id,
            'customer_id'       => $customer->id,
            'invoice_date'      => $createdDate->format('Y-m-d'),
            'payment_term_days' => $paymentTermDays,
            'due_date'          => $dueDate->format('Y-m-d'),
            'subtotal'          => $lineTotal,
            'grand_total'       => $lineTotal,
            'paid_total'        => $paidTotal,
            'receivable_amount' => $receivableAmount,
            'status'            => $status,
        ]);

        InvoiceItem::create([
            'invoice_id'          => $invoice->id,
            'product_id'          => $product->id,
            'qty'                 => $qty,
            'base_price'          => $basePrice,
            'discount_percentage' => $discountPercentage,
            'final_price'         => $finalPrice,
            'line_total'          => $lineTotal,
        ]);

        // 3. Buat Record Payment jika Lunas
        if ($isPaid) {
            Payment::create([
                'payment_number' => 'PAY-' . $createdDate->format('Ymd') . '-' . rand(100, 999),
                'invoice_id'     => $invoice->id,
                'payment_date'   => $createdDate->format('Y-m-d'),
                'payment_method' => 'cash',
                'amount'         => $lineTotal,
                'notes'          => 'Pelunasan Cash saat transaksi',
            ]);
        }
    }
}
