<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'departments.view',
            'departments.create',
            'departments.update',
            'departments.delete',

            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',

            'messages.view',
            'messages.create',
            'messages.delete',

            'attachments.view',
            'attachments.create',
            'attachments.delete',

            'reports.view',
            'reports.create',
            'reports.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $executor = Role::firstOrCreate(['name' => 'executor', 'guard_name' => 'web']);
        $analyst = Role::firstOrCreate(['name' => 'analyst', 'guard_name' => 'web']);

        $admin->syncPermissions($permissions);

        $manager->syncPermissions([
            'departments.view',
            'users.view',
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'messages.view',
            'messages.create',
            'attachments.view',
            'attachments.create',
            'reports.view',
            'reports.create',
        ]);

        $executor->syncPermissions([
            'tasks.view',
            'tasks.update',
            'messages.view',
            'messages.create',
            'attachments.view',
            'attachments.create',
        ]);

        $analyst->syncPermissions([
            'tasks.view',
            'reports.view',
            'reports.create',
            'reports.delete',
            'users.view',
            'departments.view',
        ]);
    }
}