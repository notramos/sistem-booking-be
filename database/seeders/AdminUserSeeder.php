<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $itAdmin = User::firstOrCreate(['email' => 'itadmin@ealbertus.org'], [
            'name' => 'IT Admin Gereja',
            'password' => Hash::make('password'),
            'phone' => '081234567889',
            'department' => 'Administrasi Gereja',
            'position' => 'IT Admin',
            'nip' => 'ITA-001',
            'is_active' => true,
        ]);
        $itAdmin->assignRole('it_admin');

        $p2 = User::firstOrCreate(['email' => 'p2@ealbertus.org'], [
            'name' => 'P2 Gereja',
            'password' => Hash::make('password'),
            'phone' => '081234567890',
            'department' => 'Administrasi Gereja',
            'position' => 'P2',
            'nip' => 'ADM-001',
            'is_active' => true,
        ]);
        $p2->assignRole('p2');

        $pastor = User::firstOrCreate(['email' => 'pastor@ealbertus.org'], [
            'name' => 'Pastor Albertus',
            'password' => Hash::make('password'),
            'phone' => '081234567893',
            'department' => 'Kepastoran',
            'position' => 'Pastor',
            'nip' => 'PST-001',
            'is_active' => true,
        ]);
        $pastor->assignRole('pastor');

        $sekretariat = User::firstOrCreate(['email' => 'sekretariat@ealbertus.org'], [
            'name' => 'Sekretariat Gereja',
            'password' => Hash::make('password'),
            'phone' => '081234567891',
            'department' => 'Sekretariat',
            'position' => 'Staff Sekretariat',
            'nip' => 'SKR-001',
            'is_active' => true,
        ]);
        $sekretariat->assignRole('sekretariat');

        $umat = User::firstOrCreate(['email' => 'umat@ealbertus.org'], [
            'name' => 'Umat Albertus',
            'password' => Hash::make('password'),
            'phone' => '081234567892',
            'department' => 'Umum',
            'position' => 'Umat',
            'nip' => 'UMT-001',
            'is_active' => true,
        ]);
        $umat->assignRole('umat');
    }
}
