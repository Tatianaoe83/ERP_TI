<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Separa el tablero de Mantenimientos de compras del dashboard de compras:
     * hasta ahora 'ver-compras' daba acceso a los dos.
     */
    public function up(): void
    {
        $permiso = Permission::firstOrCreate([
            'name' => 'ver-mantenimientos-compras',
            'guard_name' => 'web',
        ]);

        // Hereda a quien ya entraba por 'ver-compras' para que nadie pierda el acceso.
        $rolesConCompras = Role::whereHas(
            'permissions',
            fn ($q) => $q->where('name', 'ver-compras')
        )->get();

        foreach ($rolesConCompras as $role) {
            $role->givePermissionTo($permiso);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'ver-mantenimientos-compras')->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
