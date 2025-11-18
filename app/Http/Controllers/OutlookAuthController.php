<?php

namespace App\Http\Controllers;

use App\Services\OutlookEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OutlookAuthController extends Controller
{
    protected $outlookService;

    public function __construct(OutlookEmailService $outlookService)
    {
        $this->outlookService = $outlookService;
    }

    /**
     * Redirigir a Microsoft para autenticación
     */
    public function redirect()
    {
        $authUrl = $this->outlookService->obtenerUrlAutenticacion();
        return redirect($authUrl);
    }

    /**
     * Manejar callback de Microsoft después de autenticación
     */
    public function callback(Request $request)
    {
        try {
            $code = $request->input('code');
            $error = $request->input('error');

            if ($error) {
                Log::error('Error en autenticación de Outlook: ' . $error);
                return redirect()->route('tickets.index')->with('error', 'Error en autenticación de Outlook: ' . $error);
            }

            if (!$code) {
                return redirect()->route('tickets.index')->with('error', 'No se recibió código de autorización');
            }

            // Intercambiar código por token
            $tokenData = $this->intercambiarCodigoPorToken($code);

            if (!$tokenData) {
                return redirect()->route('tickets.index')->with('error', 'Error obteniendo token de acceso');
            }

            // Guardar token en la base de datos
            $success = $this->outlookService->guardarTokenInicial(
                $tokenData['access_token'],
                $tokenData['refresh_token'] ?? null,
                $tokenData['expires_in'] ?? 3600
            );

            if ($success) {
                return redirect()->route('tickets.index')->with('success', 'Autenticación de Outlook configurada exitosamente');
            } else {
                return redirect()->route('tickets.index')->with('error', 'Error guardando credenciales de Outlook');
            }

        } catch (\Exception $e) {
            Log::error('Error en callback de Outlook: ' . $e->getMessage());
            return redirect()->route('tickets.index')->with('error', 'Error procesando autenticación: ' . $e->getMessage());
        }
    }

    /**
     * Intercambiar código de autorización por token de acceso
     */
    private function intercambiarCodigoPorToken($code)
    {
        try {
            $authType = config('services.outlook.auth_type', 'personal');
            $tenantId = ($authType === 'personal') ? 'common' : config('services.outlook.tenant_id');
            $clientId = config('services.outlook.client_id');
            $clientSecret = config('services.outlook.client_secret');
            $redirectUri = config('services.outlook.redirect_uri');

            $response = \Illuminate\Support\Facades\Http::asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error intercambiando código por token', [
                'status' => $response->status(),
                'response' => $response->body(),
                'auth_type' => $authType,
                'tenant_id' => $tenantId
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Excepción intercambiando código por token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar estado de autenticación
     */
    public function status()
    {
        try {
            $token = \App\Models\OutlookToken::getValidToken();
            
            return response()->json([
                'authenticated' => $token !== null,
                'expires_at' => $token ? $token->expires_at : null,
                'auth_url' => $this->outlookService->obtenerUrlAutenticacion()
            ]);

        } catch (\Exception $e) {
            Log::error('Error verificando estado de autenticación: ' . $e->getMessage());
            return response()->json(['error' => 'Error verificando estado'], 500);
        }
    }
}
