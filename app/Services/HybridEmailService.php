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

    public function enviarRespuestaConInstrucciones($ticketId, $mensaje, $adjuntos = [], $mensajeParaCorreo = null)
    {
        try {
            $ticket = Tickets::with('empleado')->find($ticketId);
            if (!$ticket) {
                throw new \Exception('Ticket no encontrado');
            }

            $empleado      = $ticket->empleado;
            $correoSoporte = config('mail.from.address');
            $nombreSoporte = config('mail.from.name');
            $messageId     = $this->generarMessageId();
            $threadId      = $this->obtenerThreadIdDelTicket($ticketId);
            $asunto        = "Ticket #{$ticket->TicketID} - {$ticket->Descripcion}";

            // Usar mensajeParaCorreo (con base64) para el correo si se proporcionó,
            // de lo contrario usar el mensaje original
            $mensajeEmail = $mensajeParaCorreo ?? $mensaje;
            $contenido    = $this->construirContenidoConInstrucciones($ticket, $empleado, $mensajeEmail, $threadId);

            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);

            $mail->addCustomHeader('In-Reply-To', $threadId);
            $mail->addCustomHeader('References', $threadId);
            $mail->addCustomHeader('Thread-Topic', "Ticket #{$ticket->TicketID}");
            $mail->addCustomHeader('Reply-To', $correoSoporte);

            $xOriginatingIp = config('email_tickets.smtp.x_originating_ip');
            $xRemoteIp      = config('email_tickets.smtp.x_remote_ip');
            $mail->addCustomHeader('X-Originating-IP', "[{$xOriginatingIp}]");
            $mail->addCustomHeader('X-Remote-IP', "[{$xRemoteIp}]");
            $mail->addCustomHeader('X-Sender', $correoSoporte);

            $fromAddress = config('email_tickets.smtp.from_address');
            $mail->setFrom($fromAddress, $nombreSoporte);
            $mail->addAddress($empleado->Correo, $empleado->NombreEmpleado);

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $contenido;

            // Adjuntar archivos normales al correo
            foreach ($adjuntos as $adjunto) {
                $tipo = $adjunto['tipo'] ?? 'archivo';

                // Las imágenes embebidas ya van en el HTML como base64, no se adjuntan
                if ($tipo === 'imagen_embebida') {
                    continue;
                }

                // Resolver la ruta absoluta usando Storage para evitar problemas con
                // separadores de ruta en Windows (mezcla de / y \)
                $rutaAbsoluta = null;

                if (!empty($adjunto['storage_path'])) {
                    // Usar Storage::disk para obtener la ruta correcta en cualquier OS
                    try {
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($adjunto['storage_path'])) {
                            $rutaAbsoluta = \Illuminate\Support\Facades\Storage::disk('public')->path($adjunto['storage_path']);
                        }
                    } catch (\Exception $e) {
                        Log::warning("No se pudo resolver storage_path '{$adjunto['storage_path']}': " . $e->getMessage());
                    }
                }

                // Fallback: usar el path directo si storage_path no funcionó
                if (!$rutaAbsoluta && !empty($adjunto['path']) && file_exists($adjunto['path'])) {
                    $rutaAbsoluta = $adjunto['path'];
                }

                if (!$rutaAbsoluta) {
                    Log::warning("Adjunto no encontrado en disco, se omite del correo: " . ($adjunto['name'] ?? 'sin nombre'));
                    continue;
                }

                try {
                    $mail->addAttachment($rutaAbsoluta, $adjunto['name'] ?? basename($rutaAbsoluta));
                    Log::info("Adjunto agregado al correo: " . ($adjunto['name'] ?? basename($rutaAbsoluta)));
                } catch (\Exception $e) {
                    Log::error("Error adjuntando archivo al correo '{$adjunto['name']}': " . $e->getMessage());
                }
            }

            $mail->send();

            // Guardar en BD usando el mensaje ORIGINAL (con URLs de storage, no base64)
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
            $ticket   = Tickets::with('empleado')->find($ticketId);
            $empleado = $ticket ? $ticket->empleado : null;

            $contenidoCompleto = $this->construirContenidoConInstrucciones($ticket, $empleado, $mensaje, $threadId);

            // Procesar adjuntos: guardar solo los datos necesarios para mostrarlos en el chat
            $adjuntosProcesados = [];
            foreach ($adjuntos as $adjunto) {
                $adjuntosProcesados[] = [
                    'name'         => $adjunto['name']         ?? basename($adjunto['path'] ?? ''),
                    'storage_path' => $adjunto['storage_path'] ?? null,
                    'url'          => $adjunto['url']          ?? null,
                    'size'         => $adjunto['size']         ?? null,
                    'mime_type'    => $adjunto['mime_type']    ?? null,
                    'tipo'         => $adjunto['tipo']         ?? 'archivo',
                ];
            }

            // Guardar el mensaje ORIGINAL sin modificar
            // Las imágenes embebidas tienen src="/storage/tickets/adjuntos/..."
            // que el chat renderiza correctamente con x-html="formatearMensaje(mensaje.mensaje)"
            TicketChat::create([
                'ticket_id'        => $ticketId,
                'mensaje'          => $mensaje, // ← sin reemplazar nada
                'remitente'        => 'soporte',
                'nombre_remitente' => config('mail.from.name'),
                'correo_remitente' => config('mail.from.address'),
                'contenido_correo' => $contenidoCompleto,
                'message_id'       => $messageId,
                'thread_id'        => $threadId,
                'adjuntos'         => $adjuntosProcesados,
                'es_correo'        => true,
                'leido'            => false,
            ]);

            Log::info("Correo guardado | Ticket #{$ticketId} | Adjuntos: " . count($adjuntosProcesados));

        } catch (\Exception $e) {
            Log::error("Error guardando correo enviado: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
