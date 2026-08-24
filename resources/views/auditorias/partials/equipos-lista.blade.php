@php
    // Un empleado puede resguardar varios equipos, así que esto siempre es una lista.
    $equipos = collect($equipos ?? []);
    $compacto = $compacto ?? false;
    // El listado tiene columna propia de modalidad, así que ahí se omite el chip
    // para no repetir el mismo dato en la misma fila.
    $mostrarTipo = $mostrarTipo ?? true;
@endphp

@if($equipos->isEmpty())
    <span class="aud-muted">Sin equipo asignado</span>
@else
    <div class="aud-equipos {{ $compacto ? 'aud-equipos--compacto' : '' }}">
        @foreach($equipos as $eq)
            @php
                $modelo = trim(($eq->Marca ?? '') . ' ' . ($eq->Modelo ?? ''));
            @endphp
            <div class="aud-eqficha">
                {{-- Categoría y tipo arriba: dicen qué es antes de entrar al detalle. --}}
                <div class="aud-eqficha__top">
                    <span class="aud-eqficha__cat">{{ $eq->CategoriaEquipo ?: 'Sin categoría' }}</span>
                    @if($mostrarTipo)
                        {!! \App\Helpers\PresupuestoAsignacion::chipHtml($eq->tipoEquipo) !!}
                    @endif
                </div>

                <div class="aud-eqficha__modelo">{{ $modelo ?: '—' }}</div>

                <div class="aud-eqficha__ids">
                    {{-- Serie y folio en tabular: se comparan en vertical sin bailar. --}}
                    <span class="aud-eqficha__id">
                        <span class="aud-eqficha__etq">Serie</span>
                        <span class="aud-num">{{ $eq->NumSerie ?: '—' }}</span>
                    </span>
                    <span class="aud-eqficha__id">
                        <span class="aud-eqficha__etq">Folio</span>
                        <span class="aud-num">{{ $eq->Folio ?: '—' }}</span>
                    </span>
                </div>
            </div>
        @endforeach
    </div>
@endif
