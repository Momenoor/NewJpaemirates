<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CalendarEventPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'ViewAny:CalendarEvent',
        'View:CalendarEvent',
        'Create:CalendarEvent',
        'Update:CalendarEvent',
        'Delete:CalendarEvent',
        'Restore:CalendarEvent',
        'ForceDelete:CalendarEvent',
        'CreateSingle:CalendarEvent',
        'CreateBulk:CalendarEvent',
        'ImportFromOutlook:CalendarEvent',
        'SyncToOutlook:CalendarEvent',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(self::PERMISSIONS);
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(self::PERMISSIONS);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
