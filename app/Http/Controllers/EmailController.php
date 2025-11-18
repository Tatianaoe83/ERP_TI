<?php

namespace App\Http\Controllers;

use App\Services\SimpleEmailService;
use App\Services\ImapEmailReceiver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    protected $emailService;
    protected $imapService;

    public function __construct()
    {
        $this->emailService = new SimpleEmailService();
        $this->imapService = new ImapEmailReceiver();
    }

    /**
     * Verificar configuración de correo
     */
    public function verificarConfiguracion()
    {
        try {
            $smtpResult = $this->emailService->verificarConfiguracion();
            
            return response()->json([
                'success' => true,
                'smtp' => $smtpResult,
                'message' => 'Configuración verificada'
            ]);

        } catch (\Exception $e) {
            Log::error('Error verificando configuración de correo: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error verificando configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar correos entrantes manualmente
     */
    public function procesarCorreos()
    {
        try {
            $result = $this->imapService->procesarCorreosEntrantes();
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Correos procesados exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error procesando correos'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error procesando correos manualmente: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando correos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar correo de prueba
     */
    public function enviarCorreoPrueba(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'asunto' => 'required|string|max:255',
                'mensaje' => 'required|string'
            ]);

            $email = $request->input('email');
            $asunto = $request->input('asunto');
            $mensaje = $request->input('mensaje');

            // Crear un PHPMailer temporal para la prueba
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = config('mail.mailers.smtp.host');
            $mail->SMTPAuth = true;
            $mail->Username = config('mail.mailers.smtp.username');
            $mail->Password = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption');
            $mail->Port = config('mail.mailers.smtp.port');
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = "
                <html>
                <body>
                    <h2>Correo de Prueba - Sistema ERP TI</h2>
                    <p><strong>Mensaje:</strong></p>
                    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>
                        " . nl2br(htmlspecialchars($mensaje)) . "
                    </div>
                    <p><em>Este es un correo de prueba enviado desde el sistema ERP TI.</em></p>
                </body>
                </html>
            ";

            $mail->send();

            Log::info("Correo de prueba enviado a: {$email}");

            return response()->json([
                'success' => true,
                'message' => 'Correo de prueba enviado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando correo de prueba: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error enviando correo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de correo
     */
    public function obtenerEstadisticas()
    {
        try {
            // Aquí podrías agregar lógica para obtener estadísticas
            // como cantidad de correos enviados, recibidos, etc.
            
            return response()->json([
                'success' => true,
                'data' => [
                    'smtp_configured' => !empty(config('mail.mailers.smtp.host')),
                    'imap_configured' => !empty(config('mail.imap.host')),
                    'last_check' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de correo: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estadísticas'
            ], 500);
        }
    }
}
