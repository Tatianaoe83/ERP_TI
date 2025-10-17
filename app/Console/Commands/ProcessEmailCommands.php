<?php

namespace App\Console\Commands;

use App\Services\ImapEmailReceiver;
use App\Services\SimpleEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessEmailCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:process {--test : Probar configuración sin procesar correos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar correos entrantes y enviar notificaciones';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            if ($this->option('test')) {
                return $this->probarConfiguracion();
            }

            $this->info('Iniciando procesamiento de correos...');
            
            // Procesar correos entrantes
            $receiver = new ImapEmailReceiver();
            $result = $receiver->procesarCorreosEntrantes();
            
            if ($result) {
                $this->info('✅ Correos procesados exitosamente');
                Log::info('Comando de procesamiento de correos ejecutado exitosamente');
                return 0;
            } else {
                $this->error('❌ Error procesando correos');
                Log::error('Error en comando de procesamiento de correos');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('Error ejecutando comando: ' . $e->getMessage());
            Log::error('Excepción en comando de correos: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Probar configuración de correo
     */
    private function probarConfiguracion()
    {
        $this->info('🔍 Probando configuración de correo...');
        
        try {
            // Probar SMTP
            $emailService = new SimpleEmailService();
            $smtpResult = $emailService->verificarConfiguracion();
            
            if ($smtpResult['success']) {
                $this->info('✅ Configuración SMTP: ' . $smtpResult['message']);
            } else {
                $this->error('❌ Error SMTP: ' . $smtpResult['message']);
            }

            // Probar IMAP
            $imapService = new ImapEmailReceiver();
            $this->info('🔍 Probando conexión IMAP...');
            
            // Intentar conectar (sin procesar correos)
            $connection = $this->probarConexionIMAP();
            if ($connection) {
                $this->info('✅ Conexión IMAP exitosa');
                imap_close($connection);
            } else {
                $this->error('❌ Error conectando a IMAP');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Error en prueba de configuración: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Probar conexión IMAP
     */
    private function probarConexionIMAP()
    {
        try {
            $imapHost = config('mail.imap.host', 'imap-mail.outlook.com');
            $imapPort = config('mail.imap.port', 993);
            $imapUsername = config('mail.mailers.smtp.username');
            $imapPassword = config('mail.mailers.smtp.password');
            $imapEncryption = config('mail.imap.encryption', 'ssl');

            // Configurar opciones para ignorar certificados SSL temporalmente
            $options = OP_READONLY | OP_HALFOPEN;
            $server = "{{$imapHost}:{$imapPort}/imap/{$imapEncryption}/notls}INBOX";
            
            $connection = imap_open($server, $imapUsername, $imapPassword, $options);
            
            if (!$connection) {
                $this->error('Error IMAP: ' . imap_last_error());
                return false;
            }

            return $connection;

        } catch (\Exception $e) {
            $this->error('Excepción IMAP: ' . $e->getMessage());
            return false;
        }
    }
}
