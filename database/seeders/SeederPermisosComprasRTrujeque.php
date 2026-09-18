<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SeederPermisosComprasRTrujeque extends Seeder
{
    public function run()
    {
        $usuario = User::find(57);

        if (!$usuario || strtoupper($usuario->username) !== 'RTRUJEQUE') {
            $this->command->error('El usuario 57 no es RTRUJEQUE, no se asignó nada.');
            return;
        }

        $permisos = [
            'ver-compras',
            'ver-mantenimientos-compras',
            'tickets.notificaciones',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $usuario->givePermissionTo($permisos);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de compras asignados a RTRUJEQUE: ' . implode(', ', $permisos));
    }
}
