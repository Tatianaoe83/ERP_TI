<?php

namespace App\Services;

use App\Models\Tickets;
use App\Models\Empleados;
use App\Models\TicketChat;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SmtpEmailReceiver
{
    protected $smtpHost;
    protected $smtpPort;
    protected $smtpUsername;
    protected $smtpPassword;
    protected $smtpEncryption;

    public function __construct()
    {
        $this->smtpHost = config('mail.mailers.smtp.host');
        $this->smtpPort = config('mail.mailers.smtp.port');
        $this->smtpUsername = config('mail.mailers.smtp.username');
        $this->smtpPassword = config('mail.mailers.smtp.password');
        $this->smtpEncryption = config('mail.mailers.smtp.encryption');
    }

    /**
     * Procesar respuestas de correos usando solo SMTP
     * Este método simula la recepción de respuestas
     */
    public function procesarRespuestasManuales($ticketId, $respuestaData)
    {
        try {
            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                Log::warning("Ticket #{$ticketId} no encontrado");
                return false;
            }

            // Buscar el empleado del ticket
            $empleado = $ticket->empleado;
            if (!$empleado) {
                Log::warning("Empleado no encontrado para ticket #{$ticketId}");
                return false;
            }

            // Crear entrada en el chat simulando una respuesta recibida
            TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => $respuestaData['mensaje'],
                'remitente' => 'usuario',
                'nombre_remitente' => $empleado->NombreEmpleado,
                'correo_remitente' => $empleado->Correo,
                'message_id' => $this->generarMessageId(),
                'thread_id' => $this->obtenerThreadIdDelTicket($ticketId),
                'es_correo' => true,
                'leido' => false
            ]);

            Log::info("Respuesta manual agregada al ticket #{$ticketId}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error procesando respuesta manual: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar correo con instrucciones para responder
     */
    public function enviarInstruccionesRespuesta($ticketId)
    {
        try {
            $ticket = Tickets::with('empleado')->find($ticketId);
            if (!$ticket) {
                return false;
            }

            $empleado = $ticket->empleado;
            $correoSoporte = config('mail.from.address');
            $nombreSoporte = config('mail.from.name');

            // Generar Message-ID y Thread-ID
            $messageId = $this->generarMessageId();
            $threadId = $this->obtenerThreadIdDelTicket($ticketId);

            $asunto = "Instrucciones para responder - Ticket #{$ticket->TicketID}";
            
            $contenido = $this->construirContenidoInstrucciones($ticket, $empleado);

            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);
            
            // Configurar headers para threading
            $mail->addCustomHeader('Message-ID', $messageId);
            $mail->addCustomHeader('Thread-Topic', "Ticket #{$ticket->TicketID}");
            $mail->addCustomHeader('Reply-To', $correoSoporte);

            $mail->setFrom($correoSoporte, $nombreSoporte);
            $mail->addAddress($empleado->Correo, $empleado->NombreEmpleado);
            
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $contenido;
            
            $mail->send();

            // Guardar en el chat
            TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => "Instrucciones de respuesta enviadas",
                'remitente' => 'soporte',
                'nombre_remitente' => $nombreSoporte,
                'correo_remitente' => $correoSoporte,
                'message_id' => $messageId,
                'thread_id' => $threadId,
                'es_correo' => true,
                'leido' => false
            ]);

            Log::info("Instrucciones de respuesta enviadas para ticket #{$ticketId}");
            return true;
            
        } catch (Exception $e) {
            Log::error("Error enviando instrucciones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Construir contenido con instrucciones para responder
     */
    private function construirContenidoInstrucciones($ticket, $empleado)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background-color: #007bff; color: white; padding: 15px; border-radius: 5px; }
                .content { margin: 20px 0; }
                .instructions { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0; }
                .ticket-info { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>📧 Instrucciones para Responder - Ticket #{$ticket->TicketID}</h2>
            </div>
            
            <div class='content'>
                <p>Hola {$empleado->NombreEmpleado},</p>
                
                <p>Para responder a este ticket, por favor:</p>
                
                <div class='instructions'>
                    <h3>📝 Instrucciones:</h3>
                    <ol>
                        <li><strong>Responde directamente a este correo</strong></li>
                        <li><strong>Mantén el asunto:</strong> Re: Ticket #{$ticket->TicketID}</li>
                        <li><strong>Escribe tu respuesta</strong> en el cuerpo del correo</li>
                        <li><strong>Envía la respuesta</strong> normalmente</li>
                    </ol>
                    
                    <p><strong>⚠️ Importante:</strong> No cambies el asunto del correo, debe mantener \"Re: Ticket #{$ticket->TicketID}\"</p>
                </div>
                
                <div class='ticket-info'>
                    <h3>📋 Información del Ticket:</h3>
                    <p><strong>ID:</strong> #{$ticket->TicketID}</p>
                    <p><strong>Descripción:</strong> {$ticket->Descripcion}</p>
                    <p><strong>Fecha:</strong> {$ticket->created_at->format('d/m/Y H:i')}</p>
                    <p><strong>Estado:</strong> {$ticket->Estatus}</p>
                </div>
                
                <p>Tu respuesta será procesada automáticamente y aparecerá en el sistema de tickets.</p>
            </div>
            
            <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d;'>
                <p>Este es un correo automático del sistema de tickets ERP TI.</p>
                <p>Para responder, simplemente responde a este correo manteniendo el asunto original.</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Configurar PHPMailer
     */
    private function configurarMailer($mail)
    {
        $mail->isSMTP();
        $mail->Host = $this->smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtpUsername;
        $mail->Password = $this->smtpPassword;
        $mail->SMTPSecure = $this->smtpEncryption;
        $mail->Port = $this->smtpPort;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 30;
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
