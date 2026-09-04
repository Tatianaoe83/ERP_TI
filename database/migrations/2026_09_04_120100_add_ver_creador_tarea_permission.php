<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $ver = Permission::firstOrCreate(['name' => 'tickets.ver-creador-tarea', 'guard_name' => 'web']);

        // Solo SUPERADMIN: el resto de roles no debe ver quién creó la tarea/métrica.
        Role::where('name', 'SUPERADMIN')->get()->each(fn ($role) => $role->givePermissionTo($ver));
    }

    public function down(): void
    {
        Permission::where('name', 'tickets.ver-creador-tarea')->delete();
    }
};
