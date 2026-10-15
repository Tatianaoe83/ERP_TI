@extends('layouts.app')

@push('styles')
<style>
    .act-stack {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    .act-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.85rem;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(16, 29, 73, 0.04);
    }
    .act-panel--baja { border-top: 3px solid #d97706; }
    .act-panel--alta { border-top: 3px solid #059669; }
    .act-panel__head {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1rem 1.1rem 0.85rem;
    }
    .act-panel__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.65rem;
        flex-shrink: 0;
    }
    .act-panel--baja .act-panel__icon { background: #fef3c7; color: #b45309; }
    .act-panel--alta .act-panel__icon { background: #d1fae5; color: #047857; }
    .act-panel__head h2 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
    }
    .act-panel__head p {
        margin: 0.2rem 0 0;
        font-size: 0.82rem;
        color: #6b7280;
    }
    .act-panel .index-table thead th {
        background: #f8fafc !important;
    }
    .act-panel--baja .index-table thead th { background: #fffbeb !important; }
    .act-panel--alta .index-table thead th { background: #ecfdf5 !important; }
    .act-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .act-pill--baja { background: #fef3c7; color: #92400e; }
    .act-pill--alta { background: #d1fae5; color: #065f46; }
    .act-pill--cambio { background: #dbeafe; color: #1e40af; }
    .act-pill--ok { background: #ecfdf3; color: #166534; }
    .act-pill--aviso { background: #f3f4f6; color: #374151; }

    html.dark .act-panel,
    .dark .act-panel {
        background: #1c1f26;
        border-color: #2a2f3a;
        box-shadow: none;
    }
    html.dark .act-panel--baja,
    .dark .act-panel--baja { border-top-color: #f59e0b; }
    html.dark .act-panel--alta,
    .dark .act-panel--alta { border-top-color: #34d399; }
    html.dark .act-panel--baja .act-panel__icon,
    .dark .act-panel--baja .act-panel__icon { background: rgba(245, 158, 11, 0.16); color: #fbbf24; }
    html.dark .act-panel--alta .act-panel__icon,
    .dark .act-panel--alta .act-panel__icon { background: rgba(16, 185, 129, 0.16); color: #6ee7b7; }
    html.dark .act-panel__head h2,
    .dark .act-panel__head h2 { color: #f9fafb; }
    html.dark .act-panel__head p,
    .dark .act-panel__head p { color: #9ca3af; }
    html.dark .act-panel--baja .index-table thead th,
    .dark .act-panel--baja .index-table thead th {
        background: rgba(245, 158, 11, 0.08) !important;
        color: #fcd34d !important;
    }
    html.dark .act-panel--alta .index-table thead th,
    .dark .act-panel--alta .index-table thead th {
        background: rgba(16, 185, 129, 0.08) !important;
        color: #6ee7b7 !important;
    }
    html.dark .act-pill--baja,
    .dark .act-pill--baja { background: rgba(245, 158, 11, 0.18); color: #fcd34d; }
    html.dark .act-pill--alta,
    .dark .act-pill--alta { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; }
    html.dark .act-pill--cambio,
    .dark .act-pill--cambio { background: rgba(96, 165, 250, 0.18); color: #bfdbfe; }
    html.dark .act-pill--ok,
    .dark .act-pill--ok { background: rgba(34, 197, 94, 0.18); color: #86efac; }
    html.dark .act-pill--aviso,
    .dark .act-pill--aviso { background: rgba(156, 163, 175, 0.16); color: #e5e7eb; }
</style>
@endpush

@section('content')
@include('flash::message')

<x-index-page
    title="Actualizaciones"
    icon="fa-sync-alt"
    subtitle="TI tiene a la gente de Capital Humano. Inventario asigna las altas y traspasa las bajas."
    :show-count="false"
    :card="false"
>
    <x-slot name="headerActions">
        @if(auth()->user()->can('editar-empleados'))
            <form method="POST" action="{{ route('actualizaciones.traer') }}">
                @csrf
                <button type="submit" class="index-page__btn-primary">Traer cambios de Capital Humano</button>
            </form>
        @endif
    </x-slot>

    <div class="act-stack">
        <section class="act-panel act-panel--baja">
            <div class="act-panel__head">
                <span class="act-panel__icon" aria-hidden="true"><i class="fas fa-exchange-alt"></i></span>
                <div>
                    <h2>Se dio de baja. Se debe traspasar</h2>
                    <p>Siguen en TI hasta que el inventario se traspase o se reasigne. Después se registra la baja.</p>
                </div>
            </div>
            <div class="index-page__table-wrap table-responsive">
                <table class="table index-table w-full">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Fecha</th>
                            <th>Motivo</th>
                            <th>Equipos</th>
                            <th>Insumos</th>
                            <th>Líneas</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bajas as $baja)
                            @php
                                $equipos = optional($baja->empleado)->inventarioequipo_count ?? 0;
                                $insumos = optional($baja->empleado)->inventarioinsumo_count ?? 0;
                                $lineas = optional($baja->empleado)->inventariolineas_count ?? 0;
                            @endphp
                            <tr>
                                <td>{{ $baja->nombre }}</td>
                                <td>{{ optional($baja->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $baja->resumen }}</td>
                                <td>{{ $equipos }}</td>
                                <td>{{ $insumos }}</td>
                                <td>{{ $lineas }}</td>
                                <td>
                                    @if($baja->empleado)
                                        <a href="{{ route('inventarios.inventario', $baja->empleado_ti_id) }}" class="index-page__btn-secondary">Ir al inventario</a>
                                        @if(auth()->user()->can('editar-empleados') && ($equipos + $insumos + $lineas) === 0)
                                            <form method="POST" action="{{ route('actualizaciones.baja', $baja->id) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="index-page__btn-primary">Registrar baja</button>
                                            </form>
                                        @endif
                                    @else
                                        No está en TI
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No hay bajas por traspasar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="act-panel act-panel--alta">
            <div class="act-panel__head">
                <span class="act-panel__icon" aria-hidden="true"><i class="fas fa-user-plus"></i></span>
                <div>
                    <h2>Se agregó</h2>
                    <p>Ya están en TI y todavía no tienen inventario. Aquí se hace la asignación.</p>
                </div>
            </div>
            <div class="index-page__table-wrap table-responsive">
                <table class="table index-table w-full">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Fecha</th>
                            <th>Detalle</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($altas as $alta)
                            <tr>
                                <td>{{ $alta->nombre }}</td>
                                <td>{{ optional($alta->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $alta->resumen }}</td>
                                <td>
                                    @if($alta->empleado_ti_id)
                                        <a href="{{ route('inventarios.inventario', $alta->empleado_ti_id) }}" class="index-page__btn-secondary">Asignar inventario</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">No hay personas nuevas por asignar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-index-page>
@endsection
