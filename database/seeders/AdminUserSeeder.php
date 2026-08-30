<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan role admin sudah ada (biasanya di-handle di RoleSeeder)
        $role = Role::firstOrCreate(['name' => 'admin']);

        // Buat user admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@tadco.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                // Jika ada field is_active dari migration terakhir Anda:
                'is_active' => true,
            ]
        );

        // Assign role ke user admin
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($role);
        }
    }
}
