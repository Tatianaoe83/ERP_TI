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
                } catch (\Exception $e) {
                    Log::error("Error adjuntando archivo al correo '{$adjunto['name']}': " . $e->getMessage());
                }
            }

            $mail->send();

            // Guardar en BD usando el mensaje ORIGINAL (con URLs de storage, no base64)
            $this->guardarCorreoEnviado($ticketId, $mensaje, $messageId, $threadId, $adjuntos);

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
        $nombre = e($empleado->NombreEmpleado ?? '');
        $folio = e($ticket->TicketID);

        return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>Ticket #' . $folio . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f7fb;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f4f7fb" style="background-color:#f4f7fb;margin:0;padding:0;">
<tr>
<td align="center" style="padding:28px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;">
<tr>
<td bgcolor="#101D49" style="background-color:#101D49;padding:22px 28px;">
<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#93c5fd;">ERP TI · Soporte</p>
<p style="margin:8px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:22px;line-height:1.2;font-weight:700;color:#ffffff;">Ticket #' . $folio . '</p>
</td>
</tr>
<tr>
<td style="padding:28px;font-family:Arial,Helvetica,sans-serif;color:#0a1628;font-size:15px;line-height:1.6;">
<p style="margin:0 0 8px;">Hola <strong>' . $nombre . '</strong>,</p>
<p style="margin:0 0 18px;color:#334155;">Recibimos tu solicitud. Esta es la respuesta del equipo de soporte:</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f4f7fb" style="background-color:#f4f7fb;border-left:4px solid #0052cc;border-radius:8px;">
<tr>
<td style="padding:16px 18px;font-family:Arial,Helvetica,sans-serif;color:#0a1628;font-size:15px;line-height:1.6;">
<p style="margin:0 0 8px;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#0052cc;font-weight:700;">Respuesta del soporte</p>
' . $mensaje . '
</td>
</tr>
</table>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#fff7ed" style="margin-top:18px;background-color:#fff7ed;border:1px solid #fed7aa;border-radius:8px;">
<tr>
<td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;color:#9a3412;font-size:13px;line-height:1.55;">
<p style="margin:0 0 6px;font-weight:700;color:#9a3412;">Para que tu respuesta llegue al ticket</p>
<p style="margin:0;color:#9a3412;">Responde este correo sin cambiar el asunto y escribe solo en el cuerpo del mensaje. El sistema la registra sola.</p>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td bgcolor="#f8fafc" style="padding:16px 28px;background-color:#f8fafc;border-top:1px solid #e2e8f0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#64748b;">
<strong style="color:#0a1628;">Sistema de Tickets ERP TI · Proser</strong><br>
Correo automático. Conserva el asunto original al responder.
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>';
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


        } catch (\Exception $e) {
            Log::error("Error guardando correo enviado: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
        }
    }
}
