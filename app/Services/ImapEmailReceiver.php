<?php

namespace App\Services;

use App\Models\Tickets;
use App\Models\Empleados;
use App\Models\TicketChat;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ImapEmailReceiver
{
    protected $imapHost;
    protected $imapPort;
    protected $imapUsername;
    protected $imapPassword;
    protected $imapEncryption;

    public function __construct()
    {
        $this->imapHost = config('mail.imap.host', 'proser.com.mx');
        $this->imapPort = config('mail.imap.port', 993);
        $this->imapUsername = config('mail.mailers.smtp.username');
        $this->imapPassword = config('mail.mailers.smtp.password');
        $this->imapEncryption = config('mail.imap.encryption', 'ssl');
    }

    /**
     * Procesar correos entrantes y crear tickets automáticamente
     */
    public function procesarCorreosEntrantes()
    {
        try {
            Log::info('Iniciando procesamiento de correos entrantes');
            
            $connection = $this->conectarIMAP();
            if (!$connection) {
                return false;
            }

            $emails = $this->obtenerCorreosNuevos($connection);
            
            foreach ($emails as $email) {
                $this->procesarCorreo($email, $connection);
            }

            imap_close($connection);
            Log::info('Procesamiento de correos completado');
            
            return true;

        } catch (\Exception $e) {
            Log::error('Error procesando correos entrantes: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Conectar al servidor IMAP
     */
    public function conectarIMAP()
    {
        try {
            // Configurar opciones para servidor proser.com.mx
            $options = OP_READONLY | OP_HALFOPEN;
            
            // Para servidores personalizados como proser.com.mx
            if (strpos($this->imapHost, 'proser.com.mx') !== false) {
                $server = "{{$this->imapHost}:{$this->imapPort}/imap/ssl/novalidate-cert}INBOX";
            } elseif (strpos($this->imapHost, 'office365.com') !== false || strpos($this->imapHost, 'outlook.com') !== false) {
                $server = "{{$this->imapHost}:{$this->imapPort}/imap/ssl/novalidate-cert}INBOX";
            } else {
                $server = "{{$this->imapHost}:{$this->imapPort}/imap/{$this->imapEncryption}/notls}INBOX";
            }
            
            Log::info("Intentando conectar a IMAP: {$server}");
            Log::info("Usuario: {$this->imapUsername}");
            
            $connection = imap_open($server, $this->imapUsername, $this->imapPassword, $options);
            
            if (!$connection) {
                $error = imap_last_error();
                Log::error('Error conectando a IMAP: ' . $error);
                Log::error('Servidor: ' . $server);
                Log::error('Usuario: ' . $this->imapUsername);
                return false;
            }

            Log::info('Conexión IMAP establecida correctamente');
            return $connection;

        } catch (\Exception $e) {
            Log::error('Excepción conectando a IMAP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener correos nuevos (no leídos)
     */
    private function obtenerCorreosNuevos($connection)
    {
        try {
            // Buscar correos no leídos
            $emails = imap_search($connection, 'UNSEEN');
            
            if (!$emails) {
                Log::info('No hay correos nuevos');
                return [];
            }

            Log::info('Encontrados ' . count($emails) . ' correos nuevos');
            return $emails;

        } catch (\Exception $e) {
            Log::error('Error obteniendo correos nuevos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Procesar un correo individual
     */
    private function procesarCorreo($emailId, $connection)
    {
        try {
            $header = imap_headerinfo($connection, $emailId);
            $body = $this->obtenerCuerpoCorreo($connection, $emailId);
            
            if (!$header || !$body) {
                Log::warning("No se pudo procesar correo ID: {$emailId}");
                return false;
            }

            // Extraer información del correo
            $fromEmail = $this->extraerEmail($header->from[0]->mailbox . '@' . $header->from[0]->host);
            $fromName = $header->from[0]->personal ?? $fromEmail;
            $subject = $this->decodificarTexto($header->subject ?? 'Sin asunto');
            $date = $header->date;
            
            // Extraer Message-ID y Thread-ID del correo
            $messageId = $this->extraerMessageId($connection, $emailId);
            $threadId = $this->extraerThreadId($connection, $emailId);
            
            Log::info("Procesando correo de: {$fromEmail} - Asunto: {$subject} - Message-ID: {$messageId}");

            // Buscar si el correo viene de un empleado registrado
            $empleado = Empleados::where('Correo', $fromEmail)->first();
            
            if (!$empleado) {
                Log::info("Correo de empleado no registrado: {$fromEmail}");
                $this->marcarComoLeido($connection, $emailId);
                return false;
            }

            // Determinar si es respuesta a un ticket existente o nuevo ticket
            $ticketId = $this->extraerTicketIdDelAsunto($subject);
            
            if ($ticketId) {
                // Es una respuesta a un ticket existente
                $this->procesarRespuestaTicket($ticketId, $empleado->EmpleadoID, $body, $fromName, $messageId, $threadId);
            } else {
                // Es un nuevo ticket
                $this->crearTicketDesdeCorreo($empleado, $subject, $body, $messageId, $threadId);
            }

            // Marcar como leído
            $this->marcarComoLeido($connection, $emailId);
            
            return true;

        } catch (\Exception $e) {
            Log::error("Error procesando correo ID {$emailId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener el cuerpo del correo
     */
    private function obtenerCuerpoCorreo($connection, $emailId)
    {
        try {
            $structure = imap_fetchstructure($connection, $emailId);
            $body = '';

            if (isset($structure->parts)) {
                // Correo multiparte
                foreach ($structure->parts as $partNum => $part) {
                    if ($part->subtype === 'PLAIN' || $part->subtype === 'HTML') {
                        $partBody = imap_fetchbody($connection, $emailId, $partNum + 1);
                        if ($part->encoding == 3) { // BASE64
                            $partBody = base64_decode($partBody);
                        } elseif ($part->encoding == 4) { // QUOTED-PRINTABLE
                            $partBody = quoted_printable_decode($partBody);
                        }
                        $body .= $partBody;
                    }
                }
            } else {
                // Correo simple
                $body = imap_fetchbody($connection, $emailId, 1);
                if ($structure->encoding == 3) { // BASE64
                    $body = base64_decode($body);
                } elseif ($structure->encoding == 4) { // QUOTED-PRINTABLE
                    $body = quoted_printable_decode($body);
                }
            }

            // Limpiar HTML si es necesario
            $body = strip_tags($body);
            $body = trim($body);
            
            return $body;

        } catch (\Exception $e) {
            Log::error("Error obteniendo cuerpo del correo ID {$emailId}: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extraer ID de ticket del asunto del correo
     */
    private function extraerTicketIdDelAsunto($subject)
    {
        // Buscar patrones como "Re: Ticket #123" o "Ticket #123"
        if (preg_match('/ticket\s*#?(\d+)/i', $subject, $matches)) {
            return (int)$matches[1];
        }
        
        // Buscar patrones como "Re: #123"
        if (preg_match('/re:\s*#?(\d+)/i', $subject, $matches)) {
            return (int)$matches[1];
        }
        
        return null;
    }

    /**
     * Procesar respuesta a ticket existente
     */
    private function procesarRespuestaTicket($ticketId, $empleadoId, $mensaje, $nombreEmisor, $messageId = null, $threadId = null)
    {
        try {
            $ticket = Tickets::find($ticketId);
            
            if (!$ticket) {
                Log::warning("Ticket #{$ticketId} no encontrado para respuesta por correo");
                return false;
            }

            // Buscar el empleado para obtener información completa
            $empleado = Empleados::find($empleadoId);
            if (!$empleado) {
                Log::warning("Empleado #{$empleadoId} no encontrado para respuesta por correo");
                return false;
            }

            // Crear entrada en el chat del ticket
            TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => $mensaje,
                'remitente' => 'usuario',
                'nombre_remitente' => $nombreEmisor,
                'correo_remitente' => $empleado->Correo,
                'message_id' => $messageId,
                'thread_id' => $threadId,
                'es_correo' => true,
                'leido' => false
            ]);

            Log::info("Respuesta por correo agregada al ticket #{$ticketId} desde {$empleado->Correo}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error procesando respuesta del ticket #{$ticketId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear nuevo ticket desde correo
     */
    private function crearTicketDesdeCorreo($empleado, $subject, $body, $messageId = null, $threadId = null)
    {
        try {
            // Crear nuevo ticket
            $ticket = Tickets::create([
                'EmpleadoID' => $empleado->EmpleadoID,
                'Descripcion' => $subject,
                'Estatus' => 'Pendiente',
                'Prioridad' => 'Media',
                'created_at' => now()
            ]);

            // Crear primera entrada en el chat
            TicketChat::create([
                'ticket_id' => $ticket->TicketID,
                'mensaje' => "Ticket creado automáticamente desde correo:\n\n" . $body,
                'remitente' => 'usuario',
                'nombre_remitente' => $empleado->NombreEmpleado,
                'correo_remitente' => $empleado->Correo,
                'message_id' => $messageId,
                'thread_id' => $threadId,
                'es_correo' => true,
                'leido' => false
            ]);

            Log::info("Nuevo ticket #{$ticket->TicketID} creado desde correo de {$empleado->Correo}");
            
            // Enviar notificación de confirmación (opcional)
            $this->enviarConfirmacionTicket($ticket, $empleado);
            
            return $ticket;

        } catch (\Exception $e) {
            Log::error("Error creando ticket desde correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar confirmación de ticket creado
     */
    private function enviarConfirmacionTicket($ticket, $empleado)
    {
        try {
            $emailService = new SimpleEmailService();
            $emailService->enviarNotificacionNuevoTicket($ticket->TicketID, $empleado->EmpleadoID);
            
        } catch (\Exception $e) {
            Log::error("Error enviando confirmación de ticket: " . $e->getMessage());
        }
    }

    /**
     * Marcar correo como leído
     */
    private function marcarComoLeido($connection, $emailId)
    {
        try {
            imap_setflag_full($connection, $emailId, "\\Seen");
        } catch (\Exception $e) {
            Log::error("Error marcando correo como leído: " . $e->getMessage());
        }
    }

    /**
     * Extraer email de una cadena
     */
    private function extraerEmail($emailString)
    {
        if (filter_var($emailString, FILTER_VALIDATE_EMAIL)) {
            return $emailString;
        }
        return '';
    }

    /**
     * Decodificar texto con encoding especial
     */
    private function decodificarTexto($text)
    {
        if (empty($text)) {
            return '';
        }

        // Decodificar si tiene encoding especial
        if (preg_match('/=\?([^?]+)\?([BQ])\?([^?]+)\?=/i', $text, $matches)) {
            $charset = $matches[1];
            $encoding = strtoupper($matches[2]);
            $encodedText = $matches[3];
            
            if ($encoding === 'B') {
                $decoded = base64_decode($encodedText);
            } elseif ($encoding === 'Q') {
                $decoded = quoted_printable_decode(str_replace('_', ' ', $encodedText));
            } else {
                $decoded = $encodedText;
            }
            
            return $decoded;
        }
        
        return $text;
    }

    /**
     * Extraer Message-ID del correo
     */
    private function extraerMessageId($connection, $emailId)
    {
        try {
            $headers = imap_fetchheader($connection, $emailId);
            
            if (preg_match('/Message-ID:\s*(.+)/i', $headers, $matches)) {
                return trim($matches[1]);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error("Error extrayendo Message-ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extraer Thread-ID del correo (In-Reply-To o References)
     */
    private function extraerThreadId($connection, $emailId)
    {
        try {
            $headers = imap_fetchheader($connection, $emailId);
            
            // Buscar In-Reply-To primero
            if (preg_match('/In-Reply-To:\s*(.+)/i', $headers, $matches)) {
                return trim($matches[1]);
            }
            
            // Si no hay In-Reply-To, buscar References
            if (preg_match('/References:\s*(.+)/i', $headers, $matches)) {
                $references = trim($matches[1]);
                // Tomar el último Message-ID de las referencias
                $refs = explode(' ', $references);
                return end($refs);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error("Error extrayendo Thread-ID: " . $e->getMessage());
            return null;
        }
    }
}
