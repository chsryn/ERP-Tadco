<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class CustomersImport implements ToCollection, WithStartRow, SkipsEmptyRows
{
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $customerName = trim($row[1] ?? '');
            $customerCode = trim($row[2] ?? '');

            // Skip jika data kode customer kosong, nama kosong, atau membaca baris header
            if ($customerCode === '' || $customerName === '' || $customerName === 'NAMA CUSTOMER') {
                continue;
            }

            Customer::updateOrCreate(
                [
                    'customer_code' => $customerCode,
                ],
                [
                    'customer_name'    => $customerName,
                    'province'         => trim($row[3] ?? ''),
                    'city'             => trim($row[4] ?? ''),
                    'district'         => trim($row[5] ?? ''),
                    'sub_district'     => trim($row[6] ?? ''),
                    'address'          => trim($row[7] ?? ''),
                    'is_active'        => true,
                ]
            );
        }
    }
}
