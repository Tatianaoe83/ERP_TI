<?php

namespace App\Http\Controllers;

use App\Models\Empleados;
use App\Models\Solicitud;
use App\Models\Tickets;
use App\Models\TicketChat;
use App\Models\Tertipos;
use App\Models\Subtipos;
use App\Models\Tipoticket;
use App\Services\SimpleEmailService;
use App\Services\TicketNotificationService;
use Illuminate\Http\Request;
use App\Models\SolicitudActivo;
use App\Http\Controllers\Traits\MetricasSolicitudesTrait;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TicketsController extends Controller
{
    use MetricasSolicitudesTrait;
    protected $emailService;

    public function __construct(SimpleEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    // Carga el dashboard principal con tickets, solicitudes y métricas del mes
    public function index(Request $request)
    {
        $mes  = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        $tickets = Tickets::with(['empleado', 'responsableTI', 'tipoticket', 'chat' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(1);
        }])->orderBy('created_at', 'desc')->get();

        $ticketsStatus = [
            'nuevos'    => $tickets->where('Estatus', 'Pendiente'),
            'proceso'   => $tickets->where('Estatus', 'En progreso'),
            'resueltos' => $tickets->where('Estatus', 'Cerrado'),
        ];

        $responsablesTI          = Empleados::where('ObraID', 46)->where('tipo_persona', 'FISICA')->get();
        $metricasProductividad   = $this->obtenerMetricasProductividad($tickets, $mes, $anio);

        $solicitudes = Solicitud::with([
            'empleadoid',
            'pasoSupervisor',
            'pasoGerencia',
            'pasoAdministracion',
            'cotizaciones',
        ])->orderBy('created_at', 'desc')->get();

        $solicitudesStatus = [$solicitudes->all()];
        $metricasSolicitudes = $this->calcularMetricasSolicitudes($mes, $anio);

        return view('tickets.index', compact(
            'ticketsStatus',
            'responsablesTI',
            'metricasProductividad',
            'mes',
            'anio',
            'solicitudesStatus',
            'metricasSolicitudes'
        ));
    }

    // Calcula métricas de productividad para el dashboard filtradas por mes y año
    private function obtenerMetricasProductividad($tickets, $mes = null, $anio = null)
    {
        $mes  = $mes  ?? now()->month;
        $anio = $anio ?? now()->year;

        $fechaInicioMes = \Carbon\Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFinMes    = \Carbon\Carbon::create($anio, $mes, 1)->endOfMonth();

        $ticketsDelMes = $tickets->filter(
            fn($t) => \Carbon\Carbon::parse($t->created_at)->between($fechaInicioMes, $fechaFinMes)
        );

        $distribucionEstado = [
            'Pendiente'   => $ticketsDelMes->where('Estatus', 'Pendiente')->count(),
            'En progreso' => $ticketsDelMes->where('Estatus', 'En progreso')->count(),
            'Cerrado'     => $ticketsDelMes->where('Estatus', 'Cerrado')->count(),
        ];

        $ticketsCerradosMes = $ticketsDelMes->filter(
            fn($t) => $t->Estatus === 'Cerrado' && $t->FechaInicioProgreso && $t->FechaFinProgreso
        );

        $tiempoPromedioResolucion = 0;
        if ($ticketsCerradosMes->count() > 0) {
            $tiempoPromedioResolucion = round(
                $ticketsCerradosMes->sum(fn($t) => $t->tiempo_resolucion ?? 0) / $ticketsCerradosMes->count(),
                2
            );
        }

        $ticketsEnProgresoMes = $ticketsDelMes->filter(
            fn($t) => $t->Estatus === 'En progreso' && $t->FechaInicioProgreso
        );

        $tiempoPromedioRespuesta = 0;
        if ($ticketsEnProgresoMes->count() > 0) {
            $tiempoPromedioRespuesta = round(
                $ticketsEnProgresoMes->sum(fn($t) => $t->tiempo_respuesta ?? 0) / $ticketsEnProgresoMes->count(),
                2
            );
        }

        $ticketsPorResponsable = $ticketsDelMes->filter(fn($t) => $t->ResponsableTI !== null)
            ->groupBy('ResponsableTI')
            ->map(function ($grupo) {
                $responsable = $grupo->first()->responsableTI;
                return [
                    'nombre'     => $responsable ? $responsable->NombreEmpleado : 'Sin asignar',
                    'total'      => $grupo->count(),
                    'cerrados'   => $grupo->where('Estatus', 'Cerrado')->count(),
                    'en_progreso' => $grupo->where('Estatus', 'En progreso')->count(),
                    'pendientes' => $grupo->where('Estatus', 'Pendiente')->count(),
                    'problemas'  => $grupo->where('Clasificacion', 'Problema')->count(),
                    'servicios'  => $grupo->where('Clasificacion', 'Servicio')->count(),
                ];
            })->sortByDesc('total')->take(10);

        $ticketsPorPrioridad = $ticketsDelMes->groupBy('Prioridad')->map(fn($g) => $g->count());

        $ticketsPorClasificacion = $ticketsDelMes->filter(
            fn($t) => $t->Estatus === 'En progreso' || $t->Estatus === 'Cerrado'
        )->groupBy('Clasificacion')->map(fn($g) => $g->count());

        $fechaInicio30       = now()->subDays(30)->startOfDay();
        $ticketsUltimos30Dias = $tickets->filter(
            fn($t) => \Carbon\Carbon::parse($t->created_at)->greaterThanOrEqualTo($fechaInicio30)
        );

        $resueltosPorDia = [];
        for ($i = 29; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            $resueltosPorDia[$fecha] = $tickets->filter(
                fn($t) => $t->Estatus === 'Cerrado'
                    && $t->FechaFinProgreso
                    && \Carbon\Carbon::parse($t->FechaFinProgreso)->format('Y-m-d') === $fecha
            )->count();
        }

        $tendenciasSemanales = [];
        for ($i = 7; $i >= 0; $i--) {
            $semanaInicio = now()->subWeeks($i)->startOfWeek();
            $semanaFin    = now()->subWeeks($i)->endOfWeek();
            $semanaLabel  = $semanaInicio->format('d/m') . ' - ' . $semanaFin->format('d/m');

            $tendenciasSemanales[$semanaLabel] = [
                'creados'   => $tickets->filter(
                    fn($t) => \Carbon\Carbon::parse($t->created_at)->between($semanaInicio, $semanaFin)
                )->count(),
                'resueltos' => $tickets->filter(function ($t) use ($semanaInicio, $semanaFin) {
                    if ($t->Estatus !== 'Cerrado' || empty($t->FechaFinProgreso)) return false;
                    return \Carbon\Carbon::parse($t->FechaFinProgreso)->between($semanaInicio, $semanaFin);
                })->count(),
            ];
        }

        $metricasPorEmpleado = $this->obtenerMetricasPorEmpleado($tickets);

        return [
            'total_tickets'               => $ticketsDelMes->count(),
            'tickets_cerrados'            => $ticketsCerradosMes->count(),
            'tickets_en_progreso'         => $ticketsEnProgresoMes->count(),
            'distribucion_estado'         => $distribucionEstado,
            'tiempo_promedio_resolucion'  => $tiempoPromedioResolucion,
            'tiempo_promedio_respuesta'   => $tiempoPromedioRespuesta,
            'tickets_por_responsable'     => $ticketsPorResponsable,
            'tickets_por_prioridad'       => $ticketsPorPrioridad,
            'tickets_por_clasificacion'   => $ticketsPorClasificacion,
            'tickets_ultimos_30_dias'     => $ticketsUltimos30Dias->count(),
            'resueltos_por_dia'           => $resueltosPorDia,
            'tendencias_semanales'        => $tendenciasSemanales,
            'metricas_por_empleado'       => $metricasPorEmpleado,
        ];
    }

    // Calcula métricas de rendimiento por empleado TI en los últimos 6 meses
    private function obtenerMetricasPorEmpleado($tickets)
    {
        $empleados = Empleados::where('ObraID', 46)->where('tipo_persona', 'FISICA')->get();
        $metricas  = [];

        foreach ($empleados as $empleado) {
            $ticketsEmpleado = $tickets->filter(fn($t) => $t->ResponsableTI == $empleado->EmpleadoID);
            if ($ticketsEmpleado->count() == 0) continue;

            $cerrados   = $ticketsEmpleado->where('Estatus', 'Cerrado');
            $enProgreso = $ticketsEmpleado->where('Estatus', 'En progreso');
            $pendientes = $ticketsEmpleado->where('Estatus', 'Pendiente');

            $ticketsConResolucion = $cerrados->filter(fn($t) => $t->FechaInicioProgreso && $t->FechaFinProgreso);

            $tiempoPromedioResolucion = 0;
            if ($ticketsConResolucion->count() > 0) {
                $tiempoPromedioResolucion = round(
                    $ticketsConResolucion->sum(fn($t) => $t->tiempo_resolucion ?? 0) / $ticketsConResolucion->count(),
                    2
                );
            }

            $tasaCierre = $ticketsEmpleado->count() > 0
                ? round(($cerrados->count() / $ticketsEmpleado->count()) * 100, 1)
                : 0;

            $ticketsPorMes = [];
            for ($i = 5; $i >= 0; $i--) {
                $mesInicio = now()->subMonthsNoOverflow($i)->startOfMonth();
                $mesFin    = now()->subMonthsNoOverflow($i)->endOfMonth();
                $mesLabel  = $mesInicio->format('M Y');

                $ticketsPorMes[$mesLabel] = [
                    'total'    => $ticketsEmpleado->filter(
                        fn($t) => \Carbon\Carbon::parse($t->created_at)->between($mesInicio, $mesFin)
                    )->count(),
                    'cerrados' => $ticketsEmpleado->filter(function ($t) use ($mesInicio, $mesFin) {
                        if ($t->Estatus !== 'Cerrado' || empty($t->FechaFinProgreso)) return false;
                        return \Carbon\Carbon::parse($t->FechaFinProgreso)->between($mesInicio, $mesFin);
                    })->count(),
                ];
            }

            $metricas[] = [
                'empleado_id'              => $empleado->EmpleadoID,
                'nombre'                   => $empleado->NombreEmpleado,
                'total'                    => $ticketsEmpleado->count(),
                'cerrados'                 => $cerrados->count(),
                'en_progreso'              => $enProgreso->count(),
                'pendientes'               => $pendientes->count(),
                'problemas'                => $ticketsEmpleado->where('Clasificacion', 'Problema')->count(),
                'servicios'                => $ticketsEmpleado->where('Clasificacion', 'Servicio')->count(),
                'tasa_cierre'              => $tasaCierre,
                'tiempo_promedio_resolucion' => $tiempoPromedioResolucion,
                'tickets_por_mes'          => $ticketsPorMes,
                'tickets_por_prioridad'    => $ticketsEmpleado->groupBy('Prioridad')->map(fn($g) => $g->count()),
                'tickets_por_clasificacion' => $ticketsEmpleado->groupBy('Clasificacion')->map(fn($g) => $g->count()),
            ];
        }

        usort($metricas, fn($a, $b) => $b['total'] <=> $a['total']);

        return $metricas;
    }

    // Retorna datos de un ticket individual en JSON
    public function show($id)
    {
        try {
            $ticket = Tickets::with('empleado')->find($id);

            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket no encontrado'], 404);
            }

            return response()->json([
                'success' => true,
                'ticket'  => [
                    'TicketID'       => $ticket->TicketID,
                    'Prioridad'      => $ticket->Prioridad,
                    'Estatus'        => $ticket->Estatus,
                    'Clasificacion'  => $ticket->Clasificacion,
                    'Resolucion'     => $ticket->Resolucion,
                    'ResponsableTI'  => $ticket->ResponsableTI,
                    'TipoID'         => $ticket->TipoID,
                    'SubtipoID'      => $ticket->SubtipoID,
                    'TertipoID'      => $ticket->TertipoID,
                    'imagen'         => $ticket->imagen,
                    'empleado'       => $ticket->empleado ? $ticket->empleado->NombreEmpleado : 'Sin asignar',
                    'correo'         => $ticket->empleado ? $ticket->empleado->Correo : '',
                    'numero'         => $ticket->Numero,
                    'anydesk'        => $ticket->CodeAnyDesk,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener el ticket: ' . $e->getMessage()], 500);
        }
    }

    // Actualiza campos de un ticket respetando las reglas de transición de estatus
    public function update(Request $request)
    {
        try {
            $ticketId = $request->input('ticketId');
            $ticket   = Tickets::find($ticketId);

            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket no encontrado'], 404);
            }

            $estatusAnterior = $ticket->Estatus;
            $nuevoEstatus    = $request->input('estatus', $estatusAnterior);

            if ($estatusAnterior === 'Cerrado') {
                return response()->json(['success' => false, 'message' => 'No se pueden realizar modificaciones en un ticket cerrado'], 400);
            }

            $transicionesValidas = [
                'Pendiente'   => ['En progreso'],
                'En progreso' => ['Cerrado'],
                'Cerrado'     => [],
            ];

            if ($nuevoEstatus !== $estatusAnterior) {
                if (!in_array($nuevoEstatus, $transicionesValidas[$estatusAnterior] ?? [])) {
                    return response()->json([
                        'success' => false,
                        'message' => "No se puede cambiar el estado de '{$estatusAnterior}' a '{$nuevoEstatus}'. Las transiciones válidas son: "
                            . implode(', ', $transicionesValidas[$estatusAnterior] ?? ['ninguna']),
                    ], 400);
                }
            }

            if ($estatusAnterior === 'Pendiente' && $nuevoEstatus === 'En progreso') {
                $responsableTI = $request->input('responsableTI');
                $tipoID        = $request->input('tipoID');
                $clasificacion = $request->input('clasificacion');

                if (empty($responsableTI) || empty($tipoID) || empty($clasificacion)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para cambiar el ticket a "En progreso" es necesario asignar un Responsable, una Categoría y una Clasificación',
                    ], 400);
                }
            }

            if ($estatusAnterior === 'En progreso' && $request->has('responsableTI')) {
                $nuevoResponsable = $request->input('responsableTI');
                if ($nuevoEstatus !== 'Cerrado' && $nuevoResponsable != $ticket->ResponsableTI) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede modificar el Responsable cuando el ticket está en "En progreso"',
                    ], 400);
                }
            }

            if ($request->has('prioridad'))      $ticket->Prioridad      = $request->input('prioridad');
            if ($request->has('clasificacion'))  $ticket->Clasificacion  = $request->input('clasificacion') ?: null;
            if ($request->has('resolucion'))      $ticket->Resolucion     = $request->input('resolucion');
            if ($request->has('estatus'))         $ticket->Estatus        = $request->input('estatus');

            if ($request->has('responsableTI')) {
                if ($estatusAnterior !== 'En progreso' || $nuevoEstatus === 'Cerrado') {
                    $ticket->ResponsableTI = $request->input('responsableTI') ?: null;
                }
            }

            if ($request->has('tipoID')) {
                $tipoID      = $request->input('tipoID') ? (int)$request->input('tipoID') : null;
                $ticket->TipoID = $tipoID;

                if ((!$request->has('subtipoID') || !$request->input('subtipoID')) && $tipoID) {
                    $tipoticket = Tipoticket::find($tipoID);
                    if ($tipoticket && $tipoticket->SubtipoID) {
                        $ticket->SubtipoID = $tipoticket->SubtipoID;
                        if (!$request->has('tertipoID') || !$request->input('tertipoID')) {
                            $subtipo = Subtipos::find($tipoticket->SubtipoID);
                            if ($subtipo && $subtipo->TertipoID) $ticket->TertipoID = $subtipo->TertipoID;
                        }
                    }
                }
            }

            if ($request->has('subtipoID')) {
                $ticket->SubtipoID = $request->input('subtipoID') ? (int)$request->input('subtipoID') : null;
                if ($ticket->SubtipoID && (!$request->has('tertipoID') || !$request->input('tertipoID'))) {
                    $subtipo = Subtipos::find($ticket->SubtipoID);
                    if ($subtipo && $subtipo->TertipoID) $ticket->TertipoID = $subtipo->TertipoID;
                }
            }

            if ($request->has('tertipoID')) {
                $ticket->TertipoID = $request->input('tertipoID') ? (int)$request->input('tertipoID') : null;
            }

            $ticket->save();

            if ($nuevoEstatus === 'En progreso') {
                $ticket->refresh();
                $ticket->load(['tipoticket', 'responsableTI']);
                try {
                    (new TicketNotificationService())->verificarYNotificarExceso($ticket);
                } catch (\Exception $e) {
                    Log::error("Error verificando exceso de tiempo al cambiar a En progreso: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados correctamente',
                'ticket'  => [
                    'TicketID'      => $ticket->TicketID,
                    'Prioridad'     => $ticket->Prioridad,
                    'Estatus'       => $ticket->Estatus,
                    'Clasificacion' => $ticket->Clasificacion,
                    'Resolucion'    => $ticket->Resolucion,
                    'ResponsableTI' => $ticket->ResponsableTI,
                    'TipoID'        => $ticket->TipoID,
                    'SubtipoID'     => $ticket->SubtipoID,
                    'TertipoID'     => $ticket->TertipoID,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar el ticket: ' . $e->getMessage()], 500);
        }
    }

    // Retorna los mensajes del chat de un ticket ordenados cronológicamente
    public function getChatMessages(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');

            $messages = TicketChat::where('ticket_id', $ticketId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($m) => [
                    'id'               => $m->id,
                    'mensaje'          => $m->mensaje,
                    'remitente'        => $m->remitente,
                    'nombre_remitente' => $m->nombre_remitente,
                    'correo_remitente' => $m->correo_remitente,
                    'message_id'       => $m->message_id,
                    'thread_id'        => $m->thread_id,
                    'es_correo'        => $m->es_correo,
                    'adjuntos'         => $m->adjuntos,
                    'created_at'       => $m->created_at->format('d/m/Y H:i:s'),
                    'leido'            => $m->leido,
                ]);

            return response()->json(['success' => true, 'messages' => $messages]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo mensajes del chat: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error obteniendo mensajes: ' . $e->getMessage()], 500);
        }
    }

    // Verifica si hay mensajes nuevos comparando el último ID conocido
    public function verificarMensajesNuevos(Request $request)
    {
        try {
            $ticketId        = $request->input('ticket_id');
            $ultimoMensajeId = $request->input('ultimo_mensaje_id', 0);

            if (!$ticketId) {
                return response()->json(['success' => false, 'message' => 'Ticket ID es requerido'], 400);
            }

            $ultimoMensaje = TicketChat::where('ticket_id', $ticketId)->orderBy('id', 'desc')->first();

            if (!$ultimoMensaje) {
                return response()->json(['success' => true, 'tiene_nuevos' => false, 'ultimo_mensaje_id' => 0]);
            }

            return response()->json([
                'success'          => true,
                'tiene_nuevos'     => $ultimoMensaje->id > (int)$ultimoMensajeId,
                'ultimo_mensaje_id' => $ultimoMensaje->id,
                'total_mensajes'   => TicketChat::where('ticket_id', $ticketId)->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error verificando mensajes nuevos: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error verificando mensajes: ' . $e->getMessage()], 500);
        }
    }

    // Envía respuesta al usuario por correo con soporte de adjuntos e imágenes embebidas
    public function enviarRespuesta(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            $mensaje  = $request->input('mensaje');
            $adjuntos = $request->file('adjuntos', []);

            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket no encontrado'], 404);
            }

            $adjuntosProcesados = [];
            if (!empty($adjuntos)) {
                foreach ($adjuntos as $adjunto) {
                    $fileName    = uniqid() . '_' . $adjunto->getClientOriginalName();
                    $path        = $adjunto->storeAs('tickets/adjuntos', $fileName, 'public');
                    $storagePath = storage_path('app/public/' . $path);

                    $adjuntosProcesados[] = [
                        'name'         => $adjunto->getClientOriginalName(),
                        'path'         => $storagePath,
                        'storage_path' => $path,
                        'url'          => asset('storage/' . $path),
                        'size'         => $adjunto->getSize(),
                        'mime_type'    => $adjunto->getMimeType(),
                        'tipo'         => 'archivo',
                    ];
                }
            }

            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $mensaje, $matches);

            $mensajeParaCorreo = $mensaje;

            if (!empty($matches[1])) {
                foreach (array_unique($matches[1]) as $urlImagen) {
                    $nombreArchivo = basename(parse_url($urlImagen, PHP_URL_PATH));
                    $rutaRelativa  = 'tickets/adjuntos/' . $nombreArchivo;
                    $rutaAbsoluta  = \Illuminate\Support\Facades\Storage::disk('public')->path($rutaRelativa);

                    Log::info("Buscando imagen en disco: {$rutaAbsoluta}");

                    if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($rutaRelativa)) {
                        Log::warning("Imagen no encontrada: {$rutaRelativa}");
                        continue;
                    }

                    $yaExiste = collect($adjuntosProcesados)
                        ->contains(fn($a) => basename($a['storage_path'] ?? '') === $nombreArchivo);

                    if (!$yaExiste) {
                        $adjuntosProcesados[] = [
                            'name'         => $nombreArchivo,
                            'path'         => $rutaAbsoluta,
                            'storage_path' => $rutaRelativa,
                            'url'          => asset('storage/' . $rutaRelativa),
                            'size'         => \Illuminate\Support\Facades\Storage::disk('public')->size($rutaRelativa),
                            'mime_type'    => \Illuminate\Support\Facades\Storage::disk('public')->mimeType($rutaRelativa),
                            'tipo'         => 'imagen_embebida',
                        ];
                    }

                    $contenidoArchivo  = \Illuminate\Support\Facades\Storage::disk('public')->get($rutaRelativa);
                    $mimeType          = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($rutaRelativa);
                    $dataUri           = 'data:' . $mimeType . ';base64,' . base64_encode($contenidoArchivo);
                    $mensajeParaCorreo = str_replace($urlImagen, $dataUri, $mensajeParaCorreo);

                    Log::info("Imagen convertida a base64 para correo: {$nombreArchivo}");
                }
            }

            $hybridService = new \App\Services\HybridEmailService();
            $resultado     = $hybridService->enviarRespuestaConInstrucciones(
                $ticketId,
                $mensaje,
                $adjuntosProcesados,
                $mensajeParaCorreo
            );

            if ($resultado) {
                return response()->json(['success' => true, 'message' => 'Respuesta enviada exitosamente']);
            }

            return response()->json(['success' => false, 'message' => 'Error enviando respuesta por correo'], 500);
        } catch (\Exception $e) {
            Log::error("Error enviando respuesta: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error enviando respuesta: ' . $e->getMessage()], 500);
        }
    }

    // Agrega un mensaje interno al chat sin enviarlo por correo
    public function agregarMensajeInterno(Request $request)
    {
        try {
            $ticketId  = $request->input('ticket_id');
            $mensaje   = $request->input('mensaje');
            $remitente = $request->input('remitente', 'soporte');

            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket no encontrado'], 404);
            }

            $chatMessage = TicketChat::create([
                'ticket_id'        => $ticketId,
                'mensaje'          => $mensaje,
                'remitente'        => $remitente,
                'nombre_remitente' => auth()->user()->name  ?? 'Soporte TI',
                'correo_remitente' => auth()->user()->email ?? config('mail.from.address'),
                'es_correo'        => false,
                'leido'            => false,
            ]);

            return response()->json([
                'success'      => true,
                'message'      => 'Mensaje agregado exitosamente',
                'chat_message' => [
                    'id'               => $chatMessage->id,
                    'mensaje'          => $chatMessage->mensaje,
                    'remitente'        => $chatMessage->remitente,
                    'nombre_remitente' => $chatMessage->nombre_remitente,
                    'created_at'       => $chatMessage->created_at->format('d/m/Y H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error agregando mensaje interno: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error agregando mensaje: ' . $e->getMessage()], 500);
        }
    }

    // Marca como leídos todos los mensajes no leídos de un ticket
    public function marcarMensajesComoLeidos(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');

            TicketChat::where('ticket_id', $ticketId)->where('leido', false)->update(['leido' => true]);

            return response()->json(['success' => true, 'message' => 'Mensajes marcados como leídos']);
        } catch (\Exception $e) {
            Log::error("Error marcando mensajes como leídos: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error marcando mensajes: ' . $e->getMessage()], 500);
        }
    }

    // Retorna todos los tipos de ticket ordenados por nombre
    public function getTipos()
    {
        try {
            $tipos = Tipoticket::select('TipoID', 'NombreTipo')->orderBy('NombreTipo')->get();
            return response()->json(['success' => true, 'tipos' => $tipos]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo tipos: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error obteniendo tipos: ' . $e->getMessage()], 500);
        }
    }

    // Retorna subtipos filtrados por tipo
    public function getSubtiposByTipo(Request $request)
    {
        try {
            $tipoId = $request->input('tipo_id');
            if (!$tipoId) {
                return response()->json(['success' => false, 'message' => 'ID de tipo requerido'], 400);
            }

            $subtipos = Subtipos::select('SubtipoID', 'NombreSubtipo', 'TipoID')
                ->where('TipoID', $tipoId)->orderBy('NombreSubtipo')->get();

            return response()->json(['success' => true, 'subtipos' => $subtipos]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo subtipos por tipo: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error obteniendo subtipos: ' . $e->getMessage()], 500);
        }
    }

    // Retorna tertipos filtrados por subtipo
    public function getTertiposBySubtipo(Request $request)
    {
        try {
            $subtipoId = $request->input('subtipo_id');
            if (!$subtipoId) {
                return response()->json(['success' => false, 'message' => 'ID de subtipo requerido'], 400);
            }

            $tertipos = Tertipos::select('TertipoID', 'NombreTertipo', 'SubtipoID')
                ->where('SubtipoID', $subtipoId)->orderBy('NombreTertipo')->get();

            return response()->json(['success' => true, 'tertipos' => $tertipos]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo tertipos por subtipo: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error obteniendo tertipos: ' . $e->getMessage()], 500);
        }
    }

    // Sincroniza correos entrantes vía IMAP y recarga los mensajes del ticket
    public function sincronizarCorreos(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            if (!$ticketId) {
                return response()->json(['success' => false, 'message' => 'ID de ticket requerido'], 400);
            }

            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket no encontrado'], 404);
            }

            $imapService = new \App\Services\ImapEmailReceiver();
            $resultado   = $imapService->procesarCorreosEntrantes();

            if (!$resultado) {
                return response()->json(['success' => false, 'message' => 'Error sincronizando correos'], 500);
            }

            $mensajes = TicketChat::where('ticket_id', $ticketId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($m) => [
                    'id'               => $m->id,
                    'mensaje'          => $m->mensaje,
                    'remitente'        => $m->remitente,
                    'nombre_remitente' => $m->nombre_remitente,
                    'correo_remitente' => $m->correo_remitente,
                    'message_id'       => $m->message_id,
                    'thread_id'        => $m->thread_id,
                    'es_correo'        => $m->es_correo,
                    'adjuntos'         => $m->adjuntos,
                    'created_at'       => $m->created_at->format('d/m/Y H:i:s'),
                    'leido'            => $m->leido,
                ]);

            return response()->json([
                'success'        => true,
                'message'        => 'Correos sincronizados exitosamente',
                'mensajes'       => $mensajes,
                'total_mensajes' => $mensajes->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error sincronizando correos: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error sincronizando correos: ' . $e->getMessage()], 500);
        }
    }

    // Retorna tiempo transcurrido y estimado de tickets en progreso para actualización en tiempo real
    public function obtenerTiempoProgreso(Request $request)
    {
        try {
            $ticketsEnProgreso = Tickets::with(['tipoticket', 'responsableTI'])
                ->where('Estatus', 'En progreso')
                ->whereNotNull('FechaInicioProgreso')
                ->get();

            $tiempos = [];
            foreach ($ticketsEnProgreso as $ticket) {
                $tiempoInfo = null;
                if ($ticket->tipoticket && $ticket->tipoticket->TiempoEstimadoMinutos) {
                    $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;
                    $tiempoTranscurrido  = $ticket->tiempo_respuesta ?? 0;
                    $porcentajeUsado     = $tiempoEstimadoHoras > 0 ? ($tiempoTranscurrido / $tiempoEstimadoHoras) * 100 : 0;

                    $tiempoInfo = [
                        'transcurrido' => round($tiempoTranscurrido, 1),
                        'estimado'     => round($tiempoEstimadoHoras, 1),
                        'porcentaje'   => round($porcentajeUsado, 1),
                        'estado'       => $porcentajeUsado >= 100 ? 'agotado' : ($porcentajeUsado >= 80 ? 'por_vencer' : 'normal'),
                    ];
                }
                $tiempos[$ticket->TicketID] = $tiempoInfo;
            }

            return response()->json(['success' => true, 'tiempos' => $tiempos]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo tiempo de progreso: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error obteniendo información de tiempo'], 500);
        }
    }

    // Retorna estadísticas de correos enviados, recibidos y no leídos de un ticket
    public function obtenerEstadisticasCorreos(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            if (!$ticketId) {
                return response()->json(['success' => false, 'message' => 'ID de ticket requerido'], 400);
            }

            return response()->json([
                'success'      => true,
                'estadisticas' => [
                    'correos_enviados'  => TicketChat::where('ticket_id', $ticketId)->where('es_correo', true)->where('remitente', 'soporte')->count(),
                    'correos_recibidos' => TicketChat::where('ticket_id', $ticketId)->where('es_correo', true)->where('remitente', 'usuario')->count(),
                    'correos_no_leidos' => TicketChat::where('ticket_id', $ticketId)->where('es_correo', true)->where('leido', false)->count(),
                    'total_correos'     => TicketChat::where('ticket_id', $ticketId)->where('es_correo', true)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo estadísticas de correos: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error obteniendo estadísticas: ' . $e->getMessage()], 500);
        }
    }

    // Diagnostica la configuración SMTP/IMAP y verifica mensajes en BD para un ticket
    public function diagnosticarCorreos(Request $request)
    {
        try {
            $diagnostico = [
                'smtp' => [
                    'host'       => config('mail.mailers.smtp.host'),
                    'port'       => config('mail.mailers.smtp.port'),
                    'username'   => config('mail.mailers.smtp.username'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                ],
                'imap' => [
                    'host'       => config('mail.imap.host', 'proser.com.mx'),
                    'port'       => config('mail.imap.port', 993),
                    'encryption' => config('mail.imap.encryption', 'ssl'),
                    'username'   => config('mail.mailers.smtp.username'),
                    'servidor'   => 'proser.com.mx (Personalizado)',
                ],
            ];

            try {
                $imapService = new \App\Services\ImapEmailReceiver();
                $connection  = $imapService->conectarIMAP();

                if ($connection) {
                    $diagnostico['imap_connection'] = 'success';
                    $emails = imap_search($connection, 'UNSEEN');
                    $diagnostico['correos_no_leidos'] = $emails ? count($emails) : 0;
                    imap_close($connection);
                } else {
                    $diagnostico['imap_connection'] = 'failed';
                    $diagnostico['imap_error']      = imap_last_error();
                }
            } catch (\Exception $e) {
                $diagnostico['imap_connection'] = 'error: ' . $e->getMessage();
            }

            $ticketId = $request->input('ticket_id');
            if ($ticketId) {
                $mensajes = TicketChat::where('ticket_id', $ticketId)->get();
                $diagnostico['mensajes_bd'] = [
                    'total'     => $mensajes->count(),
                    'enviados'  => $mensajes->where('remitente', 'soporte')->count(),
                    'recibidos' => $mensajes->where('remitente', 'usuario')->count(),
                    'correos'   => $mensajes->where('es_correo', true)->count(),
                ];
            }

            return response()->json(['success' => true, 'diagnostico' => $diagnostico]);
        } catch (\Exception $e) {
            Log::error("Error en diagnóstico: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error en diagnóstico: ' . $e->getMessage()], 500);
        }
    }

    // Agrega una respuesta simulando un correo recibido del usuario
    public function agregarRespuestaManual(Request $request)
    {
        try {
            $ticketId     = $request->input('ticket_id');
            $mensaje      = $request->input('mensaje');
            $nombreEmisor = $request->input('nombre_emisor');
            $correoEmisor = $request->input('correo_emisor');

            if (!$ticketId || !$mensaje) {
                return response()->json(['success' => false, 'message' => 'Ticket ID y mensaje son requeridos'], 400);
            }

            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Ticket no encontrado'], 404);
            }

            $hybridService = new \App\Services\HybridEmailService();
            $resultado = $hybridService->procesarRespuestaManual($ticketId, [
                'mensaje' => $mensaje,
                'nombre'  => $nombreEmisor,
                'correo'  => $correoEmisor,
            ]);

            if (!$resultado) {
                return response()->json(['success' => false, 'message' => 'Error procesando respuesta manual'], 500);
            }

            $mensajes = TicketChat::where('ticket_id', $ticketId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($m) => [
                    'id'               => $m->id,
                    'mensaje'          => $m->mensaje,
                    'remitente'        => $m->remitente,
                    'nombre_remitente' => $m->nombre_remitente,
                    'correo_remitente' => $m->correo_remitente,
                    'message_id'       => $m->message_id,
                    'thread_id'        => $m->thread_id,
                    'es_correo'        => $m->es_correo,
                    'adjuntos'         => $m->adjuntos,
                    'created_at'       => $m->created_at->format('d/m/Y H:i:s'),
                    'leido'            => $m->leido,
                ]);

            return response()->json(['success' => true, 'message' => 'Respuesta agregada exitosamente', 'mensajes' => $mensajes]);
        } catch (\Exception $e) {
            Log::error("Error agregando respuesta manual: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error agregando respuesta: ' . $e->getMessage()], 500);
        }
    }

    // Envía correo de instrucciones al usuario para que responda al ticket
    public function enviarInstruccionesRespuesta(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            if (!$ticketId) {
                return response()->json(['success' => false, 'message' => 'Ticket ID requerido'], 400);
            }

            $hybridService = new \App\Services\HybridEmailService();
            $instrucciones = 'Por favor, responde a este correo para continuar la conversación sobre tu ticket. Tu respuesta será procesada automáticamente.';
            $resultado     = $hybridService->enviarRespuestaConInstrucciones($ticketId, $instrucciones);

            if ($resultado) {
                return response()->json(['success' => true, 'message' => 'Instrucciones de respuesta enviadas por correo']);
            }

            return response()->json(['success' => false, 'message' => 'Error enviando instrucciones'], 500);
        } catch (\Exception $e) {
            Log::error("Error enviando instrucciones: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error enviando instrucciones: ' . $e->getMessage()], 500);
        }
    }

    // Retorna tipos de ticket con su tiempo estimado configurado
    public function getTiposConMetricas()
    {
        try {
            $tipos = Tipoticket::select('TipoID', 'NombreTipo', 'TiempoEstimadoMinutos')
                ->orderBy('NombreTipo')
                ->get()
                ->map(fn($t) => [
                    'TipoID'                 => $t->TipoID,
                    'NombreTipo'             => $t->NombreTipo,
                    'TiempoEstimadoMinutos'  => $t->TiempoEstimadoMinutos,
                ]);

            return response()->json(['success' => true, 'tipos' => $tipos]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo tipos con métricas: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error obteniendo tipos: ' . $e->getMessage()], 500);
        }
    }

    // Actualiza el tiempo estimado de un tipo de ticket y recalcula fechas de notificación
    public function actualizarTiempoEstimado(Request $request)
    {
        try {
            $request->validate([
                'tipo_id'                    => 'required|integer|exists:tipotickets,TipoID',
                'tiempo_estimado_minutos'    => 'nullable|integer|min:0',
            ]);

            $tipo = Tipoticket::where('TipoID', $request->input('tipo_id'))->first();
            if (!$tipo) {
                return response()->json(['success' => false, 'message' => 'Tipo de ticket no encontrado'], 404);
            }

            $tiempoAnterior = $tipo->TiempoEstimadoMinutos;
            $nuevoTiempo    = $request->input('tiempo_estimado_minutos');

            $tipo->TiempoEstimadoMinutos = $nuevoTiempo;
            $tipo->save();

            if ($tiempoAnterior != $nuevoTiempo) {
                $notificationService = new \App\Services\TicketNotificationService();
                $ticketsActualizados = $notificationService->recalcularFechasNotificacionPorTipo($tipo->TipoID, $nuevoTiempo);
                Log::info("Tipo {$tipo->TipoID}: Intervalo actualizado de {$tiempoAnterior} a {$nuevoTiempo} minutos. {$ticketsActualizados} tickets actualizados.");
            }

            return response()->json([
                'success' => true,
                'message' => 'Tiempo estimado actualizado correctamente',
                'tipo'    => [
                    'TipoID'                => $tipo->TipoID,
                    'NombreTipo'            => $tipo->NombreTipo,
                    'TiempoEstimadoMinutos' => $tipo->TiempoEstimadoMinutos,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error actualizando tiempo estimado: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error actualizando tiempo estimado: ' . $e->getMessage()], 500);
        }
    }

    // Actualiza el tiempo estimado de múltiples tipos de ticket en una sola petición
    public function actualizarMetricasMasivo(Request $request)
    {
        try {
            $request->validate([
                'metricas'                              => 'required|array',
                'metricas.*.tipo_id'                   => 'required|integer|exists:tipotickets,TipoID',
                'metricas.*.tiempo_estimado_minutos'   => 'nullable|integer|min:0',
            ]);

            $actualizados = 0;
            $errores      = [];

            foreach ($request->input('metricas') as $metrica) {
                try {
                    $tipoId       = $metrica['tipo_id'];
                    $tiempoEstimado = isset($metrica['tiempo_estimado_minutos']) && $metrica['tiempo_estimado_minutos'] !== ''
                        ? (int)$metrica['tiempo_estimado_minutos']
                        : null;

                    $tipo = Tipoticket::where('TipoID', $tipoId)->first();
                    if (!$tipo) {
                        $errores[] = ['tipo_id' => $tipoId, 'error' => 'Tipo de ticket no encontrado'];
                        continue;
                    }

                    $tiempoAnterior = $tipo->TiempoEstimadoMinutos;
                    Tipoticket::where('TipoID', $tipoId)->update(['TiempoEstimadoMinutos' => $tiempoEstimado]);

                    if ($tiempoAnterior != $tiempoEstimado) {
                        $notificationService = new \App\Services\TicketNotificationService();
                        $ticketsActualizados = $notificationService->recalcularFechasNotificacionPorTipo($tipoId, $tiempoEstimado);
                        Log::info("Tipo {$tipoId}: Intervalo actualizado de {$tiempoAnterior} a {$tiempoEstimado} minutos. {$ticketsActualizados} tickets actualizados.");
                    }

                    $actualizados++;
                } catch (\Exception $e) {
                    Log::error("Error actualizando tipo {$metrica['tipo_id']}: " . $e->getMessage());
                    $errores[] = ['tipo_id' => $metrica['tipo_id'], 'error' => $e->getMessage()];
                }
            }

            return response()->json([
                'success'     => true,
                'message'     => "Se actualizaron {$actualizados} tipos de tickets",
                'actualizados' => $actualizados,
                'errores'     => $errores,
            ]);
        } catch (\Exception $e) {
            Log::error("Error actualizando métricas masivas: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error actualizando métricas: ' . $e->getMessage()], 500);
        }
    }

    // Retorna métricas de productividad en JSON para actualización dinámica del dashboard
    public function obtenerProductividadAjax(Request $request)
    {
        $mes  = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        $tickets = Tickets::with(['empleado', 'responsableTI', 'chat' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(1);
        }])->orderBy('created_at', 'desc')->get();

        $metricasProductividad = $this->obtenerMetricasProductividad($tickets, $mes, $anio);
        $metricasSolicitudes = $this->calcularMetricasSolicitudes($mes, $anio);

        $html = view('tickets.productividad', [
            'metricasProductividad' => $metricasProductividad,
            'mes'                   => $mes,
            'metricasSolicitudes'   => $metricasSolicitudes,
            'anio'                  => $anio,
        ])->render();

        return response()->json(['success' => true, 'html' => $html, 'mes' => $mes, 'anio' => $anio]);
    }

    // Muestra la vista del reporte mensual con tickets del mes seleccionado
    public function reporteMensual(Request $request)
    {
        $mes  = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        $fechaInicio = \Carbon\Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin    = \Carbon\Carbon::create($anio, $mes, 1)->endOfMonth();

        $tickets = Tickets::with([
            'empleado.puestos.departamentos.gerencia',
            'empleado.gerencia',
            'responsableTI.gerencia',
            'tipoticket',
            'subtipo',
            'tertipo',
        ])->whereBetween('created_at', [$fechaInicio, $fechaFin])->get();

        $resumen = $this->calcularResumenMensual($tickets, $fechaInicio, $fechaFin);

        return view('tickets.reporte-mensual', [
            'tickets'      => $tickets,
            'resumen'      => $resumen,
            'mes'          => $mes,
            'anio'         => $anio,
            'fechaInicio'  => $fechaInicio,
            'fechaFin'     => $fechaFin,
        ]);
    }

    // Genera y descarga el reporte mensual en Excel con datos de dos meses
    public function exportarReporteMensualExcel(Request $request)
    {
        $mes  = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        $fechaInicioActual  = \Carbon\Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFinActual     = \Carbon\Carbon::create($anio, $mes, 1)->endOfMonth();
        $fechaInicioAnterior = \Carbon\Carbon::create($anio, $mes, 1)->subMonth()->startOfMonth();

        $ticketsDosMeses = Tickets::with([
            'empleado.puestos.departamentos.gerencia',
            'empleado.gerencia',
            'responsableTI.gerencia',
            'tipoticket',
            'subtipo',
            'tertipo',
        ])->whereBetween('created_at', [$fechaInicioAnterior, $fechaFinActual])->get();

        $ticketsMesActual = $ticketsDosMeses->filter(
            fn($t) => $t->created_at->between($fechaInicioActual, $fechaFinActual)
        );

        $todosTipos    = \App\Models\Tipoticket::all();
        $todosSubtipos = \App\Models\Subtipos::all();
        $todosTertipos = \App\Models\Tertipos::all();

        $catalogo = [];
        foreach ($todosTipos as $tipo) {
            $catalogo[$tipo->NombreTipo] = [];
            foreach ($todosSubtipos->where('TipoID', $tipo->TipoID) as $sub) {
                foreach ($todosTertipos->where('SubtipoID', $sub->SubtipoID) as $ter) {
                    if (!in_array($ter->NombreTertipo, $catalogo[$tipo->NombreTipo])) {
                        $catalogo[$tipo->NombreTipo][] = $ter->NombreTertipo;
                    }
                }
            }
            sort($catalogo[$tipo->NombreTipo]);
        }
        ksort($catalogo);

        $resumen             = $this->calcularResumenMensual($ticketsMesActual, $fechaInicioActual, $fechaFinActual);
        $tiempoPorEmpleado   = $this->calcularTiempoResolucionPorEmpleado($ticketsMesActual);
        $tiempoPorCategoria  = $this->calcularTiempoPorCategoriaResponsable($ticketsMesActual);
        
        // Calcular métricas de solicitudes del mes actual
        $metricasSolicitudes = $this->calcularMetricasSolicitudes($mes, $anio);
        $solicitudesMesActual = $metricasSolicitudes['desglose'] ?? [];
        
        $nombreArchivo       = 'reporte_tickets_' . date('d-m-Y-H-i') . '.xlsx';

        return Excel::download(
            new \App\Exports\ReporteMensualTicketsExport(
                $ticketsDosMeses,
                $resumen,
                $tiempoPorEmpleado,
                $tiempoPorCategoria,
                $mes,
                $anio,
                $ticketsMesActual,
                $catalogo,
                $solicitudesMesActual,
                $metricasSolicitudes
            ),
            $nombreArchivo
        );
    }

    // Calcula resumen de incidencias, tiempos promedio y totales por empleado del mes
    private function calcularResumenMensual($tickets, $fechaInicio, $fechaFin)
    {
        $incidenciasPorGerencia = [];
        foreach ($tickets as $ticket) {
            $gerenciaNombre = 'Sin gerencia';
            if ($ticket->empleado && $ticket->empleado->gerencia) {
                $gerenciaNombre = $ticket->empleado->gerencia->NombreGerencia ?? 'Sin gerencia';
            }

            if (!isset($incidenciasPorGerencia[$gerenciaNombre])) {
                $incidenciasPorGerencia[$gerenciaNombre] = [
                    'gerencia'         => $gerenciaNombre,
                    'total'            => 0,
                    'resueltos'        => 0,
                    'en_progreso'      => 0,
                    'pendientes'       => 0,
                    'problemas'        => 0,
                    'servicios'        => 0,
                    'por_responsable'  => [],
                ];
            }

            $incidenciasPorGerencia[$gerenciaNombre]['total']++;
            if ($ticket->Clasificacion === 'Problema')  $incidenciasPorGerencia[$gerenciaNombre]['problemas']++;
            elseif ($ticket->Clasificacion === 'Servicio') $incidenciasPorGerencia[$gerenciaNombre]['servicios']++;

            if ($ticket->Estatus === 'Cerrado') {
                $incidenciasPorGerencia[$gerenciaNombre]['resueltos']++;
                $responsableNombre = $ticket->responsableTI ? $ticket->responsableTI->NombreEmpleado : 'Sin responsable';
                $incidenciasPorGerencia[$gerenciaNombre]['por_responsable'][$responsableNombre] =
                    ($incidenciasPorGerencia[$gerenciaNombre]['por_responsable'][$responsableNombre] ?? 0) + 1;
            } elseif ($ticket->Estatus === 'En progreso') {
                $incidenciasPorGerencia[$gerenciaNombre]['en_progreso']++;
            } else {
                $incidenciasPorGerencia[$gerenciaNombre]['pendientes']++;
            }
        }

        $ticketsConRespuesta  = $tickets->filter(fn($t) => $t->FechaInicioProgreso && $t->tiempo_respuesta !== null);
        $ticketsConResolucion = $tickets->filter(fn($t) => $t->FechaInicioProgreso && $t->FechaFinProgreso && $t->tiempo_resolucion !== null);

        $promedioRespuesta  = $ticketsConRespuesta->count() > 0 ? $ticketsConRespuesta->avg(fn($t) => $t->tiempo_respuesta ?? 0) : 0;
        $promedioResolucion = $ticketsConResolucion->count() > 0 ? $ticketsConResolucion->avg(fn($t) => $t->tiempo_resolucion ?? 0) : 0;

        $ticketsCerrados      = $tickets->where('Estatus', 'Cerrado')->count();
        $porcentajeCumplimiento = $tickets->count() > 0 ? round(($ticketsCerrados / $tickets->count()) * 100, 2) : 0;

        $totalesPorEmpleado = [];
        foreach ($tickets as $ticket) {
            $empleadoNombre = $ticket->responsableTI ? $ticket->responsableTI->NombreEmpleado : 'Sin empleado';

            if (!isset($totalesPorEmpleado[$empleadoNombre])) {
                $totalesPorEmpleado[$empleadoNombre] = [
                    'empleado'    => $empleadoNombre,
                    'total'       => 0,
                    'cerrados'    => 0,
                    'en_progreso' => 0,
                    'pendientes'  => 0,
                    'problemas'   => 0,
                    'servicios'   => 0,
                ];
            }

            $totalesPorEmpleado[$empleadoNombre]['total']++;
            if ($ticket->Clasificacion === 'Problema')  $totalesPorEmpleado[$empleadoNombre]['problemas']++;
            elseif ($ticket->Clasificacion === 'Servicio') $totalesPorEmpleado[$empleadoNombre]['servicios']++;

            if ($ticket->Estatus === 'Cerrado')          $totalesPorEmpleado[$empleadoNombre]['cerrados']++;
            elseif ($ticket->Estatus === 'En progreso')  $totalesPorEmpleado[$empleadoNombre]['en_progreso']++;
            else                                          $totalesPorEmpleado[$empleadoNombre]['pendientes']++;
        }

        $ticketsPorGerenciaResponsable = [];
        foreach ($tickets as $ticket) {
            $gerenciaNombre = 'Sin gerencia';
            if ($ticket->empleado) {
                if ($ticket->empleado->puestos && $ticket->empleado->puestos->departamentos && $ticket->empleado->puestos->departamentos->gerencia) {
                    $gerenciaNombre = $ticket->empleado->puestos->departamentos->gerencia->NombreGerencia ?? 'Sin gerencia';
                } elseif ($ticket->empleado->gerencia) {
                    $gerenciaNombre = $ticket->empleado->gerencia->NombreGerencia ?? 'Sin gerencia';
                }
            }

            $responsableNombre = $ticket->responsableTI ? $ticket->responsableTI->NombreEmpleado : 'Sin responsable';
            $key               = $gerenciaNombre . '|' . $responsableNombre;

            if (!isset($ticketsPorGerenciaResponsable[$key])) {
                $ticketsPorGerenciaResponsable[$key] = [
                    'gerencia'    => $gerenciaNombre,
                    'responsable' => $responsableNombre,
                    'total'       => 0,
                    'cerrados'    => 0,
                    'en_progreso' => 0,
                    'pendientes'  => 0,
                    'problemas'   => 0,
                    'servicios'   => 0,
                ];
            }

            $ticketsPorGerenciaResponsable[$key]['total']++;
            if ($ticket->Clasificacion === 'Problema')  $ticketsPorGerenciaResponsable[$key]['problemas']++;
            elseif ($ticket->Clasificacion === 'Servicio') $ticketsPorGerenciaResponsable[$key]['servicios']++;

            if ($ticket->Estatus === 'Cerrado')          $ticketsPorGerenciaResponsable[$key]['cerrados']++;
            elseif ($ticket->Estatus === 'En progreso')  $ticketsPorGerenciaResponsable[$key]['en_progreso']++;
            else                                          $ticketsPorGerenciaResponsable[$key]['pendientes']++;
        }

        usort($ticketsPorGerenciaResponsable, function ($a, $b) {
            $cmp = strcmp($a['gerencia'], $b['gerencia']);
            return $cmp !== 0 ? $cmp : strcmp($a['responsable'], $b['responsable']);
        });

        return [
            'incidencias_por_gerencia'          => $incidenciasPorGerencia,
            'promedio_tiempo_respuesta'         => round($promedioRespuesta, 2),
            'promedio_tiempo_resolucion'        => round($promedioResolucion, 2),
            'porcentaje_cumplimiento'           => $porcentajeCumplimiento,
            'totales_por_empleado'              => array_values($totalesPorEmpleado),
            'tickets_por_gerencia_responsable'  => $ticketsPorGerenciaResponsable,
            'total_tickets'                     => $tickets->count(),
            'tickets_cerrados'                  => $ticketsCerrados,
        ];
    }

    // Calcula tiempo de resolución por par responsable-empleado para tickets cerrados
    private function calcularTiempoResolucionPorEmpleado($tickets)
    {
        $datos    = [];
        $agrupados = [];

        $ticketsCerrados = $tickets->filter(
            fn($t) => $t->Estatus === 'Cerrado'
                && $t->FechaInicioProgreso && $t->FechaFinProgreso
                && $t->tiempo_resolucion !== null
                && $t->responsableTI && $t->empleado
        );

        foreach ($ticketsCerrados as $ticket) {
            $responsableNombre = $ticket->responsableTI->NombreEmpleado ?? 'Sin responsable';
            $empleadoNombre    = $ticket->empleado->NombreEmpleado ?? 'Sin empleado';

            $agrupados[$responsableNombre][$empleadoNombre]['tickets'][] = $ticket;
            $agrupados[$responsableNombre][$empleadoNombre]['tiempos'][] = $ticket->tiempo_resolucion ?? 0;
        }

        foreach ($agrupados as $responsableNombre => $empleados) {
            foreach ($empleados as $empleadoNombre => $datosEmpleado) {
                $tiempos      = $datosEmpleado['tiempos'];
                $totalTickets = count($tiempos);
                if ($totalTickets === 0) continue;

                $datos[] = [
                    'responsable'     => $responsableNombre,
                    'empleado'        => $empleadoNombre,
                    'total_tickets'   => $totalTickets,
                    'tiempo_promedio' => round(array_sum($tiempos) / $totalTickets, 2),
                    'tiempo_minimo'   => round(min($tiempos), 2),
                    'tiempo_maximo'   => round(max($tiempos), 2),
                    'tiempo_total'    => round(array_sum($tiempos), 2),
                ];
            }
        }

        usort($datos, function ($a, $b) {
            $cmp = strcmp($a['responsable'], $b['responsable']);
            return $cmp !== 0 ? $cmp : strcmp($a['empleado'], $b['empleado']);
        });

        return $datos;
    }

    // Calcula tiempo de resolución agrupado por categoría (tipo/subtipo/tertipo) y responsable
    private function calcularTiempoPorCategoriaResponsable($tickets)
    {
        $datos     = [];
        $agrupados = [];

        $ticketsCerrados = $tickets->filter(
            fn($t) => $t->Estatus === 'Cerrado'
                && $t->FechaInicioProgreso && $t->FechaFinProgreso
                && $t->tiempo_resolucion !== null
                && $t->responsableTI
        );

        foreach ($ticketsCerrados as $ticket) {
            $tipoNombre    = $ticket->tipoticket ? $ticket->tipoticket->NombreTipo   : 'Sin tipo';
            $subtipoNombre = $ticket->subtipo    ? $ticket->subtipo->NombreSubtipo   : 'Sin subtipo';
            $tertipoNombre = $ticket->tertipo    ? $ticket->tertipo->NombreTertipo   : 'Sin tertipo';

            $tipoID    = $ticket->TipoID    ?? 'null';
            $subtipoID = $ticket->SubtipoID ?? 'null';
            $tertipoID = $ticket->TertipoID ?? 'null';

            $claveCategoria    = $tipoID . '_' . $subtipoID . '_' . $tertipoID;
            $responsableNombre = $ticket->responsableTI->NombreEmpleado ?? 'Sin responsable';

            if (!isset($agrupados[$claveCategoria][$responsableNombre])) {
                $agrupados[$claveCategoria][$responsableNombre] = [
                    'tipo_id'        => $tipoID,
                    'tipo_nombre'    => $tipoNombre,
                    'subtipo_id'     => $subtipoID,
                    'subtipo_nombre' => $subtipoNombre,
                    'tertipo_id'     => $tertipoID,
                    'tertipo_nombre' => $tertipoNombre,
                    'responsable'    => $responsableNombre,
                    'tiempos'        => [],
                ];
            }

            $agrupados[$claveCategoria][$responsableNombre]['tiempos'][] = $ticket->tiempo_resolucion ?? 0;
        }

        foreach ($agrupados as $claveCategoria => $responsables) {
            foreach ($responsables as $responsableNombre => $datosResponsable) {
                $tiempos      = $datosResponsable['tiempos'];
                $totalTickets = count($tiempos);
                if ($totalTickets === 0) continue;

                $datos[] = array_merge(
                    array_diff_key($datosResponsable, ['tiempos' => null]),
                    [
                        'total_tickets'   => $totalTickets,
                        'tiempo_promedio' => round(array_sum($tiempos) / $totalTickets, 2),
                        'tiempo_minimo'   => round(min($tiempos), 2),
                        'tiempo_maximo'   => round(max($tiempos), 2),
                        'tiempo_total'    => round(array_sum($tiempos), 2),
                    ]
                );
            }
        }

        usort($datos, function ($a, $b) {
            foreach (['tipo_nombre', 'subtipo_nombre', 'tertipo_nombre', 'responsable'] as $campo) {
                $cmp = strcmp($a[$campo], $b[$campo]);
                if ($cmp !== 0) return $cmp;
            }
            return 0;
        });

        return $datos;
    }

    // Retorna tickets en progreso que superaron su tiempo estimado, ordenados por exceso
    public function obtenerTicketsExcedidos(Request $request)
    {
        try {
            $tickets = Tickets::with(['tipoticket', 'responsableTI', 'empleado'])
                ->where('Estatus', 'En progreso')
                ->whereNotNull('FechaInicioProgreso')
                ->whereNotNull('TipoID')
                ->get();

            $ticketsExcedidos = [];

            foreach ($tickets as $ticket) {
                if (!$ticket->tipoticket || !$ticket->tipoticket->TiempoEstimadoMinutos) continue;

                $tiempoRespuesta = $ticket->tiempo_respuesta;
                if ($tiempoRespuesta === null) continue;

                $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;

                if ($tiempoRespuesta > $tiempoEstimadoHoras) {
                    $ticketsExcedidos[] = [
                        'id'                => $ticket->TicketID,
                        'descripcion'       => \Illuminate\Support\Str::limit($ticket->Descripcion, 80),
                        'responsable'       => $ticket->responsableTI ? $ticket->responsableTI->NombreEmpleado : 'Sin asignar',
                        'empleado'          => $ticket->empleado ? $ticket->empleado->NombreEmpleado : 'Sin empleado',
                        'prioridad'         => $ticket->Prioridad,
                        'tiempo_estimado'   => round($tiempoEstimadoHoras, 2),
                        'tiempo_respuesta'  => round($tiempoRespuesta, 2),
                        'tiempo_excedido'   => round($tiempoRespuesta - $tiempoEstimadoHoras, 2),
                        'porcentaje_excedido' => round(($tiempoRespuesta / $tiempoEstimadoHoras) * 100, 1),
                        'categoria'         => $ticket->tipoticket->NombreTipo,
                    ];
                }
            }

            usort($ticketsExcedidos, fn($a, $b) => $b['tiempo_excedido'] <=> $a['tiempo_excedido']);

            return response()->json(['success' => true, 'tickets' => $ticketsExcedidos, 'total' => count($ticketsExcedidos)]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo tickets excedidos: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error obteniendo tickets excedidos', 'tickets' => [], 'total' => 0], 500);
        }
    }

    // Almacena imágenes subidas desde el editor TinyMCE y retorna la URL pública
    public function subirImagenTinyMCE(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
            ]);

            $file        = $request->file('file');
            $extension   = $file->getClientOriginalExtension() ?: 'png';
            $nombreUnico = uniqid('img_', true) . '.' . $extension;

            $rutaStorage = $file->storeAs('tickets/adjuntos', $nombreUnico, 'public');

            if (!$rutaStorage) {
                Log::error('TinyMCE upload: storeAs devolvió false');
                return response()->json(['error' => 'No se pudo guardar el archivo'], 500);
            }

            return response()->json(['location' => asset('storage/' . $rutaStorage)]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $error = implode(', ', $e->errors()['file'] ?? ['Archivo no válido']);
            Log::error("TinyMCE upload validación: {$error}");
            return response()->json(['error' => $error], 422);
        } catch (\Exception $e) {
            Log::error('TinyMCE imagen upload error: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }
}
