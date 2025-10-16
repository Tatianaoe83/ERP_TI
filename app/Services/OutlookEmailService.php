<?php

namespace App\Services;

use App\Models\TicketChat;
use App\Models\Tickets;
use App\Models\Empleados;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class OutlookEmailService
{
    protected $tenantId;
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;

    public function __construct()
    {
        $this->tenantId = config('services.outlook.tenant_id');
        $this->clientId = config('services.outlook.client_id');
        $this->clientSecret = config('services.outlook.client_secret');
        $this->redirectUri = config('services.outlook.redirect_uri');
    }

    /**
     * Enviar correo de respuesta desde el sistema
     */
    public function enviarRespuestaTicket($ticketId, $mensaje, $adjuntos = [])
    {
        try {
            $ticket = Tickets::with(['empleado', 'chat'])->find($ticketId);
            
            if (!$ticket) {
                throw new \Exception('Ticket no encontrado');
            }

            // Obtener información del empleado
            $empleado = $ticket->empleado;
            $correoSoporte = config('mail.from.address');
            $nombreSoporte = config('mail.from.name');

            // Crear el asunto del correo
            $asunto = "Re: Ticket #{$ticketId} - {$ticket->Descripcion}";

            // Preparar el contenido del correo
            $contenidoCorreo = $this->generarContenidoCorreo($ticket, $mensaje);

            // Guardar mensaje en la base de datos
            $chatMessage = TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => $mensaje,
                'remitente' => 'soporte',
                'correo_remitente' => $correoSoporte,
                'nombre_remitente' => $nombreSoporte,
                'contenido_correo' => $contenidoCorreo,
                'es_correo' => true,
                'leido' => false,
                'thread_id' => $this->generarThreadId($ticketId)
            ]);

            // Enviar correo usando Laravel Mail
            Mail::send([], [], function ($message) use ($empleado, $asunto, $contenidoCorreo, $adjuntos) {
                $message->to($empleado->Correo, $empleado->NombreEmpleado)
                        ->subject($asunto)
                        ->setBody($contenidoCorreo, 'text/html');

                // Agregar adjuntos si existen
                foreach ($adjuntos as $adjunto) {
                    if (isset($adjunto['path'])) {
                        $message->attach($adjunto['path'], [
                            'as' => $adjunto['name'] ?? basename($adjunto['path'])
                        ]);
                    }
                }
            });

            Log::info("Correo enviado exitosamente para ticket #{$ticketId}", [
                'ticket_id' => $ticketId,
                'destinatario' => $empleado->Correo,
                'chat_message_id' => $chatMessage->id
            ]);

            return [
                'success' => true,
                'message' => 'Correo enviado exitosamente',
                'chat_message_id' => $chatMessage->id
            ];

        } catch (\Exception $e) {
            Log::error("Error enviando correo para ticket #{$ticketId}: " . $e->getMessage(), [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error enviando correo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar correo entrante y guardarlo en el chat
     */
    public function procesarCorreoEntrante($emailData)
    {
        try {
            // Extraer información del correo
            $from = $emailData['from'] ?? '';
            $subject = $emailData['subject'] ?? '';
            $body = $emailData['body'] ?? '';
            $messageId = $emailData['message_id'] ?? '';
            $threadId = $emailData['thread_id'] ?? '';
            $attachments = $emailData['attachments'] ?? [];

            // Buscar el ticket por el asunto o thread_id
            $ticketId = $this->extraerTicketIdDelAsunto($subject, $threadId);
            
            if (!$ticketId) {
                Log::warning('No se pudo identificar el ticket del correo', [
                    'subject' => $subject,
                    'thread_id' => $threadId,
                    'from' => $from
                ]);
                return false;
            }

            $ticket = Tickets::find($ticketId);
            if (!$ticket) {
                Log::warning("Ticket #{$ticketId} no encontrado para correo entrante");
                return false;
            }

            // Verificar si el correo ya fue procesado
            if (TicketChat::where('message_id', $messageId)->exists()) {
                Log::info("Correo ya procesado: {$messageId}");
                return true;
            }

            // Crear mensaje en el chat
            $chatMessage = TicketChat::create([
                'ticket_id' => $ticketId,
                'mensaje' => $this->extraerTextoDelCorreo($body),
                'remitente' => 'usuario',
                'correo_remitente' => $from,
                'nombre_remitente' => $this->extraerNombreDelCorreo($from),
                'contenido_correo' => $body,
                'message_id' => $messageId,
                'thread_id' => $threadId,
                'adjuntos' => $attachments,
                'es_correo' => true,
                'leido' => false
            ]);

            Log::info("Correo entrante procesado exitosamente", [
                'ticket_id' => $ticketId,
                'message_id' => $messageId,
                'chat_message_id' => $chatMessage->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Error procesando correo entrante: " . $e->getMessage(), [
                'email_data' => $emailData,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generar contenido HTML para el correo
     */
    private function generarContenidoCorreo($ticket, $mensaje)
    {
        $fecha = Carbon::now()->format('d/m/Y H:i:s');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; }
                .content { padding: 20px; }
                .footer { background-color: #f8f9fa; padding: 10px; font-size: 12px; color: #666; }
                .ticket-info { background-color: #e9ecef; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Soporte Técnico - Respuesta a tu Ticket</h2>
            </div>
            
            <div class='content'>
                <div class='ticket-info'>
                    <strong>Ticket #{$ticket->TicketID}</strong><br>
                    <strong>Fecha:</strong> {$fecha}<br>
                    <strong>Estado:</strong> {$ticket->Estatus}
                </div>
                
                <p>Estimado/a {$ticket->empleado->NombreEmpleado},</p>
                
                <p>{$mensaje}</p>
                
                <p>Si necesitas más asistencia, puedes responder a este correo o contactar directamente con el equipo de soporte.</p>
                
                <p>Saludos cordiales,<br>
                <strong>Equipo de Soporte Técnico</strong></p>
            </div>
            
            <div class='footer'>
                <p>Este es un mensaje automático del sistema de tickets. Por favor no modificar el asunto del correo.</p>
                <p>Ticket ID: {$ticket->TicketID} | Thread ID: {$this->generarThreadId($ticket->TicketID)}</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Extraer ID del ticket del asunto del correo
     */
    private function extraerTicketIdDelAsunto($subject, $threadId)
    {
        // Buscar patrón "Ticket #123" o "Re: Ticket #123"
        if (preg_match('/Ticket\s*#(\d+)/i', $subject, $matches)) {
            return $matches[1];
        }

        // Si no se encuentra en el asunto, intentar extraer del thread_id
        if (preg_match('/ticket-(\d+)/', $threadId, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extraer texto limpio del cuerpo del correo HTML
     */
    private function extraerTextoDelCorreo($htmlBody)
    {
        // Remover HTML y obtener solo el texto
        $texto = strip_tags($htmlBody);
        
        // Limpiar espacios en blanco excesivos
        $texto = preg_replace('/\s+/', ' ', $texto);
        
        // Remover líneas de firma automática comunes
        $texto = preg_replace('/Este es un mensaje automático.*$/s', '', $texto);
        $texto = preg_replace('/Por favor no modificar.*$/s', '', $texto);
        
        return trim($texto);
    }

    /**
     * Extraer nombre del correo electrónico
     */
    private function extraerNombreDelCorreo($email)
    {
        // Si el formato es "Nombre <email@domain.com>"
        if (preg_match('/^(.+?)\s*<(.+?)>$/', $email, $matches)) {
            return trim($matches[1], '"\'');
        }
        
        // Si es solo el email, extraer la parte antes del @
        if (strpos($email, '@') !== false) {
            return explode('@', $email)[0];
        }
        
        return $email;
    }

    /**
     * Generar ID único para el hilo de conversación
     */
    private function generarThreadId($ticketId)
    {
        return "ticket-{$ticketId}-" . uniqid();
    }

    /**
     * Obtener configuración de Outlook para autenticación
     */
    public function obtenerUrlAutenticacion()
    {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => 'https://graph.microsoft.com/Mail.ReadWrite https://graph.microsoft.com/Mail.Send',
            'response_mode' => 'query'
        ];

        return 'https://login.microsoftonline.com/' . $this->tenantId . '/oauth2/v2.0/authorize?' . http_build_query($params);
    }
}

