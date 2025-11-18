<?php

namespace App\Services;

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use App\Models\Tickets;
use App\Models\TicketChat;
use App\Models\Empleados;
use Illuminate\Support\Facades\Log;

class SimpleWebklexImapService
{
    protected $client;
    protected $clientManager;
    
    public function __construct()
    {
        $this->clientManager = new ClientManager([
            'default' => 'default',
            'accounts' => [
                'default' => [
                    'host'          => env('IMAP_HOST', 'proser.com.mx'),
                    'port'          => env('IMAP_PORT', 993),
                    'encryption'    => env('IMAP_ENCRYPTION', 'ssl'),
                    'validate_cert' => env('IMAP_VALIDATE_CERT', false),
                    'username'      => env('IMAP_USERNAME', 'tordonez@proser.com.mx'),
                    'password'      => env('IMAP_PASSWORD'),
                    'protocol'      => 'imap',
                ]
            ]
        ]);
        
        $this->client = $this->clientManager->account('default');
    }
    
    /**
     * Probar conexión básica con manejo mejorado de errores
     */
    public function probarConexion()
    {
        try {
            Log::info('Probando conexión IMAP básica...');
            
            $this->client->connect();
            
            // Solo obtener información básica del buzón
            $folder = $this->client->getFolder('INBOX');
            
            Log::info("Conexión exitosa al buzón INBOX");
            
            return [
                'success' => true,
                'message' => "Conexión exitosa al buzón INBOX",
                'folder_name' => 'INBOX'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error probando conexión: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Probar procesamiento de correos sin errores
     */
    public function probarProcesamiento()
    {
        try {
            Log::info('Probando procesamiento de correos...');
            
            if (!$this->conectar()) {
                return [
                    'success' => false,
                    'message' => 'No se pudo conectar al servidor IMAP'
                ];
            }
            
            $folder = $this->client->getFolder('INBOX');
            
            // Probar obtención de mensajes de manera segura
            $messages = $this->obtenerMensajesSeguro($folder);
            
            return [
                'success' => true,
                'message' => "Procesamiento exitoso. Encontrados {$messages->count()} mensajes",
                'mensajes_encontrados' => $messages->count()
            ];
            
        } catch (\Exception $e) {
            Log::error('Error probando procesamiento: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error en procesamiento: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener información básica del buzón
     */
    public function obtenerInfoBasica()
    {
        try {
            if (!$this->conectar()) {
                return null;
            }
            
            $folder = $this->client->getFolder('INBOX');
            
            return [
                'folder_name' => 'INBOX',
                'connection_status' => 'connected',
                'message' => 'Conexión exitosa'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo información básica: ' . $e->getMessage());
            return [
                'folder_name' => 'INBOX',
                'connection_status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Conectar al servidor IMAP
     */
    public function conectar()
    {
        try {
            Log::info('Conectando a IMAP con Webklex...');
            
            $this->client->connect();
            
            Log::info('Conexión IMAP exitosa con Webklex');
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error conectando a IMAP con Webklex: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Procesar correos usando método simple
     */
    public function procesarCorreosSimples()
    {
        try {
            if (!$this->conectar()) {
                return false;
            }
            
            Log::info('Procesando correos con método simple...');
            
            $folder = $this->client->getFolder('INBOX');
            
            // Usar método más seguro para obtener mensajes
            $messages = $this->obtenerMensajesSeguro($folder);
            
            Log::info("Encontrados {$messages->count()} mensajes");
            
            $procesados = 0;
            
            foreach ($messages as $message) {
                if ($this->procesarMensajeSimple($message)) {
                    $procesados++;
                }
            }
            
            Log::info("Procesados {$procesados} mensajes exitosamente");
            
            return $procesados > 0;
            
        } catch (\Exception $e) {
            Log::error('Error procesando correos simples: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener mensajes de manera segura filtrando solo respuestas de tickets
     */
    protected function obtenerMensajesSeguro($folder)
    {
        try {
            // Método 1: Intentar obtener mensajes recientes con fecha específica
            $messages = $folder->messages()->since(now()->subDays(7))->limit(50)->get();
            if ($messages->count() > 0) {
                Log::info("Método 1 exitoso: encontrados {$messages->count()} mensajes de los últimos 7 días");
                return $this->filtrarRespuestasTickets($messages);
            }
        } catch (\Exception $e) {
            Log::warning("Método 1 falló: " . $e->getMessage());
        }
        
        try {
            // Método 2: Intentar obtener mensajes con ALL (todos los mensajes)
            $messages = $folder->messages()->all()->limit(50)->get();
            if ($messages->count() > 0) {
                Log::info("Método 2 exitoso: encontrados {$messages->count()} mensajes");
                return $this->filtrarRespuestasTickets($messages);
            }
        } catch (\Exception $e) {
            Log::warning("Método 2 falló: " . $e->getMessage());
        }
        
        try {
            // Método 3: Usar query básica sin parámetros complejos
            $messages = $folder->query()->limit(50)->get();
            if ($messages->count() > 0) {
                Log::info("Método 3 exitoso: encontrados {$messages->count()} mensajes");
                return $this->filtrarRespuestasTickets($messages);
            }
        } catch (\Exception $e) {
            Log::warning("Método 3 falló: " . $e->getMessage());
        }
        
        // Método 4: Fallback - retornar colección vacía
        Log::warning("Todos los métodos de obtención de mensajes fallaron, retornando colección vacía");
        return collect();
    }
    
    /**
     * Filtrar solo los mensajes que son respuestas de tickets
     */
    protected function filtrarRespuestasTickets($messages)
    {
        $respuestasTickets = collect();
        
        foreach ($messages as $message) {
            try {
                $subject = $message->getSubject();
                
                // Solo procesar si contiene "Ticket #" en el asunto
                if ($this->esRespuestaTicket($subject)) {
                    $respuestasTickets->push($message);
                    Log::info("Mensaje de ticket encontrado: {$subject}");
                }
            } catch (\Exception $e) {
                Log::warning("Error procesando mensaje para filtro: " . $e->getMessage());
                continue;
            }
        }
        
        Log::info("Filtrados {$respuestasTickets->count()} mensajes de respuestas de tickets de {$messages->count()} mensajes totales");
        
        return $respuestasTickets;
    }
    
    /**
     * Procesar un mensaje de manera simple
     */
    protected function procesarMensajeSimple($message)
    {
        try {
            // Obtener información básica del mensaje
            $subject = $message->getSubject();
            $from = $message->getFrom();
            $body = $message->getTextBody();
            $fromEmail = $from ? $from->first()->mail : 'desconocido@email.com';
            
            Log::info("Procesando mensaje: {$subject}");
            Log::info("De: {$fromEmail}");
            
            // Solo procesar si es una respuesta a un ticket
            if (!$this->esRespuestaTicket($subject)) {
                Log::info("No es respuesta de ticket: {$subject}");
                return false;
            }
            
            // Verificar que no sea un correo del sistema
            if ($this->esCorreoSistema($fromEmail)) {
                Log::info("Correo del sistema ignorado: {$fromEmail}");
                return false;
            }
            
            // Buscar ticket por asunto
            $ticket = $this->buscarTicketPorAsunto($subject);
            
            if (!$ticket) {
                Log::info("No se encontró ticket para: {$subject}");
                return false;
            }
            
            // Verificar que el correo no haya sido procesado antes
            if ($this->correoYaProcesado($ticket->TicketID, $fromEmail, $subject)) {
                Log::info("Correo ya procesado anteriormente: {$subject}");
                return false;
            }
            
            // Crear respuesta del usuario
            $this->crearRespuestaUsuario($ticket, $body, $from);
            
            Log::info("Respuesta procesada para ticket #{$ticket->TicketID}");
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error procesando mensaje simple: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si el correo es del sistema
     */
    protected function esCorreoSistema($fromEmail)
    {
        $correosSistema = [
            'tordonez@proser.com.mx',
            'sistema@proser.com.mx',
            'noreply@proser.com.mx',
            'tickets@proser.com.mx'
        ];
        
        return in_array(strtolower($fromEmail), $correosSistema);
    }
    
    /**
     * Verificar si el correo ya fue procesado
     */
    protected function correoYaProcesado($ticketId, $fromEmail, $subject)
    {
        $existingChat = TicketChat::where('ticket_id', $ticketId)
            ->where('correo_remitente', $fromEmail)
            ->where('es_correo', true)
            ->where('mensaje', 'LIKE', '%' . substr($subject, 0, 50) . '%')
            ->first();
            
        return $existingChat !== null;
    }
    
    /**
     * Verificar si es una respuesta a un ticket (más específico)
     */
    protected function esRespuestaTicket($subject)
    {
        // Limpiar el asunto para mejor comparación
        $subject = trim($subject);
        
        // Patrones específicos para respuestas de tickets
        $patrones = [
            // Respuestas directas: "Re: Ticket #32"
            '/^Re:\s*Ticket\s*#\d+/i',
            // Respuestas con espacios: "Re:  Ticket #32"
            '/^Re:\s+Ticket\s*#\d+/i',
            // Respuestas sin "Re:": "Ticket #32"
            '/^Ticket\s*#\d+/i',
            // Respuestas con texto adicional: "Re: Ticket #32 - Problema"
            '/^Re:\s*Ticket\s*#\d+\s*-/i',
            // Respuestas con texto adicional sin "Re:": "Ticket #32 - Problema"
            '/^Ticket\s*#\d+\s*-/i',
            // Respuestas con texto en el medio: "Respuesta Ticket #32"
            '/Ticket\s*#\d+/i'
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match($patron, $subject)) {
                Log::info("Asunto coincide con patrón de ticket: {$subject}");
                return true;
            }
        }
        
        Log::info("Asunto NO coincide con patrones de ticket: {$subject}");
        return false;
    }
    
    /**
     * Buscar ticket por asunto (mejorado)
     */
    protected function buscarTicketPorAsunto($subject)
    {
        // Limpiar el asunto
        $subject = trim($subject);
        
        // Patrones para extraer el número de ticket
        $patrones = [
            // "Re: Ticket #32" o "Re:  Ticket #32"
            '/Re:\s*Ticket\s*#(\d+)/i',
            // "Ticket #32"
            '/Ticket\s*#(\d+)/i',
            // "Re: Ticket #32 - Descripción"
            '/Re:\s*Ticket\s*#(\d+)\s*-/i',
            // "Ticket #32 - Descripción"
            '/Ticket\s*#(\d+)\s*-/i',
            // "Respuesta Ticket #32"
            '/Ticket\s*#(\d+)/i'
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match($patron, $subject, $matches)) {
                $ticketId = (int) $matches[1];
                Log::info("Extraído Ticket ID: {$ticketId} del asunto: {$subject}");
                
                $ticket = Tickets::find($ticketId);
                if ($ticket) {
                    Log::info("Ticket encontrado: #{$ticketId} - {$ticket->Titulo}");
                    return $ticket;
                } else {
                    Log::warning("Ticket #{$ticketId} no encontrado en la base de datos");
                }
            }
        }
        
        Log::warning("No se pudo extraer número de ticket del asunto: {$subject}");
        return null;
    }
    
    /**
     * Crear respuesta del usuario
     */
    protected function crearRespuestaUsuario($ticket, $body, $from)
    {
        $fromEmail = $from ? $from->first()->mail : $ticket->empleado->Correo;
        $fromName = $from ? $from->first()->personal : $ticket->empleado->NombreEmpleado;
        
        TicketChat::create([
            'ticket_id' => $ticket->TicketID,
            'mensaje' => $body,
            'remitente' => 'usuario',
            'nombre_remitente' => $fromName,
            'correo_remitente' => $fromEmail,
            'message_id' => $this->generarMessageId(),
            'thread_id' => $this->obtenerThreadIdDelTicket($ticket->TicketID),
            'es_correo' => true,
            'leido' => false
        ]);
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
