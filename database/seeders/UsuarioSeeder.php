<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run()
    {
        // En mayúsculas: AuthServiceProvider da todos los permisos a 'TORDONEZ' con comparación estricta
        $usuario = User::firstOrCreate(
            ['username' => 'TORDONEZ'],
            ['name' => 'TORDONEZ', 'password' => Hash::make('12345678')]
        );

        $usuario->assignRole('SUPERADMIN');
    }
}
