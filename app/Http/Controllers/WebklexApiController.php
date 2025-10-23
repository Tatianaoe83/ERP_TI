<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SimpleWebklexImapService;
use Illuminate\Support\Facades\Log;

class WebklexApiController extends Controller
{
    protected $imapService;
    
    public function __construct()
    {
        $this->imapService = new SimpleWebklexImapService();
    }
    
    /**
     * Probar conexión Webklex IMAP
     */
    public function testConnection(Request $request)
    {
        try {
            Log::info('Probando conexión Webklex IMAP desde API');
            
            $resultado = $this->imapService->probarConexion();
            
            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $resultado['message'],
                    'connection_info' => $resultado
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message'],
                    'error' => $resultado['message']
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Error probando conexión Webklex: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error probando conexión: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Procesar respuestas usando Webklex
     */
    public function processResponses(Request $request)
    {
        try {
            $ticketId = $request->input('ticket_id');
            
            Log::info("Procesando respuestas Webklex para ticket: {$ticketId}");
            
            $resultado = $this->imapService->procesarCorreosSimples();
            
            if ($resultado) {
                // Obtener estadísticas básicas
                $estadisticas = $this->imapService->obtenerInfoBasica();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Respuestas procesadas exitosamente con Webklex IMAP',
                    'estadisticas' => $estadisticas,
                    'procesados' => true
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'No se encontraron correos nuevos para procesar',
                    'procesados' => false
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error procesando respuestas Webklex: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error procesando respuestas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener información del buzón
     */
    public function getMailboxInfo(Request $request)
    {
        try {
            $info = $this->imapService->obtenerInfoBasica();
            
            return response()->json([
                'success' => true,
                'mailbox_info' => $info
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo información del buzón: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo información del buzón: ' . $e->getMessage()
            ], 500);
        }
    }
}
