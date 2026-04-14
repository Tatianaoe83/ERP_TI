<?php

use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\SolicitudesController;
use App\Http\Controllers\SoporteTIController;
use App\Http\Controllers\TicketsController;
use App\Http\Livewire\ReportesLista;

Route::get('/', function () {
    return redirect('/login');
});

Auth::routes(['register' => false]);

Route::group(['middleware' => ['auth']], function () {
    // Dashboard principal
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/insumos-licencia-pagination', [HomeController::class, 'insumosLicenciaPagination'])->name('insumos.licencia.pagination');

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
    Route::get('equipos-inventario-records', [App\Http\Controllers\EquiposController::class, 'getInventarioRecords'])->name('equipos.inventario-records');
    Route::resource('insumos', App\Http\Controllers\InsumosController::class);
    Route::get('insumos-inventario-records', [App\Http\Controllers\InsumosController::class, 'getInventarioRecords'])->name('insumos.inventario-records');
    Route::resource('categorias', App\Http\Controllers\CategoriasController::class);
    Route::resource('planes', App\Http\Controllers\PlanesController::class);
    Route::GET('InventarioVista', [InventarioController::class, 'indexVista'])->name('inventarios.indexVista');
    Route::GET('inventarios/{id}/inventario', [InventarioController::class, 'inventario'])->name('inventarios.inventario');
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

    Route::get('reportes/{id}/data', [ReportesController::class, 'showData'])->name('reportes.data');
    Route::resource('reportes', ReportesController::class);
    Route::get('reportes/{id}/export-pdf', [ReportesController::class, 'exportPdf'])->name('reportes.exportPdf');
    Route::post('reportes/{id}/export-excel', [ReportesController::class, 'exportExcel'])->name('reportes.exportExcel');
    Route::post('/reportes/preview', [ReportesController::class, 'preview'])->name('reportes.preview');
    Route::get('autocomplete', [ReportesController::class, 'autocomplete']);
    Route::get('reportes/{id}/export-pdf-async', [ReportesController::class, 'iniciarExportPdf'])->name('reportes.iniciarExportPdf');

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
    Route::post('/facturas/parsear-xml', [App\Http\Controllers\FacturasController::class, 'parsearXml'])
    ->name('facturas.parsearXml');

    Route::post('/facturas/previsualizar-pdf', [App\Http\Controllers\FacturasController::class, 'previsualizarPdf'])
    ->name('facturas.previsualizarPdf');

    Route::post('/facturas/{id}/reemplazar-archivo', [App\Http\Controllers\FacturasController::class, 'reemplazarArchivo'])
    ->name('facturas.reemplazar');
    Route::get('facturas/comparativa', [FacturasController::class, 'comparativa'])->name('facturas.comparativa');
    Route::get('facturas/historial', [FacturasController::class, 'historial'])->name('facturas.historial');
    Route::get('verFacturas', [FacturasController::class, 'indexVista'])->name('facturas.ver');
    Route::get('facturas/insumos-por-gerencia', [FacturasController::class, 'getInsumosPorGerencia'])->name('facturas.getInsumosPorGerencia');
    Route::patch('facturas/{id}/insumo', [FacturasController::class, 'actualizarInsumo'])->name('facturas.actualizarInsumo');
    Route::patch('facturas/{id}/mes', [FacturasController::class, 'actualizarMes'])->name('facturas.actualizarMes');
    Route::get('facturas/{id}/datos', [FacturasController::class, 'obtenerDatos'])->name('facturas.obtenerDatos');
    Route::patch('facturas/{id}/actualizar-datos', [FacturasController::class, 'actualizarDatos'])->name('facturas.actualizarDatos');
    Route::post('facturas/{id}/actualizar-completo', [FacturasController::class, 'actualizarCompleto'])->name('facturas.actualizarCompleto');
    Route::patch('facturas/{id}/registrar-cambio', [FacturasController::class, 'ultimoCambioPorUsuario'])->name('facturas.registrarCambio');
    Route::post('facturas/directa', [FacturasController::class, 'storeDirecta'])->name('facturas.storeDirecta')->middleware('permission:facturas.create');
    Route::resource('facturas', FacturasController::class);

    Route::post('cortes/store-all', [CortesController::class, 'storeAll'])->name('cortes.storeAll');
    Route::get('/cortes/guardados', [CortesController::class, 'obtenerCorteGuardado'])->name('cortes.guardados');
    Route::get('/verInsumos', [CortesController::class, 'obtenerInsumos'])->name('cortes.ver');
    Route::get('indexVista', [CortesController::class, 'indexVista'])->name('cortes.indexVista');
    Route::post('/cortes/saveXML', [CortesController::class, 'saveXML'])->name('cortes.saveXML');
    Route::post('/cortes/readXML', [CortesController::class, 'readXML'])->name('cortes.readXML');
    Route::resource('cortes', CortesController::class);

    // Tickets
    Route::get('/tickets', [TicketsController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/productividad-ajax', [TicketsController::class, 'obtenerProductividadAjax'])->name('tickets.productividad-ajax');
    Route::get('/tickets/chat-messages', [TicketsController::class, 'getChatMessages']);
    Route::get('/tickets/verificar-mensajes-nuevos', [TicketsController::class, 'verificarMensajesNuevos']);
    Route::get('/tickets/estadisticas-correos', [TicketsController::class, 'obtenerEstadisticasCorreos']);
    Route::get('/tickets/diagnosticar-correos', [TicketsController::class, 'diagnosticarCorreos']);
    Route::get('/tickets/tiempo-progreso', [TicketsController::class, 'obtenerTiempoProgreso']);
    Route::get('/tickets/tipos', [TicketsController::class, 'getTipos']);
    Route::get('/tickets/subtipos', [TicketsController::class, 'getSubtiposByTipo']);
    Route::get('/tickets/tertipos', [TicketsController::class, 'getTertiposBySubtipo']);
    Route::get('/tickets/tipos-con-metricas', [TicketsController::class, 'getTiposConMetricas']);
    Route::get('/tickets/excedidos', [TicketsController::class, 'obtenerTicketsExcedidos'])->name('tickets.excedidos');
    Route::get('/tickets/reporte-mensual', [TicketsController::class, 'reporteMensual'])->name('tickets.reporte-mensual');
    Route::get('/tickets/exportar-reporte-mensual-excel', [TicketsController::class, 'exportarReporteMensualExcel'])->name('tickets.exportar-reporte-mensual-excel');
    Route::post('/tickets/update', [TicketsController::class, 'update']);
    Route::post('/tickets/enviar-respuesta', [TicketsController::class, 'enviarRespuesta']);
    Route::post('/tickets/mensaje-interno', [TicketsController::class, 'agregarMensajeInterno']);
    Route::post('/tickets/marcar-leidos', [TicketsController::class, 'marcarMensajesComoLeidos']);
    Route::post('/tickets/sincronizar-correos', [TicketsController::class, 'sincronizarCorreos']);
    Route::post('/tickets/agregar-respuesta-manual', [TicketsController::class, 'agregarRespuestaManual']);
    Route::post('/tickets/enviar-instrucciones', [TicketsController::class, 'enviarInstruccionesRespuesta']);
    Route::post('/tickets/actualizar-tiempo-estimado', [TicketsController::class, 'actualizarTiempoEstimado']);
    Route::post('/tickets/actualizar-metricas-masivo', [TicketsController::class, 'actualizarMetricasMasivo']);
    Route::post('/tickets/subir-imagen-tinymce', [TicketsController::class, 'subirImagenTinyMCE'])->name('tickets.subir-imagen-tinymce');
    Route::get('/tickets/{id}', [TicketsController::class, 'show']);

    // Solicitudes (requieren auth)
    Route::get('/solicitudes/{id}/cotizar', [SolicitudesController::class, 'mostrarPaginaCotizacion'])->name('solicitudes.cotizar');

    // Correos / webhooks
    Route::post('/api/webhook/email-response', [App\Http\Controllers\EmailWebhookController::class, 'handleEmailResponse']);
    Route::post('/api/process-manual-response', [App\Http\Controllers\EmailWebhookController::class, 'processManualResponse']);
    Route::post('/api/test-webklex-connection', [App\Http\Controllers\WebklexApiController::class, 'testConnection']);
    Route::post('/api/process-webklex-responses', [App\Http\Controllers\WebklexApiController::class, 'processResponses']);
    Route::get('/api/webklex-mailbox-info', [App\Http\Controllers\WebklexApiController::class, 'getMailboxInfo']);
    Route::get('/email/verificar-configuracion', [App\Http\Controllers\EmailController::class, 'verificarConfiguracion']);
    Route::post('/email/procesar-correos', [App\Http\Controllers\EmailController::class, 'procesarCorreos']);
    Route::post('/email/enviar-prueba', [App\Http\Controllers\EmailController::class, 'enviarCorreoPrueba']);
    Route::get('/email/estadisticas', [App\Http\Controllers\EmailController::class, 'obtenerEstadisticas']);
    Route::get('/auth/outlook', [App\Http\Controllers\OutlookAuthController::class, 'redirect']);
    Route::get('/auth/outlook/callback', [App\Http\Controllers\OutlookAuthController::class, 'callback']);
    Route::get('/auth/outlook/status', [App\Http\Controllers\OutlookAuthController::class, 'status']);
});

// Soporte TI (sin auth)
Route::get('/SoporteTI', [SoporteTIController::class, 'index']);
Route::get('/autocompleteEmpleado', [SoporteTIController::class, 'autocompleteEmpleado']);
Route::get('/getEmpleadoInfo', [SoporteTIController::class, 'getEmpleadoInfo']);
Route::get('/buscarEmpleadoPorCorreo', [SoporteTIController::class, 'buscarEmpleadoPorCorreo']);
Route::post('/crearTickets', [SoporteTIController::class, 'crearTickets'])->name('soporte.ticket')->withoutMiddleware(['auth']);
Route::get('/getTypes', [SoporteTIController::class, 'getTypes']);

// Aprobación de solicitudes (sin auth, acceso por token)
Route::get('/revision-solicitud/{token}', [SolicitudAprobacionController::class, 'show'])->name('solicitudes.public.show');
Route::post('/revision-solicitud/{token}/decide', [SolicitudAprobacionController::class, 'decide'])->name('solicitudes.public.decide');
Route::post('/revision-solicitud/{token}/transferir', [SolicitudAprobacionController::class, 'transferir'])->name('solicitudes.public.transferir');
Route::get('/solicitudes/empleados-transferir', [SolicitudAprobacionController::class, 'obtenerEmpleadosParaTransferir'])->name('solicitudes.empleados-transferir');
Route::get('/solicitudes/recotizacion-solicitada', fn() => view('solicitudes.recotizacion-solicitada'))->name('solicitudes.recotizacion-solicitada');
Route::get('/solicitudes/ganadores-confirmados', fn() => view('solicitudes.ganadores-confirmados'))->name('solicitudes.ganadores-confirmados');
Route::get('/solicitudes/{id}/datos', [SolicitudesController::class, 'obtenerDatosSolicitud'])->name('solicitudes.datos');
Route::get('/solicitudes/{id}/cotizaciones', [SolicitudesController::class, 'obtenerCotizaciones'])->name('solicitudes.cotizaciones');
Route::post('/solicitudes/{id}/guardar-cotizaciones', [SolicitudesController::class, 'guardarCotizaciones'])->name('solicitudes.guardar-cotizaciones');
Route::post('/solicitudes/{id}/enviar-cotizaciones-gerente', [SolicitudesController::class, 'enviarCotizacionesAlGerente'])->name('solicitudes.enviar-cotizaciones-gerente');
Route::post('/solicitudes/{id}/seleccionar-cotizacion', [SolicitudesController::class, 'seleccionarCotizacion'])->name('solicitudes.seleccionar-cotizacion');
Route::post('/solicitudes/{id}/confirmar-ganadores', [SolicitudesController::class, 'confirmarGanadores'])->name('solicitudes.confirmar-ganadores');
Route::post('/solicitudes/{id}/solicitar-recotizacion', [SolicitudesController::class, 'solicitarRecotizacion'])->name('solicitudes.solicitar-recotizacion');
Route::post('/solicitudes/{id}/aprobar-{nivel}', [SolicitudAprobacionController::class, 'aprobarPorNivel'])->name('solicitudes.aprobar-nivel');
Route::post('/solicitudes/{id}/rechazar-{nivel}', [SolicitudAprobacionController::class, 'rechazarPorNivel'])->name('solicitudes.rechazar-nivel');
Route::get('/elegir-ganador/{token}', [SolicitudesController::class, 'elegirGanadorConToken'])->name('solicitudes.elegir-ganador-token');

Route::fallback(function () {
    if (auth()->check()) {
        return redirect()->route('home')->with('warning', 'La página solicitada no existe. Has sido redirigido al dashboard.');
    }
    return redirect('/login')->with('error', 'Debes iniciar sesión para acceder al sistema.');
});