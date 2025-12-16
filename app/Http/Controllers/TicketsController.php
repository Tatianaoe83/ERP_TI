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
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TicketsController extends Controller
{
    protected $emailService;

    public function __construct(SimpleEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function index(Request $request)
    {
        $mes = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        // Si se solicita un mes específico para productividad, filtrar tickets
        $ticketsQuery = Tickets::with(['empleado', 'responsableTI', 'chat' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(1);
        }]);

        // Filtrar por mes si se especifica
        if ($request->has('mes') && $request->has('anio')) {
            $fechaInicio = \Carbon\Carbon::create($anio, $mes, 1)->startOfMonth();
            $fechaFin = \Carbon\Carbon::create($anio, $mes, 1)->endOfMonth();
            $ticketsQuery->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        }

        $tickets = $ticketsQuery->orderBy('created_at', 'desc')->get();

        $ticketsStatus = [
            'nuevos' => $tickets->where('Estatus', 'Pendiente'),
            'proceso' => $tickets->where('Estatus', 'En progreso'),
            'resueltos' => $tickets->where('Estatus', 'Cerrado'),
        ];

        $responsablesTI = Empleados::where('ObraID', 46)->where('tipo_persona', 'FISICA')->get();

        // Métricas de productividad
        $metricasProductividad = $this->obtenerMetricasProductividad($tickets);

        return view('tickets.index', compact('ticketsStatus', 'responsablesTI', 'metricasProductividad', 'mes', 'anio'));
    }

    /**
     * Obtener métricas de productividad para el dashboard
     */
    private function obtenerMetricasProductividad($tickets)
    {
        // Tickets resueltos en los últimos 30 días
        $fechaInicio = now()->subDays(30);
        $ticketsUltimos30Dias = $tickets->filter(function($ticket) use ($fechaInicio) {
            return $ticket->created_at >= $fechaInicio;
        });

        // Distribución por estado
        $distribucionEstado = [
            'Pendiente' => $tickets->where('Estatus', 'Pendiente')->count(),
            'En progreso' => $tickets->where('Estatus', 'En progreso')->count(),
            'Cerrado' => $tickets->where('Estatus', 'Cerrado')->count(),
        ];

        // Tickets resueltos por día (últimos 30 días)
        $resueltosPorDia = [];
        for ($i = 29; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            $resueltosPorDia[$fecha] = $tickets->filter(function($ticket) use ($fecha) {
                return $ticket->Estatus === 'Cerrado' && 
                       $ticket->FechaFinProgreso && 
                       $ticket->FechaFinProgreso->format('Y-m-d') === $fecha;
            })->count();
        }

        // Tiempo promedio de resolución (solo tickets cerrados)
        $ticketsCerrados = $tickets->filter(function($ticket) {
            return $ticket->Estatus === 'Cerrado' && $ticket->FechaInicioProgreso && $ticket->FechaFinProgreso;
        });
        
        $tiempoPromedioResolucion = 0;
        if ($ticketsCerrados->count() > 0) {
            $sumaTiempos = $ticketsCerrados->sum(function($ticket) {
                return $ticket->tiempo_resolucion ?? 0;
            });
            $tiempoPromedioResolucion = round($sumaTiempos / $ticketsCerrados->count(), 2);
        }

        // Tiempo promedio de respuesta (tickets en progreso)
        $ticketsEnProgreso = $tickets->filter(function($ticket) {
            return $ticket->Estatus === 'En progreso' && $ticket->FechaInicioProgreso;
        });
        
        $tiempoPromedioRespuesta = 0;
        if ($ticketsEnProgreso->count() > 0) {
            $sumaTiempos = $ticketsEnProgreso->sum(function($ticket) {
                return $ticket->tiempo_respuesta ?? 0;
            });
            $tiempoPromedioRespuesta = round($sumaTiempos / $ticketsEnProgreso->count(), 2);
        }

        // Tickets por responsable TI
        $ticketsPorResponsable = $tickets->filter(function($ticket) {
            return $ticket->ResponsableTI !== null;
        })->groupBy('ResponsableTI')->map(function($grupo) {
            $responsable = $grupo->first()->responsableTI;
            return [
                'nombre' => $responsable ? $responsable->NombreEmpleado : 'Sin asignar',
                'total' => $grupo->count(),
                'cerrados' => $grupo->where('Estatus', 'Cerrado')->count(),
                'en_progreso' => $grupo->where('Estatus', 'En progreso')->count(),
                'pendientes' => $grupo->where('Estatus', 'Pendiente')->count(),
            ];
        })->sortByDesc('total')->take(10);

        // Métricas detalladas por empleado (responsable TI)
        $metricasPorEmpleado = $this->obtenerMetricasPorEmpleado($tickets);

        // Tickets por prioridad
        $ticketsPorPrioridad = $tickets->groupBy('Prioridad')->map(function($grupo) {
            return $grupo->count();
        });

        // Tendencias semanales (últimas 8 semanas)
        $tendenciasSemanales = [];
        for ($i = 7; $i >= 0; $i--) {
            $semanaInicio = now()->subWeeks($i)->startOfWeek();
            $semanaFin = now()->subWeeks($i)->endOfWeek();
            $semanaLabel = $semanaInicio->format('d/m') . ' - ' . $semanaFin->format('d/m');
            
            $tendenciasSemanales[$semanaLabel] = [
                'creados' => $tickets->filter(function($ticket) use ($semanaInicio, $semanaFin) {
                    return $ticket->created_at >= $semanaInicio && $ticket->created_at <= $semanaFin;
                })->count(),
                'resueltos' => $tickets->filter(function($ticket) use ($semanaInicio, $semanaFin) {
                    return $ticket->Estatus === 'Cerrado' && 
                           $ticket->FechaFinProgreso && 
                           $ticket->FechaFinProgreso >= $semanaInicio && 
                           $ticket->FechaFinProgreso <= $semanaFin;
                })->count(),
            ];
        }

        return [
            'total_tickets' => $tickets->count(),
            'tickets_ultimos_30_dias' => $ticketsUltimos30Dias->count(),
            'distribucion_estado' => $distribucionEstado,
            'resueltos_por_dia' => $resueltosPorDia,
            'tiempo_promedio_resolucion' => $tiempoPromedioResolucion,
            'tiempo_promedio_respuesta' => $tiempoPromedioRespuesta,
            'tickets_por_responsable' => $ticketsPorResponsable,
            'tickets_por_prioridad' => $ticketsPorPrioridad,
            'tendencias_semanales' => $tendenciasSemanales,
            'tickets_cerrados' => $ticketsCerrados->count(),
            'tickets_en_progreso' => $ticketsEnProgreso->count(),
            'metricas_por_empleado' => $metricasPorEmpleado,
        ];
    }

    /**
     * Obtener métricas detalladas por empleado (responsable TI)
     */
    private function obtenerMetricasPorEmpleado($tickets)
    {
        $empleados = Empleados::where('ObraID', 46)
            ->where('tipo_persona', 'FISICA')
            ->get();

        $metricas = [];

        foreach ($empleados as $empleado) {
            $ticketsEmpleado = $tickets->filter(function($ticket) use ($empleado) {
                return $ticket->ResponsableTI == $empleado->EmpleadoID;
            });

            if ($ticketsEmpleado->count() == 0) {
                continue; // Saltar empleados sin tickets
            }

            // Tickets por estado
            $cerrados = $ticketsEmpleado->where('Estatus', 'Cerrado');
            $enProgreso = $ticketsEmpleado->where('Estatus', 'En progreso');
            $pendientes = $ticketsEmpleado->where('Estatus', 'Pendiente');

            // Tiempo promedio de resolución (solo tickets cerrados con fechas)
            $ticketsConResolucion = $cerrados->filter(function($ticket) {
                return $ticket->FechaInicioProgreso && $ticket->FechaFinProgreso;
            });

            $tiempoPromedioResolucion = 0;
            if ($ticketsConResolucion->count() > 0) {
                $sumaTiempos = $ticketsConResolucion->sum(function($ticket) {
                    return $ticket->tiempo_resolucion ?? 0;
                });
                $tiempoPromedioResolucion = round($sumaTiempos / $ticketsConResolucion->count(), 2);
            }

            // Tasa de cierre
            $tasaCierre = $ticketsEmpleado->count() > 0 
                ? round(($cerrados->count() / $ticketsEmpleado->count()) * 100, 1) 
                : 0;

            // Tickets por mes (últimos 6 meses)
            $ticketsPorMes = [];
            for ($i = 5; $i >= 0; $i--) {
                $mesInicio = now()->subMonths($i)->startOfMonth();
                $mesFin = now()->subMonths($i)->endOfMonth();
                $mesLabel = $mesInicio->format('M Y');
                
                $ticketsPorMes[$mesLabel] = [
                    'total' => $ticketsEmpleado->filter(function($ticket) use ($mesInicio, $mesFin) {
                        return $ticket->created_at >= $mesInicio && $ticket->created_at <= $mesFin;
                    })->count(),
                    'cerrados' => $ticketsEmpleado->filter(function($ticket) use ($mesInicio, $mesFin) {
                        return $ticket->Estatus === 'Cerrado' && 
                               $ticket->FechaFinProgreso && 
                               $ticket->FechaFinProgreso >= $mesInicio && 
                               $ticket->FechaFinProgreso <= $mesFin;
                    })->count(),
                ];
            }

            // Tickets por prioridad
            $ticketsPorPrioridad = $ticketsEmpleado->groupBy('Prioridad')->map(function($grupo) {
                return $grupo->count();
            });

            $metricas[] = [
                'empleado_id' => $empleado->EmpleadoID,
                'nombre' => $empleado->NombreEmpleado,
                'total' => $ticketsEmpleado->count(),
                'cerrados' => $cerrados->count(),
                'en_progreso' => $enProgreso->count(),
                'pendientes' => $pendientes->count(),
                'tasa_cierre' => $tasaCierre,
                'tiempo_promedio_resolucion' => $tiempoPromedioResolucion,
                'tickets_por_mes' => $ticketsPorMes,
                'tickets_por_prioridad' => $ticketsPorPrioridad,
            ];
        }

        // Ordenar por total de tickets descendente
        usort($metricas, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $metricas;
    }

    public function show($id)
    {
        try {
            $ticket = Tickets::find($id);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'ticket' => [
                    'TicketID' => $ticket->TicketID,
                    'Prioridad' => $ticket->Prioridad,
                    'Estatus' => $ticket->Estatus,
                    'ResponsableTI' => $ticket->ResponsableTI,
                    'TipoID' => $ticket->TipoID,
                    'SubtipoID' => $ticket->SubtipoID,
                    'TertipoID' => $ticket->TertipoID,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $ticketId = $request->input('ticketId');
            $ticket = Tickets::find($ticketId);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            $estatusAnterior = $ticket->Estatus;
            $nuevoEstatus = $request->input('estatus', $estatusAnterior);

            // REGLA 4: Si está Cerrado, bloquear todos los cambios
            if ($estatusAnterior === 'Cerrado') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden realizar modificaciones en un ticket cerrado'
                ], 400);
            }

            // REGLA 4: Validar transiciones de estado (solo Pendiente->En progreso->Cerrado)
            $transicionesValidas = [
                'Pendiente' => ['En progreso'],
                'En progreso' => ['Cerrado'],
                'Cerrado' => [] // No se puede cambiar desde Cerrado
            ];

            if ($nuevoEstatus !== $estatusAnterior) {
                // Validar que la transición sea válida
                if (!in_array($nuevoEstatus, $transicionesValidas[$estatusAnterior] ?? [])) {
                    return response()->json([
                        'success' => false,
                        'message' => "No se puede cambiar el estado de '{$estatusAnterior}' a '{$nuevoEstatus}'. Las transiciones válidas son: " . implode(', ', $transicionesValidas[$estatusAnterior] ?? ['ninguna'])
                    ], 400);
                }
            }

            // REGLA 1: Si pasa de "Pendiente" a "En progreso", se requieren ResponsableTI y TipoID
            if ($estatusAnterior === 'Pendiente' && $nuevoEstatus === 'En progreso') {
                $responsableTI = $request->input('responsableTI');
                $tipoID = $request->input('tipoID');

                if (empty($responsableTI) || empty($tipoID)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para cambiar el ticket a "En progreso" es necesario asignar un Responsable y una Categoría'
                    ], 400);
                }
            }

            // REGLA 2: Si está en "En progreso", no se puede modificar el ResponsableTI
            if ($estatusAnterior === 'En progreso') {
                if ($request->has('responsableTI')) {
                    $nuevoResponsable = $request->input('responsableTI');
                    // Solo permitir si el nuevo responsable es el mismo o si está pasando a Cerrado
                    if ($nuevoEstatus !== 'Cerrado' && $nuevoResponsable != $ticket->ResponsableTI) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No se puede modificar el Responsable cuando el ticket está en "En progreso"'
                        ], 400);
                    }
                }
            }

            // Actualizar los campos permitidos
            if ($request->has('prioridad')) {
                $ticket->Prioridad = $request->input('prioridad');
            }

            if ($request->has('responsableTI')) {
                // Solo actualizar si no está en "En progreso" o si está pasando a Cerrado
                if ($estatusAnterior !== 'En progreso' || $nuevoEstatus === 'Cerrado') {
                    $ticket->ResponsableTI = $request->input('responsableTI') ?: null;
                }
            }

            if ($request->has('estatus')) {
                $ticket->Estatus = $request->input('estatus');
            }

            if ($request->has('tipoID')) {
                $tipoID = $request->input('tipoID') ? (int)$request->input('tipoID') : null;
                $ticket->TipoID = $tipoID;
                
                // Si no se proporciona subtipoID, obtenerlo automáticamente de la relación con Tipoticket
                if (!$request->has('subtipoID') || !$request->input('subtipoID')) {
                    if ($tipoID) {
                        $tipoticket = Tipoticket::find($tipoID);
                        if ($tipoticket && $tipoticket->SubtipoID) {
                            $ticket->SubtipoID = $tipoticket->SubtipoID;
                            // Si no se proporciona tertipoID, obtenerlo automáticamente de la relación con Subtipo
                            if (!$request->has('tertipoID') || !$request->input('tertipoID')) {
                                $subtipo = Subtipos::find($tipoticket->SubtipoID);
                                if ($subtipo && $subtipo->TertipoID) {
                                    $ticket->TertipoID = $subtipo->TertipoID;
                                }
                            }
                        }
                    }
                }
            }

            // Guardar SubtipoID si se proporciona directamente
            if ($request->has('subtipoID')) {
                $ticket->SubtipoID = $request->input('subtipoID') ? (int)$request->input('subtipoID') : null;
                
                // Si se cambia el subtipoID y no se proporciona tertipoID, obtenerlo automáticamente
                if ($ticket->SubtipoID && (!$request->has('tertipoID') || !$request->input('tertipoID'))) {
                    $subtipo = Subtipos::find($ticket->SubtipoID);
                    if ($subtipo && $subtipo->TertipoID) {
                        $ticket->TertipoID = $subtipo->TertipoID;
                    }
                }
            }

            // Guardar TertipoID si se proporciona directamente
            if ($request->has('tertipoID')) {
                $ticket->TertipoID = $request->input('tertipoID') ? (int)$request->input('tertipoID') : null;
            }

            $ticket->save();

            // Si el ticket está o cambió a "En progreso", verificar si excede el tiempo de respuesta
            if ($nuevoEstatus === 'En progreso') {
                // Recargar el ticket con relaciones para calcular tiempos
                $ticket->refresh();
                $ticket->load(['tipoticket', 'responsableTI']);
                
                // Verificar y enviar notificación si excede el tiempo
                // Nota: Esto verificará después de que se haya actualizado FechaInicioProgreso en el modelo
                try {
                    $notificationService = new TicketNotificationService();
                    // El modelo Tickets tiene un boot() que actualiza FechaInicioProgreso cuando cambia a "En progreso"
                    // Verificar si excede el tiempo estimado según la métrica de la categoría
                    $notificationService->verificarYNotificarExceso($ticket);
                } catch (\Exception $e) {
                    Log::error("Error verificando exceso de tiempo al cambiar a En progreso: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados correctamente',
                'ticket' => [
                    'TicketID' => $ticket->TicketID,
                    'Prioridad' => $ticket->Prioridad,
                    'Estatus' => $ticket->Estatus,
                    'ResponsableTI' => $ticket->ResponsableTI,
                    'TipoID' => $ticket->TipoID,
                    'SubtipoID' => $ticket->SubtipoID,
                    'TertipoID' => $ticket->TertipoID,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener mensajes del chat de un ticket
     */
    public function getChatMessages(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            
            $messages = TicketChat::where('ticket_id', $ticketId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($message) {
                    return [
                        'id' => $message->id,
                        'mensaje' => $message->mensaje,
                        'remitente' => $message->remitente,
                        'nombre_remitente' => $message->nombre_remitente,
                        'correo_remitente' => $message->correo_remitente,
                        'message_id' => $message->message_id,
                        'thread_id' => $message->thread_id,
                        'es_correo' => $message->es_correo,
                        'adjuntos' => $message->adjuntos,
                        'created_at' => $message->created_at->format('d/m/Y H:i:s'),
                        'leido' => $message->leido
                    ];
                });

            return response()->json([
                'success' => true,
                'messages' => $messages
            ]);

        } catch (\Exception $e) {
            Log::error("Error obteniendo mensajes del chat: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo mensajes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar respuesta por correo
     */
    public function enviarRespuesta(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            $mensaje = $request->input('mensaje');
            $adjuntos = $request->file('adjuntos', []);

            // Validar que el ticket existe
            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            // Procesar adjuntos si existen
            $adjuntosProcesados = [];
            if (!empty($adjuntos)) {
                foreach ($adjuntos as $adjunto) {
                    $fileName = uniqid() . '_' . $adjunto->getClientOriginalName();
                    $path = $adjunto->storeAs('tickets/adjuntos', $fileName, 'public');
                    $adjuntosProcesados[] = [
                        'name' => $adjunto->getClientOriginalName(),
                        'path' => storage_path('app/public/' . $path)
                    ];
                }
            }

            // Enviar correo usando el servicio híbrido (SMTP + instrucciones)
            $hybridService = new \App\Services\HybridEmailService();
            $resultado = $hybridService->enviarRespuestaConInstrucciones($ticketId, $mensaje, $adjuntosProcesados);

            if ($resultado) {
                // El servicio híbrido ya guarda el mensaje en el chat
                return response()->json([
                    'success' => true,
                    'message' => 'Respuesta enviada exitosamente con instrucciones'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error enviando respuesta por correo'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error("Error enviando respuesta: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error enviando respuesta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar mensaje interno al chat
     */
    public function agregarMensajeInterno(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            $mensaje = $request->input('mensaje');
            $remitente = $request->input('remitente', 'soporte');

            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            $chatMessage = TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => $mensaje,
                'remitente' => $remitente,
                'nombre_remitente' => auth()->user()->name ?? 'Soporte TI',
                'correo_remitente' => auth()->user()->email ?? config('mail.from.address'),
                'es_correo' => false,
                'leido' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje agregado exitosamente',
                'chat_message' => [
                    'id' => $chatMessage->id,
                    'mensaje' => $chatMessage->mensaje,
                    'remitente' => $chatMessage->remitente,
                    'nombre_remitente' => $chatMessage->nombre_remitente,
                    'created_at' => $chatMessage->created_at->format('d/m/Y H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error agregando mensaje interno: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error agregando mensaje: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar mensajes como leídos
     */
    public function marcarMensajesComoLeidos(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');

            TicketChat::where('ticket_id', $ticketId)
                ->where('leido', false)
                ->update(['leido' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Mensajes marcados como leídos'
            ]);

        } catch (\Exception $e) {
            Log::error("Error marcando mensajes como leídos: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error marcando mensajes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los tipos de tickets
     */
    public function getTipos()
    {
        try {
            $tipos = Tipoticket::select('TipoID', 'NombreTipo')
                ->orderBy('NombreTipo')
                ->get();

            return response()->json([
                'success' => true,
                'tipos' => $tipos
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo tipos: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo tipos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener subtipos por tipo específico
     */
    public function getSubtiposByTipo(Request $request)
    {
        try {
            $tipoId = $request->input('tipo_id');
            
            if (!$tipoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de tipo requerido'
                ], 400);
            }
            
            // Filtrar subtipos por el TipoID seleccionado
            $subtipos = Subtipos::select('SubtipoID', 'NombreSubtipo', 'TipoID')
                ->where('TipoID', $tipoId)
                ->orderBy('NombreSubtipo')
                ->get();

            return response()->json([
                'success' => true,
                'subtipos' => $subtipos
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo subtipos por tipo: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo subtipos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tertipos por subtipo específico
     */
    public function getTertiposBySubtipo(Request $request)
    {
        try {
            $subtipoId = $request->input('subtipo_id');
            
            if (!$subtipoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de subtipo requerido'
                ], 400);
            }
            
            // Filtrar tertipos por el SubtipoID seleccionado
            $tertipos = Tertipos::select('TertipoID', 'NombreTertipo', 'SubtipoID')
                ->where('SubtipoID', $subtipoId)
                ->orderBy('NombreTertipo')
                ->get();

            return response()->json([
                'success' => true,
                'tertipos' => $tertipos
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo tertipos por subtipo: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo tertipos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar correos de Outlook para un ticket específico
     */
    public function sincronizarCorreos(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            
            if (!$ticketId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de ticket requerido'
                ], 400);
            }

            // Verificar que el ticket existe
            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            // Procesar correos entrantes usando IMAP
            $imapService = new \App\Services\ImapEmailReceiver();
            $resultado = $imapService->procesarCorreosEntrantes();

            if ($resultado) {
                // Recargar mensajes después de la sincronización
                $mensajes = TicketChat::where('ticket_id', $ticketId)
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(function($message) {
                        return [
                            'id' => $message->id,
                            'mensaje' => $message->mensaje,
                            'remitente' => $message->remitente,
                            'nombre_remitente' => $message->nombre_remitente,
                            'correo_remitente' => $message->correo_remitente,
                            'message_id' => $message->message_id,
                            'thread_id' => $message->thread_id,
                            'es_correo' => $message->es_correo,
                            'adjuntos' => $message->adjuntos,
                            'created_at' => $message->created_at->format('d/m/Y H:i:s'),
                            'leido' => $message->leido
                        ];
                    });

                return response()->json([
                    'success' => true,
                    'message' => 'Correos sincronizados exitosamente',
                    'mensajes' => $mensajes,
                    'total_mensajes' => $mensajes->count()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error sincronizando correos'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error("Error sincronizando correos: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sincronizando correos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de correos para un ticket
     */
    public function obtenerEstadisticasCorreos(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            
            if (!$ticketId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de ticket requerido'
                ], 400);
            }

            $estadisticas = [
                'correos_enviados' => TicketChat::where('ticket_id', $ticketId)
                    ->where('es_correo', true)
                    ->where('remitente', 'soporte')
                    ->count(),
                'correos_recibidos' => TicketChat::where('ticket_id', $ticketId)
                    ->where('es_correo', true)
                    ->where('remitente', 'usuario')
                    ->count(),
                'correos_no_leidos' => TicketChat::where('ticket_id', $ticketId)
                    ->where('es_correo', true)
                    ->where('leido', false)
                    ->count(),
                'total_correos' => TicketChat::where('ticket_id', $ticketId)
                    ->where('es_correo', true)
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'estadisticas' => $estadisticas
            ]);

        } catch (\Exception $e) {
            Log::error("Error obteniendo estadísticas de correos: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Diagnosticar configuración de correos
     */
    public function diagnosticarCorreos(Request $request)
    {
        try {
            $diagnostico = [];
            
            // Verificar configuración SMTP
            $smtpConfig = [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'encryption' => config('mail.mailers.smtp.encryption'),
            ];
            $diagnostico['smtp'] = $smtpConfig;
            
            // Verificar configuración IMAP
            $imapConfig = [
                'host' => config('mail.imap.host', 'proser.com.mx'),
                'port' => config('mail.imap.port', 993),
                'encryption' => config('mail.imap.encryption', 'ssl'),
                'username' => config('mail.mailers.smtp.username'),
                'servidor' => 'proser.com.mx (Personalizado)',
            ];
            $diagnostico['imap'] = $imapConfig;
            
            // Probar conexión IMAP
            try {
                $imapService = new \App\Services\ImapEmailReceiver();
                $connection = $imapService->conectarIMAP();
                
                if ($connection) {
                    $diagnostico['imap_connection'] = 'success';
                    
                    // Probar obtener correos
                    $emails = imap_search($connection, 'UNSEEN');
                    $diagnostico['correos_no_leidos'] = $emails ? count($emails) : 0;
                    
                    imap_close($connection);
                } else {
                    $diagnostico['imap_connection'] = 'failed';
                    $diagnostico['imap_error'] = imap_last_error();
                }
            } catch (\Exception $e) {
                $diagnostico['imap_connection'] = 'error: ' . $e->getMessage();
            }
            
            // Verificar correos en la base de datos
            $ticketId = $request->input('ticket_id');
            if ($ticketId) {
                $mensajes = TicketChat::where('ticket_id', $ticketId)->get();
                $diagnostico['mensajes_bd'] = [
                    'total' => $mensajes->count(),
                    'enviados' => $mensajes->where('remitente', 'soporte')->count(),
                    'recibidos' => $mensajes->where('remitente', 'usuario')->count(),
                    'correos' => $mensajes->where('es_correo', true)->count(),
                ];
            }
            
            return response()->json([
                'success' => true,
                'diagnostico' => $diagnostico
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error en diagnóstico: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en diagnóstico: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar respuesta manual (simulando respuesta por correo)
     */
    public function agregarRespuestaManual(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            $mensaje = $request->input('mensaje');
            $nombreEmisor = $request->input('nombre_emisor');
            $correoEmisor = $request->input('correo_emisor');

            if (!$ticketId || !$mensaje) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket ID y mensaje son requeridos'
                ], 400);
            }

            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }

            // Usar el servicio híbrido para procesar la respuesta manual
            $hybridService = new \App\Services\HybridEmailService();
            $resultado = $hybridService->procesarRespuestaManual($ticketId, [
                'mensaje' => $mensaje,
                'nombre' => $nombreEmisor,
                'correo' => $correoEmisor
            ]);

            if (!$resultado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error procesando respuesta manual'
                ], 500);
            }

            // Recargar mensajes
            $mensajes = TicketChat::where('ticket_id', $ticketId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($message) {
                    return [
                        'id' => $message->id,
                        'mensaje' => $message->mensaje,
                        'remitente' => $message->remitente,
                        'nombre_remitente' => $message->nombre_remitente,
                        'correo_remitente' => $message->correo_remitente,
                        'message_id' => $message->message_id,
                        'thread_id' => $message->thread_id,
                        'es_correo' => $message->es_correo,
                        'adjuntos' => $message->adjuntos,
                        'created_at' => $message->created_at->format('d/m/Y H:i:s'),
                        'leido' => $message->leido
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Respuesta agregada exitosamente',
                'mensajes' => $mensajes
            ]);

        } catch (\Exception $e) {
            Log::error("Error agregando respuesta manual: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error agregando respuesta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar instrucciones de respuesta por correo
     */
    public function enviarInstruccionesRespuesta(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            
            if (!$ticketId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket ID requerido'
                ], 400);
            }

            // Enviar instrucciones usando el servicio híbrido
            $hybridService = new \App\Services\HybridEmailService();
            $instrucciones = "Por favor, responde a este correo para continuar la conversación sobre tu ticket. Tu respuesta será procesada automáticamente.";
            $resultado = $hybridService->enviarRespuestaConInstrucciones($ticketId, $instrucciones);

            if ($resultado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Instrucciones de respuesta enviadas por correo'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error enviando instrucciones'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error("Error enviando instrucciones: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error enviando instrucciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar Message-ID único
     */
    private function generarMessageId()
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $timestamp = time();
        $random = uniqid();
        return "<ticket-{$timestamp}-{$random}@{$domain}>";
    }

    /**
     * Obtener Thread-ID del ticket
     */
    private function obtenerThreadIdDelTicket($ticketId)
    {
        $existingChat = TicketChat::where('ticket_id', $ticketId)
            ->whereNotNull('thread_id')
            ->first();

        if ($existingChat) {
            return $existingChat->thread_id;
        }

        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        return "<thread-ticket-{$ticketId}-" . time() . "@{$domain}>";
    }

    /**
     * Obtener todos los tipos de tickets con sus métricas
     */
    public function getTiposConMetricas()
    {
        try {
            $tipos = Tipoticket::select('TipoID', 'NombreTipo', 'TiempoEstimadoMinutos')
                ->orderBy('NombreTipo')
                ->get()
                ->map(function($tipo) {
                    return [
                        'TipoID' => $tipo->TipoID,
                        'NombreTipo' => $tipo->NombreTipo,
                        'TiempoEstimadoMinutos' => $tipo->TiempoEstimadoMinutos
                    ];
                });

            return response()->json([
                'success' => true,
                'tipos' => $tipos
            ]);
        } catch (\Exception $e) {
            Log::error("Error obteniendo tipos con métricas: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo tipos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar tiempo estimado en minutos para un tipo de ticket
     */
    public function actualizarTiempoEstimado(Request $request)
    {
        try {
            $request->validate([
                'tipo_id' => 'required|integer|exists:tipotickets,TipoID',
                'tiempo_estimado_minutos' => 'nullable|integer|min:0'
            ]);

            $tipo = Tipoticket::where('TipoID', $request->input('tipo_id'))->first();
            
            if (!$tipo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de ticket no encontrado'
                ], 404);
            }

            $tipo->TiempoEstimadoMinutos = $request->input('tiempo_estimado_minutos');
            $tipo->save();

            return response()->json([
                'success' => true,
                'message' => 'Tiempo estimado actualizado correctamente',
                'tipo' => [
                    'TipoID' => $tipo->TipoID,
                    'NombreTipo' => $tipo->NombreTipo,
                    'TiempoEstimadoMinutos' => $tipo->TiempoEstimadoMinutos
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error actualizando tiempo estimado: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error actualizando tiempo estimado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar múltiples tiempos estimados a la vez
     */
    public function actualizarMetricasMasivo(Request $request)
    {
        try {
            $request->validate([
                'metricas' => 'required|array',
                'metricas.*.tipo_id' => 'required|integer|exists:tipotickets,TipoID',
                'metricas.*.tiempo_estimado_minutos' => 'nullable|integer|min:0'
            ]);

            $actualizados = 0;
            $errores = [];

            foreach ($request->input('metricas') as $metrica) {
                try {
                    $tipoId = $metrica['tipo_id'];
                    $tiempoEstimado = isset($metrica['tiempo_estimado_minutos']) && $metrica['tiempo_estimado_minutos'] !== '' 
                        ? (int)$metrica['tiempo_estimado_minutos'] 
                        : null;
                    
                    $tipo = Tipoticket::where('TipoID', $tipoId)->first();
                    if (!$tipo) {
                        $errores[] = [
                            'tipo_id' => $tipoId,
                            'error' => 'Tipo de ticket no encontrado'
                        ];
                        continue;
                    }
                    
                    // Usar update() para forzar la actualización en la base de datos
                    $filasAfectadas = Tipoticket::where('TipoID', $tipoId)
                        ->update(['TiempoEstimadoMinutos' => $tiempoEstimado]);
                    
                    // Si update() se ejecutó sin excepciones, la operación fue exitosa
                    // Incluso si retorna 0 (valor ya era el mismo), la operación fue correcta
                    $actualizados++;
                } catch (\Exception $e) {
                    Log::error("Error actualizando tipo {$metrica['tipo_id']}: " . $e->getMessage());
                    $errores[] = [
                        'tipo_id' => $metrica['tipo_id'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Se actualizaron {$actualizados} tipos de tickets",
                'actualizados' => $actualizados,
                'errores' => $errores
            ]);
        } catch (\Exception $e) {
            Log::error("Error actualizando métricas masivas: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error actualizando métricas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos de productividad vía AJAX
     */
    public function obtenerProductividadAjax(Request $request)
    {
        $mes = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        // Fechas del mes seleccionado
        $fechaInicio = \Carbon\Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin = \Carbon\Carbon::create($anio, $mes, 1)->endOfMonth();

        // Obtener tickets del mes
        $tickets = Tickets::with(['empleado', 'responsableTI', 'chat' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(1);
        }])
        ->whereBetween('created_at', [$fechaInicio, $fechaFin])
        ->orderBy('created_at', 'desc')
        ->get();

        // Métricas de productividad
        $metricasProductividad = $this->obtenerMetricasProductividad($tickets);

        $html = view('tickets.productividad', [
            'metricasProductividad' => $metricasProductividad,
            'mes' => $mes,
            'anio' => $anio
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'mes' => $mes,
            'anio' => $anio
        ]);
    }

    /**
     * Mostrar reporte mensual de tickets
     */
    public function reporteMensual(Request $request)
    {
        $mes = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        // Fechas del mes seleccionado
        $fechaInicio = \Carbon\Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin = \Carbon\Carbon::create($anio, $mes, 1)->endOfMonth();

        // Obtener tickets del mes
        $tickets = Tickets::with([
            'empleado.gerencia',
            'responsableTI.gerencia',
            'tipoticket.subtipoid.tertipoid'
        ])
        ->whereBetween('created_at', [$fechaInicio, $fechaFin])
        ->get();

        // Calcular datos para el resumen
        $resumen = $this->calcularResumenMensual($tickets, $fechaInicio, $fechaFin);

        return view('tickets.reporte-mensual', [
            'tickets' => $tickets,
            'resumen' => $resumen,
            'mes' => $mes,
            'anio' => $anio,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin
        ]);
    }

    /**
     * Exportar reporte mensual a Excel
     */
    public function exportarReporteMensualExcel(Request $request)
    {
        $mes = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        // Fechas del mes seleccionado
        $fechaInicio = \Carbon\Carbon::create($anio, $mes, 1)->startOfMonth();
        $fechaFin = \Carbon\Carbon::create($anio, $mes, 1)->endOfMonth();

        // Obtener tickets del mes
        $tickets = Tickets::with([
            'empleado.gerencia',
            'responsableTI.gerencia',
            'tipoticket.subtipoid.tertipoid'
        ])
        ->whereBetween('created_at', [$fechaInicio, $fechaFin])
        ->get();

        // Calcular datos para el resumen
        $resumen = $this->calcularResumenMensual($tickets, $fechaInicio, $fechaFin);

        $nombreArchivo = 'reporte_tickets_' . date('d-m-Y-H-i') . '.xlsx';

        return Excel::download(
            new \App\Exports\ReporteMensualTicketsExport($tickets, $resumen, $mes, $anio),
            $nombreArchivo
        );
    }

    /**
     * Calcular resumen mensual de tickets
     */
    private function calcularResumenMensual($tickets, $fechaInicio, $fechaFin)
    {
        // Incidencias por gerencia
        $incidenciasPorGerencia = [];
        foreach ($tickets as $ticket) {
            $gerenciaNombre = 'Sin gerencia';
            if ($ticket->empleado && $ticket->empleado->gerencia) {
                $gerenciaNombre = $ticket->empleado->gerencia->NombreGerencia ?? 'Sin gerencia';
            }

            if (!isset($incidenciasPorGerencia[$gerenciaNombre])) {
                $incidenciasPorGerencia[$gerenciaNombre] = [
                    'gerencia' => $gerenciaNombre,
                    'total' => 0,
                    'resueltos' => 0,
                    'en_progreso' => 0,
                    'pendientes' => 0,
                    'por_responsable' => []
                ];
            }

            $incidenciasPorGerencia[$gerenciaNombre]['total']++;

            if ($ticket->Estatus === 'Cerrado') {
                $incidenciasPorGerencia[$gerenciaNombre]['resueltos']++;

                $responsableNombre = 'Sin responsable';
                if ($ticket->responsableTI && $ticket->responsableTI->NombreEmpleado) {
                    $responsableNombre = $ticket->responsableTI->NombreEmpleado;
                }

                if (!isset($incidenciasPorGerencia[$gerenciaNombre]['por_responsable'][$responsableNombre])) {
                    $incidenciasPorGerencia[$gerenciaNombre]['por_responsable'][$responsableNombre] = 0;
                }
                $incidenciasPorGerencia[$gerenciaNombre]['por_responsable'][$responsableNombre]++;
            } elseif ($ticket->Estatus === 'En progreso') {
                $incidenciasPorGerencia[$gerenciaNombre]['en_progreso']++;
            } else {
                $incidenciasPorGerencia[$gerenciaNombre]['pendientes']++;
            }
        }

        // Promedios de tiempos
        $ticketsConRespuesta = $tickets->filter(function($t) {
            return $t->FechaInicioProgreso && $t->tiempo_respuesta !== null;
        });

        $ticketsConResolucion = $tickets->filter(function($t) {
            return $t->FechaInicioProgreso && $t->FechaFinProgreso && $t->tiempo_resolucion !== null;
        });

        $promedioRespuesta = 0;
        if ($ticketsConRespuesta->count() > 0) {
            $promedioRespuesta = $ticketsConRespuesta->avg(function($t) {
                return $t->tiempo_respuesta ?? 0;
            });
        }

        $promedioResolucion = 0;
        if ($ticketsConResolucion->count() > 0) {
            $promedioResolucion = $ticketsConResolucion->avg(function($t) {
                return $t->tiempo_resolucion ?? 0;
            });
        }

        // Porcentaje de cumplimiento (tickets cerrados vs total)
        $ticketsCerrados = $tickets->where('Estatus', 'Cerrado')->count();
        $porcentajeCumplimiento = $tickets->count() > 0 
            ? round(($ticketsCerrados / $tickets->count()) * 100, 2) 
            : 0;

        // Totales por empleado
        $totalesPorEmpleado = [];
        foreach ($tickets as $ticket) {
            $empleadoNombre = 'Sin empleado';
            if ($ticket->responsableTI && $ticket->responsableTI->NombreEmpleado) {
                $empleadoNombre = $ticket->responsableTI->NombreEmpleado;
            }

            if (!isset($totalesPorEmpleado[$empleadoNombre])) {
                $totalesPorEmpleado[$empleadoNombre] = [
                    'empleado' => $empleadoNombre,
                    'total' => 0,
                    'cerrados' => 0,
                    'en_progreso' => 0,
                    'pendientes' => 0
                ];
            }

            $totalesPorEmpleado[$empleadoNombre]['total']++;

            if ($ticket->Estatus === 'Cerrado') {
                $totalesPorEmpleado[$empleadoNombre]['cerrados']++;
            } elseif ($ticket->Estatus === 'En progreso') {
                $totalesPorEmpleado[$empleadoNombre]['en_progreso']++;
            } else {
                $totalesPorEmpleado[$empleadoNombre]['pendientes']++;
            }
        }

        return [
            'incidencias_por_gerencia' => $incidenciasPorGerencia,
            'promedio_tiempo_respuesta' => round($promedioRespuesta, 2),
            'promedio_tiempo_resolucion' => round($promedioResolucion, 2),
            'porcentaje_cumplimiento' => $porcentajeCumplimiento,
            'totales_por_empleado' => array_values($totalesPorEmpleado),
            'total_tickets' => $tickets->count(),
            'tickets_cerrados' => $ticketsCerrados
        ];
    }
}
