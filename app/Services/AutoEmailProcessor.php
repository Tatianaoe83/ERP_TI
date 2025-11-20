<?php

namespace App\Services;

use App\Models\Tickets;
use App\Models\TicketChat;
use App\Models\Empleados;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutoEmailProcessor
{
    protected $imapReceiver;
    
    public function __construct()
    {
        $this->imapReceiver = new ImapEmailReceiver();
    }
    
    /**
     * Procesar automáticamente respuestas de correo
     */
    public function procesarRespuestasAutomaticas()
    {
        try {
            Log::info('Iniciando procesamiento automático de respuestas');
            
            // Intentar conectar con IMAP
            $connection = $this->imapReceiver->conectarIMAP();
            
            if ($connection) {
                Log::info('Conexión IMAP exitosa, procesando correos');
                return $this->procesarConIMAP();
            } else {
                Log::warning('Conexión IMAP fallida, usando procesamiento alternativo');
                return $this->procesarSinIMAP();
            }
            
        } catch (\Exception $e) {
            Log::error('Error en procesamiento automático: ' . $e->getMessage());
            return $this->procesarSinIMAP();
        }
    }
    
    /**
     * Procesar con IMAP (cuando funciona)
     */
    private function procesarConIMAP()
    {
        try {
            $resultado = $this->imapReceiver->procesarCorreosNuevos();
            
            if ($resultado) {
                Log::info('Correos procesados exitosamente con IMAP');
                return [
                    'success' => true,
                    'method' => 'imap',
                    'message' => 'Correos procesados automáticamente con IMAP'
                ];
            } else {
                Log::info('No se encontraron correos nuevos con IMAP');
                return [
                    'success' => true,
                    'method' => 'imap',
                    'message' => 'No hay correos nuevos para procesar'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error procesando con IMAP: ' . $e->getMessage());
            return $this->procesarSinIMAP();
        }
    }
    
    /**
     * Procesar sin IMAP (procesamiento alternativo)
     */
    private function procesarSinIMAP()
    {
        try {
            // Buscar tickets con correos enviados recientemente
            $ticketsConCorreos = Tickets::whereHas('chat', function($query) {
                $query->where('remitente', 'soporte')
                      ->where('es_correo', true)
                      ->where('created_at', '>', now()->subDays(3)); // Últimos 3 días
            })->with(['empleado', 'chat' => function($query) {
                $query->where('remitente', 'soporte')
                      ->where('es_correo', true)
                      ->orderBy('created_at', 'desc');
            }])->get();
            
            $procesados = 0;
            
            foreach ($ticketsConCorreos as $ticket) {
                // Verificar si ya hay respuesta del usuario
                $respuestaUsuario = TicketChat::where('ticket_id', $ticket->TicketID)
                    ->where('remitente', 'usuario')
                    ->where('es_correo', true)
                    ->where('created_at', '>', $ticket->chat->first()->created_at)
                    ->first();
                
                if (!$respuestaUsuario) {
                    // No hay respuesta del usuario aún
                    Log::info("Ticket #{$ticket->TicketID} esperando respuesta del usuario");
                    continue;
                }
                
                $procesados++;
            }
            
            Log::info("Procesamiento alternativo completado. Tickets verificados: {$ticketsConCorreos->count()}");
            
            return [
                'success' => true,
                'method' => 'alternative',
                'message' => "Verificados {$ticketsConCorreos->count()} tickets, {$procesados} con respuestas",
                'tickets_checked' => $ticketsConCorreos->count(),
                'tickets_with_responses' => $procesados
            ];
            
        } catch (\Exception $e) {
            Log::error('Error en procesamiento alternativo: ' . $e->getMessage());
            return [
                'success' => false,
                'method' => 'alternative',
                'message' => 'Error en procesamiento alternativo: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Simular respuesta automática (para pruebas)
     */
    public function simularRespuestaAutomatica($ticketId, $mensajeRespuesta)
    {
        try {
            $ticket = Tickets::with('empleado')->find($ticketId);
            
            if (!$ticket) {
                throw new \Exception('Ticket no encontrado');
            }
            
            // Crear respuesta simulada del usuario
            TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => $mensajeRespuesta,
                'remitente' => 'usuario',
                'nombre_remitente' => $ticket->empleado->NombreEmpleado,
                'correo_remitente' => $ticket->empleado->Correo,
                'message_id' => $this->generarMessageId(),
                'thread_id' => $this->obtenerThreadIdDelTicket($ticketId),
                'es_correo' => true,
                'leido' => false
            ]);
            
            Log::info("Respuesta simulada creada para ticket #{$ticketId}");
            
            return [
                'success' => true,
                'message' => 'Respuesta simulada creada exitosamente'
            ];
            
        } catch (\Exception $e) {
            Log::error("Error simulando respuesta: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error simulando respuesta: ' . $e->getMessage()
            ];
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
    
    /**
     * Crear webhook para recibir respuestas automáticamente
     */
    public function crearWebhookEndpoint()
    {
        // Esta función podría crear un endpoint webhook
        // que reciba notificaciones cuando lleguen correos
        return [
            'endpoint' => '/api/webhook/email-response',
            'method' => 'POST',
            'description' => 'Endpoint para recibir respuestas de correo automáticamente'
        ];
    }
}
