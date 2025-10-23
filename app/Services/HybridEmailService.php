<?php

namespace App\Services;

use App\Models\Tickets;
use App\Models\Empleados;
use App\Models\TicketChat;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class HybridEmailService
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
        
        
        // Para servidores personalizados como proser.com.mx
        if (strpos($this->smtpHost, 'proser.com.mx') !== false) {
            $this->smtpPort = 465;
            $this->smtpEncryption = 'ssl';
        }
        
        // Forzar configuración para proser.com.mx
        $this->smtpHost = 'proser.com.mx';
        $this->smtpPort = 465;
        $this->smtpEncryption = 'ssl';
    }

    /**
     * Enviar respuesta por correo con instrucciones claras
     */
    public function enviarRespuestaConInstrucciones($ticketId, $mensaje, $adjuntos = [])
    {
        try {
            $ticket = Tickets::with('empleado')->find($ticketId);
            if (!$ticket) {
                throw new \Exception('Ticket no encontrado');
            }

            $empleado = $ticket->empleado;
            $correoSoporte = config('mail.from.address');
            $nombreSoporte = config('mail.from.name');

            // Generar IDs únicos
            $messageId = $this->generarMessageId();
            $threadId = $this->obtenerThreadIdDelTicket($ticketId);

            // Crear asunto con formato específico para threading
            $asunto = "Re: Ticket #{$ticket->TicketID} - {$ticket->Descripcion}";

            // Construir contenido con instrucciones claras
            $contenido = $this->construirContenidoConInstrucciones($ticket, $empleado, $mensaje, $threadId);

            // Enviar correo
            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);
            
            // Configurar headers para threading (sin duplicar Message-ID)
            $mail->addCustomHeader('In-Reply-To', $threadId);
            $mail->addCustomHeader('References', $threadId);
            $mail->addCustomHeader('Thread-Topic', "Ticket #{$ticket->TicketID}");
            $mail->addCustomHeader('Reply-To', $correoSoporte);
            
            // Headers adicionales para evitar interceptación
            $mail->addCustomHeader('X-Originating-IP', '[127.0.0.1]');
            $mail->addCustomHeader('X-Remote-IP', '[127.0.0.1]');
            $mail->addCustomHeader('X-Sender', $correoSoporte);

            // Forzar FROM desde proser.com.mx
            $mail->setFrom('tordonez@proser.com.mx', $nombreSoporte);
            $mail->addAddress($empleado->Correo, $empleado->NombreEmpleado);
            
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $contenido;

            // Adjuntos
            foreach ($adjuntos as $adjunto) {
                if (file_exists($adjunto['path'])) {
                    $mail->addAttachment($adjunto['path'], $adjunto['name']);
                }
            }
            
            $mail->send();

            // Guardar en el chat
            $this->guardarCorreoEnviado($ticketId, $mensaje, $messageId, $threadId, $adjuntos);

            Log::info("Respuesta con instrucciones enviada para ticket #{$ticketId}");
            return true;
            
        } catch (Exception $e) {
            Log::error("Error enviando respuesta con instrucciones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Construir contenido con instrucciones claras para responder
     */
    private function construirContenidoConInstrucciones($ticket, $empleado, $mensaje, $threadId)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                .header { background-color: #007bff; color: white; padding: 20px; border-radius: 8px; }
                .content { margin: 20px 0; }
                .response { background-color: #f8f9fa; padding: 20px; border-left: 4px solid #007bff; margin: 20px 0; border-radius: 4px; }
                .instructions { background-color: #fff3cd; padding: 20px; border: 1px solid #ffeaa7; border-radius: 8px; margin: 20px 0; }
                .ticket-info { background-color: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .important { background-color: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 8px; margin: 15px 0; }
                .code { background-color: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>📧 Respuesta a tu Ticket #{$ticket->TicketID}</h2>
            </div>
            
            <div class='content'>
                <p>Hola <strong>{$empleado->NombreEmpleado}</strong>,</p>
                
                <p>Hemos recibido tu solicitud y te proporcionamos la siguiente respuesta:</p>
                
                <div class='response'>
                    <h3>💬 Respuesta del Soporte:</h3>
                    " . nl2br(htmlspecialchars($mensaje)) . "
                </div>
                
                <div class='instructions'>
                    <h3>📝 ¿Necesitas responder?</h3>
                    <p>Si necesitas enviar más información o tienes preguntas adicionales:</p>
                    <ol>
                        <li><strong>Responde directamente a este correo</strong></li>
                        <li><strong>Mantén el asunto exacto:</strong> <span class='code'>Re: Ticket #{$ticket->TicketID} - {$ticket->Descripcion}</span></li>
                        <li><strong>Escribe tu mensaje</strong> en el cuerpo del correo</li>
                        <li><strong>Envía normalmente</strong></li>
                    </ol>
                </div>
                
                <div class='important'>
                    <h3>⚠️ Importante:</h3>
                    <ul>
                        <li><strong>NO cambies el asunto del correo</strong></li>
                        <li><strong>NO agregues texto al asunto</strong></li>
                        <li><strong>Responde solo al cuerpo del mensaje</strong></li>
                        <li>Tu respuesta será procesada automáticamente</li>
                    </ul>
                </div>
                
                <div class='ticket-info'>
                    <h3>📋 Información del Ticket:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #dee2e6;'><strong>ID:</strong></td><td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>#{$ticket->TicketID}</td></tr>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #dee2e6;'><strong>Descripción:</strong></td><td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$ticket->Descripcion}</td></tr>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #dee2e6;'><strong>Fecha:</strong></td><td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$ticket->created_at->format('d/m/Y H:i')}</td></tr>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #dee2e6;'><strong>Estado:</strong></td><td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$ticket->Estatus}</td></tr>
                        <tr><td style='padding: 8px;'><strong>Thread ID:</strong></td><td style='padding: 8px;'><span class='code'>{$threadId}</span></td></tr>
                    </table>
                </div>
                
                <p>Gracias por usar nuestro sistema de tickets.</p>
            </div>
            
            <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d;'>
                <p><strong>Sistema de Tickets ERP TI - Proser</strong></p>
                <p>Este es un correo automático. Para responder, simplemente responde a este correo manteniendo el asunto original.</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Procesar respuesta manual (cuando el usuario responde por correo)
     */
    public function procesarRespuestaManual($ticketId, $respuestaData)
    {
        try {
            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                return false;
            }

            $empleado = $ticket->empleado;
            
            // Crear entrada en el chat simulando respuesta por correo
            TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => $respuestaData['mensaje'],
                'remitente' => 'usuario',
                'nombre_remitente' => $respuestaData['nombre'] ?: $empleado->NombreEmpleado,
                'correo_remitente' => $respuestaData['correo'] ?: $empleado->Correo,
                'message_id' => $this->generarMessageId(),
                'thread_id' => $this->obtenerThreadIdDelTicket($ticketId),
                'es_correo' => true,
                'leido' => false
            ]);

            Log::info("Respuesta manual procesada para ticket #{$ticketId}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error procesando respuesta manual: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configurar PHPMailer
     */
    private function configurarMailer($mail)
    {
        $mail->isSMTP();
        
        // Forzar configuración específica para proser.com.mx
        $mail->Host = 'proser.com.mx';
        $mail->Port = 465;
        $mail->SMTPSecure = 'ssl';
        $mail->SMTPAuth = true;
        $mail->Username = 'tordonez@proser.com.mx';
        $mail->Password = $this->smtpPassword;
        
        // Configuraciones adicionales para evitar interceptación
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
        
        // Configuración básica sin headers duplicados
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 30;
    }

    /**
     * Generar Message-ID único
     */
    private function generarMessageId()
    {
        // Forzar dominio proser.com.mx para evitar rechazo de correos
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

        // Forzar dominio proser.com.mx para evitar rechazo de correos
        $domain = 'proser.com.mx';
        return "<thread-ticket-{$ticketId}-" . time() . "@{$domain}>";
    }

    /**
     * Guardar correo enviado en la base de datos
     */
    private function guardarCorreoEnviado($ticketId, $mensaje, $messageId, $threadId, $adjuntos = [])
    {
        try {
            // Truncar mensaje si es muy largo
            $mensajeTruncado = strlen($mensaje) > 1000 ? substr($mensaje, 0, 1000) . '...' : $mensaje;
            
            TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => $mensajeTruncado,
                'remitente' => 'soporte',
                'nombre_remitente' => config('mail.from.name'),
                'correo_remitente' => config('mail.from.address'),
                'message_id' => $messageId,
                'thread_id' => $threadId,
                'adjuntos' => $adjuntos,
                'es_correo' => true,
                'leido' => false
            ]);
        } catch (\Exception $e) {
            Log::error("Error guardando correo enviado: " . $e->getMessage());
        }
    }
}
