@extends('layouts.app')

@section('content')
@include('auditorias.partials.styles')

<div class="aud aud--fijo">
<div class="aud-flash">@include('flash::message')</div>
<x-index-page
    id="auditoria-detalle"
    title="{{ $auditoria->Folio }}"
    icon="fa-clipboard-check"
    subtitle="Generada el {{ $auditoria->created_at?->format('d/m/Y H:i') ?: '—' }} · por {{ $auditoria->generada_por_nombre ?: 'Sin usuario' }}"
    :show-count="false"
    :card="false"
>
    <x-slot name="headerActions">
        <a href="{{ route('auditorias.index') }}" class="aud-btn aud-btn--ghost">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver al listado
        </a>
    </x-slot>

    @php
        $e = $auditoria->empleado;
    @endphp

    {{-- Datos del resguardante y equipo auditado. --}}
    <section class="aud-meta" aria-label="Datos del empleado y equipo auditado">
        <div class="aud-meta__card">
            <div class="aud-meta__dato">
                <span class="aud-meta__label">Empleado auditado</span>
                <span class="aud-meta__valor aud-meta__valor--destacado">
                    {{ $e?->NombreEmpleado ?: 'Sin asignar' }}
                </span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Tipo de empleado</span>
                <span class="aud-meta__valor">{{ $e?->tipo_persona ?: '—' }}</span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Gerencia</span>
                <span class="aud-meta__valor">
                    {{ $e?->puestos?->departamentos?->gerencia?->NombreGerencia ?: '—' }}
                </span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Obra</span>
                <span class="aud-meta__valor">{{ $e?->obras?->NombreObra ?: '—' }}</span>
            </div>

            <div class="aud-meta__dato aud-meta__dato--equipo">
                <span class="aud-meta__label">
                    Equipo auditado
                    @if($equipos->count() > 1)
                        <span class="aud-meta__conteo aud-num">{{ $equipos->count() }}</span>
                    @endif
                </span>
                <span class="aud-meta__valor">
                    @include('auditorias.partials.equipos-lista', ['equipos' => $equipos])
                </span>
            </div>
        </div>
    </section>

    {{-- Referencia de la comparación: qué corrida es y qué cambió contra ella. --}}
    <div class="aud-diff">
        <div class="aud-diff__ref">
            @if($anterior)
                Comparando contra
                <a href="{{ route('auditorias.show', $anterior->id) }}" class="aud-meta__link">{{ $anterior->Folio }}</a>
                <span class="aud-meta__tenue">({{ $anterior->created_at?->format('d/m/Y') }})</span>
            @else
                <span class="aud-meta__tenue">Primera auditoría de este empleado · sin corrida anterior con la cual comparar</span>
            @endif
        </div>

        {{-- Filtros: al hacer clic ocultan las filas que no correspondan. --}}
        @if($anterior)
        <div class="aud-diff__marcas" role="group" aria-label="Filtrar por tipo de cambio">
            <button type="button" class="aud-marca aud-marca--todas is-activo" data-filtro-marca="todas">
                Todas <span class="aud-num">{{ $detalle->count() }}</span>
            </button>

            <button type="button" class="aud-marca aud-marca--nueva" data-filtro-marca="nueva">
                <i class="fas fa-circle-plus" aria-hidden="true"></i>
                Nuevas <span class="aud-num">{{ $resumen['nueva'] }}</span>
            </button>

            <button type="button" class="aud-marca aud-marca--cambio" data-filtro-marca="cambio">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                Cambiaron <span class="aud-num">{{ $resumen['cambio'] }}</span>
            </button>

            <button type="button" class="aud-marca aud-marca--baja" data-filtro-marca="baja">
                <i class="fas fa-circle-minus" aria-hidden="true"></i>
                Bajas <span class="aud-num">{{ $resumen['baja'] }}</span>
            </button>

            <button type="button" class="aud-marca aud-marca--igual" data-filtro-marca="igual">
                <i class="fas fa-equals" aria-hidden="true"></i>
                Sin cambio <span class="aud-num">{{ $resumen['igual'] }}</span>
            </button>
        </div>
        @endif
    </div>

    {{-- Tabla de licencias congeladas en esta corrida. --}}
    <div class="index-page__card">
        @if($detalle->isEmpty())
            @include('auditorias.partials.vacio', [
                'icono'  => 'fa-key',
                'titulo' => 'Sin licencias que auditar',
                'texto'  => 'Esta corrida no congeló ninguna licencia del empleado.',
            ])
        @else
        <div class="table-responsive">
            <table id="tablaGeneral" class="table index-table w-full">
                <thead>
                    <tr>
                        <th scope="col">Licencia</th>
                        <th scope="col">¿Tiene licencia?</th>
                        <th scope="col">¿Es original?</th>
                        <th scope="col">Observaciones</th>
                        <th scope="col">Auditoría anterior</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalle as $fila)
                        @include('auditorias.partials.fila-equipo', ['fila' => $fila])
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</x-index-page>
</div>

@endsection

@push('third_party_scripts')
    @include('layouts.datatables_js')
    @include('auditorias.partials.scripts')
@endpush
