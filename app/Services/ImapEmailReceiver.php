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
        $this->imapHost = config('mail.imap.host', 'imap-mail.outlook.com');
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
    private function conectarIMAP()
    {
        try {
            // Configurar opciones para ignorar certificados SSL temporalmente
            $options = OP_READONLY | OP_HALFOPEN;
            $server = "{{$this->imapHost}:{$this->imapPort}/imap/{$this->imapEncryption}/notls}INBOX";
            
            $connection = imap_open($server, $this->imapUsername, $this->imapPassword, $options);
            
            if (!$connection) {
                Log::error('Error conectando a IMAP: ' . imap_last_error());
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
            
            Log::info("Procesando correo de: {$fromEmail} - Asunto: {$subject}");

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
                $this->procesarRespuestaTicket($ticketId, $empleado->id, $body, $fromName);
            } else {
                // Es un nuevo ticket
                $this->crearTicketDesdeCorreo($empleado, $subject, $body);
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
    private function procesarRespuestaTicket($ticketId, $empleadoId, $mensaje, $nombreEmisor)
    {
        try {
            $ticket = Tickets::find($ticketId);
            
            if (!$ticket) {
                Log::warning("Ticket #{$ticketId} no encontrado para respuesta por correo");
                return false;
            }

            // Crear entrada en el chat del ticket
            TicketChat::create([
                'ticket_id' => $ticketId,
                'empleado_id' => $empleadoId,
                'mensaje' => $mensaje,
                'tipo' => 'email_response',
                'created_at' => now()
            ]);

            Log::info("Respuesta por correo agregada al ticket #{$ticketId}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error procesando respuesta del ticket #{$ticketId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear nuevo ticket desde correo
     */
    private function crearTicketDesdeCorreo($empleado, $subject, $body)
    {
        try {
            // Crear nuevo ticket
            $ticket = Tickets::create([
                'empleado_id' => $empleado->id,
                'Descripcion' => $subject,
                'Estatus' => 'Abierto',
                'Prioridad' => 'Media',
                'created_at' => now()
            ]);

            // Crear primera entrada en el chat
            TicketChat::create([
                'ticket_id' => $ticket->id,
                'empleado_id' => $empleado->id,
                'mensaje' => "Ticket creado automáticamente desde correo:\n\n" . $body,
                'tipo' => 'email_created',
                'created_at' => now()
            ]);

            Log::info("Nuevo ticket #{$ticket->id} creado desde correo de {$empleado->Correo}");
            
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
            $emailService->enviarNotificacionNuevoTicket($ticket->id, $empleado->id);
            
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
}
