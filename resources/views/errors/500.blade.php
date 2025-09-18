@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 px-2">
    
    <!-- Error 500 - Error del Servidor -->
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="text-center">
            
            <!-- Icono y Código de Error -->
            <div class="mb-4">
                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <i class="fas fa-server text-danger" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <h1 class="display-1 fw-bold text-danger mb-0">500</h1>
                <h2 class="h4 text-muted mb-3">Error del Servidor</h2>
            </div>

            <!-- Mensaje Principal -->
            <div class="card border-0 shadow-sm mb-4" style="max-width: 600px;">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-danger mt-1 fs-5"></i>
                        <div class="text-start">
                            <h6 class="fw-bold text-danger mb-2">
                                <i class="fas fa-cogs me-2"></i>Error Interno del Servidor
                            </h6>
                            <p class="mb-2 small">
                                <strong>⚠️ Error del Sistema:</strong> Ha ocurrido un problema interno en el servidor que impide procesar tu solicitud.
                            </p>
                            <p class="mb-0 small">
                                <strong>🔧 Acciones recomendadas:</strong>
                            </p>
                            <ul class="small text-muted mt-2 mb-0">
                                <li>Intenta actualizar la página en unos minutos</li>
                                <li>Verifica tu conexión a internet</li>
                                <li>Si el problema persiste, contacta al administrador</li>
                                <li>El equipo técnico ha sido notificado automáticamente</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Técnica (solo en desarrollo) -->
            @if(config('app.debug') && isset($exception))
            <div class="card border-0 shadow-sm mb-4" style="max-width: 600px;">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-bug text-muted"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Información de Debug</h6>
                            <p class="small text-muted mb-0">Solo visible en modo desarrollo</p>
                        </div>
                    </div>
                    
                    <div class="text-start">
                        <strong class="small">Error:</strong>
                        <p class="small text-muted mb-2 font-monospace">{{ $exception->getMessage() ?? 'Error no especificado' }}</p>
                        
                        <strong class="small">Archivo:</strong>
                        <p class="small text-muted mb-0 font-monospace">{{ $exception->getFile() ?? 'No disponible' }}:{{ $exception->getLine() ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Acciones Disponibles -->
            <div class="card border-0 shadow-sm" style="max-width: 600px;">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-tools text-muted"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">¿Qué puedes hacer ahora?</h6>
                            <p class="small text-muted mb-0">Opciones para resolver el problema</p>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <button onclick="location.reload()" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="fas fa-sync-alt"></i>
                            Intentar de Nuevo
                        </button>
                        
                        @auth
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="fas fa-home"></i>
                            Ir al Dashboard
                        </a>
                        @endauth
                        
                        <button onclick="history.back()" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            Página Anterior
                        </button>
                    </div>

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i>
                            Si el problema persiste, intenta nuevamente en unos minutos
                        </small>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .min-vh-100 {
        min-height: 100vh;
    }
    
    .display-1 {
        font-size: 6rem;
        font-weight: 300;
        line-height: 1.2;
    }
    
    @media (max-width: 768px) {
        .display-1 {
            font-size: 4rem;
        }
    }
    
    .font-monospace {
        font-family: 'Courier New', monospace;
    }
</style>
@endpush
