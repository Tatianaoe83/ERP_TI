@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 px-2">
    
    <!-- Error Genérico -->
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="text-center">
            
            <!-- Icono y Código de Error -->
            <div class="mb-4">
                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <i class="fas fa-exclamation-triangle text-secondary" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <h1 class="display-1 fw-bold text-secondary mb-0">@yield('code', 'Error')</h1>
                <h2 class="h4 text-muted mb-3">@yield('title', 'Ha ocurrido un error')</h2>
            </div>

            <!-- Mensaje Principal -->
            <div class="card border-0 shadow-sm mb-4" style="max-width: 600px;">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-info-circle text-secondary mt-1 fs-5"></i>
                        <div class="text-start">
                            <h6 class="fw-bold text-secondary mb-2">
                                <i class="fas fa-exclamation me-2"></i>@yield('title', 'Error del Sistema')
                            </h6>
                            <div class="small">
                                @yield('message', 'Ha ocurrido un error inesperado. Por favor, intenta nuevamente.')
                            </div>
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
                        
                        <button onclick="location.reload()" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="fas fa-sync-alt"></i>
                            Actualizar
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
