<?php

namespace Tests\Feature;

use App\Exports\PaymentsReportExport;
use App\Exports\ReceivablesReportExport;
use App\Exports\SalesReportExport;
use App\Exports\StockReportExport;
use App\Imports\CustomersImport;
use App\Imports\DailySalesImport;
use App\Imports\ProductsImport;
use App\Imports\StockImport;
use App\Imports\TadcoMasterImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ExcelImportExportTest extends TestCase
{
    use RefreshDatabase;

    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    /**
     * Bangun file .xlsx multi-sheet dari array baris (0-based, null dilewati).
     *
     * @param  array<string, array<int, array<int, mixed>>>  $sheets
     */
    private function makeXlsx(array $sheets): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheets as $title => $rows) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($title);

            foreach ($rows as $rowIndex => $columns) {
                foreach ($columns as $columnIndex => $value) {
                    if ($value !== null) {
                        $sheet->setCellValue([$columnIndex + 1, $rowIndex + 1], $value);
                    }
                }
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $path = $this->tempPath();
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return $path;
    }

    private function reopen(string $binary): Spreadsheet
    {
        $path = $this->tempPath();
        file_put_contents($path, $binary);

        return IOFactory::load($path);
    }

    private function tempPath(): string
    {
        $path = sys_get_temp_dir().'/erp-excel-test-'.bin2hex(random_bytes(8)).'.xlsx';
        $this->tempFiles[] = $path;

        return $path;
    }

    private function activeUser(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    /**
     * Seed satu customer, satu produk, satu DO, satu invoice berisi 1 item, dan 1 payment.
     *
     * @return array{0: User, 1: int} [user, invoiceId]
     */
    private function seedSalesData(): array
    {
        $user = $this->activeUser();
        $now = now();

        $customerId = DB::table('customers')->insertGetId([
            'customer_code' => 'C-001',
            'customer_name' => 'Toko Uji',
            'city' => 'Gorontalo',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $productId = DB::table('products')->insertGetId([
            'product_code' => 'P-001',
            'product_name' => 'Produk Uji',
            'uom' => 'BOX',
            'segment' => 'SEG1',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $doId = DB::table('delivery_orders')->insertGetId([
            'do_number' => 'DO-001',
            'customer_id' => $customerId,
            'sales_id' => $user->id,
            'do_date' => '2026-11-01',
            'total_amount' => 2000,
            'status' => 'shipped',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-001',
            'delivery_order_id' => $doId,
            'customer_id' => $customerId,
            'invoice_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'subtotal' => 2000,
            'grand_total' => 2000,
            'paid_total' => 500,
            'receivable_amount' => 1500,
            'status' => 'unpaid',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('invoice_items')->insert([
            'invoice_id' => $invoiceId,
            'product_id' => $productId,
            'qty' => 2,
            'base_price' => 1000,
            'discount_percentage' => 0,
            'final_price' => 1000,
            'line_total' => 2000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('payments')->insert([
            'payment_number' => 'PAY-001',
            'invoice_id' => $invoiceId,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 500,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$user, $invoiceId];
    }

    // ---------------------------------------------------------------------
    // EXPORT
    // ---------------------------------------------------------------------

    public function test_sales_report_export_produces_readable_xlsx(): void
    {
        $this->seedSalesData();

        $binary = Excel::raw(new SalesReportExport, ExcelType::XLSX);

        $this->assertNotSame('', $binary);

        $sheet = $this->reopen($binary)->getActiveSheet();

        $this->assertSame('No Invoice', $sheet->getCell('A1')->getValue());
        $this->assertSame('Nama Customer', $sheet->getCell('F1')->getValue());
        $this->assertSame('INV-001', $sheet->getCell('A2')->getValue());
        $this->assertSame('DO-001', $sheet->getCell('B2')->getValue());
        $this->assertSame('Toko Uji', $sheet->getCell('F2')->getValue());
        $this->assertSame(1500.0, (float) $sheet->getCell('I2')->getValue());
    }

    public function test_receivables_and_payments_exports_produce_readable_xlsx(): void
    {
        $this->seedSalesData();

        $receivables = $this->reopen(Excel::raw(new ReceivablesReportExport, ExcelType::XLSX))->getActiveSheet();
        $this->assertSame('Sisa Piutang', $receivables->getCell('I1')->getValue());
        $this->assertSame('INV-001', $receivables->getCell('A2')->getValue());
        $this->assertSame(1500.0, (float) $receivables->getCell('I2')->getValue());

        $payments = $this->reopen(Excel::raw(new PaymentsReportExport, ExcelType::XLSX))->getActiveSheet();
        $this->assertSame('Nominal', $payments->getCell('I1')->getValue());
        $this->assertSame('PAY-001', $payments->getCell('A2')->getValue());
        $this->assertSame(500.0, (float) $payments->getCell('I2')->getValue());
    }

    public function test_report_export_routes_stream_xlsx_files(): void
    {
        [$user] = $this->seedSalesData();

        foreach ([
            'reports.sales.export',
            'reports.receivables.export',
            'reports.payments.export',
            'reports.stock.export', // tanpa data stok: jalur kosong tetap harus valid
        ] as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));

            $response->assertOk();
            $this->assertStringContainsString(
                'spreadsheetml.sheet',
                (string) $response->headers->get('content-type'),
                "Route {$routeName} tidak mengembalikan file xlsx."
            );
        }
    }

    public function test_invoice_template_export_fills_template_cells(): void
    {
        [$user, $invoiceId] = $this->seedSalesData();

        $response = $this->actingAs($user)->get(route('invoices.export_excel', $invoiceId));
        $response->assertOk();

        $sheet = $this->reopen($response->streamedContent())->getActiveSheet();

        $this->assertSame('INV-001', $sheet->getCell('H5')->getValue());
        $this->assertSame('TOKO UJI', $sheet->getCell('A10')->getValue());
        $this->assertSame('Produk Uji', $sheet->getCell('A15')->getValue());
        // Tanpa diskon: PRICE (final_price) = base_price.
        $this->assertSame(1000.0, (float) $sheet->getCell('F15')->getValue());
        $this->assertSame(2, (int) $sheet->getCell('G15')->getValue());
        $this->assertSame(2000.0, (float) $sheet->getCell('H15')->getValue());
    }

    // ---------------------------------------------------------------------
    // IMPORT
    // ---------------------------------------------------------------------

    public function test_customers_import_creates_customers(): void
    {
        $path = $this->makeXlsx([
            'LIST CUSTOMER' => [
                [null, 'NAMA CUSTOMER', 'KODE'],
                [null, 'Toko A', 'C-100'],
                [null, 'Toko B', 'C-101'],
            ],
        ]);

        Excel::import(new CustomersImport, $path);

        $this->assertDatabaseHas('customers', ['customer_code' => 'C-100', 'customer_name' => 'Toko A']);
        $this->assertDatabaseHas('customers', ['customer_code' => 'C-101', 'customer_name' => 'Toko B']);
        $this->assertSame(2, DB::table('customers')->count());
    }

    public function test_stock_import_updates_stock_balance(): void
    {
        $productId = DB::table('products')->insertGetId([
            'product_code' => 'P-200',
            'product_name' => 'Produk Stok',
            'uom' => 'BOX',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->makeXlsx([
            'LAP.STOK' => [
                [null], [null], [null], [null], [null], [null],
                [null, 'P-200', 12.5],
            ],
        ]);

        Excel::import(new StockImport, $path);

        $this->assertDatabaseHas('stock_balances', ['product_id' => $productId]);
        $this->assertSame(
            12.5,
            (float) DB::table('stock_balances')->where('product_id', $productId)->value('qty_available')
        );
    }

    public function test_daily_sales_import_creates_delivery_order_invoice_and_payment(): void
    {
        $now = now();
        DB::table('customers')->insert([
            'customer_code' => 'C-001',
            'customer_name' => 'Toko Uji',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $productId = DB::table('products')->insertGetId([
            'product_code' => 'P-001',
            'product_name' => 'Produk Uji',
            'uom' => 'BOX',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $path = $this->makeXlsx([
            'LAP.HARIAN' => [
                [null], [null], [null], [null], [null], [null],
                [null, null, 'C-001', null, 'F-001', null, '2026-11-01', null, 'TUNAI', null, null, null, 'S1', 'P-001', 0, 1000, 2, 2000, 2000, 0, null, 0],
            ],
        ]);

        Excel::import(new DailySalesImport, $path);

        $this->assertDatabaseHas('delivery_orders', ['do_number' => 'F-001', 'status' => 'shipped']);
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-F-001',
            'grand_total' => 2000,
            'paid_total' => 2000,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('payments', ['payment_number' => 'PAY-C-F-001-'.$productId, 'payment_method' => 'cash']);
        $this->assertSame(2, (int) DB::table('delivery_order_items')->value('qty'));
        $this->assertSame(2000.0, (float) DB::table('invoice_items')->value('line_total'));
    }

    public function test_master_import_workbook_exposes_four_named_sheets(): void
    {
        $path = $this->makeXlsx([
            'LIST CUSTOMER' => [[null, 'NAMA CUSTOMER', 'KODE']],
            'LIST ITEM' => [[null]],
            'LAP.HARIAN' => [[null]],
            'LAP.STOK' => [[null]],
        ]);

        $import = new TadcoMasterImport;

        $this->assertInstanceOf(Import::class, $import, 'TadcoMasterImport wajib implements Import sejak Excel 4.x.');
        $this->assertSame(
            ['LIST CUSTOMER', 'LIST ITEM', 'LAP.HARIAN', 'LAP.STOK'],
            array_keys($import->sheets())
        );

        $sheets = Excel::toArray($import, $path);
        $this->assertCount(4, $sheets);
    }

    // ---------------------------------------------------------------------
    // Schema drift remediation (Plan A)
    // ---------------------------------------------------------------------

    private function seedCustomer(): void
    {
        DB::table('customers')->insert([
            'customer_code' => 'C-001',
            'customer_name' => 'Toko Uji',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedProductWithDiscounts(): int
    {
        $now = now();

        $productId = DB::table('products')->insertGetId([
            'product_code' => 'P-001',
            'product_name' => 'Produk Uji',
            'base_price' => 1000,
            'uom' => 'BOX',
            'segment' => 'SEG1',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_discounts')->insert([
            ['product_id' => $productId, 'strata_level' => 'S1', 'min_qty' => 1, 'max_qty' => 5, 'discount_percentage' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['product_id' => $productId, 'strata_level' => 'S2', 'min_qty' => 6, 'max_qty' => 14, 'discount_percentage' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['product_id' => $productId, 'strata_level' => 'S3', 'min_qty' => 15, 'max_qty' => null, 'discount_percentage' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        return $productId;
    }

    public function test_products_import_creates_and_updates_products(): void
    {
        $path = $this->makeXlsx([
            'LIST ITEM' => [
                [null],
                [null],
                ['box', 'SEG1', 'Produk Import', 'P-300', 'SEC', 1000, 0, 950, 0.01],
            ],
        ]);

        Excel::import(new ProductsImport, $path);

        // Produk baru tanpa sumber harga yang terbukti harus inactive dan
        // tidak mendapat harga/diskon spekulatif.
        $this->assertDatabaseHas('products', [
            'product_code' => 'P-300',
            'product_name' => 'Produk Import',
            'uom' => 'BOX',
            'segment' => 'SEG1',
            'is_active' => false,
            'base_price' => 0,
        ]);
        $importedProductId = DB::table('products')->where('product_code', 'P-300')->value('id');
        $this->assertSame(0, DB::table('product_discounts')->where('product_id', $importedProductId)->count());

        // Import ulang harus memperbarui, bukan membuat duplikat.
        $path2 = $this->makeXlsx([
            'LIST ITEM' => [
                [null],
                [null],
                ['SACK', 'SEG2', 'Produk Import Revisi', 'P-300'],
            ],
        ]);

        Excel::import(new ProductsImport, $path2);

        $this->assertSame(1, DB::table('products')->where('product_code', 'P-300')->count());
        $this->assertDatabaseHas('products', [
            'product_code' => 'P-300',
            'product_name' => 'Produk Import Revisi',
            'uom' => 'SACK',
            'segment' => 'SEG2',
        ]);
    }

    public function test_products_import_preserves_existing_price_and_status(): void
    {
        $now = now();
        $existingId = DB::table('products')->insertGetId([
            'product_code' => 'P-400',
            'product_name' => 'Produk Lama',
            'base_price' => 15000,
            'uom' => 'SACK',
            'segment' => 'SEG-LAMA',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $path = $this->makeXlsx([
            'LIST ITEM' => [
                [null],
                [null],
                ['SACK', 'SEG-BARU', 'Produk Lama Revisi', 'P-400'],
                ['BOX', 'SEG-BARU', 'Produk Baru', 'P-401'],
            ],
        ]);

        Excel::import(new ProductsImport, $path);

        // Produk lama: identitas berubah, harga dan status tidak boleh disentuh.
        $this->assertDatabaseHas('products', [
            'id' => $existingId,
            'product_code' => 'P-400',
            'product_name' => 'Produk Lama Revisi',
            'segment' => 'SEG-BARU',
            'base_price' => 15000,
            'is_active' => true,
        ]);
        $this->assertSame(0, DB::table('product_discounts')->where('product_id', $existingId)->count());

        // Produk baru: inactive, tanpa harga/diskon.
        $newId = DB::table('products')->where('product_code', 'P-401')->value('id');
        $this->assertNotNull($newId);
        $this->assertDatabaseHas('products', [
            'id' => $newId,
            'base_price' => 0,
            'is_active' => false,
        ]);
        $this->assertSame(0, DB::table('product_discounts')->where('product_id', $newId)->count());
    }

    public function test_products_import_rejects_uom_outside_enum(): void
    {
        $path = $this->makeXlsx([
            'LIST ITEM' => [
                [null],
                [null],
                ['PCS', 'SEG1', 'Produk Invalid', 'P-BAD'],
            ],
        ]);

        try {
            Excel::import(new ProductsImport, $path);
            $this->fail('Import seharusnya menolak UOM di luar BOX/SACK.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('UOM tidak valid', $e->errors()['file_master'][0]);
        }

        $this->assertDatabaseMissing('products', ['product_code' => 'P-BAD']);
    }

    public function test_master_import_persists_customer_and_product_sheets(): void
    {
        $path = $this->makeXlsx([
            'LIST CUSTOMER' => [
                [null, 'NAMA CUSTOMER', 'KODE'],
                [null, 'Toko Master', 'C-300'],
            ],
            'LIST ITEM' => [
                [null],
                [null],
                ['BOX', 'SEG1', 'Produk Master', 'P-301'],
            ],
            'LAP.HARIAN' => [[null]],
            'LAP.STOK' => [[null]],
        ]);

        Excel::import(new TadcoMasterImport, $path);

        $this->assertDatabaseHas('customers', ['customer_code' => 'C-300', 'customer_name' => 'Toko Master']);
        $this->assertDatabaseHas('products', ['product_code' => 'P-301', 'product_name' => 'Produk Master', 'uom' => 'BOX']);
    }

    public function test_daily_sales_import_uses_product_discounts_and_keeps_excel_amounts(): void
    {
        $this->seedCustomer();
        $productId = $this->seedProductWithDiscounts();

        // DISCOUNT workbook 0.05 sengaja berbeda dari product_discounts (S2 = 2%).
        // product_discounts harus menang; PRICE dan AMOUNT workbook tetap dipakai.
        $path = $this->makeXlsx([
            'LAP.HARIAN' => [
                [null], [null], [null], [null], [null], [null],
                [null, null, 'C-001', null, 'F-010', null, '2026-11-01', null, 'TUNAI', null, null, null, 'S2', 'P-001', 0.05, 980, 10, 9800, 9800, 0, null, 0],
            ],
        ]);

        Excel::import(new DailySalesImport, $path);

        $this->assertDatabaseHas('delivery_order_items', [
            'product_id' => $productId,
            'qty' => 10,
            'base_price' => 1000,
            'discount_percentage' => 2,
            'final_price' => 980,
            'line_total' => 9800,
        ]);
        $this->assertDatabaseHas('invoice_items', [
            'product_id' => $productId,
            'qty' => 10,
            'base_price' => 1000,
            'discount_percentage' => 2,
            'final_price' => 980,
            'line_total' => 9800,
        ]);
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-F-010',
            'grand_total' => 9800,
            'paid_total' => 9800,
            'status' => 'paid',
        ]);
    }

    public function test_daily_sales_import_uses_net_price_without_double_discount(): void
    {
        $this->seedCustomer();
        $now = now();

        // Harga dasar Rp100.000, diskon 2% untuk qty 2.
        $productId = DB::table('products')->insertGetId([
            'product_code' => 'P-001',
            'product_name' => 'Produk Net Price',
            'base_price' => 100000,
            'uom' => 'BOX',
            'segment' => 'SEG1',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_discounts')->insert([
            'product_id' => $productId,
            'strata_level' => 'S1',
            'min_qty' => 1,
            'max_qty' => 5,
            'discount_percentage' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // PRICE workbook adalah harga BERSIH Rp98.000, bukan harga dasar.
        // AMOUNT = PRICE x QTY = 98.000 x 2 = 196.000.
        $path = $this->makeXlsx([
            'LAP.HARIAN' => [
                [null], [null], [null], [null], [null], [null],
                [null, null, 'C-001', null, 'F-100', null, '2026-11-01', null, 'TUNAI', null, null, null, 'S1', 'P-001', 0.02, 98000, 2, 196000, 196000, 0, null, 0],
            ],
        ]);

        Excel::import(new DailySalesImport, $path);

        $doItem = DB::table('delivery_order_items')->where('product_id', $productId)->first();
        $invoiceItem = DB::table('invoice_items')->where('product_id', $productId)->first();

        foreach ([$doItem, $invoiceItem] as $item) {
            $this->assertNotNull($item);
            $this->assertSame(2, (int) $item->qty);
            $this->assertSame(100000.0, (float) $item->base_price);
            $this->assertSame(2.0, (float) $item->discount_percentage);
            // Bukan 98.000 x 0,98 = 96.040 (diskon ganda).
            $this->assertSame(98000.0, (float) $item->final_price);
            $this->assertSame(196000.0, (float) $item->line_total);
            $this->assertSame(
                (float) $item->line_total,
                (int) $item->qty * (float) $item->final_price
            );
        }

        $this->assertDatabaseHas('delivery_orders', [
            'do_number' => 'F-100',
            'total_amount' => 196000,
        ]);
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-F-100',
            'subtotal' => 196000,
            'grand_total' => 196000,
            'paid_total' => 196000,
            'receivable_amount' => 0,
            'status' => 'paid',
        ]);
    }

    public function test_daily_sales_import_falls_back_to_excel_discount_when_no_product_discount_matches(): void
    {
        $this->seedCustomer();
        $productId = DB::table('products')->insertGetId([
            'product_code' => 'P-001',
            'product_name' => 'Produk Tanpa Diskon',
            'base_price' => 1000,
            'uom' => 'BOX',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->makeXlsx([
            'LAP.HARIAN' => [
                [null], [null], [null], [null], [null], [null],
                [null, null, 'C-001', null, 'F-011', null, '2026-11-01', null, 'TUNAI', null, null, null, 'S2', 'P-001', 0.05, 950, 10, 9500, 9500, 0, null, 0],
            ],
        ]);

        Excel::import(new DailySalesImport, $path);

        $this->assertDatabaseHas('delivery_order_items', [
            'product_id' => $productId,
            'qty' => 10,
            'base_price' => 1000,
            'discount_percentage' => 5,
            'final_price' => 950,
            'line_total' => 9500,
        ]);
    }

    public function test_daily_sales_import_rejects_fractional_qty(): void
    {
        $this->seedCustomer();
        DB::table('products')->insert([
            'product_code' => 'P-001',
            'product_name' => 'Produk Uji',
            'base_price' => 1000,
            'uom' => 'BOX',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->makeXlsx([
            'LAP.HARIAN' => [
                [null], [null], [null], [null], [null], [null],
                [null, null, 'C-001', null, 'F-020', null, '2026-11-01', null, 'TUNAI', null, null, null, 'S1', 'P-001', 0, 1000, 1.5, 1500, 1500, 0, null, 0],
            ],
        ]);

        try {
            Excel::import(new DailySalesImport, $path);
            $this->fail('Import seharusnya menolak qty desimal.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Qty tidak valid', $e->errors()['file_master'][0]);
        }

        $this->assertSame(0, DB::table('delivery_orders')->count());
        $this->assertSame(0, DB::table('invoices')->count());
    }

    public function test_daily_sales_import_rejects_zero_price(): void
    {
        $this->seedCustomer();
        DB::table('products')->insert([
            'product_code' => 'P-001',
            'product_name' => 'Produk Uji',
            'base_price' => 1000,
            'uom' => 'BOX',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->makeXlsx([
            'LAP.HARIAN' => [
                [null], [null], [null], [null], [null], [null],
                [null, null, 'C-001', null, 'F-021', null, '2026-11-01', null, 'TUNAI', null, null, null, 'S1', 'P-001', 0, 0, 2, 0, 0, 0, null, 0],
            ],
        ]);

        try {
            Excel::import(new DailySalesImport, $path);
            $this->fail('Import seharusnya menolak harga nol.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Harga (PRICE) tidak valid', $e->errors()['file_master'][0]);
        }

        $this->assertSame(0, DB::table('delivery_orders')->count());
        $this->assertSame(0, DB::table('invoices')->count());
    }

    public function test_daily_sales_import_rejects_inconsistent_amount(): void
    {
        $this->seedCustomer();
        DB::table('products')->insert([
            'product_code' => 'P-001',
            'product_name' => 'Produk Uji',
            'base_price' => 1000,
            'uom' => 'BOX',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $this->makeXlsx([
            'LAP.HARIAN' => [
                [null], [null], [null], [null], [null], [null],
                [null, null, 'C-001', null, 'F-022', null, '2026-11-01', null, 'TUNAI', null, null, null, 'S1', 'P-001', 0, 1000, 2, 1900, 1900, 0, null, 0],
            ],
        ]);

        try {
            Excel::import(new DailySalesImport, $path);
            $this->fail('Import seharusnya menolak AMOUNT yang tidak konsisten.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('AMOUNT tidak konsisten', $e->errors()['file_master'][0]);
        }

        $this->assertSame(0, DB::table('delivery_orders')->count());
        $this->assertSame(0, DB::table('invoices')->count());
    }

    public function test_import_controller_returns_validation_errors_instead_of_server_error(): void
    {
        $user = $this->activeUser();

        $path = $this->makeXlsx([
            'LIST CUSTOMER' => [[null]],
            'LIST ITEM' => [
                [null],
                [null],
                ['PCS', 'SEG1', 'Produk Invalid', 'P-BAD'],
            ],
            'LAP.HARIAN' => [[null]],
            'LAP.STOK' => [[null]],
        ]);

        $file = new UploadedFile(
            $path,
            'master.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($user)->post(route('imports.master'), [
            'file_master' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file_master');
        $this->assertDatabaseMissing('products', ['product_code' => 'P-BAD']);
    }

    public function test_invoice_template_shows_strata_from_product_discounts(): void
    {
        [$user] = $this->seedSalesData();
        $productId = DB::table('products')->where('product_code', 'P-001')->value('id');

        $now = now();
        DB::table('product_discounts')->insert([
            'product_id' => $productId,
            'strata_level' => 'S2',
            'min_qty' => 6,
            'max_qty' => 14,
            'discount_percentage' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $invoiceId = DB::table('invoices')->where('invoice_number', 'INV-001')->value('id');
        DB::table('invoice_items')->where('invoice_id', $invoiceId)->update([
            'qty' => 10,
            'base_price' => 1000,
            'discount_percentage' => 2,
            'final_price' => 980,
            'line_total' => 9800,
        ]);

        $response = $this->actingAs($user)->get(route('invoices.export_excel', $invoiceId));
        $response->assertOk();

        $sheet = $this->reopen($response->streamedContent())->getActiveSheet();

        $this->assertSame('S2', $sheet->getCell('C15')->getValue());
        $this->assertSame(0.02, (float) $sheet->getCell('E15')->getValue());
        // PRICE harus harga bersih (final_price), bukan base_price.
        $this->assertSame(980.0, (float) $sheet->getCell('F15')->getValue());
        $this->assertSame(10, (int) $sheet->getCell('G15')->getValue());
        $this->assertSame(9800.0, (float) $sheet->getCell('H15')->getValue());
        $this->assertSame(
            (float) $sheet->getCell('H15')->getValue(),
            (float) $sheet->getCell('F15')->getValue() * (int) $sheet->getCell('G15')->getValue()
        );
    }

    public function test_stock_report_export_works_when_stock_data_exists(): void
    {
        [$user] = $this->seedSalesData();
        $productId = DB::table('products')->where('product_code', 'P-001')->value('id');

        DB::table('stock_balances')->insert([
            'product_id' => $productId,
            'qty_available' => 12.5,
            'qty_reserved' => 2.5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $binary = Excel::raw(new StockReportExport, ExcelType::XLSX);
        $sheet = $this->reopen($binary)->getActiveSheet();

        $this->assertSame('Kode Produk', $sheet->getCell('A1')->getValue());
        $this->assertSame('Qty Available', $sheet->getCell('D1')->getValue());
        $this->assertSame('Qty Bisa Dipakai', $sheet->getCell('F1')->getValue());
        $this->assertSame('P-001', $sheet->getCell('A2')->getValue());
        $this->assertSame(12.5, (float) $sheet->getCell('D2')->getValue());
        $this->assertSame(2.5, (float) $sheet->getCell('E2')->getValue());
        $this->assertSame(10.0, (float) $sheet->getCell('F2')->getValue());

        $this->actingAs($user)
            ->get(route('reports.stock.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
