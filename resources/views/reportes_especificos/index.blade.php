@extends('layouts.app')

@section('content')
<x-index-page
    title="Reportes específicos"
    icon="fa-chart-line"
    subtitle="Reportes predefinidos con filtrado y descarga"
    :show-count="false"
    :card="false"
>
    <x-slot name="headerActions">
        <a href="{{ route('reportes.index') }}" class="index-page__btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al reporteador
        </a>
    </x-slot>

    <div class="index-page__hub">
        <article class="index-page__hub-card">
            <div class="index-page__hub-card-top">
                <span class="index-page__icon" aria-hidden="true">
                    <i class="fas fa-certificate"></i>
                </span>
                <span class="index-page__hub-card-badge">Licencias</span>
            </div>
            <h3>Estatus de licencias asignadas</h3>
            <p>Estado de las licencias de software asignadas a empleados.</p>
            <div class="index-page__hub-card-actions">
                <a href="{{ route('reportes-especificos.estatus-licencias') }}" class="index-page__btn-primary">
                    <i class="fas fa-eye"></i> Ver reporte
                </a>
                @can('exportar-reportes-especificos')
                <a href="{{ route('reportes-especificos.export-estatus-licencias-excel') }}" class="index-page__btn-secondary js-excel-download" title="Descargar Excel" data-download-label="Excel">
                    <i class="fas fa-file-excel"></i>
                </a>
                @endcan
            </div>
        </article>

        <article class="index-page__hub-card">
            <div class="index-page__hub-card-top">
                <span class="index-page__icon" aria-hidden="true">
                    <i class="fas fa-laptop"></i>
                </span>
                <span class="index-page__hub-card-badge">Equipos</span>
            </div>
            <h3>Listado de equipos asignados</h3>
            <p>Inventario de equipos de cómputo asignados a empleados.</p>
            <div class="index-page__hub-card-actions">
                <a href="{{ route('reportes-especificos.equipos-asignados') }}" class="index-page__btn-primary">
                    <i class="fas fa-eye"></i> Ver reporte
                </a>
                @can('exportar-reportes-especificos')
                <a href="{{ route('reportes-especificos.export-equipos-asignados-excel') }}" class="index-page__btn-secondary js-excel-download" title="Descargar Excel" data-download-label="Excel">
                    <i class="fas fa-file-excel"></i>
                </a>
                @endcan
            </div>
        </article>

        <article class="index-page__hub-card">
            <div class="index-page__hub-card-top">
                <span class="index-page__icon" aria-hidden="true">
                    <i class="fas fa-phone"></i>
                </span>
                <span class="index-page__hub-card-badge">Líneas</span>
            </div>
            <h3>Listado de líneas asignadas</h3>
            <p>Líneas telefónicas asignadas a empleados y obras.</p>
            <div class="index-page__hub-card-actions">
                <a href="{{ route('reportes-especificos.lineas-asignadas') }}" class="index-page__btn-primary">
                    <i class="fas fa-eye"></i> Ver reporte
                </a>
                @can('exportar-reportes-especificos')
                <a href="{{ route('reportes-especificos.export-lineas-asignadas-excel') }}" class="index-page__btn-secondary js-excel-download" title="Descargar Excel" data-download-label="Excel">
                    <i class="fas fa-file-excel"></i>
                </a>
                @endcan
            </div>
        </article>
    </div>

    <div class="index-page__note">
        <span class="index-page__note-icon" aria-hidden="true">
            <i class="fas fa-info-circle"></i>
        </span>
        <div>
            <h4>Información sobre los reportes</h4>
            <ul>
                <li>Todos incluyen filtrado por empleado y fechas</li>
                <li>La descarga en Excel respeta los filtros aplicados</li>
                <li>Los datos se actualizan según la información del sistema</li>
            </ul>
        </div>
    </div>
</x-index-page>
@endsection
