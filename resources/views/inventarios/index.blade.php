@extends('layouts.app')

@section('content')
@include('flash::message')
@include('inventarios.partials.tipo-persona-styles')
<style>
    .index-page__filters .index-page__note { grid-column: 1 / -1; margin-bottom: 0.15rem; }
    .inv-leyenda--inline {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1.25rem;
        padding: 0;
        margin: 0;
        background: transparent;
        border: 0;
    }
    .inv-leyenda--inline .inv-leyenda-item { max-width: 320px; }

    .index-search {
        position: relative;
    }
    .index-search__icon {
        position: absolute;
        left: 0.7rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.75rem;
        line-height: 1;
        pointer-events: none;
        z-index: 2;
    }
    .index-search .form-control,
    .index-page__filters .index-search .form-control {
        padding-left: 2.35rem !important;
    }
    .dark .index-search__icon {
        color: #9ca3af;
    }
    .inv-index-page .index-page__filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    @media (min-width: 900px) {
        .inv-index-page .index-page__filters {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (min-width: 1280px) {
        .inv-index-page .index-page__filters {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
    }
    .inv-index-page .index-page__filters .form-group {
        min-width: 0;
        width: 100%;
    }
    .inv-index-page .index-page__filters .select2-container,
    .inv-index-page .index-page__filters .form-control {
        width: 100% !important;
    }
    .inv-index-page .index-page__filters label {
        color: #64748b;
    }
    .dark .inv-index-page .index-page__filters label {
        color: #e5e7eb;
    }
    body > .select2-container .select2-results__options {
        max-height: 320px;
    }
</style>

<x-index-page
    class="inv-index-page"
    title="Inventario"
    icon="fa-clipboard-list"
    subtitle="0 registros"
>
    <x-slot name="filters">
        <div class="index-page__note">
            <span class="index-page__note-icon" aria-hidden="true">
                <i class="fas fa-info-circle"></i>
            </span>
            <div class="inv-leyenda inv-leyenda--inline">
                <div class="inv-leyenda-item">
                    <span class="inv-tipo-badge inv-tipo-fisica">Física</span>
                    <div><strong>Persona física</strong>Stock (asignado actual) + Extra (presupuesto futuro).</div>
                </div>
                <div class="inv-leyenda-item">
                    <span class="inv-tipo-badge inv-tipo-referenciado">Referenciado</span>
                    <div><strong>Gerencia / referenciado</strong>Solo stock. Tiene asignaciones actuales, sin extras.</div>
                </div>
                <div class="inv-leyenda-item">
                    <span class="inv-tipo-badge inv-tipo-extraordinario">Extraordinario</span>
                    <div><strong>Plaza extraordinaria</strong>Todo es Extra (presupuesto futuro).</div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="filtro-nombre">Nombre empleado</label>
            <div class="index-search">
                <i class="fas fa-search index-search__icon" aria-hidden="true"></i>
                <input type="text" class="form-control" id="filtro-nombre" placeholder="Buscar empleado..." autocomplete="off">
            </div>
        </div>
        <div class="form-group">
            <label for="filtro-obra">Obra</label>
            <select id="filtro-obra" class="jz-inv form-control">
                <option value="">Todas las obras</option>
                @foreach($obrasFiltro as $obra)
                    <option value="{{ $obra }}">{{ $obra }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="filtro-puesto">Puesto</label>
            <select id="filtro-puesto" class="jz-inv form-control">
                <option value="">Todos los puestos</option>
                @foreach($puestosFiltro as $puesto)
                    <option value="{{ $puesto }}">{{ $puesto }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="filtro-inventario">Inventario</label>
            <div class="index-search">
                <i class="fas fa-search index-search__icon" aria-hidden="true"></i>
                <input type="text" class="form-control" id="filtro-inventario" placeholder="Buscar inventario..." autocomplete="off">
            </div>
        </div>
        <div class="form-group">
            <label for="filtro-persona">Tipo de persona</label>
            <select class="form-control jz-inv" id="filtro-persona">
                <option value="">Físicas y referenciados</option>
                <option value="FISICA">Física — stock + extra</option>
                <option value="REFERENCIADO">Referenciado — solo stock</option>
                <option value="EXTRAORDINARIO">Extraordinario — todo extra</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filtro-estatus">Estatus</label>
            <select class="form-control" id="filtro-estatus">
                <option value="1" selected>Activo</option>
                <option value="0">Inactivo</option>
                <option value="2">Todos</option>
            </select>
        </div>
        <div class="index-page__filters-actions">
            <button type="button" id="limpiar-filtros-inv" class="index-page__btn-secondary">
                <i class="fa fa-times"></i> Limpiar filtros
            </button>
        </div>
    </x-slot>

    @include('inventarios.table')
</x-index-page>
@endsection
