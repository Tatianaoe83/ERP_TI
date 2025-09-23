@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 px-2">
    
    <!-- Error 403 - Acceso Denegado -->
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="text-center">
            
            <!-- Icono y Código de Error -->
            <div class="mb-4">
                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <i class="fas fa-lock text-danger" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <h1 class="display-1 fw-bold text-danger mb-0">403</h1>
                <h2 class="h4 text-muted mb-3">Acceso Denegado</h2>
            </div>

            <!-- Mensaje Principal -->
            <div class="card border-0 shadow-sm mb-4" style="max-width: 600px;">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-warning mt-1 fs-5"></i>
                        <div class="text-start">
                            <h6 class="fw-bold text-warning mb-2">
                                <i class="fas fa-user-shield me-2"></i>Permisos Insuficientes
                            </h6>
                            <p class="mb-2 small">
                                <strong>🚫 Error de Autorización:</strong> No tienes los permisos necesarios para acceder a esta página o realizar esta acción.
                            </p>
                            <p class="mb-0 small">
                                <strong>💡 Soluciones posibles:</strong>
                            </p>
                            <ul class="small text-muted mt-2 mb-0">
                                <li>Contacta al administrador del sistema para solicitar los permisos necesarios</li>
                                <li>Verifica que estés usando la cuenta correcta</li>
                                <li>Regresa al dashboard para acceder a las funciones disponibles</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Acciones Disponibles -->
            <div class="card border-0 shadow-sm" style="max-width: 600px;">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-compass text-muted"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">¿Qué puedes hacer ahora?</h6>
                            <p class="small text-muted mb-0">Opciones disponibles para continuar</p>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        @auth
                        <a href="{{ route('home') }}" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="fas fa-home"></i>
                            Ir al Dashboard
                        </a>
                        @endauth
                        
                        <button onclick="history.back()" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="fas fa-arrow-left"></i>
                            Página Anterior
                        </button>
                        
                        @guest
                        <a href="{{ route('login') }}" class="btn btn-outline-primary d-flex align-items-center gap-2">
                            <i class="fas fa-sign-in-alt"></i>
                            Iniciar Sesión
                        </a>
                        @endguest
                    </div>

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-question-circle"></i>
                            ¿Necesitas ayuda? Contacta al administrador del sistema
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
</style>
@endpush
