<?php

namespace App\Http\Controllers;

use App\Models\Empleados;
use App\Models\Solicitud;
use App\Models\Tickets;
use App\Models\TicketChat;
use App\Models\Tertipos;
use App\Models\Subtipos;
use App\Models\Tipos;
use App\Services\SimpleEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketsController extends Controller
{
    protected $emailService;

    public function __construct(SimpleEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function index()
    {
        $tickets = Tickets::with(['empleado', 'chat' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(1);
        }])->orderBy('created_at', 'desc')->get();

        $ticketsStatus = [
            'nuevos' => $tickets->where('Estatus', 'Pendiente'),
            'proceso' => $tickets->where('Estatus', 'En progreso'),
            'resueltos' => $tickets->where('Estatus', 'Cerrado'),
        ];

        $responsablesTI = Empleados::where('ObraID', 46)->where('tipo_persona', 'FISICA')->get();

        return view('tickets.index', compact('ticketsStatus', 'responsablesTI'));
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

            // Actualizar los campos permitidos
            if ($request->has('prioridad')) {
                $ticket->Prioridad = $request->input('prioridad');
            }

            if ($request->has('responsableTI')) {
                $ticket->ResponsableTI = $request->input('responsableTI');
            }

            if ($request->has('estatus')) {
                $ticket->Estatus = $request->input('estatus');
            }

            $ticket->save();

            return response()->json([
                'success' => true,
                'message' => 'Ticket actualizado correctamente',
                'ticket' => $ticket
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
            $tipos = Tipos::select('TipoID', 'NombreTipo')
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
}
