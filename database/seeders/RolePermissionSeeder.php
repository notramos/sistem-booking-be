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

        // Admin — full access
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all());

        // Sekretariat — manage bookings, approvals, reports
        $sekretariat = Role::firstOrCreate(['name' => 'sekretariat', 'guard_name' => 'web']);
        $sekretariat->givePermissionTo([
            'rooms.view', 'bookings.create', 'bookings.cancel', 'bookings.view-all',
            'bookings.approve', 'bookings.reject', 'reports.view', 'reports.export',
        ]);

        // Jemaat — basic booking
        $jemaat = Role::firstOrCreate(['name' => 'jemaat', 'guard_name' => 'web']);
        $jemaat->givePermissionTo([
            'rooms.view', 'bookings.create', 'bookings.cancel',
        ]);
    }
}
