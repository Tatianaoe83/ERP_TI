<?php

namespace App\Console\Commands;

use App\Services\OutlookEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProcessIncomingEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:process {--limit=50 : Número máximo de correos a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa correos entrantes relacionados con tickets de soporte';

    protected $outlookService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(OutlookEmailService $outlookService)
    {
        parent::__construct();
        $this->outlookService = $outlookService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando procesamiento de correos entrantes...');
        
        $limit = $this->option('limit');
        $processedCount = 0;
        $errorCount = 0;

        try {
            // Obtener token de acceso
            $accessToken = $this->obtenerAccessToken();
            
            if (!$accessToken) {
                $this->error('No se pudo obtener el token de acceso de Outlook');
                return 1;
            }

            // Obtener correos de la bandeja de entrada
            $emails = $this->obtenerCorreos($accessToken, $limit);
            
            if (empty($emails)) {
                $this->info('No hay correos nuevos para procesar');
                return 0;
            }

            $this->info("Procesando " . count($emails) . " correos...");

            foreach ($emails as $email) {
                try {
                    $this->line("Procesando correo: {$email['subject']}");
                    
                    $success = $this->outlookService->procesarCorreoEntrante([
                        'from' => $email['from']['emailAddress']['address'],
                        'subject' => $email['subject'],
                        'body' => $email['body']['content'],
                        'message_id' => $email['internetMessageId'],
                        'thread_id' => $email['conversationId'],
                        'attachments' => $this->obtenerAdjuntos($email, $accessToken)
                    ]);

                    if ($success) {
                        $processedCount++;
                        $this->info("✓ Correo procesado exitosamente");
                        
                        // Marcar como leído en Outlook
                        $this->marcarCorreoComoLeido($email['id'], $accessToken);
                    } else {
                        $errorCount++;
                        $this->warn("⚠ Error procesando correo: {$email['subject']}");
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("✗ Error procesando correo: " . $e->getMessage());
                    Log::error("Error procesando correo individual", [
                        'email_id' => $email['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->info("Procesamiento completado:");
            $this->info("  - Correos procesados exitosamente: {$processedCount}");
            $this->info("  - Correos con errores: {$errorCount}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error general en el procesamiento: " . $e->getMessage());
            Log::error("Error en procesamiento de correos", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Obtener token de acceso de Microsoft Graph
     */
    private function obtenerAccessToken()
    {
        try {
            $tenantId = config('services.outlook.tenant_id');
            $clientId = config('services.outlook.client_id');
            $clientSecret = config('services.outlook.client_secret');

            $response = Http::asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::error('Error obteniendo token de acceso', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Excepción obteniendo token de acceso: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener correos de la bandeja de entrada
     */
    private function obtenerCorreos($accessToken, $limit)
    {
        try {
            // Buscar correos que contengan palabras clave relacionadas con tickets
            $searchQuery = urlencode("subject:ticket OR subject:soporte OR subject:ayuda OR from:" . config('mail.from.address'));
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->get("https://graph.microsoft.com/v1.0/me/messages", [
                '$filter' => "isRead eq false and subject contains 'ticket'",
                '$top' => $limit,
                '$select' => 'id,subject,from,body,internetMessageId,conversationId,receivedDateTime'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['value'] ?? [];
            }

            Log::error('Error obteniendo correos', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('Excepción obteniendo correos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener adjuntos de un correo
     */
    private function obtenerAdjuntos($email, $accessToken)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->get("https://graph.microsoft.com/v1.0/me/messages/{$email['id']}/attachments");

            if ($response->successful()) {
                $data = $response->json();
                return array_map(function($attachment) {
                    return [
                        'name' => $attachment['name'],
                        'content_type' => $attachment['contentType'],
                        'size' => $attachment['size']
                    ];
                }, $data['value'] ?? []);
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Error obteniendo adjuntos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Marcar correo como leído en Outlook
     */
    private function marcarCorreoComoLeido($emailId, $accessToken)
    {
        try {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->patch("https://graph.microsoft.com/v1.0/me/messages/{$emailId}", [
                'isRead' => true
            ]);

        } catch (\Exception $e) {
            Log::error("Error marcando correo como leído: " . $e->getMessage());
        }
    }
}
