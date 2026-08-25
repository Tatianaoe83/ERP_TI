<style>
    .dash-top-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
        align-items: stretch;
    }
    @media (min-width: 1024px) {
        .dash-top-grid { grid-template-columns: 1fr 1fr; }
    }
    .dash-top-col {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-height: 0;
    }
    .dash-kpi--fill {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
    }
    .dash-kpi--fill .dash-equipos-grid {
        flex: 1 1 auto;
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        align-items: stretch;
    }
    @media (min-width: 768px) {
        .dash-kpi--fill .dash-equipos-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .dash-kpi--fill .dash-stat {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 0.35rem;
        min-height: 5.5rem;
    }
</style>

<div class="dash-top-grid">
    <div class="dash-top-col">
        <div class="dash-kpi">
            <div class="dash-kpi__top">
                <div>
                    <p class="dash-kpi__label">Líneas que puedes asignar</p>
                    <p class="dash-kpi__hint">Libres + referenciadas</p>
                    <p class="dash-kpi__value">{{ $stats['inventario']['lineas']['disponibles'] }}</p>
                </div>
                <span class="dash-kpi__icon dash-kpi__icon--amber" aria-hidden="true">
                    <i class="fas fa-phone"></i>
                </span>
            </div>
            <div class="dash-stat mt-4 flex items-center justify-between">
                <span class="dash-stat__label">Asignadas a persona física</span>
                <span class="dash-stat__value" style="font-size:1.05rem;">{{ $stats['inventario']['lineas']['asignadas'] }}</span>
            </div>
        </div>

        <div class="dash-kpi">
            <div class="grid grid-cols-2 gap-3">
                <div class="dash-stat text-center">
                    <p class="dash-stat__label">Sin asignar</p>
                    <p class="dash-stat__value">{{ $stats['inventario']['lineas']['libres'] }}</p>
                </div>
                <div class="dash-stat text-center">
                    <p class="dash-stat__label">Referenciadas</p>
                    <p class="dash-stat__value">{{ $stats['inventario']['lineas']['referenciados'] }}</p>
                </div>
            </div>
            @if($stats['inventario']['lineas']['referenciados'] > 0)
                <p class="dash-kpi__hint mt-3">Referenciadas = asignadas pero no a persona física</p>
            @endif
        </div>
    </div>

    <div class="dash-top-col">
        <div class="dash-kpi">
            <div class="dash-kpi__top">
                <div>
                    <p class="dash-kpi__label">Total de empleados activos</p>
                    <p class="dash-kpi__value">{{ $stats['empleados']['activos'] }}</p>
                </div>
                <span class="dash-kpi__icon dash-kpi__icon--blue" aria-hidden="true">
                    <i class="fas fa-users"></i>
                </span>
            </div>
        </div>

        <div class="dash-kpi dash-kpi--fill">
            <p class="dash-kpi__label mb-3">Equipos asignados en inventario</p>
            <div class="dash-equipos-grid">
                @forelse($stats['equipos_por_categoria']->take(3) as $equipo)
                <div class="dash-stat">
                    <span class="dash-stat__name">{{ Str::limit($equipo->CategoriaEquipo, 18) }}</span>
                    <span class="dash-stat__value" style="font-size:1.25rem;">{{ $equipo->total_inventario }}</span>
                </div>
                @empty
                <p class="dash-kpi__hint col-span-3 text-center py-2">No hay equipos disponibles</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="dash-panel mb-6">
    <div class="dash-panel__head">
        <h3 class="dash-panel__title">
            <i class="fas fa-tools"></i>
            Mantenimientos {{ $stats['mantenimientos']['anio'] }}
        </h3>
        @can('ver-mantenimientos')
            <a href="{{ route('mantenimientos.index') }}" class="dash-panel__link">Ver detalle</a>
        @endcan
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="dash-stat flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="dash-kpi__icon dash-kpi__icon--rose"><i class="fas fa-clock"></i></span>
                <span class="dash-stat__name">Pendientes</span>
            </div>
            <span class="dash-stat__value">{{ $stats['mantenimientos']['pendientes'] }}</span>
        </div>
        <div class="dash-stat flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="dash-kpi__icon dash-kpi__icon--green"><i class="fas fa-check-circle"></i></span>
                <span class="dash-stat__name">Realizados</span>
            </div>
            <span class="dash-stat__value">{{ $stats['mantenimientos']['realizados'] }}</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="dash-panel">
        <h3 class="dash-panel__title mb-3">
            <i class="fas fa-certificate"></i>
            Licencias asignadas
        </h3>
        @include('partials.insumos-licencia', ['stats' => $stats])
    </div>

    <div class="dash-panel">
        <h3 class="dash-panel__title mb-3">
            <i class="fas fa-building"></i>
            Gerencias con mayor número de empleados activos
        </h3>
        <div class="space-y-2">
            @forelse($stats['estadisticas_gerencia'] as $gerencia)
            <div class="dash-row">
                <p class="dash-row__name">{{ $gerencia->NombreGerencia }}</p>
                <span class="dash-chip">{{ $gerencia->empleados_activos }}</span>
            </div>
            @empty
            <p class="dash-kpi__hint text-center py-4">No hay datos disponibles</p>
            @endforelse
        </div>
    </div>
</div>

<div class="dash-panel mb-6">
    <h3 class="dash-panel__title mb-3">
        <i class="fas fa-sitemap"></i>
        Resumen organizacional
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="dash-stat flex items-center justify-between">
            <span class="dash-stat__name">Obras</span>
            <span class="dash-stat__value">{{ $stats['organizacion']['obras'] }}</span>
        </div>
        <div class="dash-stat flex items-center justify-between">
            <span class="dash-stat__name">Gerencias</span>
            <span class="dash-stat__value">{{ $stats['organizacion']['gerencias'] }}</span>
        </div>
        <div class="dash-stat flex items-center justify-between">
            <span class="dash-stat__name">Unidades</span>
            <span class="dash-stat__value">{{ $stats['organizacion']['unidades_negocio'] }}</span>
        </div>
    </div>
</div>

<p class="dash-section-title">Accesos rápidos</p>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
    @if(auth()->user()->can('ver-inventario') or auth()->user()->can('transferir-inventario') or auth()->user()->can('cartas-inventario') or auth()->user()->can('asignar-inventario'))
    <a href="/inventarios" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--blue"><i class="fas fa-clipboard-list"></i></span>
        <span class="dash-quick__name">Inventarios</span>
        <span class="dash-quick__hint">Gestión</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-empleados') or auth()->user()->can('crear-empleados') or auth()->user()->can('editar-empleados') or auth()->user()->can('borrar-empleados'))
    <a href="/empleados" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--green"><i class="fas fa-users"></i></span>
        <span class="dash-quick__name">Empleados</span>
        <span class="dash-quick__hint">Gestión</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-equipos') or auth()->user()->can('crear-equipos') or auth()->user()->can('editar-equipos') or auth()->user()->can('borrar-equipes'))
    <a href="/equipos" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--navy"><i class="fas fa-laptop"></i></span>
        <span class="dash-quick__name">Equipos</span>
        <span class="dash-quick__hint">Catálogo</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-reportes') or auth()->user()->can('crear-reportes') or auth()->user()->can('editar-reportes') or auth()->user()->can('borrar-reportes') or auth()->user()->can('exportar-reportes') or auth()->user()->can('ver-reportes-especificos'))
    <a href="/reportes" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--amber"><i class="fas fa-chart-bar"></i></span>
        <span class="dash-quick__name">Reportes</span>
        <span class="dash-quick__hint">Generar</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-reportes-especificos') or auth()->user()->can('exportar-reportes-especificos'))
    <a href="/reportes-especificos" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--green"><i class="fas fa-download"></i></span>
        <span class="dash-quick__name">Reportes específicos</span>
        <span class="dash-quick__hint">Descargar</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-usuarios') or auth()->user()->can('crear-usuarios') or auth()->user()->can('editar-usuarios') or auth()->user()->can('borrar-usuarios'))
    <a href="/usuarios" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--navy"><i class="fas fa-user-cog"></i></span>
        <span class="dash-quick__name">Usuarios</span>
        <span class="dash-quick__hint">Gestión</span>
    </a>
    @endif

    @if(auth()->user()->can('ver-rol') or auth()->user()->can('crear-rol') or auth()->user()->can('editar-rol') or auth()->user()->can('borrar-rol'))
    <a href="/roles" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--rose"><i class="fas fa-shield-alt"></i></span>
        <span class="dash-quick__name">Roles</span>
        <span class="dash-quick__hint">Permisos</span>
    </a>
    @endif

    @can('ver-presupuesto')
    <a href="/presupuesto" class="dash-quick">
        <span class="dash-kpi__icon dash-kpi__icon--blue"><i class="fas fa-file-invoice-dollar"></i></span>
        <span class="dash-quick__name">Presupuestos</span>
        <span class="dash-quick__hint">Informes</span>
    </a>
    @endcan

    @if(auth()->user()->can('ver-informe') or auth()->user()->can('buscar-informe'))
    <a href="/informe" class="dash-quick">
        <span class="dash-kpi__icon"><i class="fas fa-history"></i></span>
        <span class="dash-quick__name">Informes</span>
        <span class="dash-quick__hint">Registro</span>
    </a>
    @endif
</div>
