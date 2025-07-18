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
use App\Http\Controllers\ReportesController;
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

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes(['register' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


//y creamos un grupo de rutas protegidas para los controladores
Route::group(['middleware' => ['auth', 'usarConexion']], function () {
    Route::resource('roles', RolController::class);
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('blogs', BlogController::class);


    Route::resource('unidadesDeNegocios', App\Http\Controllers\UnidadesDeNegocioController::class);
    Route::resource('gerencias', App\Http\Controllers\GerenciaController::class);
    Route::resource('obras', App\Http\Controllers\ObrasController::class);
    Route::resource('departamentos', App\Http\Controllers\DepartamentosController::class);
    Route::resource('puestos', App\Http\Controllers\PuestosController::class);
    Route::resource('empleados', App\Http\Controllers\EmpleadosController::class);
    Route::resource('lineasTelefonicas', App\Http\Controllers\LineasTelefonicasController::class);
    Route::resource('equipos', App\Http\Controllers\EquiposController::class);
    Route::resource('insumos', App\Http\Controllers\InsumosController::class);
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
});

Route::post('/update-database', [App\Http\Controllers\DatabaseController::class, 'updateDatabase'])
    ->name('update.database')
    ->withoutMiddleware(['auth']);
