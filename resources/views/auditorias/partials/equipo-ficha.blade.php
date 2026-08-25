@php
    // Un equipo por corrida: el que se revisó en esa visita. Los datos vienen del
    // inventario en vivo, así que siempre reflejan la ficha actual del equipo.
    $equipo = $equipo ?? null;
    $compacto = $compacto ?? false;
    // El listado tiene columna propia de modalidad, así que ahí se omite el chip
    // para no repetir el mismo dato en la misma fila.
    $mostrarTipo = $mostrarTipo ?? true;
@endphp

@if(! $equipo)
    <span class="aud-muted">Sin equipo registrado</span>
@else
    <div class="aud-equipos {{ $compacto ? 'aud-equipos--compacto' : '' }}">
        <div class="aud-eqficha">
            {{-- Categoría y modalidad arriba: dicen qué es antes de entrar al detalle. --}}
            <div class="aud-eqficha__top">
                <span class="aud-eqficha__cat">{{ $equipo->CategoriaEquipo ?: 'Sin categoría' }}</span>
                @if($mostrarTipo)
                    {!! \App\Helpers\PresupuestoAsignacion::chipHtml($equipo->tipoEquipo) !!}
                @endif
            </div>

            <div class="aud-eqficha__modelo">{{ $equipo->Marca ?: '—' }}</div>

            <div class="aud-eqficha__ids">
                {{-- Modelo, serie y folio en tabular: se comparan en vertical sin bailar. --}}
                <span class="aud-eqficha__id">
                    <span class="aud-eqficha__etq">Modelo</span>
                    <span>{{ $equipo->Modelo ?: '—' }}</span>
                </span>
                <span class="aud-eqficha__id">
                    <span class="aud-eqficha__etq">Serie</span>
                    <span class="aud-num">{{ $equipo->NumSerie ?: '—' }}</span>
                </span>
                <span class="aud-eqficha__id">
                    <span class="aud-eqficha__etq">Folio</span>
                    <span class="aud-num">{{ $equipo->Folio ?: '—' }}</span>
                </span>
            </div>
        </div>
    </div>
@endif
