<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SimpleWebklexImapService;
use Illuminate\Support\Facades\Log;

class TestSimpleWebklexFixed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:simple-webklex-fixed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar la conexión y procesamiento corregido de Webklex IMAP';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔧 Probando Webklex IMAP con correcciones...');
        
        $imapService = new SimpleWebklexImapService();
        
        // Paso 1: Probar conexión
        $this->info('📡 Paso 1: Probando conexión...');
        $conexionResult = $imapService->probarConexion();
        
        if ($conexionResult['success']) {
            $this->info("✅ {$conexionResult['message']}");
        } else {
            $this->error("❌ {$conexionResult['message']}");
            return 1;
        }
        
        // Paso 2: Probar procesamiento
        $this->info('📧 Paso 2: Probando procesamiento de correos...');
        $procesamientoResult = $imapService->probarProcesamiento();
        
        if ($procesamientoResult['success']) {
            $this->info("✅ {$procesamientoResult['message']}");
            if (isset($procesamientoResult['mensajes_encontrados'])) {
                $this->info("📊 Mensajes encontrados: {$procesamientoResult['mensajes_encontrados']}");
            }
        } else {
            $this->error("❌ {$procesamientoResult['message']}");
            return 1;
        }
        
        // Paso 3: Probar procesamiento real
        $this->info('🔄 Paso 3: Ejecutando procesamiento real...');
        $procesados = $imapService->procesarCorreosSimples();
        
        if ($procesados) {
            $this->info('✅ Procesamiento exitoso - Se encontraron correos para procesar');
        } else {
            $this->info('ℹ️ Procesamiento completado - No se encontraron correos nuevos');
        }
        
        $this->info('🎉 Prueba completada exitosamente');
        
        return 0;
    }
}
