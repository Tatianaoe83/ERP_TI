<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SimpleWebklexImapService;
use Illuminate\Support\Facades\Log;

class ProcesarRespuestasAutomaticas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:procesar-respuestas-automaticas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa automáticamente las respuestas de correo usando Webklex IMAP';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Iniciando procesamiento automático de respuestas...');
            
            $imapService = new SimpleWebklexImapService();
            
            // Procesar correos y obtener resultado detallado
            $resultado = $imapService->procesarCorreosSimples();
            
            // Obtener estadísticas básicas del buzón
            $estadisticas = $imapService->obtenerInfoBasica();
            
            // Verificar si se procesaron correos
            $procesados = is_array($resultado) ? ($resultado['procesados'] ?? 0) : ($resultado ? 1 : 0);
            $descartados = is_array($resultado) ? ($resultado['descartados'] ?? 0) : 0;
            
            if ($procesados > 0) {
                $mensaje = "Se procesaron {$procesados} correo(s) exitosamente.";
                if ($descartados > 0) {
                    $mensaje .= " Se descartaron {$descartados} correo(s).";
                }
                $this->info($mensaje);
                Log::info("Procesamiento automático completado: {$mensaje}");
            } else {
                $mensaje = 'No se encontraron correos nuevos para procesar';
                if ($descartados > 0) {
                    $mensaje .= " (se descartaron {$descartados} correo(s))";
                }
                $this->info($mensaje);
                Log::info("Procesamiento automático: {$mensaje}");
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $error = 'Error procesando respuestas automáticas: ' . $e->getMessage();
            $this->error($error);
            Log::error($error);
            
            return Command::FAILURE;
        }
    }
}
