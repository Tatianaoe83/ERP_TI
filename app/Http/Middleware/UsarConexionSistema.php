<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Helpers\SistemaHelper;

class UsarConexionSistema
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $sistema = SistemaHelper::obtenerSistema(); // usa sesión o cookie

        if ($sistema === 'presupuesto') {
            Config::set('database.default', 'mysql_presupuesto');
        } else {
            Config::set('database.default', 'mysql_inventario');
        }
    
        DB::purge(Config::get('database.default'));
        DB::reconnect(Config::get('database.default'));
    
        return $next($request);
    }
}
?>