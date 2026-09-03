<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $ver = Permission::firstOrCreate(['name' => 'tickets.ver-tareas', 'guard_name' => 'web']);
        $gestionar = Permission::firstOrCreate(['name' => 'tickets.gestionar-tareas', 'guard_name' => 'web']);

        Role::all()->each(function ($role) use ($ver, $gestionar) {
            $role->givePermissionTo($ver);
            $role->givePermissionTo($gestionar);
        });
    }

    public function down(): void
    {
        Permission::whereIn('name', ['tickets.ver-tareas', 'tickets.gestionar-tareas'])->delete();
    }
};
