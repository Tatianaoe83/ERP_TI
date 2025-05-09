<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\SistemaHelper;

class DatabaseController extends Controller
{
    public function updateDatabase(Request $request)
    {
        try {
            $database = $request->input('database');

            if (empty($database)) {
                return response()->json(['success' => false, 'error' => 'No se seleccionó una base de datos'], 400);
            }

         
            $sistema = match ($database) {
                'unidplay_presupuestoscontrol' => 'presupuesto',
                'unidplay_controlinventarioti' => 'inventario',
                default => 'inventario'
            };

           
            Config::set('database.connections.mysql.database', $database);
            DB::purge('mysql');
            DB::reconnect('mysql');

           
            SistemaHelper::establecerSistema($sistema);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar la base de datos:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
} 