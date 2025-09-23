@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 px-2">
    
    <!-- Error 404 - Página No Encontrada -->
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="text-center">
            
            <!-- Icono y Código de Error -->
            <div class="mb-4">
                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <i class="fas fa-search text-warning" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <h1 class="display-1 fw-bold text-warning mb-0">404</h1>
                <h2 class="h4 text-muted mb-3">Página No Encontrada</h2>
            </div>

            <!-- Mensaje Principal -->
            <div class="card border-0 shadow-sm mb-4" style="max-width: 600px;">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-warning mt-1 fs-5"></i>
                        <div class="text-start">
                            <h6 class="fw-bold text-warning mb-2">
                                <i class="fas fa-map-marked-alt me-2"></i>Página No Encontrada
                            </h6>
                            <p class="mb-2 small">
                                <strong>🔍 Error de Navegación:</strong> La página que buscas no existe o ha sido movida a otra ubicación.
                            </p>
                            <p class="mb-0 small">
                                <strong>💡 Posibles causas:</strong>
                            </p>
                            <ul class="small text-muted mt-2 mb-0">
                                <li>La URL fue escrita incorrectamente</li>
                                <li>La página fue eliminada o movida</li>
                                <li>El enlace que seguiste está desactualizado</li>
                                <li>No tienes permisos para acceder a esta sección</li>
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

                    <!-- Accesos Rápidos para Usuarios Autenticados -->
                    @auth
                    <hr class="my-4">
                    <div>
                        <h6 class="fw-bold mb-3">Accesos Rápidos</h6>
                        <div class="row g-2">
                            @if(auth()->user()->can('ver-inventario'))
                            <div class="col-6 col-md-3">
                                <a href="{{ route('inventarios.index') }}" class="btn btn-outline-success btn-sm w-100">
                                    <i class="fas fa-boxes"></i>
                                    <span class="d-none d-sm-inline ms-1">Inventario</span>
                                </a>
                            </div>
                            @endif

                            @if(auth()->user()->can('ver-empleados'))
                            <div class="col-6 col-md-3">
                                <a href="{{ route('empleados.index') }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-users"></i>
                                    <span class="d-none d-sm-inline ms-1">Empleados</span>
                                </a>
                            </div>
                            @endif

                            @if(auth()->user()->can('ver-reportes'))
                            <div class="col-6 col-md-3">
                                <a href="{{ route('reportes.index') }}" class="btn btn-outline-info btn-sm w-100">
                                    <i class="fas fa-chart-bar"></i>
                                    <span class="d-none d-sm-inline ms-1">Reportes</span>
                                </a>
                            </div>
                            @endif

                            <div class="col-6 col-md-3">
                                <button onclick="location.reload()" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-sync-alt"></i>
                                    <span class="d-none d-sm-inline ms-1">Actualizar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endauth

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-question-circle"></i>
                            ¿La página debería existir? Contacta al administrador del sistema
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
