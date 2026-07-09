<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permisos = ['ver-dashboard', 'ver-compras'];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $dashboardRoles = ['SUPERADMIN', 'ADMIN', 'DIRECTOR_VIEW'];
        $comprasRoles = ['DIRECTOR_VIEW'];

        foreach ($dashboardRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo('ver-dashboard');
            }
        }

        foreach ($comprasRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo('ver-compras');
            }
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', ['ver-dashboard', 'ver-compras'])->delete();
    }
};
