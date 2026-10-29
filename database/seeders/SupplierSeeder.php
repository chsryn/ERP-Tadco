<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // Field region/port_depo/pic_name sengaja null: mapping visual dokumen
        // PO003 belum terkonfirmasi. Yang pasti dari dokumen hanya nama principal dan TOP.
        Supplier::updateOrCreate(
            ['code' => 'SUP-EPFM'],
            [
                'name' => 'EPFM',
                'term_of_payment_days' => 30,
                'is_active' => true,
            ]
        );
    }
}
