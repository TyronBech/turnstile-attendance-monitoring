<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Sync Permissions
        $enumPermissions = array_map(fn ($p) => $p->value, PermissionEnum::cases());
        
        if (empty($enumPermissions)) {
            Permission::query()->delete();
        } else {
            Permission::whereNotIn('name', $enumPermissions)->delete();
        }

        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate(['name' => $permission->value]);
        }

        // Sync Roles
        $enumRoles = array_map(fn ($r) => $r->value, RoleEnum::cases());
        
        if (empty($enumRoles)) {
            Role::query()->delete();
        } else {
            Role::whereNotIn('name', $enumRoles)->delete();
        }

        $roles = [];
        foreach (RoleEnum::cases() as $role) {
            $roles[$role->value] = Role::firstOrCreate(['name' => $role->value]);
        }

        // Assign permissions to roles
        // Super Admin gets all permissions
        $roles[RoleEnum::SuperAdmin->value]->syncPermissions(Permission::all());

        // Admin
        $roles[RoleEnum::Admin->value]->syncPermissions(collect([
            PermissionEnum::ViewDashboard,
            PermissionEnum::ViewMonitoring,
            PermissionEnum::ViewUsers,
            PermissionEnum::CreateUser,
            PermissionEnum::EditUser,
            PermissionEnum::ImportUsers,
            PermissionEnum::ViewReports,
            PermissionEnum::ExportReports,
        ])->map(fn ($p) => $p->value)->toArray());

        // Security Guard
        $roles[RoleEnum::SecurityGuard->value]->syncPermissions(collect([
            PermissionEnum::ViewMonitoring,
        ])->map(fn ($p) => $p->value)->toArray());
    }
}
