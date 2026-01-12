<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Empleados;
use App\Services\SimpleEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SolicitudAprobacionController extends Controller
{
    protected $emailService;

    public function __construct(SimpleEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Aprobar solicitud por supervisor
     */
    public function aprobarSupervisor(Request $request, $id)
    {
        try {
            $solicitud = Solicitud::with(['empleadoid', 'gerenciaid'])->findOrFail($id);
            $usuario = auth()->user();
            
            $solicitud->AprobacionSupervisor = 'Aprobado';
            $solicitud->FechaAprobacionSupervisor = now();
            $solicitud->SupervisorAprobadorID = $usuario->id;
            $solicitud->ComentarioSupervisor = $request->input('comentario');
            $solicitud->AprobacionGerencia = 'Pendiente';
            $solicitud->Estatus = 'Pendiente Aprobación Gerencia';
            $solicitud->save();

            // Enviar notificación a gerencia
            $this->enviarNotificacionGerencia($solicitud);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada por supervisor. Notificación enviada a gerencia.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error aprobando solicitud por supervisor: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar solicitud por supervisor
     */
    public function rechazarSupervisor(Request $request, $id)
    {
        try {
            $solicitud = Solicitud::findOrFail($id);
            $usuario = auth()->user();
            
            $solicitud->AprobacionSupervisor = 'Rechazado';
            $solicitud->FechaAprobacionSupervisor = now();
            $solicitud->SupervisorAprobadorID = $usuario->id;
            $solicitud->ComentarioSupervisor = $request->input('comentario');
            $solicitud->Estatus = 'Rechazada';
            $solicitud->save();

            // Enviar notificación al empleado
            $this->enviarNotificacionRechazo($solicitud, 'Supervisor');

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada por supervisor.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error rechazando solicitud por supervisor: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar solicitud por gerencia
     */
    public function aprobarGerencia(Request $request, $id)
    {
        try {
            $solicitud = Solicitud::with(['empleadoid', 'gerenciaid'])->findOrFail($id);
            $usuario = auth()->user();
            
            $solicitud->AprobacionGerencia = 'Aprobado';
            $solicitud->FechaAprobacionGerencia = now();
            $solicitud->GerenteAprobadorID = $usuario->id;
            $solicitud->ComentarioGerencia = $request->input('comentario');
            $solicitud->AprobacionAdministracion = 'Pendiente';
            $solicitud->Estatus = 'Pendiente Aprobación Administración';
            $solicitud->save();

            // Enviar notificación a administración
            $this->enviarNotificacionAdministracion($solicitud);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada por gerencia. Notificación enviada a administración.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error aprobando solicitud por gerencia: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar solicitud por gerencia
     */
    public function rechazarGerencia(Request $request, $id)
    {
        try {
            $solicitud = Solicitud::findOrFail($id);
            $usuario = auth()->user();
            
            $solicitud->AprobacionGerencia = 'Rechazado';
            $solicitud->FechaAprobacionGerencia = now();
            $solicitud->GerenteAprobadorID = $usuario->id;
            $solicitud->ComentarioGerencia = $request->input('comentario');
            $solicitud->Estatus = 'Rechazada';
            $solicitud->save();

            // Enviar notificación al empleado
            $this->enviarNotificacionRechazo($solicitud, 'Gerencia');

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada por gerencia.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error rechazando solicitud por gerencia: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar solicitud por administración
     */
    public function aprobarAdministracion(Request $request, $id)
    {
        try {
            $solicitud = Solicitud::with(['empleadoid'])->findOrFail($id);
            $usuario = auth()->user();
            
            $solicitud->AprobacionAdministracion = 'Aprobado';
            $solicitud->FechaAprobacionAdministracion = now();
            $solicitud->AdministradorAprobadorID = $usuario->id;
            $solicitud->ComentarioAdministracion = $request->input('comentario');
            $solicitud->Estatus = 'Pendiente Cotización TI';
            $solicitud->save();

            // Enviar notificación a TI
            $this->enviarNotificacionTI($solicitud);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud aprobada por administración. Notificación enviada a TI para cotización.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error aprobando solicitud por administración: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar solicitud por administración
     */
    public function rechazarAdministracion(Request $request, $id)
    {
        try {
            $solicitud = Solicitud::findOrFail($id);
            $usuario = auth()->user();
            
            $solicitud->AprobacionAdministracion = 'Rechazado';
            $solicitud->FechaAprobacionAdministracion = now();
            $solicitud->AdministradorAprobadorID = $usuario->id;
            $solicitud->ComentarioAdministracion = $request->input('comentario');
            $solicitud->Estatus = 'Rechazada';
            $solicitud->save();

            // Enviar notificación al empleado
            $this->enviarNotificacionRechazo($solicitud, 'Administración');

            return response()->json([
                'success' => true,
                'message' => 'Solicitud rechazada por administración.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error rechazando solicitud por administración: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar notificación a gerencia
     */
    private function enviarNotificacionGerencia($solicitud)
    {
        // Obtener gerentes de la gerencia de la solicitud
        $gerentes = Empleados::where('GerenciaID', $solicitud->GerenciaID)
            ->where('tipo_persona', 'FISICA')
            ->get();

        foreach ($gerentes as $gerente) {
            if ($gerente->Correo) {
                $this->enviarEmailAprobacion($solicitud, $gerente->Correo, 'Gerencia');
            }
        }
    }

    /**
     * Enviar notificación a administración
     */
    private function enviarNotificacionAdministracion($solicitud)
    {
        // Obtener usuarios de administración (puedes ajustar este criterio)
        $administradores = Empleados::where('ObraID', 46) // Ajustar según tu lógica
            ->where('tipo_persona', 'FISICA')
            ->get();

        foreach ($administradores as $admin) {
            if ($admin->Correo) {
                $this->enviarEmailAprobacion($solicitud, $admin->Correo, 'Administración');
            }
        }
    }

    /**
     * Enviar notificación a TI
     */
    private function enviarNotificacionTI($solicitud)
    {
        // Obtener usuarios de TI
        $tiUsers = Empleados::where('ObraID', 46) // Ajustar según tu lógica
            ->where('tipo_persona', 'FISICA')
            ->get();

        foreach ($tiUsers as $ti) {
            if ($ti->Correo) {
                $this->enviarEmailAprobacion($solicitud, $ti->Correo, 'TI');
            }
        }
    }

    /**
     * Enviar email de aprobación
     */
    private function enviarEmailAprobacion($solicitud, $correo, $nivel)
    {
        try {
            $asunto = "Solicitud #{$solicitud->SolicitudID} - Requiere aprobación de {$nivel}";
            $contenido = $this->construirContenidoEmailAprobacion($solicitud, $nivel);

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $this->configurarMailer($mail);
            
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($correo);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $contenido;
            
            $mail->send();
            
            Log::info("Notificación de aprobación enviada a {$correo} para solicitud #{$solicitud->SolicitudID}");
        } catch (\Exception $e) {
            Log::error("Error enviando notificación de aprobación: " . $e->getMessage());
        }
    }

    /**
     * Enviar notificación de rechazo
     */
    private function enviarNotificacionRechazo($solicitud, $nivel)
    {
        $empleado = $solicitud->empleadoid;
        if ($empleado && $empleado->Correo) {
            try {
                $asunto = "Solicitud #{$solicitud->SolicitudID} - Rechazada por {$nivel}";
                $contenido = $this->construirContenidoEmailRechazo($solicitud, $nivel);

                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $this->configurarMailer($mail);
                
                $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
                $mail->addAddress($empleado->Correo);
                $mail->isHTML(true);
                $mail->Subject = $asunto;
                $mail->Body = $contenido;
                
                $mail->send();
                
                Log::info("Notificación de rechazo enviada a {$empleado->Correo} para solicitud #{$solicitud->SolicitudID}");
            } catch (\Exception $e) {
                Log::error("Error enviando notificación de rechazo: " . $e->getMessage());
            }
        }
    }

    /**
     * Construir contenido de email de aprobación
     */
    private function construirContenidoEmailAprobacion($solicitud, $nivel)
    {
        $empleado = $solicitud->empleadoid;
        $url = url("/solicitudes/{$solicitud->SolicitudID}/aprobar");
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Solicitud Requiere Aprobación de {$nivel}</h2>
            <p><strong>Solicitud #{$solicitud->SolicitudID}</strong></p>
            <p><strong>Empleado:</strong> {$empleado->NombreEmpleado}</p>
            <p><strong>Motivo:</strong> {$solicitud->Motivo}</p>
            <p><strong>Descripción:</strong> {$solicitud->DescripcionMotivo}</p>
            <p><a href='{$url}'>Ver y Aprobar Solicitud</a></p>
        </body>
        </html>
        ";
    }

    /**
     * Construir contenido de email de rechazo
     */
    private function construirContenidoEmailRechazo($solicitud, $nivel)
    {
        $comentario = match($nivel) {
            'Supervisor' => $solicitud->ComentarioSupervisor,
            'Gerencia' => $solicitud->ComentarioGerencia,
            'Administración' => $solicitud->ComentarioAdministracion,
            default => ''
        };
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Solicitud Rechazada</h2>
            <p>Su solicitud #{$solicitud->SolicitudID} ha sido rechazada por {$nivel}.</p>
            " . ($comentario ? "<p><strong>Comentario:</strong> {$comentario}</p>" : "") . "
        </body>
        </html>
        ";
    }

    /**
     * Configurar PHPMailer
     */
    private function configurarMailer($mail)
    {
        $mail->isSMTP();
        $mail->Host = config('mail.mailers.smtp.host');
        $mail->SMTPAuth = true;
        $mail->Username = config('mail.mailers.smtp.username');
        $mail->Password = config('mail.mailers.smtp.password');
        $mail->SMTPSecure = config('mail.mailers.smtp.encryption');
        $mail->Port = config('mail.mailers.smtp.port');
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 30;
    }
}
