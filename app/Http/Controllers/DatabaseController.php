<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DatabaseController extends Controller
{
    public function updateDatabase(Request $request)
    {
        
        
        try {
            $database = $request->input('database');
            /* \Log::info('Base de datos seleccionada:', ['database' => $database]); */
            
            if (empty($database)) {
                return response()->json(['success' => false, 'error' => 'No se seleccionó una base de datos'], 400);
            }

            // Actualizar el archivo .env
            $envContent = file_get_contents(base_path('.env'));
            $envContent = preg_replace('/DB_DATABASE=.*/', 'DB_DATABASE=' . $database, $envContent);
            file_put_contents(base_path('.env'), $envContent);
            
            // Limpiar la caché de configuración
            Artisan::call('config:clear');
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar la base de datos:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
} 