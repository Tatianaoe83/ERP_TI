@extends('layouts.app')

@section('content')
@include('auditorias.partials.styles')

{{-- aud--fijo: la vista se ajusta al alto de #app-main y sólo desplaza la tabla.
     El flash va dentro para que descuente altura en vez de desbordar la página. --}}
<div class="aud aud--fijo">
<div class="aud-flash">@include('flash::message')</div>
<x-index-page
    id="auditoria-detalle"
    title="{{ $auditoria->Folio }}"
    icon="fa-clipboard-check"
    subtitle="Generada el {{ $auditoria->created_at?->format('d/m/Y H:i') ?: '—' }} por {{ $auditoria->generada_por_nombre ?: 'Sin usuario' }}"
    :show-count="false"
    :card="false"
>
    <x-slot name="headerActions">
        <a href="{{ route('auditorias.index') }}" class="aud-btn aud-btn--ghost">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Volver
        </a>
    </x-slot>

    {{-- Las licencias auditadas ya no se resumen aquí: el detalle trae una fila por
         licencia, así que repetirlas arriba sólo duplicaba la misma información. --}}
    <section class="aud-meta" aria-label="Resumen de la corrida">
        <div class="aud-meta__card">
            <div class="aud-meta__dato">
                <span class="aud-meta__label">Auditoría anterior</span>
                <span class="aud-meta__valor">
                    @if($anterior)
                        <a href="{{ route('auditorias.show', $anterior->id) }}" class="aud-meta__link">{{ $anterior->Folio }}</a>
                        <span class="aud-meta__sep" aria-hidden="true">·</span>
                        <span class="aud-num aud-meta__tenue">{{ $anterior->created_at?->format('d/m/Y H:i') ?: '—' }}</span>
                    @else
                        <span class="aud-meta__tenue">Es la primera auditoría</span>
                    @endif
                </span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">La generó</span>
                <span class="aud-meta__valor">{{ $anterior?->generada_por_nombre ?: '—' }}</span>
            </div>

            {{-- El conteo sale del detalle congelado; la cabecera ya no lo duplica. --}}
            <div class="aud-meta__dato">
                <span class="aud-meta__label">Equipos auditados</span>
                <span class="aud-meta__valor aud-num">{{ $detalle->count() }}</span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Empleado auditado</span>
                <span class="aud-meta__valor">
                    {{ $auditoria->empleado?->NombreEmpleado ?: '—' }}
                    @if($auditoria->empleado)
                        <span class="aud-meta__sep" aria-hidden="true">·</span>
                        <span class="aud-meta__tenue">{{ $auditoria->empleado->tipo_persona }}</span>
                    @endif
                </span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Gerencia</span>
                <span class="aud-meta__valor">
                    {{ $auditoria->empleado?->puestos?->departamentos?->gerencia?->NombreGerencia ?: '—' }}
                </span>
            </div>
        </div>
    </section>

    {{-- ── Hoja única: laptops y PC con las licencias del resguardante ── --}}
    <div id="hoja-general" class="aud-tabla">
        <div class="index-page__card">
            @if($general->isEmpty())
                @include('auditorias.partials.vacio', [
                    'icono' => 'fa-laptop',
                    'titulo' => 'Sin laptops ni PC de escritorio',
                    'texto' => 'Esta corrida no encontró equipos de cómputo asignados.',
                ])
            @else
            <div class="table-responsive">
                <table id="tablaGeneral" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th scope="col">Equipo</th>
                            <th scope="col">Marca / Modelo</th>
                            <th scope="col">Num. Serie</th>
                            <th scope="col">Resguardante</th>
                            <th scope="col">Gerencia</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Licencia</th>
                            <th scope="col">Dominio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($general as $fila)
                            @include('auditorias.partials.fila-equipo', ['fila' => $fila])
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</x-index-page>
</div>

@endsection

{{-- jQuery se carga al final del layout, después de #app-main: los scripts de la vista
     van en este stack o DataTables truena con "jQuery is not defined". --}}
@push('third_party_scripts')
    @include('layouts.datatables_js')
    @include('auditorias.partials.scripts')
@endpush
