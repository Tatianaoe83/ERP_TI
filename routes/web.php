<?php

use Illuminate\Support\Facades\Route;
//agregamos los siguientes controladores
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\CortesController;
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\SolicitudAprobacionController;
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
Route::group(['middleware' => ['auth']], function () {
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

    Route::get('/tickets', [App\Http\Controllers\TicketsController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/productividad-ajax', [App\Http\Controllers\TicketsController::class, 'obtenerProductividadAjax'])->name('tickets.productividad-ajax');
    // Rutas específicas deben ir ANTES de las rutas con parámetros dinámicos
    Route::get('/tickets/chat-messages', [App\Http\Controllers\TicketsController::class, 'getChatMessages']);
    Route::get('/tickets/verificar-mensajes-nuevos', [App\Http\Controllers\TicketsController::class, 'verificarMensajesNuevos']);
    Route::get('/tickets/estadisticas-correos', [App\Http\Controllers\TicketsController::class, 'obtenerEstadisticasCorreos']);
    Route::get('/tickets/diagnosticar-correos', [App\Http\Controllers\TicketsController::class, 'diagnosticarCorreos']);
    Route::get('/tickets/tiempo-progreso', [App\Http\Controllers\TicketsController::class, 'obtenerTiempoProgreso']);
    Route::get('/tickets/tipos-con-metricas', [App\Http\Controllers\TicketsController::class, 'getTiposConMetricas']);
    Route::get('/tickets/excedidos', [App\Http\Controllers\TicketsController::class, 'obtenerTicketsExcedidos'])->name('tickets.excedidos');
    Route::post('/tickets/update', [App\Http\Controllers\TicketsController::class, 'update']);
    Route::post('/tickets/enviar-respuesta', [App\Http\Controllers\TicketsController::class, 'enviarRespuesta']);
    Route::post('/tickets/mensaje-interno', [App\Http\Controllers\TicketsController::class, 'agregarMensajeInterno']);
    Route::post('/tickets/marcar-leidos', [App\Http\Controllers\TicketsController::class, 'marcarMensajesComoLeidos']);
    Route::post('/tickets/sincronizar-correos', [App\Http\Controllers\TicketsController::class, 'sincronizarCorreos']);
    Route::post('/tickets/agregar-respuesta-manual', [App\Http\Controllers\TicketsController::class, 'agregarRespuestaManual']);
    Route::post('/tickets/enviar-instrucciones', [App\Http\Controllers\TicketsController::class, 'enviarInstruccionesRespuesta']);
    Route::post('/tickets/actualizar-tiempo-estimado', [App\Http\Controllers\TicketsController::class, 'actualizarTiempoEstimado']);
    Route::post('/tickets/actualizar-metricas-masivo', [App\Http\Controllers\TicketsController::class, 'actualizarMetricasMasivo']);
    Route::get('/tickets/reporte-mensual', [App\Http\Controllers\TicketsController::class, 'reporteMensual'])->name('tickets.reporte-mensual');
    Route::get('/tickets/exportar-reporte-mensual-excel', [App\Http\Controllers\TicketsController::class, 'exportarReporteMensualExcel'])->name('tickets.exportar-reporte-mensual-excel');
    Route::post('/tickets/subir-imagen-tinymce', [App\Http\Controllers\TicketsController::class, 'subirImagenTinyMCE']);
    // Ruta con parámetro dinámico debe ir AL FINAL
    Route::get('/tickets/{id}', [App\Http\Controllers\TicketsController::class, 'show']);

    // Rutas para procesamiento automático de correos
    Route::post('/api/webhook/email-response', [App\Http\Controllers\EmailWebhookController::class, 'handleEmailResponse']);
    Route::post('/api/process-manual-response', [App\Http\Controllers\EmailWebhookController::class, 'processManualResponse']);

    // Rutas para Webklex IMAP
    Route::post('/api/test-webklex-connection', [App\Http\Controllers\WebklexApiController::class, 'testConnection']);
    Route::post('/api/process-webklex-responses', [App\Http\Controllers\WebklexApiController::class, 'processResponses']);
    Route::get('/api/webklex-mailbox-info', [App\Http\Controllers\WebklexApiController::class, 'getMailboxInfo']);

    // Rutas de correo (SMTP/IMAP)
    Route::get('/email/verificar-configuracion', [App\Http\Controllers\EmailController::class, 'verificarConfiguracion']);
    Route::post('/email/procesar-correos', [App\Http\Controllers\EmailController::class, 'procesarCorreos']);
    Route::post('/email/enviar-prueba', [App\Http\Controllers\EmailController::class, 'enviarCorreoPrueba']);
    Route::get('/email/estadisticas', [App\Http\Controllers\EmailController::class, 'obtenerEstadisticas']);

    // Rutas de autenticación de Outlook (mantener para compatibilidad)
    Route::get('/auth/outlook', [App\Http\Controllers\OutlookAuthController::class, 'redirect']);
    Route::get('/auth/outlook/callback', [App\Http\Controllers\OutlookAuthController::class, 'callback']);
    Route::get('/auth/outlook/status', [App\Http\Controllers\OutlookAuthController::class, 'status']);
});

//Rutas para soporte de ticket y solicitudes    
Route::get('/SoporteTI', [App\Http\Controllers\SoporteTIController::class, 'index']);
Route::get('/autocompleteEmpleado', [SoporteTIController::class, 'autocompleteEmpleado']);
Route::get('/getEmpleadoInfo', [SoporteTIController::class, 'getEmpleadoInfo']);
Route::get('/buscarEmpleadoPorCorreo', [SoporteTIController::class, 'buscarEmpleadoPorCorreo']);
Route::POST('/crearTickets', [SoporteTIController::class, 'crearTickets'])
    ->name('soporte.ticket')
    ->withoutMiddleware(['auth']);
Route::get('/getTypes', [SoporteTIController::class, 'getTypes']);

// Rutas de aprobación de solicitudes
Route::get('/revision-solicitud/{token}', [App\Http\Controllers\SolicitudAprobacionController::class, 'show'])->name('solicitudes.public.show');
Route::post('/revision-solicitud/{token}/decide', [SolicitudAprobacionController::class, 'decide'])->name('solicitudes.public.decide');
Route::post('/revision-solicitud/{token}/transferir', [SolicitudAprobacionController::class, 'transferir'])->name('solicitudes.public.transferir');
Route::get('/solicitudes/empleados-transferir', [SolicitudAprobacionController::class, 'obtenerEmpleadosParaTransferir'])->name('solicitudes.empleados-transferir');
Route::get('/solicitudes/{id}/datos', [App\Http\Controllers\TicketsController::class, 'obtenerDatosSolicitud'])->name('solicitudes.datos');
Route::get('/solicitudes/{id}/cotizar', [App\Http\Controllers\TicketsController::class, 'mostrarPaginaCotizacion'])->name('solicitudes.cotizar')->middleware('auth');
Route::get('/solicitudes/{id}/cotizaciones', [App\Http\Controllers\TicketsController::class, 'obtenerCotizaciones'])->name('solicitudes.cotizaciones');
Route::post('/solicitudes/{id}/guardar-cotizaciones', [App\Http\Controllers\TicketsController::class, 'guardarCotizaciones'])->name('solicitudes.guardar-cotizaciones');
Route::post('/solicitudes/{id}/enviar-cotizaciones-gerente', [App\Http\Controllers\TicketsController::class, 'enviarCotizacionesAlGerente'])->name('solicitudes.enviar-cotizaciones-gerente');
Route::get('/elegir-ganador/{token}', [App\Http\Controllers\TicketsController::class, 'elegirGanadorConToken'])->name('solicitudes.elegir-ganador-token');
Route::post('/solicitudes/{id}/seleccionar-cotizacion', [App\Http\Controllers\TicketsController::class, 'seleccionarCotizacion'])->name('solicitudes.seleccionar-cotizacion');
Route::post('/solicitudes/{id}/confirmar-ganadores', [App\Http\Controllers\TicketsController::class, 'confirmarGanadores'])->name('solicitudes.confirmar-ganadores');
Route::post('/solicitudes/{id}/aprobar-{nivel}', [App\Http\Controllers\SolicitudAprobacionController::class, 'aprobarPorNivel'])->name('solicitudes.aprobar-nivel');
Route::post('/solicitudes/{id}/rechazar-{nivel}', [App\Http\Controllers\SolicitudAprobacionController::class, 'rechazarPorNivel'])->name('solicitudes.rechazar-nivel');
/* Route::post('/solicitudes/{id}/rechazar-supervisor', [App\Http\Controllers\SolicitudAprobacionController::class, 'rechazarSupervisor'])->name('solicitudes.rechazar-supervisor');
    Route::post('/solicitudes/{id}/aprobar-gerencia', [App\Http\Controllers\SolicitudAprobacionController::class, 'aprobarGerencia'])->name('solicitudes.aprobar-gerencia');
    Route::post('/solicitudes/{id}/rechazar-gerencia', [App\Http\Controllers\SolicitudAprobacionController::class, 'rechazarGerencia'])->name('solicitudes.rechazar-gerencia');
    Route::post('/solicitudes/{id}/aprobar-administracion', [App\Http\Controllers\SolicitudAprobacionController::class, 'aprobarAdministracion'])->name('solicitudes.aprobar-administracion');
    Route::post('/solicitudes/{id}/rechazar-administracion', [App\Http\Controllers\SolicitudAprobacionController::class, 'rechazarAdministracion'])->name('solicitudes.rechazar-administracion'); */


// Ruta de fallback para redirigir al dashboard
Route::fallback(function () {
    if (auth()->check()) {
        return redirect()->route('home')->with('warning', 'La página solicitada no existe. Has sido redirigido al dashboard.');
    }
    return redirect('/login')->with('error', 'Debes iniciar sesión para acceder al sistema.');
});
