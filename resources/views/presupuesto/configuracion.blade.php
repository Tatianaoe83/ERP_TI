@extends('layouts.app')

@section('content')
@include('flash::message')

<style>
    .pconf-note {
        margin: 0 0 1.15rem;
        padding: 0.85rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid #dbeafe;
        background: #eff6ff;
        color: #1e3a8a;
        font-size: 0.85rem;
        line-height: 1.45;
    }
    .dark .pconf-note {
        background: rgba(30, 58, 95, 0.35);
        border-color: #1e3a5f;
        color: #bfdbfe;
    }
    .pconf-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 960px) {
        .pconf-grid { grid-template-columns: 1fr 1fr; }
    }
    .pconf-card {
        background: var(--index-card, #fff);
        border: 1px solid var(--index-border, #e5e7eb);
        border-radius: 0.85rem;
        padding: 1rem 1.05rem 0.9rem;
        min-width: 0;
    }
    .dark .pconf-card {
        background: #1f2937;
        border-color: #374151;
    }
    .pconf-card h3 {
        margin: 0 0 0.25rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--index-navy, #101d49);
    }
    .dark .pconf-card h3 { color: #fff; }
    .pconf-card p {
        margin: 0 0 0.65rem;
        font-size: 0.78rem;
        color: var(--index-muted, #6b7280);
        line-height: 1.4;
    }
    .pconf-toolbar {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        margin-bottom: 0.7rem;
    }
    .pconf-search {
        position: relative;
        flex: 1 1 auto;
        min-width: 0;
    }
    .pconf-search i {
        position: absolute;
        left: 0.7rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.78rem;
        pointer-events: none;
    }
    .pconf-search input {
        width: 100%;
        height: 2.35rem;
        padding: 0.4rem 0.75rem 0.4rem 2rem;
        border: 1px solid var(--index-border, #e5e7eb);
        border-radius: 0.55rem;
        background: #fff;
        color: #111827;
        font-size: 0.82rem;
    }
    .pconf-search input:focus {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.28);
    }
    .dark .pconf-search input {
        background: #111827;
        border-color: #374151;
        color: #f8fafc;
    }
    .pconf-count {
        flex: 0 0 auto;
        font-size: 0.72rem;
        font-weight: 600;
        color: #6b7280;
        white-space: nowrap;
    }
    .dark .pconf-count { color: #9ca3af; }
    .pconf-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem 0.6rem;
        max-height: 16rem;
        overflow: auto;
        padding: 0.2rem 0.1rem 0.45rem;
    }
    .pconf-empty {
        display: none;
        width: 100%;
        font-size: 0.78rem;
        color: #94a3b8;
        padding: 0.35rem 0;
    }
    .pconf-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin: 0;
        padding: 0.4rem 0.7rem;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #374151;
        background: #f8fafc;
        cursor: pointer;
        max-width: 100%;
    }
    .dark .pconf-chip {
        background: #111827;
        border-color: #374151;
        color: #e5e7eb;
    }
    .pconf-chip input { margin: 0; }
    .pconf-chip.is-on {
        background: #dbeafe;
        border-color: #93c5fd;
        color: #1e3a8a;
    }
    .dark .pconf-chip.is-on {
        background: #1e3a5f;
        border-color: #3b82f6;
        color: #dbeafe;
    }
    .pconf-add {
        display: flex;
        gap: 0.45rem;
        margin-top: 0.55rem;
    }
    .pconf-add .form-control { min-width: 0; }
    .pconf-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        margin-top: 1.15rem;
    }
</style>

<x-index-page
    title="Conf. presupuesto"
    icon="fa-sliders-h"
    subtitle="Define qué categorías de inventario entran en cada bloque del presupuesto"
    :show-count="false"
    :card="false"
>
    <x-slot name="headerActions">
        <a href="{{ route('presupuesto.index') }}" class="index-page__btn-secondary">Volver</a>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <p class="pconf-note">
        Si en inventario capturan una categoría nueva (por ejemplo una licencia o un tipo de equipo),
        márcala aquí para que sume en el reporte.
    </p>

    <form method="POST" action="{{ route('presupuesto.configuracion.guardar') }}">
        @csrf
        <div class="pconf-grid">
            @foreach($grupos as $grupo)
                <section class="pconf-card" data-pconf-card>
                    <h3>{{ $grupo['label'] }}</h3>
                    <p>{{ $grupo['hint'] }}</p>
                    @if(count($grupo['opciones']) > 0)
                    <div class="pconf-toolbar">
                        <div class="pconf-search">
                            <i class="fas fa-search"></i>
                            <input type="search"
                                class="pconf-filter"
                                placeholder="Buscar categoría..."
                                autocomplete="off">
                        </div>
                        <span class="pconf-count" data-pconf-count></span>
                    </div>
                    @endif
                    <div class="pconf-list">
                        @forelse($grupo['opciones'] as $opcion)
                            @php $on = in_array(mb_strtoupper($opcion, 'UTF-8'), $grupo['seleccionadas_upper'], true); @endphp
                            <label class="pconf-chip {{ $on ? 'is-on' : '' }}" data-pconf-label="{{ mb_strtoupper($opcion, 'UTF-8') }}">
                                <input type="checkbox"
                                    name="grupos[{{ $grupo['clave'] }}][]"
                                    value="{{ $opcion }}"
                                    {{ $on ? 'checked' : '' }}
                                    onchange="this.closest('label').classList.toggle('is-on', this.checked); if (window.pconfRefreshCount) window.pconfRefreshCount(this.closest('[data-pconf-card]'));">
                                <span>{{ $opcion }}</span>
                            </label>
                        @empty
                            <span class="text-muted" style="font-size:0.8rem;">No hay valores en catálogo. Agrégalo abajo.</span>
                        @endforelse
                        <span class="pconf-empty">No hay coincidencias con esa búsqueda.</span>
                    </div>
                    <div class="pconf-add">
                        <input type="text"
                            class="form-control"
                            name="nuevo[{{ $grupo['clave'] }}]"
                            maxlength="150"
                            placeholder="Agregar categoría que no aparezca en la lista">
                    </div>
                </section>
            @endforeach
        </div>

        <div class="pconf-actions">
            <button type="submit" class="index-page__btn-primary">Guardar configuración</button>
            <a href="{{ route('presupuesto.index') }}" class="index-page__btn-secondary">Cancelar</a>
        </div>
    </form>
</x-index-page>

<script>
(function () {
    function refreshCount(card) {
        if (!card) return;
        var el = card.querySelector('[data-pconf-count]');
        if (!el) return;
        var chips = card.querySelectorAll('.pconf-chip');
        var visible = 0;
        var selected = 0;
        chips.forEach(function (chip) {
            if (chip.style.display !== 'none') visible += 1;
            if (chip.querySelector('input') && chip.querySelector('input').checked) selected += 1;
        });
        el.textContent = selected + ' seleccionada' + (selected === 1 ? '' : 's') +
            (visible !== chips.length ? ' · ' + visible + ' visibles' : '');
    }

    function filterCard(card) {
        var input = card.querySelector('.pconf-filter');
        var q = (input && input.value ? input.value : '').trim().toUpperCase();
        var chips = card.querySelectorAll('.pconf-chip');
        var empty = card.querySelector('.pconf-empty');
        var shown = 0;
        chips.forEach(function (chip) {
            var hay = !q || (chip.getAttribute('data-pconf-label') || '').indexOf(q) !== -1;
            chip.style.display = hay ? '' : 'none';
            if (hay) shown += 1;
        });
        if (empty) empty.style.display = chips.length && shown === 0 ? 'block' : 'none';
        refreshCount(card);
    }

    function bind() {
        document.querySelectorAll('[data-pconf-card]').forEach(function (card) {
            var input = card.querySelector('.pconf-filter');
            if (input && !input.dataset.bound) {
                input.dataset.bound = '1';
                input.addEventListener('input', function () { filterCard(card); });
            }
            refreshCount(card);
        });
    }

    window.pconfRefreshCount = refreshCount;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
</script>
@endsection
