<?php

namespace Tests\Feature;

use App\Exports\PaymentsReportExport;
use App\Exports\ReceivablesReportExport;
use App\Exports\SalesReportExport;
use App\Imports\CustomersImport;
use App\Imports\DailySalesImport;
use App\Imports\StockImport;
use App\Imports\TadcoMasterImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $this->assertSame(2, (int) $sheet->getCell('G15')->getValue());
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
    // Known issues (pre-existing, bukan akibat upgrade PHP 8.5)
    // ---------------------------------------------------------------------

    public function test_known_issue_master_import_cannot_persist_product_sheet(): void
    {
        $this->markTestSkipped(
            'Schema drift: tabel product_prices tidak ada di database/migrations, sehingga ProductsImport '.
            '(dan TadcoMasterImport) gagal QueryException saat sheet LIST ITEM berisi data. '.
            'Butuh migration baru — di luar izin upgrade dependency.'
        );
    }

    public function test_known_issue_stock_report_export_fails_when_stock_data_exists(): void
    {
        $this->markTestSkipped(
            'Schema drift: StockReportExport memakai relasi "warehouse" yang tidak ada di model StockBalance '.
            '(kolom warehouse_id sudah di-drop 2026-10-27). Export gagal begitu ada baris stok.'
        );
    }
}
