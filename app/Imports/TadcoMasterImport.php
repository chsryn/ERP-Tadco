<?php
namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TadcoMasterImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            // Kunci array (String) merujuk pada nama Sheet di Excel
            'LIST CUSTOMER' => new CustomersImport(),
            'LIST ITEM' => new ProductsImport(), // Anda perlu membuat class ini mirip seperti customer di atas
            'LAP.HARIAN' => new DailySalesImport(), // Untuk data transaksi
            'LAP.STOK'      => new StockImport(),
        ];
    }
}
