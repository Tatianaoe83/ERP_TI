<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets_mantenimiento', function (Blueprint $table) {
            $table->unsignedBigInteger('ResponsableID')->nullable()->after('Estatus');
        });

        $empleadosCompras = DB::table('empleados')
            ->join('obras', 'empleados.ObraID', '=', 'obras.ObraID')
            ->where('obras.NombreObra', 'like', '%Compras%')
            ->where('empleados.Estado', 1)
            ->pluck('empleados.EmpleadoID', 'empleados.NombreEmpleado');

        $fallbackResponsable = $empleadosCompras->first();

        DB::table('tickets_mantenimiento')
            ->whereNull('EmpleadoID')
            ->whereNotNull('Correo')
            ->orderBy('MantenimientoID')
            ->each(function ($ticket) {
                $empleadoId = DB::table('empleados')
                    ->where('Correo', $ticket->Correo)
                    ->where('Estado', 1)
                    ->value('EmpleadoID');

                if ($empleadoId) {
                    DB::table('tickets_mantenimiento')
                        ->where('MantenimientoID', $ticket->MantenimientoID)
                        ->update(['EmpleadoID' => $empleadoId]);
                }
            });

        DB::table('tickets_mantenimiento')
            ->whereNotNull('Responsable')
            ->orderBy('MantenimientoID')
            ->each(function ($ticket) use ($empleadosCompras, $fallbackResponsable) {
                $responsableId = $empleadosCompras[$ticket->Responsable] ?? null;

                if (!$responsableId && $ticket->Responsable === 'LOA') {
                    $responsableId = $fallbackResponsable;
                }

                if (!$responsableId) {
                    $responsableId = DB::table('empleados')
                        ->where('NombreEmpleado', $ticket->Responsable)
                        ->value('EmpleadoID');
                }

                if ($responsableId) {
                    DB::table('tickets_mantenimiento')
                        ->where('MantenimientoID', $ticket->MantenimientoID)
                        ->update(['ResponsableID' => $responsableId]);
                }
            });

        Schema::table('tickets_mantenimiento', function (Blueprint $table) {
            $table->dropColumn(['Asunto', 'NombreSolicitante', 'Correo', 'AreaDepartamento', 'Responsable']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets_mantenimiento', function (Blueprint $table) {
            $table->string('NombreSolicitante')->nullable();
            $table->string('Correo')->nullable();
            $table->string('AreaDepartamento')->nullable();
            $table->string('Asunto')->nullable();
            $table->string('Responsable')->nullable();
        });

        DB::table('tickets_mantenimiento')
            ->orderBy('MantenimientoID')
            ->each(function ($ticket) {
                $empleado = DB::table('empleados')->where('EmpleadoID', $ticket->EmpleadoID)->first();
                $responsable = DB::table('empleados')->where('EmpleadoID', $ticket->ResponsableID)->first();

                DB::table('tickets_mantenimiento')
                    ->where('MantenimientoID', $ticket->MantenimientoID)
                    ->update([
                        'NombreSolicitante' => $empleado->NombreEmpleado ?? null,
                        'Correo'            => $empleado->Correo ?? null,
                        'Asunto'            => \Illuminate\Support\Str::limit($ticket->Descripcion ?? '', 80),
                        'Responsable'       => $responsable->NombreEmpleado ?? null,
                    ]);
            });

        Schema::table('tickets_mantenimiento', function (Blueprint $table) {
            $table->dropColumn('ResponsableID');
        });
    }
};
