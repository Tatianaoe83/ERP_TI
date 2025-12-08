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
    private const DIAS_BUSQUEDA = 15; // Aumentado de 7 a 15 días para buscar tickets más antiguos
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
            
            // Retornar el resultado completo para tener más información
            return $resultado;
            
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
            
            // Obtener TODOS los mensajes (leídos y no leídos) para asegurar que no se pierdan correos
            $mensajes = $folder->messages()
                ->since($fechaInicio)
                ->get();
            
            Log::info("Mensajes obtenidos desde IMAP (sin filtrar): {$mensajes->count()}");
            
            // Filtrar por fecha (últimos 15 días hasta mañana)
            $mensajesFiltrados = $this->filtrarPorFecha($mensajes);
            
            Log::info("Mensajes después de filtrar por fecha: {$mensajesFiltrados->count()}");
            
            // Ordenar por fecha descendente
            $mensajesFiltrados = $this->ordenarPorFecha($mensajesFiltrados);
            
            Log::info("Mensajes finales a procesar: {$mensajesFiltrados->count()}");
            
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
        
        Log::info("🔄 Iniciando procesamiento de {$mensajes->count()} mensajes");
        
        foreach ($mensajes as $index => $mensaje) {
            // Control de tiempo
            if ($this->deberDetenerPorTiempo($inicioTiempo)) {
                Log::warning("⏱️ Tiempo máximo alcanzado, deteniendo procesamiento");
                break;
            }
            
            // Control de memoria
            if ($this->deberDetenerPorMemoria($index)) {
                Log::warning("💾 Memoria máxima alcanzada, deteniendo procesamiento");
                break;
            }
            
            // Procesar mensaje
            try {
                $resultado = $this->procesarMensajeSimple($mensaje);
                if ($resultado) {
                    $procesados++;
                    Log::info("✅ Mensaje #{$index} procesado exitosamente");
                } else {
                    $descartados++;
                    Log::info("⚠️ Mensaje #{$index} descartado");
                }
                
                // Log de progreso cada 10 mensajes
                if ($index > 0 && ($index + 1) % 10 == 0) {
                    $this->logearProgreso($index + 1, $mensajes->count(), $inicioTiempo);
                }
                
            } catch (\Exception $e) {
                Log::error("❌ Error procesando mensaje #{$index}: " . $e->getMessage());
                Log::error("Stack trace: " . $e->getTraceAsString());
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
        
        Log::info("📊 Procesamiento completado | Procesados: {$procesados} | Descartados: {$descartados} | Tiempo: {$tiempoTotal}s");
        
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
            
            Log::info("📧 Procesando mensaje | Subject: {$subject}");
            
            // Extraer información completa del correo
            $bodyTexto = $message->getTextBody();
            $bodyHtml = $message->getHTMLBody();
            $adjuntos = $this->extraerAdjuntos($message);
            $fechaCorreo = $message->getDate();
            
            $fromEmail = $from ? $from->first()->mail : 'desconocido@email.com';
            $fromName = $from ? $from->first()->personal : null;
            $threadId = $this->extraerThreadId($message);
            $messageId = $message->getMessageId();
            $dominio = $this->extraerDominio($fromEmail);
            
            Log::info("📧 Datos extraídos | From: {$fromEmail} | Message-ID: {$messageId} | Thread-ID: " . ($threadId ?? 'N/A'));
            
            // Filtrar solo correos de dominios permitidos (proser y konkret)
            if (!$this->esDominioPermitido($dominio)) {
                Log::info("❌ Correo descartado - dominio no permitido: {$dominio}");
                return false;
            }
            
            // Verificar si es correo del sistema
            if ($this->esCorreoSistema($fromEmail)) {
                Log::info("❌ Correo descartado - correo del sistema: {$fromEmail}");
                return false;
            }
            
            // Buscar ticket existente
            $ticket = $this->buscarTicketPorMensaje($subject, $messageId, $threadId, $fromEmail);
            
            if ($ticket) {
                // Respuesta a ticket existente
                // Verificar si ya fue procesado SOLO si tiene message_id válido
                // Si no tiene message_id, procesarlo para mantener historial completo
                $yaProcesado = false;
                if ($messageId) {
                    $yaProcesado = $this->correoYaProcesado($ticket->TicketID, $fromEmail, $subject, $messageId, $threadId);
                }
                
                if ($yaProcesado) {
                    Log::info("⚠️ Correo descartado - ya procesado | Ticket #{$ticket->TicketID} | From: {$fromEmail} | Subject: {$subject}");
                    return false;
                }
                
                Log::info("✅ Procesando respuesta para ticket existente | Ticket #{$ticket->TicketID} | From: {$fromEmail} | Subject: {$subject}");
                Log::info("Body texto length: " . strlen($bodyTexto ?? '') . " | Body HTML length: " . strlen($bodyHtml ?? ''));
                Log::info("Message-ID: " . ($messageId ?? 'N/A') . " | Thread-ID: " . ($threadId ?? 'N/A'));
                
                $resultado = $this->crearRespuestaUsuario($ticket, $bodyTexto, $bodyHtml, $adjuntos, $fechaCorreo, $from, $messageId, $threadId);
                
                if ($resultado) {
                    Log::info("✅✅ Respuesta guardada exitosamente en historial | Ticket #{$ticket->TicketID} | Chat ID: {$resultado->id}");
                    return true;
                } else {
                    Log::error("❌❌ Error guardando respuesta - crearRespuestaUsuario retornó null | Ticket #{$ticket->TicketID}");
                    return false;
                }
            } else {
                // Intentar crear nuevo ticket
                Log::info("⚠️ No se encontró ticket existente, intentando crear nuevo ticket | From: {$fromEmail} | Subject: {$subject}");
                $nuevoTicket = $this->intentarCrearNuevoTicket($fromEmail, $subject, $bodyTexto, $bodyHtml, $adjuntos, $fechaCorreo, $messageId, $threadId, $fromName);
                if ($nuevoTicket && is_object($nuevoTicket) && isset($nuevoTicket->TicketID)) {
                    Log::info("✅ Nuevo ticket creado: #{$nuevoTicket->TicketID}");
                    return true;
                } else {
                    Log::info("⚠️ No se pudo crear nuevo ticket o no es válido");
                    return false;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error procesando mensaje: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Intentar crear nuevo ticket desde correo
     */
    private function intentarCrearNuevoTicket($fromEmail, $subject, $bodyTexto, $bodyHtml, $adjuntos, $fechaCorreo, $messageId, $threadId, $fromName)
    {
        // Buscar empleado por correo (sin importar mayúsculas/minúsculas)
        $empleado = Empleados::whereRaw('LOWER(Correo) = ?', [strtolower($fromEmail)])->first();
        
        if (!$empleado) {
            return false;
        }
        
        if ($this->esCorreoComunicado($subject, $fromEmail)) {
            return false;
        }
        
        $nuevoTicket = $this->crearTicketDesdeCorreo($empleado, $subject, $bodyTexto, $bodyHtml, $adjuntos, $fechaCorreo, $messageId, $threadId, $fromName);
        
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
     * Mejorado para usar message_id y thread_id en lugar de buscar asunto en mensaje
     */
    protected function correoYaProcesado($ticketId, $fromEmail, $subject, $messageId = null, $threadId = null)
    {
        // Primero verificar por message_id (más confiable)
        if ($messageId) {
            $normalizedMessageId = $this->normalizarMessageId($messageId);
            $existe = TicketChat::where('ticket_id', $ticketId)
                ->where('message_id', $normalizedMessageId)
                ->exists();
            
            if ($existe) {
                Log::info("⚠️ Correo ya procesado detectado por message_id: {$normalizedMessageId} | Ticket #{$ticketId}");
                return true;
            }
        }
        
        // Verificar por thread_id si no se encontró por message_id
        // PERO solo si hay thread_id válido y el correo es del mismo remitente
        if ($threadId) {
            $normalizedThreadId = $this->normalizarThreadId($threadId);
            $existe = TicketChat::where('ticket_id', $ticketId)
                ->where('thread_id', $normalizedThreadId)
                ->whereRaw('LOWER(correo_remitente) = ?', [strtolower($fromEmail)])
                ->where('es_correo', true)
                ->where('created_at', '>=', now()->subDays(30)) // Ampliar a 30 días para historial
                ->exists();
            
            if ($existe) {
                Log::info("⚠️ Correo ya procesado detectado por thread_id: {$normalizedThreadId} | Ticket #{$ticketId}");
                return true;
            }
        }
        
        // NO usar el fallback por asunto y email porque puede causar falsos positivos
        // Si no hay message_id ni thread_id válido, permitir procesar el correo
        // para mantener el historial completo
        
        Log::info("✅ Correo NO procesado anteriormente | Ticket #{$ticketId} | From: {$fromEmail}");
        return false;
    }
    
    /**
     * Buscar ticket por mensaje - Prioriza búsqueda por número de ticket
     */
    protected function buscarTicketPorMensaje($subject, $messageId = null, $threadId = null, $fromEmail = null)
    {
        // 1. PRIMERO: Buscar por número de ticket en asunto (más confiable)
        // Esto mapea correctamente con el TicketID de la BD
        $ticket = $this->buscarPorNumeroTicket($subject);
        if ($ticket) {
            Log::info("Ticket mapeado por número en asunto: #{$ticket->TicketID}");
            return $ticket;
        }
        
        // 2. Buscar por Thread-ID
        if ($threadId) {
            $ticket = $this->buscarPorThreadId($threadId);
            if ($ticket) {
                Log::info("Ticket mapeado por Thread-ID: #{$ticket->TicketID}");
                return $ticket;
            }
        }
        
        // 3. Buscar por Message-ID
        if ($messageId) {
            $ticket = $this->buscarPorMessageId($messageId);
            if ($ticket) {
                Log::info("Ticket mapeado por Message-ID: #{$ticket->TicketID}");
                return $ticket;
            }
        }
        
        // 4. Buscar por asunto original (solo si hay empleado)
        if ($fromEmail) {
            $ticket = $this->buscarPorAsuntoOriginal($subject, $fromEmail);
            if ($ticket) {
                Log::info("Ticket mapeado por asunto original: #{$ticket->TicketID}");
                return $ticket;
            }
        }
        
        Log::info("No se encontró ticket para mapear | Asunto: {$subject}");
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
     * Buscar ticket por número en asunto - Mejorado para mapear correctamente
     */
    private function buscarPorNumeroTicket($subject)
    {
        if (empty($subject)) {
            Log::warning("Asunto vacío al buscar ticket por número");
            return null;
        }
        
        // Patrones mejorados para buscar "Ticket #42" en diferentes formatos
        // Ordenados por especificidad (más específicos primero)
        $patrones = [
            '/Re:\s*Ticket\s*#\s*(\d+)\s*-/i',         // "Re: Ticket #42 -"
            '/RE:\s*Ticket\s*#\s*(\d+)\s*-/i',         // "RE: Ticket #42 -"
            '/Re:\s*Ticket\s*#\s*(\d+)/i',              // "Re: Ticket #42"
            '/RE:\s*Ticket\s*#\s*(\d+)/i',             // "RE: Ticket #42"
            '/Ticket\s*#\s*(\d+)\s*-/i',                // "Ticket #42 -"
            '/Ticket\s*#\s*(\d+)/i',                    // "Ticket #42"
            '/\[Ticket\s*#(\d+)\]/i',                  // "[Ticket #42]"
            '/Ticket\s*N[úu]mero\s*(\d+)/i',           // "Ticket Número 42"
            '/Ticket\s*ID\s*(\d+)/i',                   // "Ticket ID 42"
            '/#\s*(\d+)/i',                             // "#42" (fallback genérico)
        ];
        
        Log::info("🔍 Buscando ticket por número en asunto: {$subject}");
        
        foreach ($patrones as $index => $patron) {
            if (preg_match($patron, $subject, $matches)) {
                $ticketId = (int) $matches[1];
                Log::info("✅ Patrón #{$index} coincidió - Ticket ID extraído: {$ticketId} | Patrón: {$patron}");
                
                // Buscar ticket en la BD por TicketID
                $ticket = Tickets::find($ticketId);
                
                if ($ticket) {
                    Log::info("✅✅ Ticket encontrado por número en asunto: #{$ticketId} | Asunto: {$subject}");
                    return $ticket;
                } else {
                    Log::warning("⚠️ Ticket #{$ticketId} mencionado en asunto pero no existe en BD | Asunto: {$subject}");
                }
            }
        }
        
        Log::info("❌ No se encontró número de ticket en asunto: {$subject}");
        return null;
    }
    
    /**
     * Buscar ticket por asunto original - Busca también por formato "Ticket #ID"
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
        
        // Primero intentar extraer número de ticket del asunto limpio
        // Si encontramos el número, no limitamos por fecha (puede ser un ticket antiguo)
        if (preg_match('/Ticket\s*#\s*(\d+)/i', $subjectLimpio, $matches)) {
            $ticketId = (int) $matches[1];
            $ticket = Tickets::find($ticketId);
            if ($ticket && $ticket->EmpleadoID == $empleado->EmpleadoID) {
                Log::info("Ticket encontrado por número en asunto limpio: #{$ticketId}");
                return $ticket;
            }
        }
        
        // Buscar exacto primero (incluyendo formato "Ticket #ID")
        // Aumentado a 15 días para buscar tickets más antiguos
        $ticket = Tickets::where('Descripcion', $subjectLimpio)
            ->where('EmpleadoID', $empleado->EmpleadoID)
            ->where('created_at', '>=', now()->subDays(15))
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($ticket) {
            Log::info("Ticket encontrado por asunto exacto: #{$ticket->TicketID}");
            return $ticket;
        }
        
        // Buscar con LIKE (incluyendo formato "Ticket #ID")
        // Aumentado a 15 días para buscar tickets más antiguos
        $ticket = Tickets::where('Descripcion', 'LIKE', '%' . $subjectLimpio . '%')
            ->where('EmpleadoID', $empleado->EmpleadoID)
            ->where('created_at', '>=', now()->subDays(15))
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($ticket) {
            Log::info("Ticket encontrado por asunto similar: #{$ticket->TicketID}");
        }
        
        return $ticket;
    }
    
    /**
     * Crear respuesta del usuario - Mapeo mejorado para BD con TicketID
     */
    protected function crearRespuestaUsuario($ticket, $bodyTexto, $bodyHtml = null, $adjuntos = [], $fechaCorreo = null, $from, $messageId = null, $threadId = null)
    {
        try {
            // Validar que el ticket existe y tiene TicketID
            if (!$ticket || !isset($ticket->TicketID)) {
                Log::error("Error: Ticket inválido al crear respuesta");
                return null;
            }
            
            $ticketId = (int) $ticket->TicketID;
            Log::info("Mapeando respuesta para Ticket #{$ticketId}");
            
            // Extraer información del remitente
            $fromEmail = $from ? $from->first()->mail : $ticket->empleado->Correo;
            $fromName = $from ? $from->first()->personal : $ticket->empleado->NombreEmpleado;
            
            // Limpiar y normalizar email y nombre
            $fromEmail = $this->limpiarEmail($fromEmail);
            $fromName = $this->limpiarNombre($fromName);
            
            // Obtener Thread-ID y Message-ID
            $finalThreadId = $threadId ?: $this->obtenerThreadIdDelTicket($ticketId);
            $finalMessageId = $messageId ?: $this->generarMessageId();
            
            // Limpiar y procesar el contenido del mensaje
            $mensajeLimpio = $this->limpiarContenidoMensaje($bodyTexto, $bodyHtml);
            $contenidoHtmlLimpio = $this->limpiarContenidoHtml($bodyHtml);
            
            // Preparar datos mapeados para la base de datos - Usar TicketID del ticket encontrado
            $datosChat = [
                'ticket_id' => $ticketId, // Mapeado correctamente con el número de ticket de la BD
                'mensaje' => $mensajeLimpio,
                'remitente' => 'usuario',
                'nombre_remitente' => $fromName,
                'correo_remitente' => $fromEmail,
                'message_id' => $this->normalizarMessageId($finalMessageId),
                'thread_id' => $this->normalizarThreadId($finalThreadId),
                'es_correo' => true,
                'leido' => false
            ];
            
            // Agregar contenido HTML si existe y está limpio
            if (!empty($contenidoHtmlLimpio)) {
                $datosChat['contenido_correo'] = $contenidoHtmlLimpio;
            }
            
            // Agregar adjuntos si existen (validar estructura)
            if (!empty($adjuntos) && is_array($adjuntos)) {
                $adjuntosValidados = $this->validarAdjuntos($adjuntos);
                if (!empty($adjuntosValidados)) {
                    $datosChat['adjuntos'] = $adjuntosValidados;
                    Log::info("Adjuntos validados: " . count($adjuntosValidados) . " | Ticket #{$ticketId}");
                }
            } else {
                // Asegurar que adjuntos sea null o array vacío si no hay adjuntos
                $datosChat['adjuntos'] = [];
            }
            
            // Usar fecha del correo si está disponible
            if ($fechaCorreo) {
                try {
                    $fechaCarbon = \Carbon\Carbon::parse($fechaCorreo);
                    // Asegurar que la fecha esté en la zona horaria correcta
                    $fechaCarbon->setTimezone(config('app.timezone'));
                    $datosChat['created_at'] = $fechaCarbon;
                    $datosChat['updated_at'] = $fechaCarbon;
                } catch (\Exception $e) {
                    Log::warning("Error parseando fecha del correo: " . $e->getMessage());
                }
            }
            
            // Validar datos antes de guardar
            Log::info("Validando datos antes de guardar | Ticket #{$ticketId}");
            try {
                $datosChat = $this->validarDatosChat($datosChat);
                Log::info("Datos validados correctamente | Ticket #{$ticketId}");
            } catch (\Exception $e) {
                Log::error("Error validando datos: " . $e->getMessage() . " | Ticket #{$ticketId}");
                throw $e;
            }
            
            // Guardar en la base de datos
            Log::info("Intentando guardar en BD | Ticket #{$ticketId} | From: {$fromEmail}");
            Log::info("Datos a guardar: " . json_encode([
                'ticket_id' => $datosChat['ticket_id'],
                'remitente' => $datosChat['remitente'],
                'es_correo' => $datosChat['es_correo'],
                'mensaje_length' => strlen($datosChat['mensaje'] ?? ''),
                'tiene_adjuntos' => !empty($datosChat['adjuntos'])
            ], JSON_UNESCAPED_UNICODE));
            
            try {
                $ticketChat = TicketChat::create($datosChat);
                Log::info("✅ Respuesta mapeada y guardada exitosamente | Ticket #{$ticketId} | Chat ID: {$ticketChat->id} | Desde: {$fromEmail}" . 
                          (!empty($adjuntos) ? " | Adjuntos: " . count($adjuntos) : ""));
                
                return $ticketChat;
            } catch (\Illuminate\Database\QueryException $e) {
                Log::error("❌ Error de BD al crear respuesta: " . $e->getMessage() . " | Ticket #{$ticketId}");
                Log::error("Código de error SQL: " . $e->getCode());
                Log::error("SQL State: " . ($e->errorInfo[0] ?? 'N/A'));
                Log::error("SQL Error: " . ($e->errorInfo[2] ?? 'N/A'));
                Log::error("Datos que se intentaron guardar: " . json_encode($datosChat, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
                throw $e;
            } catch (\Exception $e) {
                Log::error("❌ Error inesperado al guardar: " . $e->getMessage() . " | Ticket #{$ticketId}");
                Log::error("Tipo de error: " . get_class($e));
                throw $e;
            }
            
        } catch (\Exception $e) {
            $ticketIdLog = isset($ticketId) ? $ticketId : 'N/A';
            Log::error("❌ Error creando respuesta de usuario: " . $e->getMessage() . " | Ticket #{$ticketIdLog}");
            Log::error("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Crear nuevo ticket desde correo - Mapeo mejorado para BD con formato "Ticket #ID"
     */
    protected function crearTicketDesdeCorreo($empleado, $subject, $bodyTexto, $bodyHtml = null, $adjuntos = [], $fechaCorreo = null, $messageId = null, $threadId = null, $fromName = null)
    {
        try {
            // Limpiar y normalizar datos
            $subjectLimpio = $this->limpiarAsunto($subject);
            $fromNameLimpio = $this->limpiarNombre($fromName ?: $empleado->NombreEmpleado);
            $fromEmailLimpio = $this->limpiarEmail($empleado->Correo);
            
            // Usar fecha del correo si está disponible, sino usar ahora
            $fechaCreacion = $fechaCorreo ? 
                \Carbon\Carbon::parse($fechaCorreo)->setTimezone(config('app.timezone')) : 
                now();
            
            // Crear ticket - El asunto se guardará sin el formato "Ticket #ID" inicialmente
            $ticket = Tickets::create([
                'EmpleadoID' => (int) $empleado->EmpleadoID,
                'Descripcion' => $subjectLimpio,
                'Estatus' => 'Pendiente',
                'Prioridad' => 'Media',
                'created_at' => $fechaCreacion
            ]);
            
            // Actualizar la descripción con el formato "Ticket #ID - [asunto original]"
            $descripcionConFormato = "Ticket #{$ticket->TicketID} - {$subjectLimpio}";
            
            // Verificar que no exceda la longitud máxima
            if (strlen($descripcionConFormato) > 500) {
                $maxLength = 500 - strlen("Ticket #{$ticket->TicketID} - ");
                $descripcionConFormato = "Ticket #{$ticket->TicketID} - " . substr($subjectLimpio, 0, $maxLength) . '...';
            }
            
            $ticket->Descripcion = $descripcionConFormato;
            $ticket->save();

            // Limpiar y procesar el contenido del mensaje
            $mensajeLimpio = $this->limpiarContenidoMensaje($bodyTexto, $bodyHtml);
            $contenidoHtmlLimpio = $this->limpiarContenidoHtml($bodyHtml);
            
            // Preparar mensaje inicial
            $mensajeCompleto = "Ticket creado automáticamente desde correo:\n\n" . $mensajeLimpio;
            
            // Obtener Thread-ID y Message-ID
            $finalThreadId = $threadId ?: $this->generarThreadId($ticket->TicketID);
            $finalMessageId = $messageId ?: $this->generarMessageId();
            
            // Preparar datos mapeados para el chat
            $datosChat = [
                'ticket_id' => (int) $ticket->TicketID,
                'mensaje' => $mensajeCompleto,
                'remitente' => 'usuario',
                'nombre_remitente' => $fromNameLimpio,
                'correo_remitente' => $fromEmailLimpio,
                'message_id' => $this->normalizarMessageId($finalMessageId),
                'thread_id' => $this->normalizarThreadId($finalThreadId),
                'es_correo' => true,
                'leido' => false
            ];
            
            // Agregar contenido HTML si existe
            if (!empty($contenidoHtmlLimpio)) {
                $datosChat['contenido_correo'] = $contenidoHtmlLimpio;
            }
            
            // Agregar adjuntos si existen (validar estructura)
            if (!empty($adjuntos) && is_array($adjuntos)) {
                $adjuntosValidados = $this->validarAdjuntos($adjuntos);
                if (!empty($adjuntosValidados)) {
                    $datosChat['adjuntos'] = $adjuntosValidados;
                    Log::info("Adjuntos validados para nuevo ticket: " . count($adjuntosValidados) . " | Ticket #{$ticket->TicketID}");
                }
            } else {
                // Asegurar que adjuntos sea null o array vacío si no hay adjuntos
                $datosChat['adjuntos'] = [];
            }
            
            // Usar fecha del correo si está disponible
            if ($fechaCorreo) {
                try {
                    $fechaCarbon = \Carbon\Carbon::parse($fechaCorreo)->setTimezone(config('app.timezone'));
                    $datosChat['created_at'] = $fechaCarbon;
                    $datosChat['updated_at'] = $fechaCarbon;
                } catch (\Exception $e) {
                    Log::warning("Error parseando fecha del correo: " . $e->getMessage());
                }
            }
            
            // Validar datos antes de guardar
            Log::info("Validando datos para nuevo ticket | Ticket #{$ticket->TicketID}");
            try {
                $datosChat = $this->validarDatosChat($datosChat);
                Log::info("Datos validados correctamente para nuevo ticket | Ticket #{$ticket->TicketID}");
            } catch (\Exception $e) {
                Log::error("Error validando datos para nuevo ticket: " . $e->getMessage() . " | Ticket #{$ticket->TicketID}");
                throw $e;
            }
            
            // Guardar en la base de datos
            Log::info("Intentando guardar chat para nuevo ticket | Ticket #{$ticket->TicketID}");
            try {
                $ticketChat = TicketChat::create($datosChat);
                Log::info("✅ Chat guardado exitosamente para nuevo ticket | Ticket #{$ticket->TicketID} | Chat ID: {$ticketChat->id}");
            } catch (\Exception $e) {
                Log::error("❌ Error guardando chat para nuevo ticket: " . $e->getMessage() . " | Ticket #{$ticket->TicketID}");
                Log::error("Datos: " . json_encode($datosChat, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
                throw $e;
            }

            Log::info("✅ Nuevo ticket creado y guardado | Ticket #{$ticket->TicketID} | Asunto: {$descripcionConFormato} | Desde: {$fromEmailLimpio}");

            return $ticket;

        } catch (\Exception $e) {
            Log::error("Error creando ticket: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Limpiar asunto del correo
     */
    private function limpiarAsunto($subject)
    {
        if (empty($subject)) {
            return 'Nuevo ticket desde correo';
        }
        
        $subject = trim($subject);
        
        // Remover prefijos comunes de respuestas
        $subject = preg_replace('/^(Re:|RE:|Fwd:|FWD:|Fw:|FW:)\s*/i', '', $subject);
        
        // Remover formato "Ticket #ID" si existe (para evitar duplicados al crear nuevo ticket)
        // Esto asegura que cuando se cree un ticket nuevo, no tenga el formato "Ticket #ID" previo
        $subject = preg_replace('/^Ticket\s*#\s*\d+\s*-\s*/i', '', $subject);
        
        // Limitar longitud
        if (strlen($subject) > 500) {
            $subject = substr($subject, 0, 497) . '...';
        }
        
        return trim($subject);
    }
    
    /**
     * Generar Thread-ID para nuevo ticket
     */
    private function generarThreadId($ticketId)
    {
        $domain = 'proser.com.mx';
        return "<thread-ticket-{$ticketId}-" . time() . "@{$domain}>";
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
     * Obtener Thread-ID del ticket (busca existente o genera uno nuevo)
     */
    private function obtenerThreadIdDelTicket($ticketId)
    {
        $existingChat = TicketChat::where('ticket_id', $ticketId)
            ->whereNotNull('thread_id')
            ->first();

        if ($existingChat) {
            return $this->normalizarThreadId($existingChat->thread_id);
        }

        // Generar nuevo thread_id si no existe
        return $this->generarThreadId($ticketId);
    }
    
    /**
     * Extraer adjuntos del mensaje
     */
    protected function extraerAdjuntos($message)
    {
        try {
            $attachments = $message->getAttachments();
            
            if (!$attachments || $attachments->isEmpty()) {
                return [];
            }
            
            $adjuntos = [];
            
            foreach ($attachments as $attachment) {
                try {
                    $adjuntos[] = [
                        'nombre' => $attachment->getName(),
                        'tipo' => $attachment->getContentType(),
                        'tamaño' => $attachment->getSize(),
                        'id' => $attachment->getId()
                    ];
                } catch (\Exception $e) {
                    Log::warning("Error extrayendo adjunto: " . $e->getMessage());
                }
            }
            
            return $adjuntos;
            
        } catch (\Exception $e) {
            Log::warning("Error extrayendo adjuntos del mensaje: " . $e->getMessage());
            return [];
        }
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
    
    /**
     * Limpiar y normalizar email
     */
    private function limpiarEmail($email)
    {
        if (empty($email)) {
            return 'desconocido@email.com';
        }
        
        $email = trim(strtolower($email));
        
        // Validar formato básico de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning("Email inválido detectado: {$email}");
            return 'desconocido@email.com';
        }
        
        return $email;
    }
    
    /**
     * Limpiar y normalizar nombre
     */
    private function limpiarNombre($nombre)
    {
        if (empty($nombre)) {
            return 'Usuario';
        }
        
        $nombre = trim($nombre);
        
        // Remover comillas y caracteres especiales
        $nombre = str_replace(['"', "'", '<', '>'], '', $nombre);
        
        // Limitar longitud
        if (strlen($nombre) > 255) {
            $nombre = substr($nombre, 0, 252) . '...';
        }
        
        return $nombre;
    }
    
    /**
     * Limpiar contenido del mensaje (texto plano)
     */
    private function limpiarContenidoMensaje($bodyTexto, $bodyHtml = null)
    {
        // Priorizar texto plano
        $contenido = !empty($bodyTexto) ? $bodyTexto : strip_tags($bodyHtml ?: '');
        
        if (empty($contenido)) {
            return 'Sin contenido';
        }
        
        // Remover respuestas anteriores (líneas que empiezan con ">", "On", "From:", etc.)
        $lineas = explode("\n", $contenido);
        $lineasLimpias = [];
        $enRespuesta = false;
        
        $patronesRespuesta = [
            '/^>\s*/',
            '/^On\s+\w+,\s+\d+/i',
            '/^From:\s*/i',
            '/^Sent:\s*/i',
            '/^To:\s*/i',
            '/^Subject:\s*/i',
            '/^Date:\s*/i',
            '/^---\s*Original Message/i',
            '/^De:\s*/i',
            '/^Enviado:\s*/i',
            '/^Para:\s*/i',
            '/^Asunto:\s*/i',
        ];
        
        foreach ($lineas as $linea) {
            $lineaTrim = trim($linea);
            
            // Detectar inicio de respuesta
            if (!$enRespuesta) {
                foreach ($patronesRespuesta as $patron) {
                    if (preg_match($patron, $lineaTrim)) {
                        $enRespuesta = true;
                        break;
                    }
                }
            }
            
            // Si no estamos en una respuesta, agregar la línea
            if (!$enRespuesta && !empty($lineaTrim)) {
                $lineasLimpias[] = $linea;
            }
        }
        
        $contenidoLimpio = implode("\n", $lineasLimpias);
        
        // Remover espacios en blanco excesivos
        $contenidoLimpio = preg_replace('/\n{3,}/', "\n\n", $contenidoLimpio);
        $contenidoLimpio = trim($contenidoLimpio);
        
        // Si después de limpiar está vacío, usar el original
        if (empty($contenidoLimpio)) {
            $contenidoLimpio = substr(trim($contenido), 0, 5000);
        }
        
        // Limitar longitud
        if (strlen($contenidoLimpio) > 10000) {
            $contenidoLimpio = substr($contenidoLimpio, 0, 9997) . '...';
        }
        
        return $contenidoLimpio;
    }
    
    /**
     * Limpiar contenido HTML
     */
    private function limpiarContenidoHtml($bodyHtml)
    {
        if (empty($bodyHtml)) {
            return null;
        }
        
        // Remover scripts y estilos peligrosos
        $bodyHtml = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $bodyHtml);
        $bodyHtml = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $bodyHtml);
        
        // Remover respuestas anteriores en HTML
        $patronesHtml = [
            '/<div[^>]*class="[^"]*gmail_quote[^"]*"[^>]*>.*?<\/div>/is',
            '/<blockquote[^>]*>.*?<\/blockquote>/is',
            '/<div[^>]*style="[^"]*border-left[^"]*"[^>]*>.*?<\/div>/is',
        ];
        
        foreach ($patronesHtml as $patron) {
            $bodyHtml = preg_replace($patron, '', $bodyHtml);
        }
        
        // Limitar longitud
        if (strlen($bodyHtml) > 50000) {
            $bodyHtml = substr($bodyHtml, 0, 49997) . '...';
        }
        
        return trim($bodyHtml);
    }
    
    /**
     * Normalizar Message-ID
     */
    private function normalizarMessageId($messageId)
    {
        if (empty($messageId)) {
            return $this->generarMessageId();
        }
        
        $messageId = trim($messageId);
        
        // Asegurar formato correcto
        if (!preg_match('/^<.*>$/', $messageId)) {
            $messageId = '<' . trim($messageId, '<>') . '>';
        }
        
        // Limitar longitud
        if (strlen($messageId) > 255) {
            $messageId = substr($messageId, 0, 252) . '...';
        }
        
        return $messageId;
    }
    
    /**
     * Normalizar Thread-ID
     */
    private function normalizarThreadId($threadId)
    {
        if (empty($threadId)) {
            return null;
        }
        
        $threadId = trim($threadId);
        
        // Asegurar formato correcto si tiene < >
        if (preg_match('/^<.*>$/', $threadId)) {
            // Ya está en formato correcto
        } elseif (!empty($threadId)) {
            $threadId = '<' . trim($threadId, '<>') . '>';
        }
        
        // Limitar longitud
        if (strlen($threadId) > 255) {
            $threadId = substr($threadId, 0, 252) . '...';
        }
        
        return $threadId;
    }
    
    /**
     * Validar estructura de adjuntos
     */
    private function validarAdjuntos($adjuntos)
    {
        if (!is_array($adjuntos)) {
            return [];
        }
        
        $adjuntosValidados = [];
        
        foreach ($adjuntos as $adjunto) {
            if (is_array($adjunto)) {
                $adjuntoValido = [
                    'nombre' => isset($adjunto['nombre']) ? substr(trim($adjunto['nombre']), 0, 255) : 'archivo',
                    'tipo' => isset($adjunto['tipo']) ? substr(trim($adjunto['tipo']), 0, 100) : 'application/octet-stream',
                    'tamaño' => isset($adjunto['tamaño']) ? (int) $adjunto['tamaño'] : 0,
                    'id' => isset($adjunto['id']) ? substr(trim($adjunto['id']), 0, 255) : null
                ];
                
                $adjuntosValidados[] = $adjuntoValido;
            }
        }
        
        return $adjuntosValidados;
    }
    
    /**
     * Validar datos antes de guardar en BD
     */
    private function validarDatosChat($datosChat)
    {
        // Validar ticket_id
        if (!isset($datosChat['ticket_id']) || !is_numeric($datosChat['ticket_id'])) {
            throw new \Exception('ticket_id inválido');
        }
        
        // Validar mensaje (requerido)
        if (empty($datosChat['mensaje'])) {
            $datosChat['mensaje'] = 'Sin contenido';
        }
        
        // Validar remitente
        if (!in_array($datosChat['remitente'], ['usuario', 'soporte'])) {
            $datosChat['remitente'] = 'usuario';
        }
        
        // Validar correo_remitente
        if (empty($datosChat['correo_remitente']) || !filter_var($datosChat['correo_remitente'], FILTER_VALIDATE_EMAIL)) {
            $datosChat['correo_remitente'] = 'desconocido@email.com';
        }
        
        // Validar nombre_remitente
        if (empty($datosChat['nombre_remitente'])) {
            $datosChat['nombre_remitente'] = 'Usuario';
        }
        
        // Validar booleanos
        $datosChat['es_correo'] = (bool) ($datosChat['es_correo'] ?? true);
        $datosChat['leido'] = (bool) ($datosChat['leido'] ?? false);
        
        // Limitar longitudes de strings
        if (isset($datosChat['mensaje']) && strlen($datosChat['mensaje']) > 10000) {
            $datosChat['mensaje'] = substr($datosChat['mensaje'], 0, 9997) . '...';
        }
        
        if (isset($datosChat['nombre_remitente']) && strlen($datosChat['nombre_remitente']) > 255) {
            $datosChat['nombre_remitente'] = substr($datosChat['nombre_remitente'], 0, 252) . '...';
        }
        
        if (isset($datosChat['correo_remitente']) && strlen($datosChat['correo_remitente']) > 255) {
            $datosChat['correo_remitente'] = substr($datosChat['correo_remitente'], 0, 252) . '...';
        }
        
        // Asegurar que adjuntos sea un array válido (null o array)
        if (isset($datosChat['adjuntos'])) {
            if (!is_array($datosChat['adjuntos'])) {
                $datosChat['adjuntos'] = [];
            }
        } else {
            $datosChat['adjuntos'] = [];
        }
        
        // Asegurar que message_id y thread_id sean strings o null
        if (isset($datosChat['message_id']) && !is_string($datosChat['message_id']) && !is_null($datosChat['message_id'])) {
            $datosChat['message_id'] = (string) $datosChat['message_id'];
        }
        
        if (isset($datosChat['thread_id']) && !is_string($datosChat['thread_id']) && !is_null($datosChat['thread_id'])) {
            $datosChat['thread_id'] = (string) $datosChat['thread_id'];
        }
        
        return $datosChat;
    }
}