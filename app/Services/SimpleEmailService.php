<?php

namespace App\Services;

use App\Models\TicketChat;
use App\Models\Tickets;
use App\Models\Empleados;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SimpleEmailService
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
        
        // Para servidores personalizados como proser.com.mx, usar SSL en puerto 465
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
     * Enviar correo de respuesta desde el sistema
     */
    public function enviarRespuestaTicket($ticketId, $mensaje, $adjuntos = [])
    {
        try {
            $ticket = Tickets::with(['empleado', 'chat'])->find($ticketId);
            
            if (!$ticket) {
                throw new \Exception('Ticket no encontrado');
            }

            // Obtener información del empleado
            $empleado = $ticket->empleado;
            $correoSoporte = config('mail.from.address');
            $nombreSoporte = config('mail.from.name');

            // Generar Message-ID único para este correo
            $messageId = $this->generarMessageId();
            
            // Obtener Thread-ID del ticket (si existe) o crear uno nuevo
            $threadId = $this->obtenerOGenerarThreadId($ticketId);

            // Crear el asunto del correo con threading
            $asunto = "Re: Ticket #{$ticket->TicketID} - {$ticket->Descripcion}";

            // Preparar el contenido del correo
            $contenido = $this->construirContenidoCorreo($ticket, $empleado, $mensaje);

            // Enviar el correo usando PHPMailer
            $mail = new PHPMailer(true);

            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = $this->smtpEncryption;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';

            // Configurar headers para threading
            $mail->addCustomHeader('Message-ID', $messageId);
            $mail->addCustomHeader('In-Reply-To', $threadId);
            $mail->addCustomHeader('References', $threadId);
            $mail->addCustomHeader('Thread-Topic', "Ticket #{$ticket->TicketID}");

            // Remitente y destinatario
            $mail->setFrom($correoSoporte, $nombreSoporte);
            $mail->addAddress(strtolower($empleado->Correo), $empleado->Nombre . ' ' . $empleado->ApellidoPaterno);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $contenido;

            // Adjuntos si los hay
            foreach ($adjuntos as $adjunto) {
                if (file_exists($adjunto['path'])) {
                    $mail->addAttachment($adjunto['path'], $adjunto['name']);
                }
            }

            // Enviar
            $mail->send();

            // Guardar información del correo enviado en la base de datos
            $this->guardarCorreoEnviado($ticketId, $mensaje, $messageId, $threadId, $adjuntos);


            return true;

        } catch (Exception $e) {
            Log::error("Error enviando correo para ticket #{$ticketId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Construir contenido del correo
     */
    private function construirContenidoCorreo($ticket, $empleado, $mensaje)
    {
        $contenido = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
                .content { margin: 20px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Respuesta a tu Ticket #{$ticket->TicketID}</h2>
            </div>
            
            <div class='content'>
                <p>Hola {$empleado->Nombre},</p>
                
                <p>Hemos recibido tu solicitud y te proporcionamos la siguiente respuesta:</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0;'>
                    " . nl2br(htmlspecialchars(strtolower($mensaje))) . "
                </div>
                
                <p><strong>Información del Ticket:</strong></p>
                <ul>
                    <li><strong>ID:</strong> #{$ticket->TicketID}</li>
                    <li><strong>Descripción:</strong> {$ticket->Descripcion}</li>
                    <li><strong>Fecha de creación:</strong> {$ticket->created_at->format('d/m/Y H:i')}</li>
                    <li><strong>Estado:</strong> {$ticket->Estatus}</li>
                </ul>
            </div>
            
            <div class='footer'>
                <p>Este es un correo automático del sistema de tickets. Por favor, no respondas directamente a este correo.</p>
                <p>Si necesitas más información, por favor accede al sistema de tickets.</p>
            </div>
        </body>
        </html>";

        return $contenido;
    }

    /**
     * Verificar configuración de correo
     */
    public function verificarConfiguracion()
    {
        try {
            $mail = new PHPMailer(true);
            
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = $this->smtpEncryption;
            $mail->Port = $this->smtpPort;
            $mail->SMTPDebug = 0; // Desactivar debug para verificación
            $mail->Timeout = 10; // Timeout de 10 segundos
            
            // Verificar conexión
            $mail->smtpConnect();
            $mail->smtpClose();
            
            return [
                'success' => true,
                'message' => 'Configuración de correo verificada correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en configuración de correo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar correo de notificación de nuevo ticket
     */
    public function enviarNotificacionNuevoTicket($ticketId, $empleadoId)
    {
        try {
            $ticket = Tickets::with('empleado')->find($ticketId);
            $empleado = Empleados::find($empleadoId);
            
            if (!$ticket || !$empleado) {
                throw new \Exception('Ticket o empleado no encontrado');
            }

            $asunto = "Nuevo Ticket #{$ticketId} - {$ticket->Descripcion}";
            
            $contenido = $this->construirContenidoNotificacionTicket($ticket, $empleado);

            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);
            
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($empleado->Correo, $empleado->Nombre . ' ' . $empleado->ApellidoPaterno);
            
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $contenido;
            
            $mail->send();
            
            return true;
            
        } catch (Exception $e) {
            Log::error("Error enviando notificación de ticket #{$ticketId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar correo de actualización de estado de ticket
     */
    public function enviarActualizacionEstadoTicket($ticketId, $nuevoEstado, $mensaje = null)
    {
        try {
            $ticket = Tickets::with('empleado')->find($ticketId);
            
            if (!$ticket) {
                throw new \Exception('Ticket no encontrado');
            }

            $asunto = "Actualización de Ticket #{$ticketId} - Estado: {$nuevoEstado}";
            
            $contenido = $this->construirContenidoActualizacionEstado($ticket, $nuevoEstado, $mensaje);

            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);
            
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($ticket->empleado->Correo, $ticket->empleado->Nombre . ' ' . $ticket->empleado->ApellidoPaterno);
            
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $contenido;
            
            $mail->send();
            
            return true;
            
        } catch (Exception $e) {
            Log::error("Error enviando actualización de ticket #{$ticketId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Configurar PHPMailer con parámetros comunes
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
     * Construir contenido para notificación de nuevo ticket
     */
    private function construirContenidoNotificacionTicket($ticket, $empleado)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background-color: #28a745; color: white; padding: 15px; border-radius: 5px; }
                .content { margin: 20px 0; }
                .ticket-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>🎫 Nuevo Ticket Creado</h2>
            </div>
            
            <div class='content'>
                <p>Hola {$empleado->Nombre},</p>
                
                <p>Se ha creado un nuevo ticket en el sistema:</p>
                
                <div class='ticket-info'>
                    <p><strong>📋 ID del Ticket:</strong> #{$ticket->TicketID}</p>
                    <p><strong>📝 Descripción:</strong> {$ticket->Descripcion}</p>
                    <p><strong>📅 Fecha de creación:</strong> {$ticket->created_at->format('d/m/Y H:i')}</p>
                    <p><strong>📊 Estado actual:</strong> {$ticket->Estatus}</p>
                    <p><strong>👤 Asignado a:</strong> {$empleado->Nombre} {$empleado->ApellidoPaterno}</p>
                </div>
                
                <p>Por favor, revisa el ticket en el sistema para más detalles y acciones requeridas.</p>
            </div>
            
            <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d;'>
                <p>Este es un correo automático del sistema de tickets ERP TI.</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Construir contenido para actualización de estado
     */
    private function construirContenidoActualizacionEstado($ticket, $nuevoEstado, $mensaje)
    {
        $estadoColors = [
            'Abierto' => '#dc3545',
            'En Progreso' => '#ffc107',
            'Resuelto' => '#28a745',
            'Cerrado' => '#6c757d'
        ];
        
        $color = $estadoColors[$nuevoEstado] ?? '#007bff';
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background-color: {$color}; color: white; padding: 15px; border-radius: 5px; }
                .content { margin: 20px 0; }
                .status-change { background-color: #e9ecef; padding: 15px; border-left: 4px solid {$color}; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>🔄 Estado Actualizado - Ticket #{$ticket->TicketID}</h2>
            </div>
            
            <div class='content'>
                <p>Hola {$ticket->empleado->Nombre},</p>
                
                <p>El estado de tu ticket ha sido actualizado:</p>
                
                <div class='status-change'>
                    <p><strong>📊 Nuevo Estado:</strong> {$nuevoEstado}</p>
                    <p><strong>📋 Ticket:</strong> #{$ticket->TicketID} - {$ticket->Descripcion}</p>
                    <p><strong>📅 Fecha de actualización:</strong> " . now()->format('d/m/Y H:i') . "</p>
                </div>";
                
        if ($mensaje) {
            $contenido .= "
                <p><strong>💬 Comentario adicional:</strong></p>
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>
                    " . nl2br(htmlspecialchars(strtolower($mensaje))) . "
                </div>";
        }
        
        $contenido .= "
                <p>Puedes revisar todos los detalles en el sistema de tickets.</p>
            </div>
            
            <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d;'>
                <p>Este es un correo automático del sistema de tickets ERP TI.</p>
            </div>
        </body>
        </html>";

        return $contenido;
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
     * Obtener o generar Thread-ID para el ticket
     */
    private function obtenerOGenerarThreadId($ticketId)
    {
        // Buscar si ya existe un Thread-ID para este ticket
        $existingChat = \App\Models\TicketChat::where('ticket_id', $ticketId)
            ->whereNotNull('thread_id')
            ->first();

        if ($existingChat) {
            return $existingChat->thread_id;
        }

        // Generar nuevo Thread-ID con dominio proser.com.mx
        $domain = 'proser.com.mx';
        $threadId = "<thread-ticket-{$ticketId}-" . time() . "@{$domain}>";
        
        return $threadId;
    }

    /**
     * Guardar información del correo enviado en la base de datos
     */
    private function guardarCorreoEnviado($ticketId, $mensaje, $messageId, $threadId, $adjuntos = [])
    {
        try {
            \App\Models\TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => $mensaje,
                'remitente' => 'soporte',
                'nombre_remitente' => auth()->user()->name ?? 'Soporte TI',
                'correo_remitente' => auth()->user()->email ?? config('mail.from.address'),
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
