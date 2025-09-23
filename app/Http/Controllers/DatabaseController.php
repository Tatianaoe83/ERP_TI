<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Helpers\SistemaHelper;



class DatabaseController extends Controller
{
    public function updateDatabase(Request $request)
    {
        try {
            $database = $request->input('database');
            \Log::debug('Database recibida:', ['database' => $database]);


            if (empty($database)) {
                return response()->json(['success' => false, 'error' => 'No se seleccionó una base de datos'], 400);
            }

            // Determinar sistema y establecerlo (Spatie y sesión)
            $sistema = match ($database) {
                'unidplay_presupuestoscontrol' => 'presupuesto',
                'unidplay_presupuestoscontrol2026' => 'presupuesto 2026',
                default => 'inventario'
            };

            SistemaHelper::establecerSistema($sistema);
            
            // Establecer la conexión correcta usando el helper
            $conexion = SistemaHelper::obtenerConexion();
            Config::set('database.default', $conexion);
            DB::purge($conexion);
            DB::reconnect($conexion);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar la base de datos:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}