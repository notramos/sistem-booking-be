<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Room permissions
            'rooms.view', 'rooms.create', 'rooms.update', 'rooms.delete',
            // Booking permissions
            'bookings.create', 'bookings.cancel', 'bookings.view-all',
            'bookings.approve', 'bookings.reject',
            // User permissions
            'users.view', 'users.create', 'users.update', 'users.delete',
            // Report permissions
            'reports.view', 'reports.export',
            // Setting permissions
            'settings.view', 'settings.update',
            // Audit permissions
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // IT Admin — full access, satu-satunya role yang bisa kelola user internal
        // (P2/Pastor/Sekretariat/IT Admin lain) — pembeda utamanya dari P2/Pastor.
        $itAdmin = Role::firstOrCreate(['name' => 'it_admin', 'guard_name' => 'web']);
        $itAdmin->givePermissionTo(Permission::all());

        // P2 & Pastor — setara: wewenang penuh di tahap approval final & manajemen
        // ruangan/laporan, TAPI tidak bisa kelola user internal (khusus IT Admin).
        $nonUserPermissions = array_values(array_filter(
            $permissions,
            fn ($p) => ! str_starts_with($p, 'users.')
        ));

        $p2 = Role::firstOrCreate(['name' => 'p2', 'guard_name' => 'web']);
        $p2->givePermissionTo($nonUserPermissions);

        $pastor = Role::firstOrCreate(['name' => 'pastor', 'guard_name' => 'web']);
        $pastor->givePermissionTo($nonUserPermissions);

        // Sekretariat — manage bookings, approvals, reports
        $sekretariat = Role::firstOrCreate(['name' => 'sekretariat', 'guard_name' => 'web']);
        $sekretariat->givePermissionTo([
            'rooms.view', 'bookings.create', 'bookings.cancel', 'bookings.view-all',
            'bookings.approve', 'bookings.reject', 'reports.view', 'reports.export',
        ]);

        // Umat — basic booking
        $umat = Role::firstOrCreate(['name' => 'umat', 'guard_name' => 'web']);
        $umat->givePermissionTo([
            'rooms.view', 'bookings.create', 'bookings.cancel',
        ]);
    }
}
