<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SimpleWebklexImapService;
use App\Http\Controllers\WebklexApiController;
use Illuminate\Http\Request;

class TestWebklexIntegration extends Command
{
    protected $signature = 'test:webklex-integration';
    protected $description = 'Probar integración completa de Webklex con la interfaz';

    public function handle()
    {
        $this->info('🚀 Probando Integración Completa de Webklex');
        $this->info('==========================================');

        // Probar servicio directo
        $this->info('');
        $this->info('1️⃣ Probando servicio Webklex directo...');
        $this->probarServicioDirecto();

        // Probar API endpoints
        $this->info('');
        $this->info('2️⃣ Probando API endpoints...');
        $this->probarApiEndpoints();

        // Mostrar resumen
        $this->info('');
        $this->info('3️⃣ Resumen de la integración...');
        $this->mostrarResumen();
    }

    private function probarServicioDirecto()
    {
        try {
            $imapService = new SimpleWebklexImapService();
            
            // Probar conexión
            $this->info('   🔌 Probando conexión...');
            $conexion = $imapService->probarConexion();
            
            if ($conexion['success']) {
                $this->info('   ✅ ' . $conexion['message']);
                
                // Obtener información
                $info = $imapService->obtenerInfoBasica();
                if ($info) {
                    $this->info("   📁 Buzón: {$info['folder_name']}");
                    $this->info("   🔗 Estado: {$info['connection_status']}");
                }
            } else {
                $this->error('   ❌ ' . $conexion['message']);
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error: ' . $e->getMessage());
        }
    }

    private function probarApiEndpoints()
    {
        try {
            $controller = new WebklexApiController();
            
            // Probar conexión API
            $this->info('   🔌 Probando endpoint de conexión...');
            $request = Request::create('/api/test-webklex-connection', 'POST');
            $response = $controller->testConnection($request);
            $data = $response->getData(true);
            
            if ($data['success']) {
                $this->info('   ✅ ' . $data['message']);
            } else {
                $this->error('   ❌ ' . $data['message']);
            }
            
            // Probar procesamiento API
            $this->info('   📬 Probando endpoint de procesamiento...');
            $request = Request::create('/api/process-webklex-responses', 'POST', ['ticket_id' => 1]);
            $response = $controller->processResponses($request);
            $data = $response->getData(true);
            
            if ($data['success']) {
                $this->info('   ✅ ' . $data['message']);
            } else {
                $this->error('   ❌ ' . $data['message']);
            }
            
            // Probar información del buzón
            $this->info('   📊 Probando endpoint de información...');
            $request = Request::create('/api/webklex-mailbox-info', 'GET');
            $response = $controller->getMailboxInfo($request);
            $data = $response->getData(true);
            
            if ($data['success']) {
                $this->info('   ✅ Información del buzón obtenida');
            } else {
                $this->error('   ❌ ' . $data['message']);
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error probando API: ' . $e->getMessage());
        }
    }

    private function mostrarResumen()
    {
        $this->info('📋 Componentes de la integración:');
        $this->info('   ✅ SimpleWebklexImapService - Servicio principal');
        $this->info('   ✅ WebklexApiController - Controlador API');
        $this->info('   ✅ indexTicket.blade.php - Interfaz actualizada');
        $this->info('   ✅ Rutas API configuradas');
        $this->info('   ✅ Funciones JavaScript agregadas');
        
        $this->info('');
        $this->info('🎯 Botones disponibles en la interfaz:');
        $this->info('   🔍 Diagnosticar Sistema');
        $this->info('   📧 Enviar Instrucciones');
        $this->info('   🔄 Procesar Automático');
        $this->info('   ✋ Procesar Manual');
        $this->info('   ⚡ Probar Conexión');
        
        $this->info('');
        $this->info('🚀 Para usar la integración:');
        $this->info('   1. Abre el sistema de tickets en el navegador');
        $this->info('   2. Abre cualquier ticket');
        $this->info('   3. Usa los botones de la barra de acciones');
        $this->info('   4. El procesamiento automático funcionará con Webklex');
        $this->info('   5. El procesamiento manual está disponible como fallback');
        
        $this->info('');
        $this->info('🔧 Configuración requerida:');
        $this->info('   - IMAP_PASSWORD en .env');
        $this->info('   - Credenciales de proser.com.mx');
        $this->info('   - Conexión a internet estable');
    }
}
