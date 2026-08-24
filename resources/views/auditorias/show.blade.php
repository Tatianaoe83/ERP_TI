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

    @php
        $empleado = $auditoria->empleado;
    @endphp

    {{-- Quién la generó y a quién se auditó. El resto (tipo, gerencia, obra) sale del
         empleado por relación, no de una copia en la cabecera. --}}
    <section class="aud-meta" aria-label="Resumen de la corrida">
        <div class="aud-meta__card">
            <div class="aud-meta__dato">
                <span class="aud-meta__label">La generó</span>
                <span class="aud-meta__valor">{{ $auditoria->generada_por_nombre ?: 'Sin usuario' }}</span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Empleado auditado</span>
                <span class="aud-meta__valor">{{ $empleado?->NombreEmpleado ?: '—' }}</span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Tipo de empleado</span>
                <span class="aud-meta__valor">{{ $empleado?->tipo_persona ?: '—' }}</span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Tipo de equipo</span>
                <span class="aud-meta__valor">
                    @forelse($tipos as $tipo)
                        {!! \App\Helpers\PresupuestoAsignacion::chipHtml($tipo) !!}
                    @empty
                        <span class="aud-meta__tenue">—</span>
                    @endforelse
                </span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Gerencia</span>
                <span class="aud-meta__valor">
                    {{ $empleado?->puestos?->departamentos?->gerencia?->NombreGerencia ?: '—' }}
                </span>
            </div>

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Obra</span>
                <span class="aud-meta__valor">{{ $empleado?->obras?->NombreObra ?: '—' }}</span>
            </div>
        </div>
    </section>

    {{-- ── Licencias congeladas de la corrida ── --}}
    <div id="hoja-general" class="aud-tabla">
        <div class="index-page__card">
            @if($detalle->isEmpty())
                @include('auditorias.partials.vacio', [
                    'icono' => 'fa-key',
                    'titulo' => 'Sin licencias',
                    'texto' => 'Esta corrida no congeló ninguna licencia del empleado.',
                ])
            @else
            <div class="table-responsive">
                <table id="tablaGeneral" class="table index-table w-full">
                    <thead>
                        <tr>
                            <th scope="col">Licencia</th>
                            <th scope="col">¿Tiene licencia?</th>
                            <th scope="col">¿Original?</th>
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
