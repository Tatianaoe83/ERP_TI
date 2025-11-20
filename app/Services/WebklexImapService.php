<?php

namespace App\Services;

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use App\Models\Tickets;
use App\Models\TicketChat;
use App\Models\Empleados;
use Illuminate\Support\Facades\Log;

class WebklexImapService
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
     * Procesar correos nuevos
     */
    public function procesarCorreosNuevos()
    {
        try {
            if (!$this->conectar()) {
                return false;
            }
            
            Log::info('Procesando correos nuevos con Webklex...');
            
            // Obtener buzón de entrada
            $folder = $this->client->getFolder('INBOX');
            
            // Intentar diferentes métodos de búsqueda
            $messages = $this->obtenerMensajesNoLeidos($folder);
            
            Log::info("Encontrados {$messages->count()} mensajes no leídos");
            
            $procesados = 0;
            
            foreach ($messages as $message) {
                if ($this->procesarMensaje($message)) {
                    $procesados++;
                }
            }
            
            Log::info("Procesados {$procesados} mensajes exitosamente");
            
            return $procesados > 0;
            
        } catch (\Exception $e) {
            Log::error('Error procesando correos con Webklex: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener mensajes no leídos con diferentes métodos
     */
    protected function obtenerMensajesNoLeidos($folder)
    {
        try {
            // Método 1: Buscar por flag UNSEEN
            $messages = $folder->messages()->unseen()->limit(50)->get();
            if ($messages->count() > 0) {
                Log::info("Método 1 exitoso: encontrados {$messages->count()} mensajes");
                return $messages;
            }
        } catch (\Exception $e) {
            Log::warning("Método 1 falló: " . $e->getMessage());
        }
        
        try {
            // Método 2: Buscar todos los mensajes y filtrar
            $messages = $folder->messages()->limit(100)->get();
            $unseenMessages = $messages->filter(function($message) {
                return !$message->hasFlag('Seen');
            });
            Log::info("Método 2 exitoso: encontrados {$unseenMessages->count()} mensajes");
            return $unseenMessages;
        } catch (\Exception $e) {
            Log::warning("Método 2 falló: " . $e->getMessage());
        }
        
        try {
            // Método 3: Buscar mensajes recientes (últimos 7 días)
            $messages = $folder->messages()->since(now()->subDays(7))->limit(50)->get();
            Log::info("Método 3 exitoso: encontrados {$messages->count()} mensajes recientes");
            return $messages;
        } catch (\Exception $e) {
            Log::warning("Método 3 falló: " . $e->getMessage());
        }
        
        // Método 4: Fallback - obtener últimos 20 mensajes
        try {
            $messages = $folder->messages()->limit(20)->get();
            Log::info("Método 4 (fallback): encontrados {$messages->count()} mensajes");
            return $messages;
        } catch (\Exception $e) {
            Log::error("Todos los métodos fallaron: " . $e->getMessage());
            return collect();
        }
    }
    
    /**
     * Procesar un mensaje individual
     */
    protected function procesarMensaje($message)
    {
        try {
            // Obtener información del mensaje
            $subject = $message->getSubject();
            $from = $message->getFrom();
            $body = $message->getTextBody();
            $messageId = $message->getMessageId();
            $threadId = $this->extraerThreadId($message);
            
            Log::info("Procesando mensaje: {$subject}");
            Log::info("De: " . ($from ? $from->first()->mail : 'Desconocido'));
            
            // Buscar ticket por asunto o Message-ID
            $ticket = $this->buscarTicketPorMensaje($subject, $messageId, $threadId);
            
            if (!$ticket) {
                Log::info("No se encontró ticket para el mensaje: {$subject}");
                return false;
            }
            
            // Crear respuesta del usuario
            $this->crearRespuestaUsuario($ticket, $body, $from, $messageId, $threadId);
            
            // Marcar mensaje como leído
            $message->setFlag('Seen');
            
            Log::info("Respuesta procesada para ticket #{$ticket->TicketID}");
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error procesando mensaje individual: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar ticket por mensaje
     */
    protected function buscarTicketPorMensaje($subject, $messageId, $threadId)
    {
        // Buscar por Thread-ID en la base de datos
        if ($threadId) {
            $chat = TicketChat::where('thread_id', $threadId)->first();
            if ($chat) {
                return Tickets::find($chat->ticket_id);
            }
        }
        
        // Buscar por Message-ID
        if ($messageId) {
            $chat = TicketChat::where('message_id', $messageId)->first();
            if ($chat) {
                return Tickets::find($chat->ticket_id);
            }
        }
        
        // Buscar por asunto (patrón "Re: Ticket #X")
        if (preg_match('/Re:\s*Ticket\s*#(\d+)/i', $subject, $matches)) {
            $ticketId = $matches[1];
            return Tickets::find($ticketId);
        }
        
        // Buscar por asunto (patrón "Ticket #X")
        if (preg_match('/Ticket\s*#(\d+)/i', $subject, $matches)) {
            $ticketId = $matches[1];
            return Tickets::find($ticketId);
        }
        
        return null;
    }
    
    /**
     * Crear respuesta del usuario
     */
    protected function crearRespuestaUsuario($ticket, $body, $from, $messageId, $threadId)
    {
        $fromEmail = $from ? $from->first()->mail : $ticket->empleado->Correo;
        $fromName = $from ? $from->first()->personal : $ticket->empleado->NombreEmpleado;
        
        TicketChat::create([
            'ticket_id' => $ticket->TicketID,
            'mensaje' => $body,
            'remitente' => 'usuario',
            'nombre_remitente' => $fromName,
            'correo_remitente' => $fromEmail,
            'message_id' => $messageId,
            'thread_id' => $threadId,
            'es_correo' => true,
            'leido' => false
        ]);
    }
    
    /**
     * Extraer Thread-ID del mensaje
     */
    protected function extraerThreadId($message)
    {
        $headers = $message->getHeaders();
        
        // Buscar Thread-ID en headers
        if (isset($headers->thread_id)) {
            return $headers->thread_id;
        }
        
        // Buscar In-Reply-To
        if (isset($headers->in_reply_to)) {
            return $headers->in_reply_to;
        }
        
        // Buscar References
        if (isset($headers->references)) {
            $references = explode(' ', $headers->references);
            return end($references);
        }
        
        return null;
    }
    
    /**
     * Obtener estadísticas del buzón
     */
    public function obtenerEstadisticas()
    {
        try {
            if (!$this->conectar()) {
                return null;
            }
            
            $folder = $this->client->getFolder('INBOX');
            
            // Obtener estadísticas de manera más segura
            $total = $folder->messages()->limit(1000)->count();
            $unseenMessages = $folder->messages()->unseen()->limit(100)->get();
            $unseen = $unseenMessages->count();
            $seen = $total - $unseen;
            
            return [
                'total' => $total,
                'unseen' => $unseen,
                'seen' => $seen,
                'connection_status' => 'connected'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            return [
                'total' => 0,
                'unseen' => 0,
                'seen' => 0,
                'connection_status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Probar conexión
     */
    public function probarConexion()
    {
        try {
            Log::info('Probando conexión IMAP con Webklex...');
            
            $this->client->connect();
            
            $folder = $this->client->getFolder('INBOX');
            $messageCount = $folder->messages()->limit(100)->count();
            
            Log::info("Conexión exitosa. Mensajes en INBOX: {$messageCount}");
            
            return [
                'success' => true,
                'message' => "Conexión exitosa. Mensajes en INBOX: {$messageCount}",
                'message_count' => $messageCount
            ];
            
        } catch (\Exception $e) {
            Log::error('Error probando conexión: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }
}
