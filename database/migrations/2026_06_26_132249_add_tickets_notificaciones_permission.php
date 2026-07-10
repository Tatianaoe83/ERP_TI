<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddTicketsNotificacionesPermission extends Migration
{
    public function up()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(['name' => 'tickets.notificaciones', 'guard_name' => 'web']);

        Role::all()->each(fn($role) => $role->givePermissionTo($permission));
    }

    public function down()
    {
        Permission::where('name', 'tickets.notificaciones')->delete();
    }
}
