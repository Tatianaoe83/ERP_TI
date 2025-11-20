<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AutoEmailProcessor;
use App\Models\Tickets;
use App\Models\TicketChat;
use Illuminate\Support\Facades\Log;

class EmailWebhookController extends Controller
{
    protected $processor;
    
    public function __construct()
    {
        $this->processor = new AutoEmailProcessor();
    }
    
    /**
     * Webhook para recibir notificaciones de correo
     */
    public function handleEmailResponse(Request $request)
    {
        try {
            Log::info('Webhook de correo recibido', $request->all());
            
            // Validar datos del webhook
            $validatedData = $request->validate([
                'ticket_id' => 'required|integer',
                'from_email' => 'required|email',
                'from_name' => 'required|string',
                'subject' => 'required|string',
                'message' => 'required|string',
                'message_id' => 'nullable|string',
                'thread_id' => 'nullable|string'
            ]);
            
            // Verificar que el ticket existe
            $ticket = Tickets::find($validatedData['ticket_id']);
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }
            
            // Crear respuesta del usuario
            TicketChat::create([
                'ticket_id' => $validatedData['ticket_id'],
                'mensaje' => $validatedData['message'],
                'remitente' => 'usuario',
                'nombre_remitente' => $validatedData['from_name'],
                'correo_remitente' => $validatedData['from_email'],
                'message_id' => $validatedData['message_id'] ?: $this->generarMessageId(),
                'thread_id' => $validatedData['thread_id'] ?: $this->obtenerThreadIdDelTicket($validatedData['ticket_id']),
                'es_correo' => true,
                'leido' => false
            ]);
            
            Log::info("Respuesta de correo procesada automáticamente para ticket #{$validatedData['ticket_id']}");
            
            return response()->json([
                'success' => true,
                'message' => 'Respuesta procesada automáticamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en webhook de correo: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando respuesta: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Endpoint para procesar respuestas manualmente via API
     */
    public function processManualResponse(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'ticket_id' => 'required|integer',
                'message' => 'required|string',
                'from_name' => 'nullable|string',
                'from_email' => 'nullable|email'
            ]);
            
            $ticket = Tickets::with('empleado')->find($validatedData['ticket_id']);
            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket no encontrado'
                ], 404);
            }
            
            // Usar datos del ticket si no se proporcionan
            $fromName = $validatedData['from_name'] ?: $ticket->empleado->NombreEmpleado;
            $fromEmail = $validatedData['from_email'] ?: $ticket->empleado->Correo;
            
            // Crear respuesta
            TicketChat::create([
                'ticket_id' => $validatedData['ticket_id'],
                'mensaje' => $validatedData['message'],
                'remitente' => 'usuario',
                'nombre_remitente' => $fromName,
                'correo_remitente' => $fromEmail,
                'message_id' => $this->generarMessageId(),
                'thread_id' => $this->obtenerThreadIdDelTicket($validatedData['ticket_id']),
                'es_correo' => true,
                'leido' => false
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Respuesta procesada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generar Message-ID único
     */
    private function generarMessageId()
    {
        $domain = 'proser.com.mx';
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

        $domain = 'proser.com.mx';
        return "<thread-ticket-{$ticketId}-" . time() . "@{$domain}>";
    }
}
