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
        $this->smtpHost = config('email_tickets.smtp.host');
        $this->smtpPort = config('email_tickets.smtp.port');
        $this->smtpUsername = config('email_tickets.smtp.username');
        $this->smtpPassword = config('email_tickets.smtp.password');
        $this->smtpEncryption = config('email_tickets.smtp.encryption');
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
            $asunto = "Ticket #{$ticket->TicketID} - {$ticket->Descripcion}";

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
            $xOriginatingIp = config('email_tickets.smtp.x_originating_ip');
            $xRemoteIp = config('email_tickets.smtp.x_remote_ip');
            $mail->addCustomHeader('X-Originating-IP', "[{$xOriginatingIp}]");
            $mail->addCustomHeader('X-Remote-IP', "[{$xRemoteIp}]");
            $mail->addCustomHeader('X-Sender', $correoSoporte);

            // FROM desde configuración
            $fromAddress = config('email_tickets.smtp.from_address');
            $mail->setFrom($fromAddress, $nombreSoporte);
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
                .content { margin: 20px 0; }
                .response { padding: 20px; margin: 20px 0; }
                .important { padding: 15px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='content'>
                <p>Hola <strong>{$empleado->NombreEmpleado}</strong>,</p>
                
                <p>Hemos recibido tu solicitud y te proporcionamos la siguiente respuesta:</p>
                
                <div class='response'>
                    <h3>Respuesta del Soporte:</h3>
                    " . $mensaje . "
                </div>
                
                
                <div class='important'>
                    <h3>Importante:</h3>
                    <ul>
                        <li><strong>NO cambies el asunto del correo</strong></li>
                        <li><strong>NO agregues texto al asunto</strong></li>
                        <li><strong>Responde solo al cuerpo del mensaje</strong></li>
                        <li>Tu respuesta será procesada automáticamente</li>
                    </ul>
                </div>
            
            </div>
            
            <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #000; font-size: 12px;'>
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
        
        // Configuración desde variables de entorno
        $mail->Host = $this->smtpHost;
        $mail->Port = $this->smtpPort;
        $mail->SMTPSecure = $this->smtpEncryption;
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtpUsername;
        $mail->Password = $this->smtpPassword;
        
        // Configuraciones adicionales para evitar interceptación
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = config('email_tickets.smtp.timeout', 30);
        $mail->SMTPKeepAlive = false;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];
    }

    /**
     * Generar Message-ID único
     */
    private function generarMessageId()
    {
        $domain = config('email_tickets.smtp.domain');
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

        $domain = config('email_tickets.smtp.domain');
        return "<thread-ticket-{$ticketId}-" . time() . "@{$domain}>";
    }

    /**
     * Guardar correo enviado en la base de datos
     */
    private function guardarCorreoEnviado($ticketId, $mensaje, $messageId, $threadId, $adjuntos = [])
    {
        try {
            // Obtener el ticket para construir el contenido completo
            $ticket = Tickets::with('empleado')->find($ticketId);
            $empleado = $ticket ? $ticket->empleado : null;
            
            // Construir el contenido HTML completo del correo
            $contenidoCompleto = $this->construirContenidoConInstrucciones($ticket, $empleado, $mensaje, $threadId);
            
            // Extraer texto plano del HTML para el campo mensaje (sin truncar tanto)
            $mensajeTexto = strip_tags($mensaje);
            $mensajeTexto = html_entity_decode($mensajeTexto, ENT_QUOTES, 'UTF-8');
            // Truncar solo si es extremadamente largo (para el campo mensaje que es string)
            $mensajeTruncado = strlen($mensajeTexto) > 500 ? substr($mensajeTexto, 0, 500) . '...' : $mensajeTexto;
            
            // Los adjuntos ya vienen procesados desde el controlador con toda la información
            // Solo asegurarnos de que tengan el formato correcto
            $adjuntosProcesados = [];
            foreach ($adjuntos as $adjunto) {
                $adjuntoData = [
                    'name' => $adjunto['name'] ?? basename($adjunto['path'] ?? ''),
                    'path' => $adjunto['path'] ?? '',
                ];
                
                // Agregar información adicional si está disponible
                if (isset($adjunto['storage_path'])) {
                    $adjuntoData['storage_path'] = $adjunto['storage_path'];
                }
                if (isset($adjunto['url'])) {
                    $adjuntoData['url'] = $adjunto['url'];
                }
                if (isset($adjunto['size'])) {
                    $adjuntoData['size'] = $adjunto['size'];
                }
                if (isset($adjunto['mime_type'])) {
                    $adjuntoData['mime_type'] = $adjunto['mime_type'];
                }
                
                $adjuntosProcesados[] = $adjuntoData;
            }
            
            TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => $mensajeTruncado,
                'remitente' => 'soporte',
                'nombre_remitente' => config('mail.from.name'),
                'correo_remitente' => config('mail.from.address'),
                'contenido_correo' => $contenidoCompleto, // Guardar el HTML completo aquí
                'message_id' => $messageId,
                'thread_id' => $threadId,
                'adjuntos' => $adjuntosProcesados, // Guardar adjuntos con más información
                'es_correo' => true,
                'leido' => false
            ]);
            
            Log::info("Correo guardado en BD | Ticket #{$ticketId} | Adjuntos: " . count($adjuntosProcesados));
        } catch (\Exception $e) {
            Log::error("Error guardando correo enviado: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
