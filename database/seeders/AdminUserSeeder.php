<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin@ealbertus.org'], [
            'name' => 'Admin Gereja',
            'password' => Hash::make('password'),
            'phone' => '081234567890',
            'department' => 'Administrasi Gereja',
            'position' => 'Admin Sistem',
            'nip' => 'ADM-001',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

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

        $jemaat = User::firstOrCreate(['email' => 'jemaat@ealbertus.org'], [
            'name' => 'Jemaat Albertus',
            'password' => Hash::make('password'),
            'phone' => '081234567892',
            'department' => 'Umum',
            'position' => 'Jemaat',
            'nip' => 'JMT-001',
            'is_active' => true,
        ]);
        $jemaat->assignRole('jemaat');
    }
}
