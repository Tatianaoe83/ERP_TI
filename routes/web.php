<?php

use Illuminate\Support\Facades\Route;
//agregamos los siguientes controladores
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\CortesController;
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\SoporteTIController;
use App\Http\Controllers\TicketsController;
use App\Http\Livewire\ReportesLista;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});

Auth::routes(['register' => false]);


//y creamos un grupo de rutas protegidas para los controladores
Route::group(['middleware' => ['auth', 'usarConexion']], function () {
    // Dashboard principal
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('dashboard');
    Route::get('/insumos-licencia-pagination', [App\Http\Controllers\HomeController::class, 'insumosLicenciaPagination'])->name('insumos.licencia.pagination');
    
    Route::resource('roles', RolController::class);
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('blogs', BlogController::class);


    Route::resource('unidadesDeNegocios', App\Http\Controllers\UnidadesDeNegocioController::class);
    Route::resource('gerencias', App\Http\Controllers\GerenciaController::class);
    Route::resource('obras', App\Http\Controllers\ObrasController::class);
    Route::resource('departamentos', App\Http\Controllers\DepartamentosController::class);
    Route::resource('puestos', App\Http\Controllers\PuestosController::class);
    Route::resource('empleados', App\Http\Controllers\EmpleadosController::class);
    Route::get('empleados-filtros', [App\Http\Controllers\EmpleadosController::class, 'filtros'])->name('empleados.filtros');
    Route::resource('lineasTelefonicas', App\Http\Controllers\LineasTelefonicasController::class);
    Route::get('lineas-telefonicas-inventario-records', [App\Http\Controllers\LineasTelefonicasController::class, 'getInventarioRecords'])->name('lineas-telefonicas.inventario-records');
    Route::resource('equipos', App\Http\Controllers\EquiposController::class);
    //Route::get('equipos-stats', [App\Http\Controllers\EquiposController::class, 'getStats'])->name('equipos.stats');
    Route::get('equipos-inventario-records', [App\Http\Controllers\EquiposController::class, 'getInventarioRecords'])->name('equipos.inventario-records');
    Route::resource('insumos', App\Http\Controllers\InsumosController::class);
    Route::get('insumos-inventario-records', [App\Http\Controllers\InsumosController::class, 'getInventarioRecords'])->name('insumos.inventario-records');
    Route::resource('categorias', App\Http\Controllers\CategoriasController::class);
    Route::resource('planes', App\Http\Controllers\PlanesController::class);
    Route::GET('InventarioVista', [App\Http\Controllers\InventarioController::class, 'indexVista'])->name('inventarios.indexVista');
    Route::GET('inventarios/{id}/inventario', [App\Http\Controllers\InventarioController::class, 'inventario'])->name('inventarios.inventario');
    Route::PUT('inventarios/editar-equipo/{id}', [InventarioController::class, 'editarequipo'])->name('inventarios.editarequipo');
    Route::POST('inventarios/crear-equipo/{id}', [InventarioController::class, 'crearequipo'])->name('inventarios.crearequipo');

    Route::DELETE('inventarios/deleteInsumo/{inventario}', [InventarioController::class, 'destroyInsumo'])->name('inventarios.destroyInsumo');
    Route::PUT('inventarios/editar-insumo/{id}', [InventarioController::class, 'editarinsumo'])->name('inventarios.editarinsumo');
    Route::POST('inventarios/crear-insumo/{id}', [InventarioController::class, 'crearinsumo'])->name('inventarios.crearinsumo');

    Route::DELETE('inventarios/deleteL/{inventario}', [InventarioController::class, 'destroylinea'])->name('inventarios.destroylinea');
    Route::PUT('inventarios/editar-linea/{id}', [InventarioController::class, 'editarlinea'])->name('inventarios.editarlinea');
    Route::POST('inventarios/crear-linea/{id}/{telf}', [InventarioController::class, 'crearlinea'])->name('inventarios.crearlinea');

    Route::GET('inventarios/{inventario}/transferir', [InventarioController::class, 'transferir'])->name('inventarios.transferir');
    Route::PUT('inventarios/{inventario}/traspaso', [InventarioController::class, 'formTraspaso'])->name('inventarios.transpaso');
    Route::GET('inventarios/{inventario}/cartas', [InventarioController::class, 'cartas'])->name('inventarios.cartas');
    Route::POST('pdffile/{id}', [InventarioController::class, 'pdffile'])->name('inventarios.pdffile');
    Route::POST('mantenimiento/{id}', [InventarioController::class, 'mantenimiento'])->name('inventarios.mantenimiento');

    Route::resource('inventarios', App\Http\Controllers\InventarioController::class);
    Route::post('presupuesto/descargar', [PresupuestoController::class, 'descargar'])->name('presupuesto.descargar');
    Route::resource('presupuesto', App\Http\Controllers\PresupuestoController::class);
    Route::get('/informe/data', [AuditController::class, 'getAudits'])->name('audits.data');
    Route::get('/informe', [AuditController::class, 'index'])->name('audits.index');

    Route::resource('reportes', \App\Http\Controllers\ReportesController::class);
    Route::post('reportes/{id}/export-pdf', [ReportesController::class, 'exportPdf'])->name('reportes.exportPdf');
    Route::post('reportes/{id}/export-excel', [ReportesController::class, 'exportExcel'])->name('reportes.exportExcel');
    Route::post('/reportes/preview', [ReportesController::class, 'preview'])->name('reportes.preview');
    Route::get('autocomplete', [ReportesController::class, 'autocomplete']);

    // Rutas para reportes específicos
    Route::prefix('reportes-especificos')->name('reportes-especificos.')->group(function () {
        Route::get('/', [App\Http\Controllers\ReportesEspecificosController::class, 'index'])->name('index');
        Route::get('/estatus-licencias', [App\Http\Controllers\ReportesEspecificosController::class, 'estatusLicencias'])->name('estatus-licencias');
        Route::get('/equipos-asignados', [App\Http\Controllers\ReportesEspecificosController::class, 'equiposAsignados'])->name('equipos-asignados');
        Route::get('/lineas-asignadas', [App\Http\Controllers\ReportesEspecificosController::class, 'lineasAsignadas'])->name('lineas-asignadas');
        Route::get('/export-estatus-licencias', [App\Http\Controllers\ReportesEspecificosController::class, 'exportEstatusLicencias'])->name('export-estatus-licencias');
        Route::get('/export-estatus-licencias-excel', [App\Http\Controllers\ReportesEspecificosController::class, 'exportEstatusLicenciasExcel'])->name('export-estatus-licencias-excel');
        Route::get('/export-equipos-asignados', [App\Http\Controllers\ReportesEspecificosController::class, 'exportEquiposAsignados'])->name('export-equipos-asignados');
        Route::get('/export-equipos-asignados-excel', [App\Http\Controllers\ReportesEspecificosController::class, 'exportEquiposAsignadosExcel'])->name('export-equipos-asignados-excel');
        Route::get('/export-lineas-asignadas', [App\Http\Controllers\ReportesEspecificosController::class, 'exportLineasAsignadas'])->name('export-lineas-asignadas');
        Route::get('/export-lineas-asignadas-excel', [App\Http\Controllers\ReportesEspecificosController::class, 'exportLineasAsignadasExcel'])->name('export-lineas-asignadas-excel');
    });

    Route::resource('facturas', App\Http\Controllers\FacturasController::class);
    Route::get('verFacturas', [FacturasController::class, 'indexVista'])->name('facturas.ver');

    Route::resource('cortes', App\Http\Controllers\CortesController::class);
    Route::get('/verInsumos', [CortesController::class, 'obtenerInsumos'])->name('cortes.ver');
    Route::get('indexVista', [App\Http\Controllers\CortesController::class, 'indexVista'])->name('cortes.indexVista');
    Route::post('/cortes/saveXML', [CortesController::class, 'saveXML'])->name('cortes.saveXML');
    Route::post('/cortes/readXML', [CortesController::class, 'readXML'])->name('cortes.readXML');

    Route::get('/tickets', [App\Http\Controllers\TicketsController::class, 'index']);
    Route::post('/tickets/update', [App\Http\Controllers\TicketsController::class, 'update']);
});

Route::post('/update-database', [App\Http\Controllers\DatabaseController::class, 'updateDatabase'])
    ->name('update.database')
    ->withoutMiddleware(['auth']);


//Rutas para soporte de ticket y solicitudes    
Route::get('/SoporteTI', [App\Http\Controllers\SoporteTIController::class, 'index']);
Route::get('/autocompleteEmpleado', [SoporteTIController::class, 'autocompleteEmpleado']);
Route::get('/getEmpleadoInfo', [SoporteTIController::class, 'getEmpleadoInfo']);
Route::get('/buscarEmpleadoPorCorreo', [SoporteTIController::class, 'buscarEmpleadoPorCorreo']);
Route::POST('/crearTickets', [SoporteTIController::class, 'crearTickets'])
    ->name('soporte.ticket')
    ->withoutMiddleware(['auth']);
Route::get('/getTypes', [SoporteTIController::class, 'getTypes']);


// Ruta de fallback para redirigir al dashboard
Route::fallback(function () {
    if (auth()->check()) {
        return redirect()->route('home')->with('warning', 'La página solicitada no existe. Has sido redirigido al dashboard.');
    }
    return redirect('/login')->with('error', 'Debes iniciar sesión para acceder al sistema.');
});