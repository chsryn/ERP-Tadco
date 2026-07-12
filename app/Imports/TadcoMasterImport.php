<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TadcoMasterImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'LIST CUSTOMER' => new CustomersImport(),
            'LIST ITEM' => new ProductsImport(),
        ];
    }
}
