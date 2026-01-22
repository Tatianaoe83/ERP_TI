<?php

namespace App\Services;

use App\Models\Empleados;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envío de correos para el flujo de aprobación de solicitudes.
 * - Revisión pendiente (al crear, al aprobar siguiente, al transferir).
 */
class SolicitudAprobacionEmailService
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
     * Enviar correo al aprobador con enlace de revisión (token).
     * Se usa al crear solicitud, al aprobar (siguiente paso) y al transferir.
     */
    public function enviarRevisionPendiente(Empleados $aprobador, Solicitud $solicitud, string $token, string $stageLabel): bool
    {
        if (empty($aprobador->Correo)) {
            Log::warning("SolicitudAprobacionEmailService: aprobador {$aprobador->EmpleadoID} sin correo, no se envía email.");
            return false;
        }

        $url = url('/revision-solicitud/' . $token);
        $asunto = "Revisión de solicitud #{$solicitud->SolicitudID} – {$stageLabel}";
        $contenido = $this->construirContenidoRevision($solicitud, $stageLabel, $url, $aprobador->NombreEmpleado);

        try {
            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);

            $fromAddress = config('email_tickets.smtp.from_address', config('mail.from.address'));
            $nombreSoporte = config('mail.from.name', 'Sistema de Solicitudes');

            $mail->setFrom($fromAddress, $nombreSoporte);
            $mail->addAddress($aprobador->Correo, $aprobador->NombreEmpleado);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto;
            $mail->Body = $contenido;
            $mail->send();

            Log::info("Email de revisión enviado para solicitud #{$solicitud->SolicitudID} a {$aprobador->Correo} ({$stageLabel})");
            return true;
        } catch (Exception $e) {
            Log::error("Error enviando email de revisión solicitud #{$solicitud->SolicitudID}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notificar a gerente que hay cotizaciones para elegir (tras crear cotizaciones).
     */
    public function enviarCotizacionesListasParaElegir(Empleados $gerente, Solicitud $solicitud, string $token = null): bool
    {
        if (empty($gerente->Correo)) {
            Log::warning("SolicitudAprobacionEmailService: gerente {$gerente->EmpleadoID} sin correo.");
            return false;
        }

        // Si hay token, usar la URL personalizada con token, sino usar la ruta general
        if ($token) {
            $urlElegir = url('/elegir-ganador/' . $token);
        } else {
            $urlElegir = route('tickets.index');
        }
        
        $asunto = "Propuestas listas – Elige ganador – Solicitud #{$solicitud->SolicitudID}";
        $contenido = $this->construirContenidoCotizacionesListas($solicitud, $urlElegir, $gerente->NombreEmpleado);

        try {
            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);

            $fromAddress = config('email_tickets.smtp.from_address', config('mail.from.address'));
            $nombreSoporte = config('mail.from.name', 'Sistema de Solicitudes');

            $mail->setFrom($fromAddress, $nombreSoporte);
            $mail->addAddress($gerente->Correo, $gerente->NombreEmpleado);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto;
            $mail->Body = $contenido;
            $mail->send();

            Log::info("Email cotizaciones listas enviado para solicitud #{$solicitud->SolicitudID} a {$gerente->Correo}");
            return true;
        } catch (Exception $e) {
            Log::error("Error enviando email cotizaciones listas solicitud #{$solicitud->SolicitudID}: " . $e->getMessage());
            return false;
        }
    }

    private function construirContenidoRevision(Solicitud $solicitud, string $stageLabel, string $url, string $nombreAprobador): string
    {
        $empleado = $solicitud->empleadoid;
        $nombreSolicitante = $empleado ? $empleado->NombreEmpleado : 'N/A';
        $motivo = e($solicitud->Motivo ?? 'N/A');
        $desc = e($solicitud->DescripcionMotivo ?? '');
        $req = e($solicitud->Requerimientos ?? '');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 20px;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="color: #0F766E;">Revisión de solicitud #{$solicitud->SolicitudID}</h2>
        <p>Hola <strong>{$nombreAprobador}</strong>,</p>
        <p>Hay una solicitud pendiente de tu aprobación en la etapa de <strong>{$stageLabel}</strong>.</p>
        <div style="background: #f3f4f6; padding: 16px; border-radius: 8px; margin: 16px 0;">
            <p><strong>Solicitante:</strong> {$nombreSolicitante}</p>
            <p><strong>Motivo:</strong> {$motivo}</p>
            <p><strong>Descripción:</strong><br>{$desc}</p>
            <p><strong>Requerimientos:</strong><br>{$req}</p>
        </div>
        <p>Accede al enlace siguiente para revisar, aprobar o rechazar:</p>
        <p style="margin: 24px 0;">
            <a href="{$url}" style="background: #0F766E; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;">Revisar solicitud</a>
        </p>
        <p style="font-size: 12px; color: #6b7280;">Si el enlace no funciona, copia y pega en tu navegador: {$url}</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
        <p style="font-size: 12px; color: #9ca3af;">Este correo fue enviado automáticamente por el Sistema de Solicitudes.</p>
    </div>
</body>
</html>
HTML;
    }

    private function construirContenidoCotizacionesListas(Solicitud $solicitud, string $url, string $nombreGerente): string
    {
        $empleado = $solicitud->empleadoid;
        $nombreSolicitante = $empleado ? $empleado->NombreEmpleado : 'N/A';
        $motivo = e($solicitud->Motivo ?? 'N/A');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 20px;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="color: #0F766E;">Propuestas listas – Elige el ganador</h2>
        <p>Hola <strong>{$nombreGerente}</strong>,</p>
        <p>La solicitud <strong>#{$solicitud->SolicitudID}</strong> tiene las propuestas de cotización cargadas. Revisa las opciones y <strong>elige el ganador</strong>.</p>
        <div style="background: #f3f4f6; padding: 16px; border-radius: 8px; margin: 16px 0;">
            <p><strong>Solicitante:</strong> {$nombreSolicitante}</p>
            <p><strong>Motivo:</strong> {$motivo}</p>
        </div>
        <p style="margin: 24px 0;">
            <a href="{$url}" style="background: #0F766E; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;">Ver propuestas y elegir ganador</a>
        </p>
        <p style="font-size: 12px; color: #6b7280;">Si el enlace no funciona, copia y pega en tu navegador: {$url}</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
        <p style="font-size: 12px; color: #9ca3af;">Este correo fue enviado automáticamente por el Sistema de Solicitudes.</p>
    </div>
</body>
</html>
HTML;
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
}
