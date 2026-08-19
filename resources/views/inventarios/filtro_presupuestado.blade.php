{{-- Barra de filtro Stock vs Extra --}}
@php $compacto = $compacto ?? false; @endphp
<div class="inventario-filtros d-flex flex-wrap align-items-center {{ $compacto ? 'mb-0' : 'w-100 mb-3' }}"
    data-tabla="{{ $tabla }}"
    data-tipo="{{ $tipo }}"
    style="{{ $compacto ? 'gap:8px;' : '' }}">

    @if($permitePresupuestado)
        @if($presupuestadoForzado ?? false)
            @unless($compacto)
            <div class="d-flex align-items-center gap-2">
                <span class="inv-chip inv-chip-extra"><i class="fas fa-calendar-plus"></i> Todo es Extra</span>
            </div>
            @endunless
        @else
            <div class="pill-group d-flex flex-wrap align-items-center {{ $compacto ? 'd-none' : '' }}">
                <button type="button" class="pill-filtro activo" data-filtro="todos">
                    Todos (<span class="conteo-todos">0</span>)
                </button>
                <button type="button" class="pill-filtro" data-filtro="no_presupuestados">
                    Stock (<span class="conteo-no">0</span>)
                </button>
                <button type="button" class="pill-filtro" data-filtro="presupuestados">
                    Extra (<span class="conteo-si">0</span>)
                </button>
            </div>
            @unless($compacto)
            <div class="inv-filtro-hint">
                <strong>Stock</strong> = asignado ahora · <strong>Extra</strong> = presupuesto futuro
            </div>
            @endunless
        @endif
    @else
        @unless($compacto)
        <div class="d-flex align-items-center gap-2">
            <span class="inv-chip inv-chip-stock"><i class="fas fa-box"></i> Solo stock</span>
        </div>
        @endunless
    @endif
</div>
