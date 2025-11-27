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
    
    // Constantes de configuración
    private const DIAS_BUSQUEDA = 7;
    private const TIEMPO_MAX_SEGUNDOS = 120;
    private const MEMORIA_MAX_MB = 800;
    
    public function __construct()
    {
        $this->clientManager = new ClientManager([
            'default' => 'default',
            'accounts' => [
                'default' => [
                    'host'          => config('email_tickets.imap.host'),
                    'port'          => config('email_tickets.imap.port', 993),
                    'encryption'    => config('email_tickets.imap.encryption', 'ssl'),
                    'validate_cert' => config('email_tickets.imap.validate_cert', false),
                    'username'      => config('email_tickets.imap.username'),
                    'password'      => config('email_tickets.imap.password'),
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
            Log::info('Conectando a IMAP...');
            $this->client->connect();
            Log::info('Conexión IMAP exitosa');
            return true;
        } catch (\Exception $e) {
            Log::error('Error conectando a IMAP: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Procesar correos - Método principal optimizado
     */
    public function procesarCorreosSimples()
    {
        try {
            // Configurar límites
            set_time_limit(180);
            $originalMemoryLimit = ini_get('memory_limit');
            ini_set('memory_limit', '1024M');
            
            if (!$this->conectar()) {
                return false;
            }
            
            Log::info('Iniciando procesamiento de correos');
            
            $folder = $this->client->getFolder('INBOX');
            $messages = $this->obtenerMensajesRecientes($folder);
            
            if ($messages->isEmpty()) {
                Log::warning('No se encontraron mensajes para procesar');
                return false;
            }
            
            Log::info("Mensajes a procesar: {$messages->count()}");
            
            // Procesar mensajes
            $resultado = $this->procesarMensajes($messages);
            
            // Restaurar configuración
            ini_set('memory_limit', $originalMemoryLimit);
            
            Log::info("Procesamiento completado - Procesados: {$resultado['procesados']}, Descartados: {$resultado['descartados']}, Tiempo: {$resultado['tiempo']}s");
            
            return $resultado['procesados'] > 0;
            
        } catch (\Exception $e) {
            Log::error('Error en procesamiento: ' . $e->getMessage());
            if (isset($originalMemoryLimit)) {
                ini_set('memory_limit', $originalMemoryLimit);
            }
            return false;
        }
    }
    
    /**
     * Obtener mensajes recientes (últimos 7 días)
     */
    protected function obtenerMensajesRecientes($folder)
    {
        try {
            $fechaInicio = now()->subDays(self::DIAS_BUSQUEDA)->startOfDay();
            Log::info("Obteniendo mensajes desde: {$fechaInicio->format('Y-m-d H:i:s')}");
            
            $mensajes = $folder->messages()
                ->since($fechaInicio)
                ->leaveUnread()
                ->get();
            
            // Filtrar por fecha (últimos 7 días hasta mañana)
            $mensajesFiltrados = $this->filtrarPorFecha($mensajes);
            
            // Ordenar por fecha descendente
            $mensajesFiltrados = $this->ordenarPorFecha($mensajesFiltrados);
            
            Log::info("Mensajes obtenidos: {$mensajesFiltrados->count()}");
            
            return $mensajesFiltrados;
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo mensajes: ' . $e->getMessage());
            return collect();
        }
    }
    
    /**
     * Filtrar mensajes por fecha (últimos 7 días)
     */
    private function filtrarPorFecha($mensajes)
    {
        $fechaLimiteInferior = now()->subDays(self::DIAS_BUSQUEDA)->startOfDay();
        $fechaLimiteSuperior = now()->addDay()->endOfDay();
        
        $filtrados = $mensajes->filter(function($mensaje) use ($fechaLimiteInferior, $fechaLimiteSuperior) {
            try {
                $fechaMensaje = $mensaje->getDate();
                
                if (!$fechaMensaje) {
                    return true; // Incluir si no tiene fecha
                }
                
                // Parsear fecha y convertir correctamente de UTC a zona horaria local
                // Asumir que las fechas de email vienen en UTC y convertir a zona horaria local
                $fechaCarbon = \Carbon\Carbon::parse($fechaMensaje, 'UTC')->setTimezone(config('app.timezone'));
                
                // Comparar solo por fecha (sin hora) para evitar problemas de zona horaria
                $fechaMensajeSolo = $fechaCarbon->format('Y-m-d');
                $fechaLimiteInferiorSolo = $fechaLimiteInferior->format('Y-m-d');
                $fechaLimiteSuperiorSolo = $fechaLimiteSuperior->format('Y-m-d');
                
                return $fechaMensajeSolo >= $fechaLimiteInferiorSolo && $fechaMensajeSolo <= $fechaLimiteSuperiorSolo;
                
            } catch (\Exception $e) {
                Log::warning("Error filtrando fecha del mensaje: " . $e->getMessage());
                return true; // Incluir en caso de error
            }
        });
        
        return $filtrados;
    }
    
    /**
     * Ordenar mensajes por fecha (más recientes primero)
     */
    private function ordenarPorFecha($mensajes)
    {
        return $mensajes->sortByDesc(function($msg) {
            try {
                $fecha = $msg->getDate();
                return $fecha ? \Carbon\Carbon::parse($fecha) : now()->subYears(10);
            } catch (\Exception $e) {
                return now()->subYears(10);
            }
        });
    }
    
    /**
     * Procesar colección de mensajes
     */
    private function procesarMensajes($mensajes)
    {
        $procesados = 0;
        $descartados = 0;
        $inicioTiempo = microtime(true);
        
        foreach ($mensajes as $index => $mensaje) {
            // Control de tiempo
            if ($this->deberDetenerPorTiempo($inicioTiempo)) {
                break;
            }
            
            // Control de memoria
            if ($this->deberDetenerPorMemoria($index)) {
                break;
            }
            
            // Procesar mensaje
            try {
                if ($this->procesarMensajeSimple($mensaje)) {
                    $procesados++;
                } else {
                    $descartados++;
                }
                
                // Log de progreso cada 20 mensajes
                if ($index > 0 && $index % 20 == 0) {
                    $this->logearProgreso($index, $mensajes->count(), $inicioTiempo);
                }
                
            } catch (\Exception $e) {
                Log::error("Error procesando mensaje #{$index}: " . $e->getMessage());
                $descartados++;
            } finally {
                unset($mensaje);
                
                // Liberar memoria cada 30 mensajes
                if ($index % 30 == 0) {
                    gc_collect_cycles();
                }
            }
        }
        
        $tiempoTotal = round(microtime(true) - $inicioTiempo, 2);
        
        return [
            'procesados' => $procesados,
            'descartados' => $descartados,
            'tiempo' => $tiempoTotal
        ];
    }
    
    /**
     * Verificar si se debe detener por tiempo
     */
    private function deberDetenerPorTiempo($inicioTiempo)
    {
        $tiempoTranscurrido = microtime(true) - $inicioTiempo;
        
        if ($tiempoTranscurrido > self::TIEMPO_MAX_SEGUNDOS) {
            Log::warning("Tiempo máximo alcanzado ({$tiempoTranscurrido}s), deteniendo");
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si se debe detener por memoria
     */
    private function deberDetenerPorMemoria($index)
    {
        if ($index > 0 && $index % 20 == 0) {
            $memoriaUsada = memory_get_usage(true);
            $memoriaMB = round($memoriaUsada / 1024 / 1024, 2);
            
            if ($memoriaUsada > self::MEMORIA_MAX_MB * 1024 * 1024) {
                Log::warning("Memoria alta ({$memoriaMB} MB), deteniendo");
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Loguear progreso del procesamiento
     */
    private function logearProgreso($indice, $total, $inicioTiempo)
    {
        $tiempoTranscurrido = round(microtime(true) - $inicioTiempo, 2);
        $memoriaMB = round(memory_get_usage(true) / 1024 / 1024, 2);
        
        Log::info("Progreso: {$indice}/{$total} | Tiempo: {$tiempoTranscurrido}s | Memoria: {$memoriaMB}MB");
    }
    
    /**
     * Procesar un mensaje individual
     */
    protected function procesarMensajeSimple($message)
    {
        try {
            $subject = $message->getSubject();
            $from = $message->getFrom();
            $body = $message->getTextBody();
            $fromEmail = $from ? $from->first()->mail : 'desconocido@email.com';
            $fromName = $from ? $from->first()->personal : null;
            $threadId = $this->extraerThreadId($message);
            $messageId = $message->getMessageId();
            $dominio = $this->extraerDominio($fromEmail);
            
            // Filtrar solo correos de dominios permitidos (proser y konkret)
            if (!$this->esDominioPermitido($dominio)) {
                return false;
            }
            
            // Verificar si es correo del sistema
            if ($this->esCorreoSistema($fromEmail)) {
                return false;
            }
            
            // Buscar ticket existente
            $ticket = $this->buscarTicketPorMensaje($subject, $messageId, $threadId, $fromEmail);
            
            if ($ticket) {
                // Respuesta a ticket existente
                if ($this->correoYaProcesado($ticket->TicketID, $fromEmail, $subject)) {
                    return false;
                }
                
                $this->crearRespuestaUsuario($ticket, $body, $from, $messageId, $threadId);
                return true;
            } else {
                // Intentar crear nuevo ticket
                return $this->intentarCrearNuevoTicket($fromEmail, $subject, $body, $messageId, $threadId, $fromName);
            }
            
        } catch (\Exception $e) {
            Log::error('Error procesando mensaje: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Intentar crear nuevo ticket desde correo
     */
    private function intentarCrearNuevoTicket($fromEmail, $subject, $body, $messageId, $threadId, $fromName)
    {
        // Buscar empleado por correo (sin importar mayúsculas/minúsculas)
        $empleado = Empleados::whereRaw('LOWER(Correo) = ?', [strtolower($fromEmail)])->first();
        
        if (!$empleado) {
            return false;
        }
        
        if ($this->esCorreoComunicado($subject, $fromEmail)) {
            return false;
        }
        
        $nuevoTicket = $this->crearTicketDesdeCorreo($empleado, $subject, $body, $messageId, $threadId, $fromName);
        
        if ($nuevoTicket) {
            Log::info("Nuevo ticket #{$nuevoTicket->TicketID} creado desde correo de {$fromEmail} (dominio: " . $this->extraerDominio($fromEmail) . ")");
            return true;
        }
        
        return false;
    }
    
    /**
     * Extraer dominio de un correo electrónico
     */
    private function extraerDominio($email)
    {
        $parts = explode('@', $email);
        return count($parts) > 1 ? strtolower($parts[1]) : 'desconocido';
    }
    
    /**
     * Verificar si el dominio está permitido (solo proser y konkret)
     */
    private function esDominioPermitido($dominio)
    {
        $dominiosPermitidos = ['proser.com.mx', 'konkret.mx'];
        return in_array(strtolower($dominio), $dominiosPermitidos);
    }
    
    /**
     * Verificar si el correo es del sistema
     */
    protected function esCorreoSistema($fromEmail)
    {
        $correosSistema = [
            // Correos del sistema de proser.com.mx
            'tordonez@proser.com.mx',
            'sistema@proser.com.mx',
            'noreply@proser.com.mx',
            'tickets@proser.com.mx',
            // Correos del sistema de konkret.mx
            'sistema@konkret.mx',
            'noreply@konkret.mx',
            'tickets@konkret.mx',
            'tordonez@konkret.mx'
        ];
        
        return in_array(strtolower($fromEmail), $correosSistema);
    }
    
    /**
     * Verificar si el correo ya fue procesado
     */
    protected function correoYaProcesado($ticketId, $fromEmail, $subject)
    {
        return TicketChat::where('ticket_id', $ticketId)
            ->whereRaw('LOWER(correo_remitente) = ?', [strtolower($fromEmail)])
            ->where('es_correo', true)
            ->where('mensaje', 'LIKE', '%' . substr($subject, 0, 50) . '%')
            ->exists();
    }
    
    /**
     * Buscar ticket por mensaje
     */
    protected function buscarTicketPorMensaje($subject, $messageId = null, $threadId = null, $fromEmail = null)
    {
        // 1. Buscar por Thread-ID
        if ($threadId) {
            $ticket = $this->buscarPorThreadId($threadId);
            if ($ticket) return $ticket;
        }
        
        // 2. Buscar por Message-ID
        if ($messageId) {
            $ticket = $this->buscarPorMessageId($messageId);
            if ($ticket) return $ticket;
        }
        
        // 3. Buscar por número de ticket en asunto
        $ticket = $this->buscarPorNumeroTicket($subject);
        if ($ticket) return $ticket;
        
        // 4. Buscar por asunto original (solo si hay empleado)
        if ($fromEmail) {
            $ticket = $this->buscarPorAsuntoOriginal($subject, $fromEmail);
            if ($ticket) return $ticket;
        }
        
        return null;
    }
    
    /**
     * Buscar ticket por Thread-ID
     */
    private function buscarPorThreadId($threadId)
    {
        $chat = TicketChat::where('thread_id', $threadId)
            ->orWhere('message_id', $threadId)
            ->first();
        
        return $chat ? Tickets::find($chat->ticket_id) : null;
    }
    
    /**
     * Buscar ticket por Message-ID
     */
    private function buscarPorMessageId($messageId)
    {
        $chat = TicketChat::where('message_id', $messageId)->first();
        return $chat ? Tickets::find($chat->ticket_id) : null;
    }
    
    /**
     * Buscar ticket por número en asunto
     */
    private function buscarPorNumeroTicket($subject)
    {
        $patrones = [
            '/Ticket\s*#(\d+)/i',
            '/Re:\s*Ticket\s*#(\d+)/i',
        ];
        
        foreach ($patrones as $patron) {
            if (preg_match($patron, $subject, $matches)) {
                $ticketId = (int) $matches[1];
                $ticket = Tickets::find($ticketId);
                
                if ($ticket) {
                    Log::info("Ticket encontrado por número: #{$ticketId}");
                    return $ticket;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Buscar ticket por asunto original
     */
    private function buscarPorAsuntoOriginal($subject, $fromEmail)
    {
        $subjectLimpio = preg_replace('/^(Re:|RE:|Fwd:|FWD:)\s*/i', '', trim($subject));
        
        if (empty($subjectLimpio)) {
            return null;
        }
        
        $empleado = Empleados::whereRaw('LOWER(Correo) = ?', [strtolower($fromEmail)])->first();
        
        if (!$empleado) {
            return null;
        }
        
        // Buscar exacto primero
        $ticket = Tickets::where('Descripcion', $subjectLimpio)
            ->where('EmpleadoID', $empleado->EmpleadoID)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($ticket) {
            Log::info("Ticket encontrado por asunto exacto: #{$ticket->TicketID}");
            return $ticket;
        }
        
        // Buscar con LIKE
        $ticket = Tickets::where('Descripcion', 'LIKE', '%' . $subjectLimpio . '%')
            ->where('EmpleadoID', $empleado->EmpleadoID)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($ticket) {
            Log::info("Ticket encontrado por asunto similar: #{$ticket->TicketID}");
        }
        
        return $ticket;
    }
    
    /**
     * Crear respuesta del usuario
     */
    protected function crearRespuestaUsuario($ticket, $body, $from, $messageId = null, $threadId = null)
    {
        $fromEmail = $from ? $from->first()->mail : $ticket->empleado->Correo;
        $fromName = $from ? $from->first()->personal : $ticket->empleado->NombreEmpleado;
        
        $finalThreadId = $threadId ?: $this->obtenerThreadIdDelTicket($ticket->TicketID);
        $finalMessageId = $messageId ?: $this->generarMessageId();
        
        TicketChat::create([
            'ticket_id' => $ticket->TicketID,
            'mensaje' => $body,
            'remitente' => 'usuario',
            'nombre_remitente' => $fromName,
            'correo_remitente' => $fromEmail,
            'message_id' => $finalMessageId,
            'thread_id' => $finalThreadId,
            'es_correo' => true,
            'leido' => false
        ]);
    }
    
    /**
     * Crear nuevo ticket desde correo
     */
    protected function crearTicketDesdeCorreo($empleado, $subject, $body, $messageId = null, $threadId = null, $fromName = null)
    {
        try {
            $ticket = Tickets::create([
                'EmpleadoID' => $empleado->EmpleadoID,
                'Descripcion' => $subject,
                'Estatus' => 'Pendiente',
                'Prioridad' => 'Media',
                'created_at' => now()
            ]);

            TicketChat::create([
                'ticket_id' => $ticket->TicketID,
                'mensaje' => "Ticket creado automáticamente desde correo:\n\n" . ($body ?: 'Sin contenido'),
                'remitente' => 'usuario',
                'nombre_remitente' => $fromName ?: $empleado->NombreEmpleado,
                'correo_remitente' => $empleado->Correo,
                'message_id' => $messageId,
                'thread_id' => $threadId,
                'es_correo' => true,
                'leido' => false
            ]);

            return $ticket;

        } catch (\Exception $e) {
            Log::error("Error creando ticket: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verificar si es correo de comunicado
     */
    protected function esCorreoComunicado($subject, $fromEmail)
    {
        $correosComunicados = [
            // Correos de comunicados de proser.com.mx
            'comunicacion@proser.com.mx',
            'noreply@proser.com.mx',
            'no-reply@proser.com.mx',
            // Correos de comunicados de konkret.mx
            'comunicacion@konkret.mx',
            'noreply@konkret.mx',
            'no-reply@konkret.mx'
        ];
        
        if (in_array(strtolower($fromEmail), $correosComunicados)) {
            return true;
        }
        
        $patronesComunicados = [
            '/^\[Corporativo\]/i',
            '/^COMUNICADO/i',
            '/^HOY ES ÚLTIMO DÍA/i',
            '/^ÚNETE A LA JORNADA/i',
            '/^USO DEL COMEDOR/i',
            '/^CAJA DE AHORRO/i',
            '/^RECORDATORIO IMPORTANTE/i',
            '/^PLÁTICA INFORMATIVA/i',
        ];
        
        foreach ($patronesComunicados as $patron) {
            if (preg_match($patron, $subject)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extraer Thread-ID del mensaje
     */
    protected function extraerThreadId($message)
    {
        try {
            $headers = $message->getHeaders();
            
            if (isset($headers->in_reply_to)) {
                $inReplyTo = $headers->in_reply_to;
                return is_array($inReplyTo) ? ($inReplyTo[0] ?? null) : $inReplyTo;
            }
            
            if (isset($headers->references)) {
                $references = $headers->references;
                if (is_array($references)) {
                    return end($references) ?: null;
                } else {
                    $refs = explode(' ', $references);
                    return trim(end($refs)) ?: null;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
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
     * Método de diagnóstico
     */
    public function diagnosticar()
    {
        try {
            if (!$this->conectar()) {
                return ['success' => false, 'message' => 'Error de conexión'];
            }
            
            $folder = $this->client->getFolder('INBOX');
            $mensajes = $this->obtenerMensajesRecientes($folder);
            
            return [
                'success' => true,
                'total' => $mensajes->count(),
                'message' => 'Diagnóstico completado. Revisa los logs.'
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener información básica del buzón
     */
    public function obtenerInfoBasica()
    {
        try {
            if (!$this->conectar()) {
                return [
                    'success' => false,
                    'message' => 'Error de conexión',
                    'total' => 0,
                    'unseen' => 0,
                    'seen' => 0,
                    'connection_status' => 'disconnected'
                ];
            }
            
            $folder = $this->client->getFolder('INBOX');
            
            // Obtener estadísticas de manera segura
            try {
                $total = $folder->messages()->limit(1000)->count();
            } catch (\Exception $e) {
                Log::warning("Error contando mensajes totales: " . $e->getMessage());
                $total = 0;
            }
            
            try {
                $unseenMessages = $folder->messages()->unseen()->limit(100)->get();
                $unseen = $unseenMessages->count();
            } catch (\Exception $e) {
                Log::warning("Error contando mensajes no leídos: " . $e->getMessage());
                $unseen = 0;
            }
            
            $seen = max(0, $total - $unseen);
            
            return [
                'success' => true,
                'total' => $total,
                'unseen' => $unseen,
                'seen' => $seen,
                'connection_status' => 'connected',
                'message' => "Total: {$total}, No leídos: {$unseen}, Leídos: {$seen}"
            ];
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo información básica: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'total' => 0,
                'unseen' => 0,
                'seen' => 0,
                'connection_status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Probar conexión IMAP
     */
    public function probarConexion()
    {
        try {
            Log::info('Probando conexión IMAP...');
            
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