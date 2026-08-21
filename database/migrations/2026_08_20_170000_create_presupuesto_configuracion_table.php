<?php

use App\Models\PresupuestoConfiguracion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('grupo', 64);
            $table->string('valor', 150);
            $table->timestamps();

            $table->index('grupo');
            $table->unique(['grupo', 'valor']);
        });

        foreach (PresupuestoConfiguracion::GRUPOS as $grupo => $meta) {
            foreach ($meta['defaults'] as $valor) {
                PresupuestoConfiguracion::firstOrCreate([
                    'grupo' => $grupo,
                    'valor' => $valor,
                ]);
            }
        }

        $permiso = Permission::firstOrCreate([
            'name' => 'editar-conf-presupuesto',
            'guard_name' => 'web',
        ]);

        $roles = Role::whereHas(
            'permissions',
            fn ($q) => $q->whereIn('name', ['ver-presupuesto', 'ver-presupuestos'])
        )->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($permiso);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_configuracion');

        Permission::where('name', 'editar-conf-presupuesto')->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
