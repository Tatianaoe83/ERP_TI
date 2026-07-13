<?php

use App\Http\Controllers\TicketSatisfactionAnswerController;
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
use App\Http\Controllers\MantenimientosController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\SolicitudAprobacionController;
use App\Http\Controllers\SolicitudesController;
use App\Http\Controllers\SoporteTIController;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\TicketsMantenimientoController;
use App\Http\Livewire\ReportesLista;
use App\Models\TicketChat;

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
    Route::GET('inventarios/verificar-folio', [InventarioController::class, 'verificarFolio'])->name('inventarios.verificarFolio');
    Route::DELETE('inventarios/deleteInsumo/{inventario}', [InventarioController::class, 'destroyInsumo'])->name('inventarios.destroyInsumo');
    Route::PUT('inventarios/editar-insumo/{id}', [InventarioController::class, 'editarinsumo'])->name('inventarios.editarinsumo');
    Route::POST('inventarios/crear-insumo/{id}', [InventarioController::class, 'crearinsumo'])->name('inventarios.crearinsumo');
    Route::DELETE('inventarios/deleteL/{inventario}', [InventarioController::class, 'destroylinea'])->name('inventarios.destroylinea');
    Route::PUT('inventarios/editar-linea/{id}', [InventarioController::class, 'editarlinea'])->name('inventarios.editarlinea');
    Route::POST('inventarios/crear-linea/{id}/{telf}', [InventarioController::class, 'crearlinea'])->name('inventarios.crearlinea');
    Route::GET('inventarios/{inventario}/transferir', [InventarioController::class, 'transferir'])->name('inventarios.transferir');
    Route::PUT('inventarios/{inventario}/traspaso', [InventarioController::class, 'formTraspaso'])->name('inventarios.transpaso');
    Route::GET('inventarios/{inventario}/cartas', [InventarioController::class, 'cartas'])->name('inventarios.cartas');
    Route::GET('inventarios/{inventario}/exportar/{tipo}', [InventarioController::class, 'exportarAsignados'])->name('inventarios.exportarAsignados');
    Route::POST('pdffile/{id}', [InventarioController::class, 'pdffile'])->name('inventarios.pdffile');
    Route::POST('mantenimiento/{id}', [InventarioController::class, 'mantenimiento'])->name('inventarios.mantenimiento');
    Route::resource('inventarios', App\Http\Controllers\InventarioController::class);

    Route::post('presupuesto/verificar', [PresupuestoController::class, 'verificarFechas'])->name('presupuesto.verificar');
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

    Route::post('/facturas/previsualizar-pdf-texto', [App\Http\Controllers\FacturasController::class, 'previsualizarPdfDesdeTexto'])
        ->name('facturas.previsualizarPdfDesdeTexto');

    Route::post('/facturas/{id}/reemplazar-archivo', [App\Http\Controllers\FacturasController::class, 'reemplazarArchivo'])
        ->name('facturas.reemplazar');
    Route::get('facturas/comparativa', [FacturasController::class, 'comparativa'])->name('facturas.comparativa');
    Route::get('facturas/comparativa/exportar', [FacturasController::class, 'exportarComparativa'])->name('facturas.comparativa.exportar');
    Route::get('facturas/historial', [FacturasController::class, 'historial'])->name('facturas.historial');
    Route::get('verFacturas', [FacturasController::class, 'indexVista'])->name('facturas.ver');
    Route::get('facturas/insumos-por-gerencia', [FacturasController::class, 'getInsumosPorGerencia'])->name('facturas.getInsumosPorGerencia');
    Route::patch('facturas/{id}/insumo', [FacturasController::class, 'actualizarInsumo'])->name('facturas.actualizarInsumo');
    Route::patch('facturas/{id}/mes', [FacturasController::class, 'actualizarMes'])->name('facturas.actualizarMes');
    Route::get('facturas/{id}/datos', [FacturasController::class, 'obtenerDatos'])->name('facturas.obtenerDatos');
    Route::patch('facturas/{id}/actualizar-datos', [FacturasController::class, 'actualizarDatos'])->name('facturas.actualizarDatos');
    Route::post('facturas/{id}/actualizar-completo', [FacturasController::class, 'actualizarCompleto'])->name('facturas.actualizarCompleto');
    Route::patch('facturas/{id}/registrar-cambio', [FacturasController::class, 'ultimoCambioPorUsuario'])->name('facturas.registrarCambio');
    Route::post('facturas/directa', [FacturasController::class, 'storeDirecta'])->name('facturas.storeDirecta')->middleware('permission:crear-facturas');
    Route::resource('facturas', FacturasController::class);
    Route::get('mantenimientos', [MantenimientosController::class, 'index'])->name('mantenimientos.index');
    Route::get('mantenimientos/exportar-excel', [MantenimientosController::class, 'exportarExcel'])->name('mantenimientos.exportar-excel');
    Route::post('mantenimientos/generar', [MantenimientosController::class, 'generar'])->name('mantenimientos.generar');
    Route::patch('mantenimientos/{mantenimiento}/realizado', [MantenimientosController::class, 'marcarRealizado'])->name('mantenimientos.realizado');
    Route::get('mantenimientos/exportar-excel', [MantenimientosController::class, 'exportarExcel'])->name('mantenimientos.exportar-excel');

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
    Route::get('/tickets/detalle-grafica', [TicketsController::class, 'getTicketsDetalleGrafica'])->name('tickets.detalle-grafica');
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
    // Endpoint ligero para polling en tiempo real de notificaciones pendientes (debe estar ANTES de /tickets/{id})
    Route::get('/tickets/notificaciones-pendientes', function () {
        $pendientes = \App\Models\TicketChat::where('notificaciones_pendientes', '>', 0)
            ->selectRaw('ticket_id, SUM(notificaciones_pendientes) as total')
            ->groupBy('ticket_id')
            ->pluck('total', 'ticket_id');
        return response()->json(['pendientes' => $pendientes]);
    });

    // Endpoint para el panel de notificaciones del sidebar
Route::get('/notificaciones-panel', function () {
    $contarFacturasSolicitud = function ($solicitud) {
        if (!$solicitud->cotizaciones || $solicitud->cotizaciones->isEmpty()) {
            return [0, 0];
        }
        $sel = $solicitud->cotizaciones->where('Estatus', 'Seleccionada');
        if ($sel->isEmpty()) {
            return [0, 0];
        }
        $totalNecesarias = $sel->pluck('Proveedor')->filter()->unique()->count();
        if ($totalNecesarias === 0) {
            return [0, 0];
        }
        $cotIds = $sel->pluck('CotizacionID')->filter()->unique()->toArray();
        if (empty($cotIds)) {
            return [0, $totalNecesarias];
        }
        if (!\Illuminate\Support\Facades\Schema::hasColumn('facturas', 'CotizacionID')) {
            $facturasSubidas = \App\Models\Facturas::query()
                ->where('SolicitudID', (int)$solicitud->SolicitudID)
                ->where(fn($q) => $q->whereNotNull('ArchivoRuta')->where('ArchivoRuta', '!=', '')
                    ->orWhereNotNull('PdfRuta')->where('PdfRuta', '!=', ''))
                ->count();

            return [min($facturasSubidas, $totalNecesarias), $totalNecesarias];
        }
        $facturas = \App\Models\Facturas::query()
            ->where('SolicitudID', (int)$solicitud->SolicitudID)
            ->whereIn('CotizacionID', $cotIds)
            ->where(fn($q) => $q->whereNotNull('ArchivoRuta')->where('ArchivoRuta', '!=', '')
                ->orWhereNotNull('PdfRuta')->where('PdfRuta', '!=', ''))
            ->select('CotizacionID')
            ->distinct()
            ->get();
        if ($facturas->isEmpty()) {
            return [0, $totalNecesarias];
        }
        $cotsConFactura = $facturas->pluck('CotizacionID')->toArray();
        $provsConFactura = $sel->whereIn('CotizacionID', $cotsConFactura)
            ->pluck('Proveedor')->filter()->unique()->count();
        return [$provsConFactura, $totalNecesarias];
    };

    $mapSolicitudFacturaPendiente = function ($solicitud) use ($contarFacturasSolicitud) {
        [$facturasSubidas, $totalNecesarias] = $contarFacturasSolicitud($solicitud);
        return [
            'SolicitudID' => $solicitud->SolicitudID,
            'Motivo' => $solicitud->Motivo,
            'empleado' => $solicitud->empleadoid ? $solicitud->empleadoid->NombreEmpleado : 'Sin asignar',
            'facturas_subidas' => $facturasSubidas,
            'facturas_necesarias' => $totalNecesarias,
            'created_at' => $solicitud->updated_at ? $solicitud->updated_at->diffForHumans() : '',
            'timestamp' => $solicitud->updated_at ? $solicitud->updated_at->timestamp : 0,
        ];
    };

    $querySolicitudesAprobadas = fn () => \App\Models\Solicitud::whereNotIn('Estatus', ['Cancelada', 'Cerrada'])
        ->whereHas('pasoSupervisor', fn($q) => $q->where('status', 'approved'))
        ->whereHas('pasoGerencia', fn($q) => $q->where('status', 'approved'))
        ->whereHas('pasoAdministracion', fn($q) => $q->where('status', 'approved'))
        ->whereHas('cotizaciones', fn($q) => $q->where('Estatus', 'Seleccionada'))
        ->with(['empleadoid', 'cotizaciones']);

    $necesitaCotizacionTI = function ($solicitud) {
        if (in_array($solicitud->Estatus, ['Cancelada', 'Cerrada', 'Aprobado', 'Aprobada', 'Cotizaciones Enviadas', 'Re-cotizar'], true)) {
            return false;
        }

        $ps = $solicitud->pasoSupervisor;
        $pg = $solicitud->pasoGerencia;
        $pa = $solicitud->pasoAdministracion;

        if (($ps && $ps->status === 'rejected') || ($pg && $pg->status === 'rejected') || ($pa && $pa->status === 'rejected')) {
            return false;
        }

        if (!$ps || $ps->status !== 'approved') {
            return false;
        }

        return !$solicitud->todosProductosTienenGanador();
    };

    // Corte de 24h: clasifica nuevos (recientes) vs vencidos (no resueltos de cortes anteriores)
    $corte = now()->subHours(24);

    // 1. Tickets (Pendiente): trae todos los no resueltos; 'vencidos' marca los de +24h arrastrados
    $ticketsNuevos = \App\Models\Tickets::where('Estatus', 'Pendiente')
        ->with('empleado')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get()
        ->map(fn($t) => [
            'TicketID' => $t->TicketID,
            'empleado' => $t->empleado ? $t->empleado->NombreEmpleado : 'Sin asignar',
            'vencidos' => $t->created_at < $corte,
            'created_at' => $t->created_at->diffForHumans(),
            'timestamp' => $t->created_at->timestamp,
        ]);

    // 2. Solicitudes (sin Vo.bo. de supervisor): trae todas las no resueltas; 'vencidos' marca las de +24h
    $solicitudesPendientes = \App\Models\Solicitud::whereNotIn('Estatus', ['Cancelada', 'Cerrada', 'Aprobado', 'Aprobada', 'Cotizaciones Enviadas', 'Re-cotizar'])
        ->whereDoesntHave('pasoSupervisor', fn($q) => $q->where('status', 'approved'))
        ->with('empleadoid')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get()
        ->map(fn($s) => [
            'SolicitudID' => $s->SolicitudID,
            'Motivo' => $s->Motivo,
            'Estatus' => $s->Estatus ?: 'Pendiente',
            'empleado' => $s->empleadoid ? $s->empleadoid->NombreEmpleado : 'Sin asignar',
            'vencidos' => $s->created_at ? $s->created_at < $corte : false,
            'created_at' => $s->created_at ? $s->created_at->diffForHumans() : '',
            'timestamp' => $s->created_at ? $s->created_at->timestamp : 0,
        ]);

    // 3. Solicitudes con Vo.bo. de supervisor que requieren cotización de TI
    $solicitudesCotizacionTIFiltradas = \App\Models\Solicitud::whereNotIn('Estatus', ['Cancelada', 'Cerrada', 'Aprobado', 'Aprobada', 'Cotizaciones Enviadas', 'Re-cotizar'])
        ->with(['empleadoid', 'pasoSupervisor', 'pasoGerencia', 'pasoAdministracion', 'cotizaciones'])
        ->whereHas('pasoSupervisor', fn($q) => $q->where('status', 'approved'))
        ->orderBy('updated_at', 'desc')
        ->get()
        ->filter($necesitaCotizacionTI);

    $solicitudesCotizacionTI = $solicitudesCotizacionTIFiltradas
        ->take(10)
        ->map(function ($s) {
            $fechaSupervisor = ($s->pasoSupervisor && $s->pasoSupervisor->decided_at)
                ? \Carbon\Carbon::parse($s->pasoSupervisor->decided_at)
                : ($s->updated_at ? \Carbon\Carbon::parse($s->updated_at) : null);

            return [
                'SolicitudID' => $s->SolicitudID,
                'Motivo' => $s->Motivo,
                'empleado' => $s->empleadoid ? $s->empleadoid->NombreEmpleado : 'Sin asignar',
                'cotizaciones_count' => $s->cotizaciones ? $s->cotizaciones->count() : 0,
                'created_at' => $fechaSupervisor ? $fechaSupervisor->diffForHumans() : '',
                'timestamp' => $fechaSupervisor ? $fechaSupervisor->timestamp : 0,
            ];
        })
        ->values();

    $solicitudesCotizacionTICount = $solicitudesCotizacionTIFiltradas->count();

    // 4. Solicitudes que requieren seguimiento de TI (cotizaciones enviadas o re-cotización)
    $solicitudesSeguimientoTI = \App\Models\Solicitud::whereIn('Estatus', ['Cotizaciones Enviadas', 'Re-cotizar'])
        ->with('empleadoid')
        ->orderBy('updated_at', 'desc')
        ->limit(10)
        ->get()
        ->map(fn($s) => [
            'SolicitudID' => $s->SolicitudID,
            'Motivo' => $s->Motivo,
            'Estatus' => $s->Estatus,
            'empleado' => $s->empleadoid ? $s->empleadoid->NombreEmpleado : 'Sin asignar',
            'created_at' => $s->updated_at ? $s->updated_at->diffForHumans() : '',
            'timestamp' => $s->updated_at ? $s->updated_at->timestamp : 0,
        ]);

    // 5. Solicitudes aprobadas con facturas pendientes de subir
    $solicitudesFacturaPendienteFiltradas = $querySolicitudesAprobadas()
        ->orderBy('updated_at', 'desc')
        ->get()
        ->filter(function ($s) use ($contarFacturasSolicitud) {
            [$subidas, $necesarias] = $contarFacturasSolicitud($s);
            return $necesarias > 0 && $subidas < $necesarias;
        });

    $solicitudesFacturaPendiente = $solicitudesFacturaPendienteFiltradas
        ->take(10)
        ->map($mapSolicitudFacturaPendiente)
        ->values();

    $solicitudesFacturaPendienteCount = $solicitudesFacturaPendienteFiltradas->count();

    // 6. CORRECCIÓN AQUÍ: Aseguramos la captura del alias en la agregación
    $mensajesNuevos = \App\Models\TicketChat::where('notificaciones_pendientes', '>', 0)
        ->selectRaw('ticket_id, SUM(notificaciones_pendientes) as total, MAX(created_at) as last_created_at')
        ->groupBy('ticket_id')
        ->orderByRaw('MAX(created_at) DESC')
        ->limit(10)
        ->get()
        ->map(function($c) {
            // Accedemos de forma segura al atributo crudo de la query
            $lastCreated = $c->getAttribute('last_created_at'); 
            $carbonDate = $lastCreated ? \Carbon\Carbon::parse($lastCreated) : null;

            return [
                'ticket_id' => $c->ticket_id,
                'total' => (int) $c->getAttribute('total'),
                'created_at' => $carbonDate ? $carbonDate->diffForHumans() : 'Hace un momento',
                'timestamp' => $carbonDate ? $carbonDate->timestamp : time(), // Si falla, le ponemos el tiempo actual para que no sea 0
            ];
        });

    return response()->json([
        'tickets_nuevos' => $ticketsNuevos,
        'tickets_nuevos_count' => \App\Models\Tickets::where('Estatus', 'Pendiente')
            ->where('created_at', '>=', $corte)->count(),
        'solicitudes_pendientes' => $solicitudesPendientes,
        'solicitudes_pendientes_count' => \App\Models\Solicitud::whereNotIn('Estatus', ['Cancelada', 'Cerrada', 'Aprobado', 'Aprobada', 'Cotizaciones Enviadas', 'Re-cotizar'])
            ->where('created_at', '>=', $corte)
            ->whereDoesntHave('pasoSupervisor', fn($q) => $q->where('status', 'approved'))
            ->count(),
        'solicitudes_cotizacion_ti' => $solicitudesCotizacionTI,
        'solicitudes_cotizacion_ti_count' => $solicitudesCotizacionTICount,
        'solicitudes_seguimiento_ti' => $solicitudesSeguimientoTI,
        'solicitudes_seguimiento_ti_count' => \App\Models\Solicitud::whereIn('Estatus', ['Cotizaciones Enviadas', 'Re-cotizar'])->count(),
        'solicitudes_factura_pendiente' => $solicitudesFacturaPendiente,
        'solicitudes_factura_pendiente_count' => $solicitudesFacturaPendienteCount,
        'mensajes_nuevos' => $mensajesNuevos,
'mensajes_nuevos_count' =>
\App\Models\TicketChat::where('notificaciones_pendientes', '>', 0)
    ->get()
    ->groupBy('ticket_id')
    ->count(),        // Cambié el count de arriba por el SUM total para que refleje cuántos mensajes reales hay sin leer en el sistema global.
    ]);
});
    Route::post('/tickets/update', [TicketsController::class, 'update']);
    Route::post('/tickets/enviar-respuesta', [TicketsController::class, 'enviarRespuesta']);
    Route::post('/tickets/mensaje-interno', [TicketsController::class, 'agregarMensajeInterno']);
    Route::post('/tickets/marcar-leidos', [TicketsController::class, 'marcarMensajesComoLeidos']);
    Route::post('/tickets/sincronizar-correos', [TicketsController::class, 'sincronizarCorreos']);
    Route::post('/tickets/agregar-respuesta-manual', [TicketsController::class, 'agregarRespuestaManual']);
    Route::post('/tickets/{id}/mark-notifications-read', [TicketsController::class, 'markNotificationsRead'])->name('tickets.mark-notifications-read');
    Route::post('/tickets/enviar-instrucciones', [TicketsController::class, 'enviarInstruccionesRespuesta']);
    Route::post('/tickets/actualizar-tiempo-estimado', [TicketsController::class, 'actualizarTiempoEstimado']);
    Route::post('/tickets/actualizar-metricas-masivo', [TicketsController::class, 'actualizarMetricasMasivo']);
    Route::post('/tickets/subir-imagen-tinymce', [TicketsController::class, 'subirImagenTinyMCE'])->name('tickets.subir-imagen-tinymce');
    Route::get('/tickets/{id}', [TicketsController::class, 'show']);

    // Mantenimientos de compras
    Route::get('/tickets-mantenimiento', [TicketsMantenimientoController::class, 'index'])->name('tickets-mantenimiento.index');
    Route::get('/tickets-mantenimiento/productividad-ajax', [TicketsMantenimientoController::class, 'obtenerProductividadAjax'])->name('tickets-mantenimiento.productividad-ajax');
    Route::get('/tickets-mantenimiento/chat-messages', [TicketsMantenimientoController::class, 'getChatMessages']);
    Route::get('/tickets-mantenimiento/verificar-mensajes-nuevos', [TicketsMantenimientoController::class, 'verificarMensajesNuevos']);
    Route::get('/tickets-mantenimiento/estadisticas-correos', [TicketsMantenimientoController::class, 'obtenerEstadisticasCorreos']);
    Route::post('/tickets-mantenimiento/update', [TicketsMantenimientoController::class, 'update']);
    Route::post('/tickets-mantenimiento/enviar-respuesta', [TicketsMantenimientoController::class, 'enviarRespuesta']);
    Route::post('/tickets-mantenimiento/marcar-leidos', [TicketsMantenimientoController::class, 'marcarMensajesComoLeidos']);
    Route::get('/tickets-mantenimiento/{id}', [TicketsMantenimientoController::class, 'show']);

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

// Encuestas de satisfacción de tickets (sin auth, protegidas por firma temporal)
Route::get(
    '/tickets/encuesta/{survey}',
    [TicketSatisfactionAnswerController::class, 'show']
)->name('tickets.satisfaction.survey');
Route::get(
    '/tickets/calificacion/{survey}/{field}/{rating}',
    [TicketSatisfactionAnswerController::class, 'store']
)->name('tickets.satisfaction.answer');
Route::post(
    '/tickets/encuesta/{survey}/comentario',
    [TicketSatisfactionAnswerController::class, 'storeComment']
)->name('tickets.satisfaction.comment');
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

Route::post('/tickets/{id}/mark-notifications-read', function ($id) {
    // Buscamos el último chat de este ticket que tenga notificaciones pendientes y las ponemos en 0
    TicketChat::where('ticket_id', $id)
        ->where('notificaciones_pendientes', '>', 0)
        ->update(['notificaciones_pendientes' => 0]);

    return response()->json(['success' => true]);
})->middleware(['web']);

