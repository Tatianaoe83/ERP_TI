<?php

namespace App\Services;

use App\Models\Empleados;
use App\Models\Solicitud;
use App\Models\SolicitudPasos;
use App\Models\SolicitudTokens;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envío de correos para el flujo de aprobación de solicitudes.
 * - Revisión pendiente (al crear, al aprobar siguiente, al transferir).
 * - Cotizaciones listas para elegir ganador.
 * - Ganadores seleccionados (proceder a compra).
 * - Recordatorios diarios.
 *
 * Todos los correos comparten la vista emails.solicitudes.mensaje.
 */
class SolicitudAprobacionEmailService
{
    /** Etiqueta corta de cada etapa del flujo. */
    private const ETAPAS = [
        'supervisor'     => 'Supervisor',
        'gerencia'       => 'Gerencia',
        'administracion' => 'Administración',
    ];

    /** Orden del flujo, para saber qué etapas ya pasaron. */
    private const ORDEN_ETAPAS = [
        'supervisor'     => 1,
        'gerencia'       => 2,
        'administracion' => 3,
    ];

    /** Paleta por tipo de correo. */
    private const ACENTO_MARCA     = ['#0F766E', '#F0FDFA'];
    private const ACENTO_EXITO     = ['#047857', '#ECFDF5'];
    private const ACENTO_RECUERDO  = ['#B45309', '#FFFBEB'];

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

        $stage = $this->resolverStage($token, $stageLabel);
        $folio = '#' . $solicitud->SolicitudID;
        $solicitante = $this->nombreSolicitante($solicitud);
        $url = url('/revision-solicitud/' . $token);
        $aprobaciones = $this->historialAprobaciones($solicitud, $stage);
        $ganadores = $stage === 'administracion' ? $this->ganadoresDe($solicitud) : collect();

        if ($stage === 'administracion') {
            $asunto = "Solicitud de compra {$folio} – Cotizaciones elegidas";
            $datos = [
                'accent'     => self::ACENTO_MARCA[0],
                'accentSoft' => self::ACENTO_MARCA[1],
                'eyebrow'    => 'Aprobación de Administración',
                'titulo'     => 'Ya hay ganadores: falta tu autorización final',
                'preheader'  => "Solicitud {$folio} de {$solicitante}: revisa los ganadores y autoriza la compra.",
                'intro'      => 'La solicitud <strong>' . e($folio) . '</strong> ya recorrió todo el flujo de aprobación y los ganadores están elegidos. Sólo falta tu autorización para proceder con la compra.',
                'boton'      => 'Ver ganadores y autorizar',
            ];
        } elseif ($stage === 'gerencia') {
            $asunto = "Cotizaciones de la compra {$folio} – Elige ganadores";
            $datos = [
                'accent'     => self::ACENTO_MARCA[0],
                'accentSoft' => self::ACENTO_MARCA[1],
                'eyebrow'    => 'Elección de ganador',
                'titulo'     => 'Las cotizaciones de esta solicitud ya están listas',
                'preheader'  => "Solicitud {$folio} de {$solicitante}: compara las propuestas y elige el ganador.",
                'intro'      => 'La solicitud <strong>' . e($folio) . '</strong> ya tiene las propuestas de cotización cargadas. Compara las opciones y <strong>elige el ganador de cada producto</strong>.',
                'boton'      => 'Ver propuestas y elegir ganador',
            ];
        } else {
            $asunto = "Solicitud de compra {$folio} – Requiere tu autorización";
            $datos = [
                'accent'     => self::ACENTO_MARCA[0],
                'accentSoft' => self::ACENTO_MARCA[1],
                'eyebrow'    => 'Vo.Bo. de supervisor',
                'titulo'     => 'Necesitamos tu firma para una compra de producto',
                'preheader'  => "{$solicitante} pide autorización de compra. Solicitud {$folio}.",
                'intro'      => '<strong>' . e($solicitante) . '</strong> solicitó una compra de producto y necesita tu firma para continuar. Revisa el detalle y da tu Vo.Bo. o recházala.',
                'boton'      => 'Revisar y firmar',
            ];
        }

        $contenido = $this->renderMensaje(array_merge($datos, [
            'folio'        => $folio,
            'saludo'       => $aprobador->NombreEmpleado,
            'url'          => $url,
            'nota'         => 'El enlace es personal y deja de funcionar cuando la solicitud avanza de etapa.',
            'filas'        => $this->filasSolicitud($solicitud),
            'bloques'      => $this->bloquesSolicitud($solicitud, $stage !== 'administracion'),
            'aprobaciones' => $aprobaciones,
            'ganadores'    => $ganadores,
        ]));

        $enviado = $this->enviar($aprobador->Correo, $aprobador->NombreEmpleado, $asunto, $contenido);

        if ($enviado) {
            Log::info(
                "Email de revisión enviado para solicitud #{$solicitud->SolicitudID} a {$aprobador->Correo} " .
                    "(etapa: {$stage}, token: {$token})"
            );
            $this->marcarTokenNotificado($token);
            return true;
        }

        Log::error(
            "Error enviando email de revisión solicitud #{$solicitud->SolicitudID} " .
                "a {$aprobador->Correo} (etapa: {$stage}, token: {$token})"
        );

        return false;
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

        $folio = '#' . $solicitud->SolicitudID;
        $propuestas = $this->totalPropuestas($solicitud);
        $detallePropuestas = $propuestas > 0
            ? ' (' . $propuestas . ' ' . ($propuestas === 1 ? 'propuesta' : 'propuestas') . ')'
            : '';

        $asunto = "Cotizaciones de la compra {$folio} – Elige ganadores";

        $contenido = $this->renderMensaje([
            'accent'       => self::ACENTO_MARCA[0],
            'accentSoft'   => self::ACENTO_MARCA[1],
            'eyebrow'      => 'Elección de ganador',
            'titulo'       => 'Ya tienes las cotizaciones de esta solicitud',
            'preheader'    => "Solicitud {$folio}: compara las propuestas y elige el ganador.",
            'folio'        => $folio,
            'saludo'       => $gerente->NombreEmpleado,
            'intro'        => 'La solicitud <strong>' . e($folio) . '</strong> ya tiene sus cotizaciones cargadas' . e($detallePropuestas) . '. Compara las opciones y <strong>elige el ganador de cada producto</strong>.',
            'url'          => $urlElegir,
            'boton'        => 'Ver cotizaciones y elegir ganador',
            'nota'         => 'El enlace es personal y deja de funcionar cuando eliges a todos los ganadores.',
            'filas'        => $this->filasSolicitud($solicitud),
            'bloques'      => $this->bloquesSolicitud($solicitud),
            'aprobaciones' => $this->historialAprobaciones($solicitud, 'gerencia'),
        ]);

        if ($this->enviar($gerente->Correo, $gerente->NombreEmpleado, $asunto, $contenido)) {
            Log::info("Email cotizaciones listas enviado exitosamente para solicitud #{$solicitud->SolicitudID} a {$gerente->Correo} - URL: {$urlElegir}");

            if ($token) {
                $this->marcarTokenNotificado($token);
            }

            return true;
        }

        Log::error("Error enviando email cotizaciones listas solicitud #{$solicitud->SolicitudID} a {$gerente->Correo}");
        return false;
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

        $folio = '#' . $solicitud->SolicitudID;
        $varios = $ganadores->count() > 1;
        $asunto = "Solicitud de compra {$folio} – Compra autorizada, proceder con el proveedor";

        $contenido = $this->renderMensaje([
            'accent'       => self::ACENTO_EXITO[0],
            'accentSoft'   => self::ACENTO_EXITO[1],
            'eyebrow'      => 'Listo para comprar',
            'titulo'       => $varios ? 'Ganadores elegidos: ya puedes proceder a la compra' : 'Ganador elegido: ya puedes proceder a la compra',
            'preheader'    => "Solicitud {$folio}: " . ($varios ? 'ganadores elegidos' : 'ganador elegido') . ', lista para compra.',
            'folio'        => $folio,
            'intro'        => 'La solicitud <strong>' . e($folio) . '</strong> completó su flujo de aprobación y ' . ($varios ? 'ya tiene un ganador por producto' : 'ya tiene ganador') . '. Puedes proceder con la compra.',
            'url'          => route('tickets.index'),
            'boton'        => 'Ver solicitud en el sistema',
            'filas'        => $this->filasSolicitud($solicitud),
            'aprobaciones' => $this->historialAprobaciones($solicitud),
            'ganadores'    => $ganadores,
        ]);

        if ($this->enviar($correoDestinatario, null, $asunto, $contenido)) {
            Log::info("Email ganador(es) seleccionado(s) enviado para solicitud #{$solicitud->SolicitudID} a {$correoDestinatario}");
            return true;
        }

        Log::error("Error enviando email ganadores seleccionados solicitud #{$solicitud->SolicitudID}");
        return false;
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
        $ganadores = null
    ): bool {
        if (empty($aprobador->Correo)) {
            Log::warning(
                "SolicitudAprobacionEmailService: aprobador {$aprobador->EmpleadoID} sin correo, " .
                    "no se envía recordatorio de la solicitud #{$solicitud->SolicitudID} (etapa {$stage})"
            );
            return false;
        }

        $folio = '#' . $solicitud->SolicitudID;
        $solicitante = $this->nombreSolicitante($solicitud);

        $url = $stage === 'gerencia'
            ? url('/elegir-ganador/' . $token)
            : url('/revision-solicitud/' . $token);

        $config = [
            'supervisor' => [
                'asunto' => "Recordatorio: Solicitud de compra {$folio} pendiente de tu autorización",
                'titulo' => 'Sigue pendiente tu firma para esta compra',
                'intro'  => 'La solicitud <strong>' . e($folio) . '</strong> de <strong>' . e($solicitante) . '</strong> sigue esperando tu Vo.Bo. Revisa el detalle y fírmala o recházala.',
                'boton'  => 'Revisar y firmar',
            ],
            'gerencia' => [
                'asunto' => "Recordatorio: Cotizaciones de la Solicitud {$folio} pendiente de elección",
                'titulo' => 'Las cotizaciones siguen esperando tu elección',
                'intro'  => 'La solicitud <strong>' . e($folio) . '</strong> ya tiene sus cotizaciones cargadas y sigue esperando que <strong>elijas el ganador</strong>.',
                'boton'  => 'Ver cotizaciones y elegir ganador',
            ],
            'administracion' => [
                'asunto' => "Recordatorio: Solicitud de compra {$folio} pendiente de aprobación",
                'titulo' => 'Los ganadores siguen esperando tu autorización',
                'intro'  => 'La solicitud <strong>' . e($folio) . '</strong> ya tiene los ganadores elegidos y sigue esperando la autorización de Administración.',
                'boton'  => 'Ver ganadores y autorizar',
            ],
        ];

        $c = $config[$stage] ?? [
            'asunto' => "Recordatorio: Solicitud de compra {$folio} pendiente de tu revisión",
            'titulo' => 'Esta solicitud sigue pendiente de tu revisión',
            'intro'  => 'La solicitud <strong>' . e($folio) . '</strong> sigue pendiente de tu revisión.',
            'boton'  => 'Revisar solicitud',
        ];

        $contenido = $this->renderMensaje([
            'accent'       => self::ACENTO_RECUERDO[0],
            'accentSoft'   => self::ACENTO_RECUERDO[1],
            'eyebrow'      => 'Recordatorio',
            'titulo'       => $c['titulo'],
            'preheader'    => "Recordatorio de la solicitud {$folio}: " . strtolower(self::ETAPAS[$stage] ?? 'revisión') . ' pendiente.',
            'folio'        => $folio,
            'saludo'       => $aprobador->NombreEmpleado,
            'intro'        => $c['intro'],
            'url'          => $url,
            'boton'        => $c['boton'],
            'nota'         => 'Si ya atendiste la solicitud, ignora este mensaje: los recordatorios se detienen solos.',
            'filas'        => $this->filasSolicitud($solicitud),
            'bloques'      => $this->bloquesSolicitud($solicitud, $stage === 'supervisor'),
            'aprobaciones' => $this->historialAprobaciones($solicitud, $stage),
            'ganadores'    => $stage === 'administracion' ? ($ganadores ?: $this->ganadoresDe($solicitud)) : collect(),
        ]);

        if ($this->enviar($aprobador->Correo, $aprobador->NombreEmpleado, $c['asunto'], $contenido)) {
            Log::info(
                "Recordatorio enviado para solicitud #{$solicitud->SolicitudID} a {$aprobador->Correo} " .
                    "(etapa: {$stage}, token: {$token})"
            );
            return true;
        }

        Log::error(
            "Error enviando recordatorio solicitud #{$solicitud->SolicitudID} a {$aprobador->Correo} " .
                "(etapa: {$stage}, token: {$token})"
        );

        return false;
    }

    /**
     * Historial de quién ya aprobó antes de la etapa indicada.
     * Cuando el solicitante es Gerencia no hay supervisor real: ese paso se auto-aprueba
     * al crear la solicitud y aquí se explica por qué aparece como aprobado.
     *
     * @return array<int, array{etapa:string,nombre:string,fecha:string,auto:bool,nota:string}>
     */
    private function historialAprobaciones(Solicitud $solicitud, ?string $stageActual = null): array
    {
        $limite = $stageActual !== null
            ? (self::ORDEN_ETAPAS[$stageActual] ?? PHP_INT_MAX)
            : PHP_INT_MAX;

        $pasos = SolicitudPasos::with(['approverEmpleado', 'decidedByEmpleado'])
            ->where('solicitud_id', $solicitud->SolicitudID)
            ->where('status', 'approved')
            ->orderBy('step_order')
            ->get();

        $historial = [];

        foreach ($pasos as $paso) {
            $orden = self::ORDEN_ETAPAS[$paso->stage] ?? PHP_INT_MAX;

            if ($orden >= $limite) {
                continue;
            }

            $empleado = $paso->decidedByEmpleado ?: $paso->approverEmpleado;
            $comentario = trim((string) $paso->comment);
            $auto = $comentario !== '' && stripos($comentario, 'autom') !== false;

            if ($auto) {
                $nota = 'Aprobación automática por jerarquía: el solicitante es Gerencia, así que la solicitud queda autorizada desde su origen.';
            } elseif ($comentario !== '') {
                $nota = 'Comentario: ' . $comentario;
            } else {
                $nota = '';
            }

            $historial[] = [
                'etapa'  => self::ETAPAS[$paso->stage] ?? ucfirst($paso->stage),
                'nombre' => $empleado->NombreEmpleado ?? 'Sin registrar',
                'fecha'  => $paso->decided_at ? $paso->decided_at->format('d/m/Y H:i') : '',
                'auto'   => $auto,
                'nota'   => $nota,
            ];
        }

        return $historial;
    }

    /**
     * Etapa real del token; si no se puede resolver, se deduce de la etiqueta recibida.
     */
    private function resolverStage(string $token, string $stageLabel): string
    {
        $tokenRow = SolicitudTokens::with('approvalStep')->where('token', $token)->first();

        if ($tokenRow && $tokenRow->approvalStep && isset(self::ETAPAS[$tokenRow->approvalStep->stage])) {
            return $tokenRow->approvalStep->stage;
        }

        $etiqueta = mb_strtolower($stageLabel);

        foreach (['administracion' => 'administraci', 'gerencia' => 'gerenc', 'supervisor' => 'supervisor'] as $stage => $aguja) {
            if (mb_strpos($etiqueta, $aguja) !== false) {
                return $stage;
            }
        }

        return 'supervisor';
    }

    /**
     * Datos cortos de la solicitud (tarjeta de detalle).
     */
    private function filasSolicitud(Solicitud $solicitud): array
    {
        $presupuesto = (float) ($solicitud->Presupuesto ?? 0);

        return array_filter([
            'Solicitante'          => $this->nombreSolicitante($solicitud),
            'Motivo'               => $solicitud->Motivo ?: null,
            'Fecha de solicitud'   => $solicitud->created_at ? $solicitud->created_at->format('d/m/Y') : null,
            'Presupuesto estimado' => $presupuesto > 0 ? '$ ' . number_format($presupuesto, 2, '.', ',') . ' MXN' : null,
        ]);
    }

    /**
     * Textos largos de la solicitud. Sólo se muestran donde aportan (etapas de revisión).
     */
    private function bloquesSolicitud(Solicitud $solicitud, bool $incluirDetalle = true): array
    {
        if (! $incluirDetalle) {
            return [];
        }

        return array_filter([
            'Descripción'    => $solicitud->DescripcionMotivo ?: null,
            'Requerimientos' => $solicitud->Requerimientos ?: null,
        ]);
    }

    private function nombreSolicitante(Solicitud $solicitud): string
    {
        return $solicitud->empleadoid->NombreEmpleado ?? 'N/A';
    }

    /**
     * Cotizaciones ganadoras de la solicitud.
     */
    private function ganadoresDe(Solicitud $solicitud)
    {
        $cotizaciones = $solicitud->relationLoaded('cotizaciones')
            ? $solicitud->cotizaciones
            : $solicitud->cotizaciones()->get();

        return $cotizaciones ? $cotizaciones->where('Estatus', 'Seleccionada')->values() : collect();
    }

    /**
     * Número de propuestas cargadas (agrupadas por NumeroPropuesta).
     */
    private function totalPropuestas(Solicitud $solicitud): int
    {
        $cotizaciones = $solicitud->relationLoaded('cotizaciones')
            ? $solicitud->cotizaciones
            : $solicitud->cotizaciones()->get();

        if (! $cotizaciones || $cotizaciones->isEmpty()) {
            return 0;
        }

        return $cotizaciones->groupBy(fn($c) => (int) ($c->NumeroPropuesta ?? 0))->count();
    }

    private function renderMensaje(array $datos): string
    {
        return view('emails.solicitudes.mensaje', $datos)->render();
    }

    /**
     * Envío por SMTP. Devuelve false si PHPMailer falla (el motivo se registra arriba).
     */
    private function enviar(string $correo, ?string $nombre, string $asunto, string $contenido): bool
    {
        try {
            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);

            $fromAddress = config('email_tickets.smtp.from_address', config('mail.from.address'));
            $nombreSoporte = config('mail.from.name', 'Sistema de Solicitudes');

            $mail->setFrom($fromAddress, $nombreSoporte);
            $mail->addAddress($correo, $nombre ?? '');
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $asunto;
            $mail->Body = $contenido;
            $mail->AltBody = trim(html_entity_decode(strip_tags($contenido), ENT_QUOTES, 'UTF-8'));
            $mail->send();

            return true;
        } catch (Exception $e) {
            Log::error("SolicitudAprobacionEmailService: fallo SMTP hacia {$correo} ({$asunto}): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marcar el token como "ya notificado" para que entre al ciclo de recordatorios diarios.
     */
    private function marcarTokenNotificado(string $token): void
    {
        try {
            SolicitudTokens::where('token', $token)
                ->whereNull('notified_at')
                ->update(['notified_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning("No se pudo marcar notified_at del token {$token}: " . $e->getMessage());
        }
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
