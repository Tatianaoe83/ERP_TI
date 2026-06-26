<?php

namespace App\Services;

use App\Models\MantenimientoChat;
use App\Models\TicketMantenimiento;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MantenimientoEmailService
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

    public function enviarRespuestaConInstrucciones($mantenimientoId, $mensaje, $adjuntos = [], $mensajeParaCorreo = null)
    {
        try {
            $ticket = TicketMantenimiento::with('empleado')->find($mantenimientoId);
            if (!$ticket) {
                throw new \Exception('Solicitud de mantenimiento no encontrada');
            }

            if (!$ticket->empleado?->Correo) {
                throw new \Exception('El solicitante no tiene correo registrado');
            }

            $correoSoporte = config('mail.from.address');
            $nombreSoporte = config('mail.from.name');
            $messageId = $this->generarMessageId();
            $threadId = $this->obtenerThreadIdDelMantenimiento($mantenimientoId);
            $asunto = "Mantenimiento #{$ticket->MantenimientoID} - {$ticket->asunto}";

            $mensajeEmail = $mensajeParaCorreo ?? $mensaje;
            $contenido = $this->construirContenidoConInstrucciones($ticket, $mensajeEmail, $threadId);

            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);

            $mail->addCustomHeader('In-Reply-To', $threadId);
            $mail->addCustomHeader('References', $threadId);
            $mail->addCustomHeader('Thread-Topic', "Mantenimiento #{$ticket->MantenimientoID}");
            $mail->addCustomHeader('Reply-To', $correoSoporte);

            $xOriginatingIp = config('email_tickets.smtp.x_originating_ip');
            $xRemoteIp = config('email_tickets.smtp.x_remote_ip');
            $mail->addCustomHeader('X-Originating-IP', "[{$xOriginatingIp}]");
            $mail->addCustomHeader('X-Remote-IP', "[{$xRemoteIp}]");
            $mail->addCustomHeader('X-Sender', $correoSoporte);

            $fromAddress = config('email_tickets.smtp.from_address');
            $mail->setFrom($fromAddress, $nombreSoporte);
            $mail->addAddress($ticket->empleado->Correo, $ticket->empleado->NombreEmpleado);

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $contenido;

            foreach ($adjuntos as $adjunto) {
                $tipo = $adjunto['tipo'] ?? 'archivo';
                if ($tipo === 'imagen_embebida') {
                    continue;
                }

                $rutaAbsoluta = null;
                if (!empty($adjunto['storage_path'])) {
                    try {
                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($adjunto['storage_path'])) {
                            $rutaAbsoluta = \Illuminate\Support\Facades\Storage::disk('public')->path($adjunto['storage_path']);
                        }
                    } catch (\Exception $e) {
                        Log::warning("No se pudo resolver storage_path '{$adjunto['storage_path']}': " . $e->getMessage());
                    }
                }

                if (!$rutaAbsoluta && !empty($adjunto['path']) && file_exists($adjunto['path'])) {
                    $rutaAbsoluta = $adjunto['path'];
                }

                if (!$rutaAbsoluta) {
                    continue;
                }

                $mail->addAttachment($rutaAbsoluta, $adjunto['name'] ?? basename($rutaAbsoluta));
            }

            $mail->send();
            $this->guardarCorreoEnviado($mantenimientoId, $mensaje, $messageId, $threadId, $adjuntos);

            Log::info("Respuesta de mantenimiento enviada para solicitud #{$mantenimientoId}");
            return true;
        } catch (Exception $e) {
            Log::error("Error enviando respuesta de mantenimiento: " . $e->getMessage());
            return false;
        }
    }

    private function construirContenidoConInstrucciones($ticket, $mensaje, $threadId)
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
                <p>Hola <strong>{$ticket->empleado->NombreEmpleado}</strong>,</p>
                <p>Hemos recibido tu solicitud de mantenimiento y te proporcionamos la siguiente respuesta:</p>
                <div class='response'>
                    <h3>Respuesta del equipo de compras:</h3>
                    {$mensaje}
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
                <p><strong>Sistema de Mantenimientos ERP TI - Proser</strong></p>
                <p>Este es un correo automático. Para responder, simplemente responde a este correo manteniendo el asunto original.</p>
            </div>
        </body>
        </html>";
    }

    private function configurarMailer(PHPMailer $mail): void
    {
        $mail->isSMTP();
        $mail->Host = $this->smtpHost;
        $mail->Port = $this->smtpPort;
        $mail->SMTPSecure = $this->smtpEncryption;
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtpUsername;
        $mail->Password = $this->smtpPassword;
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

    private function generarMessageId(): string
    {
        $domain = config('email_tickets.smtp.domain');
        return "<mantenimiento-" . time() . '-' . uniqid() . "@{$domain}>";
    }

    private function obtenerThreadIdDelMantenimiento($mantenimientoId): string
    {
        $existingChat = MantenimientoChat::where('mantenimiento_id', $mantenimientoId)
            ->whereNotNull('thread_id')
            ->first();

        if ($existingChat) {
            return $existingChat->thread_id;
        }

        $domain = config('email_tickets.smtp.domain');
        return "<thread-mantenimiento-{$mantenimientoId}-" . time() . "@{$domain}>";
    }

    private function guardarCorreoEnviado($mantenimientoId, $mensaje, $messageId, $threadId, $adjuntos = []): void
    {
        $ticket = TicketMantenimiento::with('empleado')->find($mantenimientoId);
        $contenidoCompleto = $this->construirContenidoConInstrucciones($ticket, $mensaje, $threadId);

        $adjuntosProcesados = [];
        foreach ($adjuntos as $adjunto) {
            $adjuntosProcesados[] = [
                'name' => $adjunto['name'] ?? basename($adjunto['path'] ?? ''),
                'storage_path' => $adjunto['storage_path'] ?? null,
                'url' => $adjunto['url'] ?? null,
                'size' => $adjunto['size'] ?? null,
                'mime_type' => $adjunto['mime_type'] ?? null,
                'tipo' => $adjunto['tipo'] ?? 'archivo',
            ];
        }

        MantenimientoChat::create([
            'mantenimiento_id' => $mantenimientoId,
            'mensaje' => $mensaje,
            'remitente' => 'soporte',
            'nombre_remitente' => config('mail.from.name'),
            'correo_remitente' => config('mail.from.address'),
            'contenido_correo' => $contenidoCompleto,
            'message_id' => $messageId,
            'thread_id' => $threadId,
            'adjuntos' => $adjuntosProcesados,
            'es_correo' => true,
            'leido' => false,
        ]);
    }
}
