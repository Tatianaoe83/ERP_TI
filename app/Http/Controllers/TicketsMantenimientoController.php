<?php

namespace App\Http\Controllers;

use App\Models\MantenimientoChat;
use App\Models\TicketMantenimiento;
use App\Services\MantenimientoEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TicketsMantenimientoController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-soporte');
    }

    public function index(Request $request)
    {
        $mes  = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);
        $mesInicio  = $request->has('mes_inicio') ? (int) $request->input('mes_inicio') : null;
        $anioInicio = $request->has('anio_inicio') ? (int) $request->input('anio_inicio') : null;
        $mesFin     = $request->has('mes_fin') ? (int) $request->input('mes_fin') : null;
        $anioFin    = $request->has('anio_fin') ? (int) $request->input('anio_fin') : null;
        $modoRango  = $mesInicio && $anioInicio && $mesFin && $anioFin;

        $tickets = TicketMantenimiento::orderBy('created_at', 'desc')->get();

        $ticketsStatus = TicketMantenimiento::agruparPorColumnas($tickets);

        $metricasProductividad = $this->obtenerMetricasProductividad(
            $tickets,
            $mes,
            $anio,
            $modoRango ? $mesInicio : null,
            $modoRango ? $anioInicio : null,
            $modoRango ? $mesFin : null,
            $modoRango ? $anioFin : null
        );

        return view('tickets-mantenimiento.index', compact(
            'ticketsStatus',
            'metricasProductividad',
            'mes',
            'anio',
            'modoRango',
            'mesInicio',
            'anioInicio',
            'mesFin',
            'anioFin'
        ));
    }

    public function obtenerProductividadAjax(Request $request)
    {
        $mes  = (int) $request->input('mes', now()->month);
        $anio = (int) $request->input('anio', now()->year);
        $esRango    = $request->has('mes_inicio') && $request->has('mes_fin');
        $mesInicio  = $esRango ? (int) $request->input('mes_inicio') : null;
        $anioInicio = $esRango ? (int) $request->input('anio_inicio') : null;
        $mesFin     = $esRango ? (int) $request->input('mes_fin') : null;
        $anioFin    = $esRango ? (int) $request->input('anio_fin') : null;

        $tickets = TicketMantenimiento::orderBy('created_at', 'desc')->get();
        $metricasProductividad = $this->obtenerMetricasProductividad($tickets, $mes, $anio, $mesInicio, $anioInicio, $mesFin, $anioFin);

        $html = view('tickets-mantenimiento.productividad', [
            'metricasProductividad' => $metricasProductividad,
            'mes'                   => $mes,
            'anio'                  => $anio,
            'modoRango'             => $esRango,
            'mesInicio'             => $mesInicio ?? $mes,
            'anioInicio'            => $anioInicio ?? $anio,
            'mesFin'                => $mesFin ?? $mes,
            'anioFin'               => $anioFin ?? $anio,
        ])->render();

        return response()->json(['success' => true, 'html' => $html, 'mes' => $mes, 'anio' => $anio]);
    }

    private function obtenerMetricasProductividad($tickets, $mes = null, $anio = null, $mesInicio = null, $anioInicio = null, $mesFin = null, $anioFin = null): array
    {
        $esRango = $mesInicio !== null && $anioInicio !== null && $mesFin !== null && $anioFin !== null;

        if ($esRango) {
            $fechaInicioMes = \Carbon\Carbon::create($anioInicio, $mesInicio, 1)->startOfMonth();
            $fechaFinMes    = \Carbon\Carbon::create($anioFin, $mesFin, 1)->endOfMonth();
        } else {
            $mes  = $mes ?? now()->month;
            $anio = $anio ?? now()->year;
            $fechaInicioMes = \Carbon\Carbon::create($anio, $mes, 1)->startOfMonth();
            $fechaFinMes    = \Carbon\Carbon::create($anio, $mes, 1)->endOfMonth();
        }

        $delPeriodo = $tickets->filter(
            fn ($t) => \Carbon\Carbon::parse($t->created_at)->between($fechaInicioMes, $fechaFinMes)
        );

        $distribucionEstado = [
            'Pendiente'   => $delPeriodo->where('Estatus', 'Pendiente')->count(),
            'En proceso'  => $delPeriodo->where('Estatus', 'En proceso')->count(),
            'Pausado'     => $delPeriodo->where('Estatus', 'Pausado')->count(),
            'Atendido'    => $delPeriodo->where('Estatus', 'Atendido')->count(),
            'Cancelado'   => $delPeriodo->where('Estatus', 'Cancelado')->count(),
        ];

        $atendidos = $delPeriodo->filter(
            fn ($t) => in_array($t->Estatus, ['Atendido', 'Cancelado'], true) && $t->FechaInicioProgreso && $t->FechaFinProgreso
        );

        $tiempoPromedioResolucion = 0;
        if ($atendidos->count() > 0) {
            $tiempoPromedioResolucion = round($atendidos->avg(fn ($t) => $t->tiempo_resolucion ?? 0), 1);
        }

        $conRespuesta = $delPeriodo->filter(
            fn ($t) => $t->FechaInicioProgreso && $t->tiempo_respuesta !== null
        );

        $tiempoPromedioRespuesta = $conRespuesta->count() > 0
            ? round($conRespuesta->avg(fn ($t) => $t->tiempo_respuesta ?? 0), 1)
            : 0;

        $porCategoria = $delPeriodo->filter(fn ($t) => $t->Categoria)
            ->groupBy('Categoria')->map->count()->sortDesc();

        $porResponsable = $delPeriodo->filter(fn ($t) => $t->Responsable)
            ->groupBy('Responsable')->map(function ($grupo) {
                return [
                    'nombre'      => $grupo->first()->Responsable,
                    'total'       => $grupo->count(),
                    'atendidos'   => $grupo->whereIn('Estatus', ['Atendido', 'Cancelado'])->count(),
                    'en_proceso'  => $grupo->whereIn('Estatus', ['En proceso', 'Pausado'])->count(),
                    'pendientes'  => $grupo->where('Estatus', 'Pendiente')->count(),
                ];
            })->sortByDesc('total')->values();

        $porPrioridad = $delPeriodo->groupBy('Prioridad')->map->count();
        $porArea = $delPeriodo->filter(fn ($t) => $t->AreaDepartamento)
            ->groupBy('AreaDepartamento')->map->count()->sortDesc()->take(10);

        $resueltosPorDia = [];
        $creadosPorDia   = [];
        $diaIter = $fechaInicioMes->copy();
        while ($diaIter->lte($fechaFinMes)) {
            $fecha = $diaIter->format('Y-m-d');
            $resueltosPorDia[$fecha] = $delPeriodo->filter(
                fn ($t) => in_array($t->Estatus, ['Atendido', 'Cancelado'], true)
                    && $t->FechaFinProgreso
                    && \Carbon\Carbon::parse($t->FechaFinProgreso)->format('Y-m-d') === $fecha
            )->count();
            $creadosPorDia[$fecha] = $delPeriodo->filter(
                fn ($t) => \Carbon\Carbon::parse($t->created_at)->format('Y-m-d') === $fecha
            )->count();
            $diaIter->addDay();
        }

        return [
            'total_tickets'              => $delPeriodo->count(),
            'tickets_cerrados'           => $delPeriodo->whereIn('Estatus', ['Atendido', 'Cancelado'])->count(),
            'tickets_en_progreso'        => $delPeriodo->whereIn('Estatus', ['En proceso', 'Pausado'])->count(),
            'distribucion_estado'        => $distribucionEstado,
            'tiempo_promedio_resolucion' => $tiempoPromedioResolucion,
            'tiempo_promedio_respuesta'  => $tiempoPromedioRespuesta,
            'tickets_por_categoria'      => $porCategoria,
            'tickets_por_responsable'    => $porResponsable,
            'tickets_por_prioridad'      => $porPrioridad,
            'tickets_por_area'           => $porArea,
            'resueltos_por_dia'          => $resueltosPorDia,
            'creados_por_dia'            => $creadosPorDia,
            'fecha_inicio_periodo'       => $fechaInicioMes->format('Y-m-d'),
            'fecha_fin_periodo'          => $fechaFinMes->format('Y-m-d'),
        ];
    }

    public function show($id)
    {
        try {
            $ticket = TicketMantenimiento::find($id);

            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
            }

            return response()->json([
                'success' => true,
                'ticket'  => $this->formatearTicket($ticket),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener la solicitud: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $ticketId = $request->input('ticketId');
            $ticket   = TicketMantenimiento::find($ticketId);

            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
            }

            if (in_array($ticket->Estatus, ['Atendido', 'Cancelado'], true)) {
                return response()->json(['success' => false, 'message' => 'No se pueden realizar modificaciones en una solicitud finalizada'], 400);
            }

            if ($request->has('prioridad')) {
                $ticket->Prioridad = $request->input('prioridad');
            }

            if ($request->has('categoria')) {
                $ticket->Categoria = $request->input('categoria') ?: null;
            }

            if ($request->has('responsable')) {
                $ticket->Responsable = $request->input('responsable') ?: null;
            }

            if ($request->has('estatus')) {
                $nuevoEstatus = $request->input('estatus');
                $actualEstatus = $ticket->Estatus;

                if ($nuevoEstatus !== $actualEstatus && !TicketMantenimiento::puedeTransicionar($actualEstatus, $nuevoEstatus)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La transición de "' . $actualEstatus . '" a "' . $nuevoEstatus . '" no está permitida',
                    ], 400);
                }

                if ($nuevoEstatus === 'En proceso' && (empty($ticket->Responsable) && empty($request->input('responsable')))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para cambiar a "En proceso" es necesario asignar un Responsable',
                    ], 400);
                }

                if ($nuevoEstatus === 'En proceso' && empty($ticket->Categoria) && empty($request->input('categoria'))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para cambiar a "En proceso" es necesario asignar una Categoría',
                    ], 400);
                }

                $ticket->Estatus = $nuevoEstatus;
            }

            $ticket->save();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud actualizada correctamente',
                'ticket'  => $ticket->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    public function getChatMessages(Request $request)
    {
        try {
            $mantenimientoId = $request->input('mantenimiento_id');

            $messages = MantenimientoChat::where('mantenimiento_id', $mantenimientoId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($m) => $this->formatearMensajeChat($m));

            return response()->json(['success' => true, 'messages' => $messages]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo mensajes de mantenimiento: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error obteniendo mensajes: ' . $e->getMessage()], 500);
        }
    }

    public function verificarMensajesNuevos(Request $request)
    {
        try {
            $mantenimientoId = $request->input('mantenimiento_id');
            $ultimoMensajeId = $request->input('ultimo_mensaje_id', 0);

            if (!$mantenimientoId) {
                return response()->json(['success' => false, 'message' => 'ID de solicitud requerido'], 400);
            }

            $ultimoMensaje = MantenimientoChat::where('mantenimiento_id', $mantenimientoId)->orderBy('id', 'desc')->first();

            if (!$ultimoMensaje) {
                return response()->json(['success' => true, 'tiene_nuevos' => false, 'ultimo_mensaje_id' => 0]);
            }

            return response()->json([
                'success'           => true,
                'tiene_nuevos'      => $ultimoMensaje->id > (int) $ultimoMensajeId,
                'ultimo_mensaje_id' => $ultimoMensaje->id,
                'total_mensajes'    => MantenimientoChat::where('mantenimiento_id', $mantenimientoId)->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error verificando mensajes: ' . $e->getMessage()], 500);
        }
    }

    public function enviarRespuesta(Request $request)
    {
        try {
            $mantenimientoId = $request->input('mantenimiento_id');
            $mensaje = $request->input('mensaje');
            $adjuntos = $request->file('adjuntos', []);

            $ticket = TicketMantenimiento::find($mantenimientoId);
            if (!$ticket) {
                return response()->json(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
            }

            if ($ticket->Estatus === 'Pendiente') {
                return response()->json(['success' => false, 'message' => 'No se pueden enviar mensajes cuando la solicitud está en estado "Pendiente". Cambia el estado a "En proceso".'], 400);
            }

            if (in_array($ticket->Estatus, ['Atendido', 'Cancelado'], true)) {
                return response()->json(['success' => false, 'message' => 'No se pueden enviar mensajes en una solicitud finalizada'], 400);
            }

            $adjuntosProcesados = [];
            foreach ($adjuntos as $adjunto) {
                $fileName = uniqid() . '_' . $adjunto->getClientOriginalName();
                $path = $adjunto->storeAs('mantenimiento/adjuntos', $fileName, 'public');
                $storagePath = storage_path('app/public/' . $path);

                $adjuntosProcesados[] = [
                    'name' => $adjunto->getClientOriginalName(),
                    'path' => $storagePath,
                    'storage_path' => $path,
                    'url' => asset('storage/' . $path),
                    'size' => $adjunto->getSize(),
                    'mime_type' => $adjunto->getMimeType(),
                    'tipo' => 'archivo',
                ];
            }

            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $mensaje, $matches);
            $mensajeParaCorreo = $mensaje;

            if (!empty($matches[1])) {
                foreach (array_unique($matches[1]) as $urlImagen) {
                    $nombreArchivo = basename(parse_url($urlImagen, PHP_URL_PATH));
                    $rutaRelativa = 'tickets/adjuntos/' . $nombreArchivo;
                    if (!Storage::disk('public')->exists($rutaRelativa)) {
                        continue;
                    }

                    $yaExiste = collect($adjuntosProcesados)
                        ->contains(fn ($a) => basename($a['storage_path'] ?? '') === $nombreArchivo);

                    if (!$yaExiste) {
                        $adjuntosProcesados[] = [
                            'name' => $nombreArchivo,
                            'path' => Storage::disk('public')->path($rutaRelativa),
                            'storage_path' => $rutaRelativa,
                            'url' => asset('storage/' . $rutaRelativa),
                            'size' => Storage::disk('public')->size($rutaRelativa),
                            'mime_type' => Storage::disk('public')->mimeType($rutaRelativa),
                            'tipo' => 'imagen_embebida',
                        ];
                    }

                    $contenidoArchivo = Storage::disk('public')->get($rutaRelativa);
                    $mimeType = Storage::disk('public')->mimeType($rutaRelativa);
                    $dataUri = 'data:' . $mimeType . ';base64,' . base64_encode($contenidoArchivo);
                    $mensajeParaCorreo = str_replace($urlImagen, $dataUri, $mensajeParaCorreo);
                }
            }

            $emailService = new MantenimientoEmailService();
            $resultado = $emailService->enviarRespuestaConInstrucciones(
                $mantenimientoId,
                $mensaje,
                $adjuntosProcesados,
                $mensajeParaCorreo
            );

            if ($resultado) {
                return response()->json(['success' => true, 'message' => 'Respuesta enviada exitosamente']);
            }

            return response()->json(['success' => false, 'message' => 'Error enviando respuesta por correo'], 500);
        } catch (\Exception $e) {
            Log::error('Error enviando respuesta de mantenimiento: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error enviando respuesta: ' . $e->getMessage()], 500);
        }
    }

    public function marcarMensajesComoLeidos(Request $request)
    {
        try {
            $mantenimientoId = $request->input('mantenimiento_id');

            MantenimientoChat::where('mantenimiento_id', $mantenimientoId)
                ->where('leido', false)
                ->update(['leido' => true]);

            MantenimientoChat::where('mantenimiento_id', $mantenimientoId)
                ->where('notificaciones_pendientes', '>', 0)
                ->update(['notificaciones_pendientes' => 0]);

            return response()->json(['success' => true, 'message' => 'Mensajes marcados como leídos']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error marcando mensajes: ' . $e->getMessage()], 500);
        }
    }

    public function obtenerEstadisticasCorreos(Request $request)
    {
        try {
            $mantenimientoId = $request->input('mantenimiento_id');
            if (!$mantenimientoId) {
                return response()->json(['success' => false, 'message' => 'ID de solicitud requerido'], 400);
            }

            return response()->json([
                'success' => true,
                'estadisticas' => [
                    'correos_enviados'  => MantenimientoChat::where('mantenimiento_id', $mantenimientoId)->where('es_correo', true)->where('remitente', 'soporte')->count(),
                    'correos_recibidos' => MantenimientoChat::where('mantenimiento_id', $mantenimientoId)->where('es_correo', true)->where('remitente', 'usuario')->count(),
                    'correos_no_leidos' => MantenimientoChat::where('mantenimiento_id', $mantenimientoId)->where('es_correo', true)->where('leido', false)->count(),
                    'total_correos'     => MantenimientoChat::where('mantenimiento_id', $mantenimientoId)->where('es_correo', true)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error obteniendo estadísticas: ' . $e->getMessage()], 500);
        }
    }

    private function formatearTicket(TicketMantenimiento $ticket): array
    {
        return [
            'MantenimientoID'   => $ticket->MantenimientoID,
            'NombreSolicitante' => $ticket->NombreSolicitante,
            'Correo'            => $ticket->Correo,
            'AreaDepartamento'  => $ticket->AreaDepartamento,
            'Asunto'            => $ticket->Asunto,
            'Descripcion'       => $ticket->Descripcion,
            'Categoria'         => $ticket->Categoria,
            'Prioridad'         => $ticket->Prioridad,
            'Estatus'           => $ticket->Estatus,
            'Responsable'       => $ticket->Responsable,
            'imagen'            => $ticket->imagen,
            'created_at'        => optional($ticket->created_at)->format('d/m/Y H:i:s'),
        ];
    }

    private function formatearMensajeChat(MantenimientoChat $m): array
    {
        return [
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
        ];
    }
}
