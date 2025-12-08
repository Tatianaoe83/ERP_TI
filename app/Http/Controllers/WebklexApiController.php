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
            
            // Procesar correos y obtener resultado detallado
            $resultado = $this->imapService->procesarCorreosSimples();
            
            // Obtener estadísticas básicas del buzón
            $estadisticas = $this->imapService->obtenerInfoBasica();
            
            // Si hay un ticket_id, obtener estadísticas específicas del ticket después del procesamiento
            $estadisticasTicket = null;
            if ($ticketId) {
                $estadisticasTicket = \App\Models\TicketChat::where('ticket_id', $ticketId)
                    ->where('es_correo', true)
                    ->where('remitente', 'usuario')
                    ->count();
            }
            
            // Verificar si se procesaron correos
            $procesados = is_array($resultado) ? ($resultado['procesados'] ?? 0) : ($resultado ? 1 : 0);
            $descartados = is_array($resultado) ? ($resultado['descartados'] ?? 0) : 0;
            
            if ($procesados > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Se procesaron {$procesados} correo(s) exitosamente. " . ($descartados > 0 ? "Se descartaron {$descartados} correo(s)." : ""),
                    'estadisticas' => $estadisticas,
                    'procesados' => $procesados,
                    'descartados' => $descartados,
                    'correos_usuarios' => $estadisticasTicket
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'No se encontraron correos nuevos para procesar' . ($descartados > 0 ? " (se descartaron {$descartados} correo(s))" : ""),
                    'estadisticas' => $estadisticas,
                    'procesados' => 0,
                    'descartados' => $descartados,
                    'correos_usuarios' => $estadisticasTicket
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
