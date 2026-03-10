@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 px-2">

    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="text-center">

            {{-- Icono --}}
            <div class="mb-4">
                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <i class="fas fa-ban text-secondary" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <h1 class="display-1 fw-bold text-secondary mb-0">
                    <i class="fas fa-ban" style="font-size: 5rem;"></i>
                </h1>
                <h2 class="h4 text-muted mb-3 mt-2">Solicitud Cancelada</h2>
            </div>

            {{-- Mensaje principal --}}
            <div class="card border-0 shadow-sm mb-4" style="max-width: 600px;">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <i class="fas fa-info-circle text-secondary mt-1 fs-5"></i>
                        <div class="text-start">
                            <h6 class="fw-bold text-secondary mb-2">
                                <i class="fas fa-file-excel me-2"></i>Esta solicitud ya no está activa
                            </h6>
                            <p class="mb-2 small">
                                <strong>🚫 Solicitud cancelada:</strong> El enlace que recibiste por correo corresponde a una solicitud que fue cancelada o cerrada por el área de TI.
                            </p>
                            <p class="mb-0 small">
                                <strong>💡 Posibles razones:</strong>
                            </p>
                            <ul class="small text-muted mt-2 mb-0">
                                <li>La solicitud fue cancelada antes de completar el proceso</li>
                                <li>El área de TI cerró la solicitud manualmente</li>
                                <li>Ya no se requiere el equipo solicitado</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detalle de cancelación si viene en la vista --}}
            @if(!empty($motivo))
            <div class="card border-0 shadow-sm mb-4 border-start border-4 border-danger" style="max-width: 600px;">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="fas fa-comment-slash text-danger"></i>
                        </div>
                        <div class="text-start">
                            <h6 class="fw-bold text-danger mb-1">Motivo de cancelación</h6>
                            <p class="small text-muted mb-2">{{ $motivo }}</p>

                            @if(!empty($canceladoPor))
                            <span class="badge bg-secondary bg-opacity-10 text-secondary me-2">
                                <i class="fas fa-user-slash me-1"></i>Cancelado por: {{ $canceladoPor }}
                            </span>
                            @endif

                            @if(!empty($fechaCancelacion))
                            <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                <i class="fas fa-calendar-times me-1"></i>{{ $fechaCancelacion }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Acciones --}}
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
                            ¿Tienes dudas? Contacta al área de TI para más información
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
    .min-vh-100 { min-height: 100vh; }
    .display-1  { font-size: 6rem; font-weight: 300; line-height: 1.2; }
    @media (max-width: 768px) {
        .display-1 { font-size: 4rem; }
    }
</style>
@endpush