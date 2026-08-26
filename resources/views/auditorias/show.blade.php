@extends('layouts.app')

@section('content')
@include('auditorias.partials.styles')

<div class="aud">
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
            <i class="fas fa-list" aria-hidden="true"></i> Volver al listado
        </a>
    </x-slot>

    @php
        $e = $auditoria->empleado;
    @endphp

    {{-- La corrida es la visita al resguardante: la ficha es de la persona, no de
         una máquina. Los equipos son parte de lo auditado, no del encabezado. --}}
    <section class="aud-meta" aria-label="Datos del empleado auditado">
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

            <div class="aud-meta__dato">
                <span class="aud-meta__label">Alcance</span>
                <span class="aud-meta__valor">
                    {{ $equiposDer->count() }} {{ $equiposDer->count() === 1 ? 'equipo' : 'equipos' }}
                    · {{ $licenciasDer->count() }} {{ $licenciasDer->count() === 1 ? 'licencia' : 'licencias' }}
                </span>
            </div>
        </div>
    </section>

    {{-- ── Comparación en dos columnas ──────────────────────────────────────
         Izquierda = una corrida anterior, elegible y de sólo lectura. Derecha =
         ESTA corrida, siempre fija y siempre editable. Una sola vista, nunca se
         navega a otra página para comparar. --}}
    <div class="aud-compare">

        <section class="aud-compare__col aud-compare__col--izq" aria-label="Auditoría de referencia">
            <div class="aud-compare__cabecera">
                <span class="aud-compare__rotulo">
                    <i class="fas fa-clock-rotate-left" aria-hidden="true"></i> Comparando con
                </span>

                @if($anteriores->isNotEmpty())
                    <form method="GET" action="{{ route('auditorias.show', $auditoria->id) }}" id="formComparar">
                        <label class="aud-sr" for="selectComparar">Elegir auditoría anterior para comparar</label>
                        <select id="selectComparar" name="comparar" class="aud-select aud-compare__select"
                                onchange="document.getElementById('formComparar').submit()">
                            @foreach($anteriores as $a)
                                <option value="{{ $a->id }}" {{ $compara && (int) $compara->id === (int) $a->id ? 'selected' : '' }}>
                                    {{ $a->Folio }} · {{ $a->created_at?->format('d/m/Y') }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <span class="aud-meta__tenue">No hay corridas anteriores</span>
                @endif

                @if($compara)
                    <span class="aud-compare__autor">
                        <i class="fas fa-user" aria-hidden="true"></i>
                        Generada por {{ $compara->generada_por_nombre ?: 'Sin usuario' }}
                    </span>
                @endif
            </div>

            @if(!$compara)
                <div class="aud-vacio">
                    <span class="aud-vacio__ico"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i></span>
                    <p class="aud-vacio__titulo">Primera auditoría de este empleado</p>
                    <p>No hay una corrida anterior con la cual comparar.</p>
                </div>
            @else
                <h3 class="aud-compare__seccion">
                    <i class="fas fa-laptop" aria-hidden="true"></i> Equipos
                    <span class="aud-conteo">{{ $equiposIzq->count() }}</span>
                </h3>
                @if($equiposIzq->isEmpty())
                    <p class="aud-muted">Sin equipos en esa corrida.</p>
                @else
                    <div class="aud-cards aud-cards--ref">
                        @foreach($equiposIzq as $fila)
                            @include('auditorias.partials.tarjeta-equipo-ref', ['fila' => $fila])
                        @endforeach
                    </div>
                @endif

                <h3 class="aud-compare__seccion">
                    <i class="fas fa-key" aria-hidden="true"></i> Licencias
                    <span class="aud-conteo">{{ $licenciasIzq->count() }}</span>
                </h3>
                @if($licenciasIzq->isEmpty())
                    <p class="aud-muted">Sin licencias en esa corrida.</p>
                @else
                    <div class="aud-cards aud-cards--ref">
                        @foreach($licenciasIzq as $fila)
                            @include('auditorias.partials.tarjeta-licencia-ref', ['fila' => $fila])
                        @endforeach
                    </div>
                @endif
            @endif
        </section>

        <section class="aud-compare__col aud-compare__col--der" aria-label="Esta auditoría">
            <div class="aud-compare__cabecera">
                <span class="aud-compare__rotulo aud-compare__rotulo--actual">
                    <i class="fas fa-flag" aria-hidden="true"></i> Esta auditoría
                </span>
                <span class="aud-strong">{{ $auditoria->Folio }}</span>
                <span class="aud-meta__tenue">({{ $auditoria->created_at?->format('d/m/Y') }})</span>
                <span class="aud-compare__autor">
                    <i class="fas fa-user" aria-hidden="true"></i>
                    Generada por {{ $auditoria->generada_por_nombre ?: 'Sin usuario' }}
                </span>
            </div>

            <h3 class="aud-compare__seccion">
                <i class="fas fa-laptop" aria-hidden="true"></i> Equipos
                <span class="aud-conteo">{{ $equiposDer->count() }}</span>
            </h3>
            @if($equiposDer->isEmpty())
                @include('auditorias.partials.vacio', [
                    'icono'  => 'fa-laptop',
                    'titulo' => 'Sin equipos que revisar',
                    'texto'  => 'Este empleado no resguardaba ninguna laptop ni PC de escritorio cuando se generó la corrida.',
                ])
            @else
                <div class="aud-cards">
                    @foreach($equiposDer as $fila)
                        @include('auditorias.partials.fila-equipo', ['fila' => $fila, 'comparando' => (bool) $compara])
                    @endforeach
                </div>
            @endif

            <h3 class="aud-compare__seccion">
                <i class="fas fa-key" aria-hidden="true"></i> Licencias
                <span class="aud-conteo">{{ $licenciasDer->count() }}</span>
            </h3>
            @if($licenciasDer->isEmpty())
                @include('auditorias.partials.vacio', [
                    'icono'  => 'fa-key',
                    'titulo' => 'Sin licencias que auditar',
                    'texto'  => 'Esta corrida no congeló ninguna licencia del empleado.',
                ])
            @else
                <div class="aud-cards">
                    @foreach($licenciasDer as $fila)
                        @include('auditorias.partials.fila-licencia', ['fila' => $fila, 'comparando' => (bool) $compara])
                    @endforeach
                </div>
            @endif
        </section>

    </div>

</x-index-page>
</div>

@endsection

@push('third_party_scripts')
    @include('auditorias.partials.scripts')
@endpush
