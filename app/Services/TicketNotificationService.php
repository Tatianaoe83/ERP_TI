<?php

namespace App\Services;

use App\Models\Tickets;
use App\Models\Tipoticket;
use App\Models\Empleados;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Str;

class TicketNotificationService
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
     * Verificar si un ticket excede el tiempo de respuesta y enviar notificación
     */
    public function verificarYNotificarExceso($ticket)
    {
        try {
            // Solo verificar tickets en progreso
            if ($ticket->Estatus !== 'En progreso' || !$ticket->FechaInicioProgreso) {
                return false;
            }

            // Obtener el tipo de ticket y su métrica
            $tipoticket = $ticket->tipoticket;
            if (!$tipoticket || !$tipoticket->TiempoEstimadoMinutos) {
                // Si no hay métrica configurada, no enviar notificación
                return false;
            }

            // Convertir minutos a horas
            $tiempoEstimadoHoras = $tipoticket->TiempoEstimadoMinutos / 60;
            
            // Calcular tiempo de respuesta actual (en horas laborales)
            $tiempoRespuestaActual = $ticket->tiempo_respuesta;
            
            if ($tiempoRespuestaActual === null) {
                return false;
            }

            // Verificar si excede el tiempo estimado
            if ($tiempoRespuestaActual > $tiempoEstimadoHoras) {
                // Usar el tiempo estimado de la métrica como intervalo de verificación
                $intervaloMinutos = $tipoticket->TiempoEstimadoMinutos;
                
                // Verificar si ya pasó el intervalo desde la última notificación
                if ($ticket->fecha_ultima_notificacion_exceso) {
                    $fechaUltimaNotificacion = \Carbon\Carbon::parse($ticket->fecha_ultima_notificacion_exceso);
                    $fechaProximaNotificacion = $fechaUltimaNotificacion->copy()->addMinutes($intervaloMinutos);
                    
                    // Si aún no es momento de la próxima notificación, no enviar
                    if (now()->lt($fechaProximaNotificacion)) {
                        return false;
                    }
                }
                
                // Enviar correo de notificación
                $enviado = $this->enviarNotificacionExceso($ticket, $tiempoEstimadoHoras, $tiempoRespuestaActual);
                
                // Si se envió correctamente, actualizar la fecha de última notificación con la hora actual
                // Esto programará la próxima notificación para dentro del intervalo configurado
                if ($enviado) {
                    $ticket->fecha_ultima_notificacion_exceso = now();
                    $ticket->save();
                }
                
                return $enviado;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Error verificando exceso de tiempo para ticket #{$ticket->TicketID}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar correo de notificación cuando se excede el tiempo de respuesta
     */
    private function enviarNotificacionExceso($ticket, $tiempoEstimadoHoras, $tiempoRespuestaActual)
    {
        try {
            // Obtener información del responsable
            $responsable = $ticket->responsableTI;
            $nombreResponsable = $responsable ? $responsable->NombreEmpleado : 'No asignado';
            
            // Calcular tiempo hasta cierre (tiempo transcurrido desde que inició)
            // Si está en progreso, es el mismo que el tiempo de respuesta
            // Si está cerrado, es el tiempo de resolución
            $tiempoHastaCierre = null;
            if ($ticket->Estatus === 'En progreso' && $ticket->FechaInicioProgreso) {
                // El tiempo hasta cierre es el tiempo que lleva desde que inició (tiempo de respuesta)
                $tiempoHastaCierre = $tiempoRespuestaActual;
            } elseif ($ticket->Estatus === 'Cerrado' && $ticket->FechaFinProgreso) {
                // Si ya está cerrado, mostrar el tiempo de resolución
                $tiempoHastaCierre = $ticket->tiempo_resolucion;
            }

            // Formatear tiempos
            $tiempoEstimadoFormateado = $this->formatearHoras($tiempoEstimadoHoras);
            $tiempoRespuestaFormateado = $this->formatearHoras($tiempoRespuestaActual);
            $tiempoHastaCierreFormateado = $tiempoHastaCierre ? $this->formatearHoras($tiempoHastaCierre) : 'N/A';
            
            // Hora actual
            $horaActual = now()->format('d/m/Y H:i:s');

            // Construir contenido del correo
            $contenido = $this->construirContenidoNotificacion(
                $ticket,
                $nombreResponsable,
                $tiempoEstimadoFormateado,
                $tiempoRespuestaFormateado,
                $tiempoHastaCierreFormateado,
                $horaActual
            );

            // Enviar correo
            $mail = new PHPMailer(true);
            $this->configurarMailer($mail);
            
            $fromAddress = config('email_tickets.smtp.from_address', config('mail.from.address'));
            $nombreSoporte = config('mail.from.name', 'Sistema de Tickets');
            
            $mail->setFrom($fromAddress, $nombreSoporte);
            $mail->addAddress('tordonez@proser.com.mx', 'Torres Ordóñez');
            
            $mail->isHTML(true);
            $mail->Subject = "⚠️ ALERTA: Ticket #{$ticket->TicketID} excede tiempo de respuesta";
            $mail->Body = $contenido;
            
            $mail->send();

            Log::info("Notificación de exceso de tiempo enviada para ticket #{$ticket->TicketID}");
            return true;
            
        } catch (Exception $e) {
            Log::error("Error enviando notificación de exceso para ticket #{$ticket->TicketID}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Construir contenido HTML del correo de notificación
     */
    private function construirContenidoNotificacion($ticket, $nombreResponsable, $tiempoEstimado, $tiempoRespuesta, $tiempoHastaCierre, $horaActual)
    {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
                .header { background-color: #dc3545; color: white; padding: 20px; border-radius: 8px; }
                .content { margin: 20px 0; }
                .alert { background-color: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin: 20px 0; border-radius: 4px; }
                .info-box { background-color: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .ticket-info { background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; }
                td { padding: 8px; border-bottom: 1px solid #dee2e6; }
                .highlight { background-color: #ffebee; font-weight: bold; }
            </style>
        </head>
        <body>
           
            <div class='content'>
                <div class='alert'>
                    <h3>🚨 Atención Requerida</h3>
                    <p>El siguiente ticket está <strong>excediendo el tiempo de respuesta</strong> establecido en la métrica de la categoría y requiere atención inmediata para concluir.</p>
                </div>
                
                <div class='ticket-info'>
                    <h3>📋 Información del Ticket</h3>
                    <table>
                        <tr>
                            <td><strong>Número de Ticket:</strong></td>
                            <td class='highlight'>#{$ticket->TicketID}</td>
                        </tr>
                        <tr>
                            <td><strong>Descripción:</strong></td>
                            <td>".Str::limit($ticket->Descripcion, 50)." ...</td>
                        </tr>
                        <tr>
                            <td><strong>Responsable:</strong></td>
                            <td class='highlight'>{$nombreResponsable}</td>
                        </tr>
                        <tr>
                            <td><strong>Prioridad:</strong></td>
                            <td>{$ticket->Prioridad}</td>
                        </tr>
                        <tr>
                            <td><strong>Estado:</strong></td>
                            <td>{$ticket->Estatus}</td>
                        </tr>
                        <tr>
                            <td><strong>Hora Actual:</strong></td>
                            <td>{$horaActual}</td>
                        </tr>
                    </table>
                </div>
                
                <div class='info-box'>
                    <h3>⏱️ Información de Tiempos</h3>
                    <table>
                        <tr>
                            <td><strong>Tiempo Estimado (Métrica de Categoría):</strong></td>
                            <td>{$tiempoEstimado}</td>
                        </tr>
                        <tr>
                            <td><strong>Tiempo de Respuesta Actual:</strong></td>
                            <td class='highlight'>{$tiempoRespuesta}</td>
                        </tr>
                    </table>
                </div>
                
                <div class='alert'>
                    <h3>📌 Acción Requerida</h3>
                    <p>Este ticket necesita ser atendido con <strong>urgencia</strong> para concluir y evitar mayores retrasos.</p>
                    <p>Por favor, contacte al responsable asignado y asegúrese de que el ticket sea resuelto lo antes posible.</p>
                </div>
            </div>
            
            <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d;'>
                <p><strong>Sistema de Tickets ERP TI - Proser</strong></p>
                <p>Este es un correo automático generado por el sistema de monitoreo de tickets.</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Formatear horas a formato legible
     */
    private function formatearHoras($horas)
    {
        if ($horas == 0) {
            return '0 horas';
        }

        $horasEnteras = floor($horas);
        $minutos = round(($horas - $horasEnteras) * 60);
        
        if ($minutos >= 60) {
            $horasEnteras += 1;
            $minutos = 0;
        }

        $partes = [];
        
        if ($horasEnteras >= 8) {
            $dias = floor($horasEnteras / 8);
            $horasRestantes = $horasEnteras % 8;
            
            if ($dias > 0) {
                $partes[] = $dias . ' día' . ($dias > 1 ? 's' : '');
            }
            
            if ($horasRestantes > 0) {
                $partes[] = $horasRestantes . ' hora' . ($horasRestantes > 1 ? 's' : '');
            }
        } else {
            if ($horasEnteras > 0) {
                $partes[] = $horasEnteras . ' hora' . ($horasEnteras > 1 ? 's' : '');
            }
        }
        
        if ($minutos > 0 && count($partes) < 2) {
            $partes[] = $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
        }

        return implode(', ', $partes) ?: '0 horas';
    }

    /**
     * Configurar PHPMailer
     */
    private function configurarMailer($mail)
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

