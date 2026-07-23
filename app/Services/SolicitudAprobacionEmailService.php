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
            Log::warning(
                "SolicitudAprobacionEmailService: aprobador {$aprobador->EmpleadoID} sin correo, no se envía email. " .
                    "Solicitud #{$solicitud->SolicitudID}, etapa {$stageLabel}"
            );
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

            Log::info(
                "Email de revisión enviado para solicitud #{$solicitud->SolicitudID} a {$aprobador->Correo} " .
                    "(etapa: {$stageLabel}, token: {$token})"
            );

            $this->marcarTokenNotificado($token);

            return true;
        } catch (Exception $e) {
            Log::error(
                "Error enviando email de revisión solicitud #{$solicitud->SolicitudID} " .
                    "a {$aprobador->Correo} (etapa: {$stageLabel}, token: {$token}): " . $e->getMessage()
            );

            return false;
        }
    }

    /**
     * Notificar a gerente que hay cotizaciones para elegir (tras crear cotizaciones).
     */
    public function enviarCotizacionesListasParaElegir(Empleados $gerente, Solicitud $solicitud, string $token = null): bool
    {
        if (empty($gerente->Correo)) {
            Log::warning("SolicitudAprobacionEmailService: gerente sin correo para solicitud #{$solicitud->SolicitudID}.");
            return false;
        }

        // Si hay token, usar la URL personalizada con token, sino usar la ruta general
        if ($token) {
            $urlElegir = url('/elegir-ganador/' . $token);
            Log::info("URL generada con token para solicitud #{$solicitud->SolicitudID}: {$urlElegir}");
        } else {
            $urlElegir = route('tickets.index');
            Log::warning("No se proporcionó token para solicitud #{$solicitud->SolicitudID}, usando ruta general: {$urlElegir}");
        }

        $asunto = "Propuestas listas – Elige ganador – Solicitud #{$solicitud->SolicitudID}";
        $contenido = $this->construirContenidoCotizacionesListas($solicitud, $urlElegir, $gerente->NombreEmpleado);

        // Log del contenido del correo (solo la URL para verificar)
        Log::info("Preparando correo para solicitud #{$solicitud->SolicitudID} - URL en correo: {$urlElegir} - Destinatario: {$gerente->Correo}");

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

            Log::info("Email cotizaciones listas enviado exitosamente para solicitud #{$solicitud->SolicitudID} a {$gerente->Correo} - URL: {$urlElegir}");

            if ($token) {
                $this->marcarTokenNotificado($token);
            }

            return true;
        } catch (Exception $e) {
            Log::error("Error enviando email cotizaciones listas solicitud #{$solicitud->SolicitudID} a {$gerente->Correo}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
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

    /**
     * Notificar que se eligió un ganador y se puede proceder a la compra.
     */
    public function enviarGanadorSeleccionado(Solicitud $solicitud, \App\Models\Cotizacion $cotizacionGanadora, string $correoDestinatario = 'tordonez@proser.com.mx'): bool
    {
        return $this->enviarGanadoresSeleccionados($solicitud, collect([$cotizacionGanadora]), $correoDestinatario);
    }

    /**
     * Notificar que se eligieron los ganadores (uno por producto) y se puede proceder a la compra.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Cotizacion>  $ganadores
     */
    public function enviarGanadoresSeleccionados(Solicitud $solicitud, $ganadores, string $correoDestinatario = 'tordonez@proser.com.mx'): bool
    {
        if (empty($correoDestinatario)) {
            Log::warning("SolicitudAprobacionEmailService: correo destinatario vacío para notificación de ganadores seleccionados.");
            return false;
        }

        $titulo = $ganadores->count() > 1 ? 'Ganadores seleccionados' : 'Ganador seleccionado';
        $asunto = "{$titulo} – Proceder a compra – Solicitud #{$solicitud->SolicitudID}";
        $contenido = $this->construirContenidoGanadoresSeleccionados($solicitud, $ganadores);

        try {
            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);

            $fromAddress = config('email_tickets.smtp.from_address', config('mail.from.address'));
            $nombreSoporte = config('mail.from.name', 'Sistema de Solicitudes');

            $mail->setFrom($fromAddress, $nombreSoporte);
            $mail->addAddress($correoDestinatario);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto;
            $mail->Body = $contenido;
            $mail->send();

            Log::info("Email ganador(es) seleccionado(s) enviado para solicitud #{$solicitud->SolicitudID} a {$correoDestinatario}");
            return true;
        } catch (Exception $e) {
            Log::error("Error enviando email ganadores seleccionados solicitud #{$solicitud->SolicitudID}: " . $e->getMessage());
            return false;
        }
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

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Cotizacion>  $ganadores
     */
    private function construirContenidoGanadoresSeleccionados(Solicitud $solicitud, $ganadores): string
    {
        $empleado = $solicitud->empleadoid;
        $nombreSolicitante = $empleado ? $empleado->NombreEmpleado : 'N/A';
        $motivo = e($solicitud->Motivo ?? 'N/A');
        $urlSistema = route('tickets.index');
        $titulo = $ganadores->count() > 1 ? 'Ganadores seleccionados – Proceder a compra' : 'Ganador seleccionado – Proceder a compra';
        $intro = $ganadores->count() > 1
            ? 'Se han seleccionado los ganadores de todos los productos de la solicitud <strong>#' . $solicitud->SolicitudID . '</strong>. Ya puedes proceder con la compra.'
            : 'Se ha seleccionado el ganador para la solicitud <strong>#' . $solicitud->SolicitudID . '</strong>. Ya puedes proceder con la compra.';

        $filas = $ganadores->map(function ($c) {
            $proveedor = e($c->Proveedor ?? 'N/A');
            $descripcion = e($c->Descripcion ?? 'N/A');
            $precio = number_format($c->Precio ?? 0, 2, '.', ',');
            $numeroParte = e($c->NumeroParte ?? 'N/A');
            $cant = (int) ($c->Cantidad ?? 1);
            $cantidad = $cant > 1 ? " × {$cant}" : '';
            return "<tr><td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;'>{$descripcion}{$cantidad}</td><td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;'>{$numeroParte}</td><td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;'>{$proveedor}</td><td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:right;'>\$ {$precio} MXN</td></tr>";
        })->implode('');

        $tabla = $ganadores->count() > 1
            ? "<div style='background:#ecfdf5;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #10b981;'><h3 style='color:#059669;margin-top:0;'>Ganadores por producto</h3><table style='width:100%;border-collapse:collapse;'><thead><tr><th style='text-align:left;padding:8px 12px;border-bottom:1px solid #10b981;'>Descripción</th><th style='text-align:left;padding:8px 12px;border-bottom:1px solid #10b981;'>No. Parte</th><th style='text-align:left;padding:8px 12px;border-bottom:1px solid #10b981;'>Proveedor</th><th style='text-align:right;padding:8px 12px;border-bottom:1px solid #10b981;'>Precio</th></tr></thead><tbody>{$filas}</tbody></table></div>"
            : $this->construirBloqueGanadorUnico($ganadores->first());

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 20px;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="color: #0F766E;">{$titulo}</h2>
        <p>Hola,</p>
        <p>{$intro}</p>
        <div style="background: #f3f4f6; padding: 16px; border-radius: 8px; margin: 16px 0;">
            <h3 style="color: #0F766E; margin-top: 0;">Información de la Solicitud</h3>
            <p><strong>Solicitante:</strong> {$nombreSolicitante}</p>
            <p><strong>Motivo:</strong> {$motivo}</p>
            <p><strong>Solicitud ID:</strong> #{$solicitud->SolicitudID}</p>
        </div>
        {$tabla}
        <p style="margin: 24px 0;">
            <a href="{$urlSistema}" style="background: #0F766E; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;">Ver solicitud en el sistema</a>
        </p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
        <p style="font-size: 12px; color: #9ca3af;">Este correo fue enviado automáticamente por el Sistema de Solicitudes.</p>
    </div>
</body>
</html>
HTML;
    }

    private function construirBloqueGanadorUnico(\App\Models\Cotizacion $cotizacion): string
    {
        $proveedor = e($cotizacion->Proveedor ?? 'N/A');
        $descripcion = e($cotizacion->Descripcion ?? 'N/A');
        $precio = number_format($cotizacion->Precio ?? 0, 2, '.', ',');
        $numeroParte = e($cotizacion->NumeroParte ?? 'N/A');
        return "<div style='background:#ecfdf5;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #10b981;'><h3 style='color:#059669;margin-top:0;'>Cotización Ganadora</h3><p><strong>Proveedor:</strong> {$proveedor}</p><p><strong>Número de Parte:</strong> {$numeroParte}</p><p><strong>Descripción:</strong> {$descripcion}</p><p><strong>Precio:</strong> \$ {$precio} MXN</p></div>";
    }

    /**
     * Marcar el token como "ya notificado" para que entre al ciclo de recordatorios diarios.
     */
    private function marcarTokenNotificado(string $token): void
    {
        try {
            \App\Models\SolicitudTokens::where('token', $token)
                ->whereNull('notified_at')
                ->update(['notified_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning("No se pudo marcar notified_at del token {$token}: " . $e->getMessage());
        }
    }

    /**
     * Recordatorio diario mientras el enlace siga activo.
     * El contenido depende de la etapa donde se quedó la solicitud:
     * - supervisor: sólo ve los datos de la solicitud.
     * - gerencia: elige un ganador de la cotización.
     * - administracion: ve los productos ganadores.
     *
     * @param  \Illuminate\Support\Collection|null  $ganadores  Sólo para la etapa de administración.
     */
    public function enviarRecordatorio(
        Empleados $aprobador,
        Solicitud $solicitud,
        string $token,
        string $stage,
        int $diasRestantes,
        $ganadores = null
    ): bool {
        if (empty($aprobador->Correo)) {
            Log::warning(
                "SolicitudAprobacionEmailService: aprobador {$aprobador->EmpleadoID} sin correo, " .
                    "no se envía recordatorio de la solicitud #{$solicitud->SolicitudID} (etapa {$stage})"
            );
            return false;
        }

        $url = $stage === 'gerencia'
            ? url('/elegir-ganador/' . $token)
            : url('/revision-solicitud/' . $token);

        $asuntos = [
            'supervisor' => "Recordatorio: solicitud #{$solicitud->SolicitudID} pendiente de tu Vo.bo.",
            'gerencia' => "Recordatorio: elige el ganador de la solicitud #{$solicitud->SolicitudID}",
            'administracion' => "Recordatorio: solicitud #{$solicitud->SolicitudID} pendiente de aprobación de Administración",
        ];
        $asunto = $asuntos[$stage] ?? "Recordatorio: solicitud #{$solicitud->SolicitudID} pendiente";

        $contenido = $this->construirContenidoRecordatorio(
            $solicitud,
            $stage,
            $url,
            $aprobador->NombreEmpleado,
            $diasRestantes,
            $ganadores
        );

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

            Log::info(
                "Recordatorio enviado para solicitud #{$solicitud->SolicitudID} a {$aprobador->Correo} " .
                    "(etapa: {$stage}, token: {$token}, días restantes: {$diasRestantes})"
            );

            return true;
        } catch (Exception $e) {
            Log::error(
                "Error enviando recordatorio solicitud #{$solicitud->SolicitudID} a {$aprobador->Correo} " .
                    "(etapa: {$stage}, token: {$token}): " . $e->getMessage()
            );

            return false;
        }
    }

    /**
     * @param  \Illuminate\Support\Collection|null  $ganadores
     */
    private function construirContenidoRecordatorio(
        Solicitud $solicitud,
        string $stage,
        string $url,
        string $nombreAprobador,
        int $diasRestantes,
        $ganadores = null
    ): string {
        $empleado = $solicitud->empleadoid;
        $nombreSolicitante = $empleado ? $empleado->NombreEmpleado : 'N/A';
        $motivo = e($solicitud->Motivo ?? 'N/A');

        $titulos = [
            'supervisor' => 'Recordatorio: solicitud pendiente de tu Vo.bo.',
            'gerencia' => 'Recordatorio: falta elegir el ganador',
            'administracion' => 'Recordatorio: solicitud pendiente de aprobación',
        ];
        $intros = [
            'supervisor' => 'La solicitud <strong>#' . $solicitud->SolicitudID . '</strong> sigue esperando tu revisión. Revisa los datos y da tu Vo.bo. o recházala.',
            'gerencia' => 'La solicitud <strong>#' . $solicitud->SolicitudID . '</strong> ya tiene las propuestas de cotización cargadas y sigue esperando que <strong>elijas el ganador</strong>.',
            'administracion' => 'La solicitud <strong>#' . $solicitud->SolicitudID . '</strong> ya tiene los ganadores seleccionados y sigue esperando la aprobación de Administración.',
        ];
        $botones = [
            'supervisor' => 'Revisar solicitud',
            'gerencia' => 'Ver propuestas y elegir ganador',
            'administracion' => 'Ver ganadores y aprobar',
        ];

        $titulo = $titulos[$stage] ?? 'Recordatorio: solicitud pendiente';
        $intro = $intros[$stage] ?? 'La solicitud <strong>#' . $solicitud->SolicitudID . '</strong> sigue pendiente de tu revisión.';
        $boton = $botones[$stage] ?? 'Revisar solicitud';

        // Detalle de la solicitud: sólo el supervisor necesita ver todo el detalle.
        $desc = e($solicitud->DescripcionMotivo ?? '');
        $req = e($solicitud->Requerimientos ?? '');
        $bloqueDatos = $stage === 'supervisor'
            ? "<div style='background:#f3f4f6;padding:16px;border-radius:8px;margin:16px 0;'>"
                . "<p><strong>Solicitante:</strong> {$nombreSolicitante}</p>"
                . "<p><strong>Motivo:</strong> {$motivo}</p>"
                . "<p><strong>Descripción:</strong><br>{$desc}</p>"
                . "<p><strong>Requerimientos:</strong><br>{$req}</p></div>"
            : "<div style='background:#f3f4f6;padding:16px;border-radius:8px;margin:16px 0;'>"
                . "<p><strong>Solicitante:</strong> {$nombreSolicitante}</p>"
                . "<p><strong>Motivo:</strong> {$motivo}</p></div>";

        // Administración: mostrar los productos ganadores.
        $bloqueGanadores = '';
        if ($stage === 'administracion' && $ganadores && $ganadores->isNotEmpty()) {
            $filas = $ganadores->map(function ($c) {
                $proveedor = e($c->Proveedor ?? 'N/A');
                $descripcion = e($c->Descripcion ?? 'N/A');
                $precio = number_format($c->Precio ?? 0, 2, '.', ',');
                $numeroParte = e($c->NumeroParte ?? 'N/A');
                $cant = (int) ($c->Cantidad ?? 1);
                $cantidad = $cant > 1 ? " × {$cant}" : '';
                return "<tr><td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;'>{$descripcion}{$cantidad}</td>"
                    . "<td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;'>{$numeroParte}</td>"
                    . "<td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;'>{$proveedor}</td>"
                    . "<td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;text-align:right;'>\$ {$precio} MXN</td></tr>";
            })->implode('');

            $bloqueGanadores = "<div style='background:#ecfdf5;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #10b981;'>"
                . "<h3 style='color:#059669;margin-top:0;'>Productos ganadores</h3>"
                . "<table style='width:100%;border-collapse:collapse;'><thead><tr>"
                . "<th style='text-align:left;padding:8px 12px;border-bottom:1px solid #10b981;'>Descripción</th>"
                . "<th style='text-align:left;padding:8px 12px;border-bottom:1px solid #10b981;'>No. Parte</th>"
                . "<th style='text-align:left;padding:8px 12px;border-bottom:1px solid #10b981;'>Proveedor</th>"
                . "<th style='text-align:right;padding:8px 12px;border-bottom:1px solid #10b981;'>Precio</th>"
                . "</tr></thead><tbody>{$filas}</tbody></table></div>";
        }

        $avisoVigencia = $diasRestantes <= 1
            ? "<p style='background:#fef2f2;border-left:4px solid #ef4444;padding:12px;border-radius:6px;color:#991b1b;'><strong>El enlace expira hoy.</strong> Después de eso deberá generarse uno nuevo.</p>"
            : "<p style='background:#fffbeb;border-left:4px solid #f59e0b;padding:12px;border-radius:6px;color:#92400e;'>Este enlace estará disponible <strong>{$diasRestantes} día(s) más</strong>.</p>";

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 20px;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="color: #0F766E;">{$titulo}</h2>
        <p>Hola <strong>{$nombreAprobador}</strong>,</p>
        <p>{$intro}</p>
        {$bloqueDatos}
        {$bloqueGanadores}
        {$avisoVigencia}
        <p style="margin: 24px 0;">
            <a href="{$url}" style="background: #0F766E; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;">{$boton}</a>
        </p>
        <p style="font-size: 12px; color: #6b7280;">Si el enlace no funciona, copia y pega en tu navegador: {$url}</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
        <p style="font-size: 12px; color: #9ca3af;">Este correo fue enviado automáticamente por el Sistema de Solicitudes. Si ya atendiste la solicitud, ignora este mensaje.</p>
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
