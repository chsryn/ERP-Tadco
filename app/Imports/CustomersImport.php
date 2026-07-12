<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class CustomersImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function headingRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        $customerCode = trim($row['item_code'] ?? '');
        $customerName = trim($row['nama_customer'] ?? '');

        if ($customerCode === '' || $customerName === '') {
            return null;
        }

        if (strtoupper($customerCode) === '#N/A') {
            return null;
        }

        return Customer::updateOrCreate(
            [
                'customer_code' => $customerCode,
            ],
            [
                'customer_name'    => $customerName,
                'province'         => trim($row['province'] ?? ''),
                'city'             => trim($row['city'] ?? ''),
                'district'         => trim($row['district'] ?? ''),
                'sub_district'     => trim($row['sub_district'] ?? ''),
                'address'          => trim($row['adress'] ?? ''),
                'type_of_business' => trim($row['type_of_bussiness'] ?? ''),
                'market'           => trim($row['market'] ?? ''),
                'customer_type'    => trim($row['type_of_customer'] ?? ''),
                'is_active'        => true,
            ]
        );
    }

    public function rules(): array
    {
        return [
            '*.item_code' => ['required'],
            '*.nama_customer' => ['required'],
        ];
    }
}
