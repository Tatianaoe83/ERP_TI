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
     * Obtener mensajes de manera segura sin usar búsquedas complejas
     */
    protected function obtenerMensajesSeguro($folder)
    {
        try {
            // Método 1: Intentar obtener mensajes recientes con fecha específica
            $messages = $folder->messages()->since(now()->subDays(1))->limit(20)->get();
            if ($messages->count() > 0) {
                Log::info("Método 1 exitoso: encontrados {$messages->count()} mensajes del último día");
                return $messages;
            }
        } catch (\Exception $e) {
            Log::warning("Método 1 falló: " . $e->getMessage());
        }
        
        try {
            // Método 2: Intentar obtener mensajes con ALL (todos los mensajes)
            $messages = $folder->messages()->all()->limit(20)->get();
            if ($messages->count() > 0) {
                Log::info("Método 2 exitoso: encontrados {$messages->count()} mensajes");
                return $messages;
            }
        } catch (\Exception $e) {
            Log::warning("Método 2 falló: " . $e->getMessage());
        }
        
        try {
            // Método 3: Usar query básica sin parámetros complejos
            $messages = $folder->query()->limit(20)->get();
            if ($messages->count() > 0) {
                Log::info("Método 3 exitoso: encontrados {$messages->count()} mensajes");
                return $messages;
            }
        } catch (\Exception $e) {
            Log::warning("Método 3 falló: " . $e->getMessage());
        }
        
        // Método 4: Fallback - retornar colección vacía
        Log::warning("Todos los métodos de obtención de mensajes fallaron, retornando colección vacía");
        return collect();
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
            
            Log::info("Procesando mensaje: {$subject}");
            
            // Solo procesar si es una respuesta a un ticket
            if (!$this->esRespuestaTicket($subject)) {
                Log::info("No es respuesta de ticket: {$subject}");
                return false;
            }
            
            // Buscar ticket por asunto
            $ticket = $this->buscarTicketPorAsunto($subject);
            
            if (!$ticket) {
                Log::info("No se encontró ticket para: {$subject}");
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
     * Verificar si es una respuesta a un ticket
     */
    protected function esRespuestaTicket($subject)
    {
        // Buscar patrones de respuesta
        $patrones = [
            '/Re:\s*Ticket\s*#\d+/i',
            '/Ticket\s*#\d+/i',
            '/Respuesta.*Ticket/i',
            '/Reply.*Ticket/i'
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match($patron, $subject)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Buscar ticket por asunto
     */
    protected function buscarTicketPorAsunto($subject)
    {
        // Buscar por patrón "Re: Ticket #X"
        if (preg_match('/Re:\s*Ticket\s*#(\d+)/i', $subject, $matches)) {
            $ticketId = $matches[1];
            return Tickets::find($ticketId);
        }
        
        // Buscar por patrón "Ticket #X"
        if (preg_match('/Ticket\s*#(\d+)/i', $subject, $matches)) {
            $ticketId = $matches[1];
            return Tickets::find($ticketId);
        }
        
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
