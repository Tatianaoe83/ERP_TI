@php
$user = auth()->user();
$puedeVerEmpresa = $user && (
    $user->can('ver-unidadesdenegocio') ||
    $user->can('ver-gerencias') ||
    $user->can('ver-obras') ||
    $user->can('ver-departamentos') ||
    $user->can('ver-puestos') ||
    $user->can('ver-empleados')
);
$puedeVerActivos = $user && (
    $user->can('ver-Lineastelefonicas') ||
    $user->can('ver-equipos') ||
    $user->can('ver-insumos') ||
    $user->can('ver-categorias') ||
    $user->can('ver-planes')
);
$puedeVerMovimientos = $user && $user->can('transferir-inventario');
$puedeVerReportes = $user && (
    $user->can('ver-presupuesto') ||
    $user->can('editar-conf-presupuesto') ||
    $user->can('ver-reportes') ||
    $user->can('ver-informe')
);
$puedeVerAdministracion = $user && (
    $user->can('ver-presupuestos') ||
    $user->can('generar-cortes') ||
    $user->can('ver-facturas') ||
    $user->can('ver-comparativa') ||
    $user->can('ver-mantenimientos')
);
$puedeVerSeguridad = $user && (
    $user->can('ver-usuarios') ||
    $user->can('ver-rol')
);
$puedeVerPrincipal = $user && (
    $user->can('ver-dashboard') ||
    $user->can('ver-compras') ||
    $user->can('ver-soporte') ||
    $user->can('ver-mantenimientos')
);
$puedeVerCatalogos = $puedeVerEmpresa || $puedeVerActivos;
$puedeVerGestion = $puedeVerMovimientos || $puedeVerReportes;

$openDefault = 'null';
if (request()->is('unidadesDeNegocios*') || request()->is('gerencias*') || request()->is('obras*') || request()->is('departamentos*') || request()->is('puestos*') || request()->is('empleados*')) {
    $openDefault = 1;
} elseif (request()->is('lineasTelefonicas*') || request()->is('equipos*') || request()->is('insumos*') || request()->is('categorias*') || request()->is('planes*')) {
    $openDefault = 2;
} elseif (request()->is('inventarios*')) {
    $openDefault = 3;
} elseif (request()->is('presupuesto*') || request()->is('reportes*') || request()->is('informe*')) {
    $openDefault = 4;
} elseif (request()->is('cortes*') || request()->is('facturas*')) {
    $openDefault = 5;
} elseif (request()->is('usuarios*') || request()->is('roles*')) {
    $openDefault = 6;
}
@endphp

<ul x-data="{ open: {{ $openDefault }} }" class="sidebar-nav flex flex-col gap-0.5 mr-1 ml-1">
    @if($puedeVerPrincipal)
    <li class="sidebar-section-label" aria-hidden="true">Principal</li>
    @endif

    @if(auth()->check() && (auth()->user()->can('ver-dashboard') || auth()->user()->can('ver-compras')))
    <li>
        <a href="/home" title="Dashboard"
            class="sidebar-link no-underline flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm {{ request()->is('home') || request()->is('/') ? 'is-active' : '' }}">
            <i class="fas fa-th-large sidebar-ico"></i>
            <span class="sidebar-text">Dashboard</span>
        </a>
    </li>
    @endif

    @if($user && $user->can('ver-soporte'))
    <li>
        <a href="/tickets" title="Soporte"
            class="sidebar-link flex items-center gap-2.5 no-underline px-2.5 py-2 rounded-lg text-sm {{ request()->is('tickets') || request()->is('tickets/*') ? 'is-active' : '' }}">
            <i class="fas fa-desktop sidebar-ico"></i>
            <span class="font-medium sidebar-text">Soporte</span>
        </a>
    </li>
    @endif

    @if($user && $user->can('ver-compras'))
    <li>
        <a href="/tickets-mantenimiento" title="Mantenimientos de compras"
            class="sidebar-link flex items-center gap-2.5 no-underline px-2.5 py-2 rounded-lg text-sm {{ request()->is('tickets-mantenimiento*') ? 'is-active' : '' }}">
            <i class="fas fa-wrench sidebar-ico"></i>
            <span class="font-medium sidebar-text">Mant. compras</span>
        </a>
    </li>
    @endif

    @if(auth()->check() && auth()->user()->can('ver-mantenimientos'))
    <li>
        <a href="/mantenimientos" title="Mantenimientos"
            class="sidebar-link flex items-center gap-2.5 no-underline px-2.5 py-2 rounded-lg text-sm {{ request()->is('mantenimientos*') ? 'is-active' : '' }}">
            <i class="fas fa-tools sidebar-ico"></i>
            <span class="sidebar-text">Mantenimientos</span>
        </a>
    </li>
    @endif

    @if($puedeVerCatalogos)
    <li class="sidebar-section-label" aria-hidden="true">Catálogos</li>
    @endif

    @if($puedeVerEmpresa)
    <li>
        <button type="button" @click="open === 1 ? open = null : open = 1" title="Empresa"
            class="sidebar-btn w-full flex items-center justify-between px-2.5 py-2 text-left rounded-lg text-sm {{ $openDefault === 1 ? 'is-open' : '' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <i class="fas fa-building sidebar-ico"></i>
                <span class="sidebar-text">Empresa</span>
            </div>
            <i :class="{ 'rotate-90': open === 1 }" class="fas fa-chevron-right sidebar-chevron transition-transform duration-200 flex-shrink-0"></i>
        </button>
        <ul x-show="open === 1" x-collapse x-cloak class="sidebar-sub space-y-0.5 text-xs">
            @if(auth()->check() && auth()->user()->can('ver-unidadesdenegocio'))
            <li>
                <a class="sidebar-link flex items-center gap-2 no-underline  px-2.5 py-1.5 rounded-md {{ request()->is('unidadesDeNegocios*') ? 'is-active' : '' }}"
                    href="/unidadesDeNegocios" title="Unidades de negocio">
                    <i class="fas fa-city sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Unidades de negocio</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-gerencias'))
            <li>
                <a class="sidebar-link flex items-center gap-2 no-underline  px-2.5 py-1.5 rounded-md {{ request()->is('gerencias*') ? 'is-active' : '' }}"
                    href="/gerencias" title="Gerencias">
                    <i class="fas fa-user-tie sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Gerencias</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-obras'))
            <li>
                <a class="sidebar-link flex items-center gap-2 no-underline  px-2.5 py-1.5 rounded-md {{ request()->is('obras*') ? 'is-active' : '' }}"
                    href="/obras" title="Obras">
                    <i class="fas fa-hard-hat sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Obras</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-departamentos'))
            <li>
                <a class="sidebar-link flex items-center gap-2 no-underline  px-2.5 py-1.5 rounded-md {{ request()->is('departamentos*') ? 'is-active' : '' }}"
                    href="/departamentos" title="Departamentos">
                    <i class="fas fa-tags sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Departamentos</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-puestos'))
            <li>
                <a class="sidebar-link flex items-center gap-2 no-underline  px-2.5 py-1.5 rounded-md {{ request()->is('puestos*') ? 'is-active' : '' }}"
                    href="/puestos" title="Puestos">
                    <i class="fas fa-briefcase sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Puestos</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-empleados'))
            <li>
                <a class="sidebar-link flex items-center gap-2 no-underline  px-2.5 py-1.5 rounded-md {{ request()->is('empleados*') ? 'is-active' : '' }}"
                    href="/empleados" title="Empleados">
                    <i class="fas fa-user sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Empleados</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($puedeVerActivos)
    <li>
        <button type="button" @click="open === 2 ? open = null : open = 2" title="Activos"
            class="sidebar-btn w-full flex items-center justify-between px-2.5 py-2 text-left rounded-lg text-sm {{ $openDefault === 2 ? 'is-open' : '' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <i class="fas fa-boxes sidebar-ico"></i>
                <span class="sidebar-text">Activos</span>
            </div>
            <i :class="{ 'rotate-90': open === 2 }" class="fas fa-chevron-right sidebar-chevron transition-transform duration-200 flex-shrink-0"></i>
        </button>
        <ul x-show="open === 2" x-collapse x-cloak class="sidebar-sub space-y-0.5 text-xs">
            @if(auth()->check() && auth()->user()->can('ver-Lineastelefonicas'))
            <li>
                <a href="/lineasTelefonicas" title="Líneas"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('lineasTelefonicas*') ? 'is-active' : '' }}">
                    <i class="fas fa-phone-alt sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Líneas</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-equipos'))
            <li>
                <a href="/equipos" title="Equipos"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('equipos*') ? 'is-active' : '' }}">
                    <i class="fas fa-laptop sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Equipos</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-insumos'))
            <li>
                <a href="/insumos" title="Insumos"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('insumos*') ? 'is-active' : '' }}">
                    <i class="fas fa-box sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Insumos</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-categorias'))
            <li>
                <a href="/categorias" title="Categorías"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('categorias*') ? 'is-active' : '' }}">
                    <i class="fas fa-sitemap sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Categorías</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-planes'))
            <li>
                <a href="/planes" title="Planes"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('planes*') ? 'is-active' : '' }}">
                    <i class="fas fa-mobile-alt sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Planes</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($puedeVerGestion)
    <li class="sidebar-section-label" aria-hidden="true">Gestión</li>
    @endif

    @if($puedeVerMovimientos)
    <li>
        <button type="button" @click="open === 3 ? open = null : open = 3" title="Movimientos"
            class="sidebar-btn w-full flex items-center justify-between px-2.5 py-2 text-left rounded-lg text-sm {{ $openDefault === 3 ? 'is-open' : '' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <i class="fas fa-chart-line sidebar-ico"></i>
                <span class="sidebar-text">Movimientos</span>
            </div>
            <i :class="{ 'rotate-90': open === 3 }" class="fas fa-chevron-right sidebar-chevron transition-transform duration-200 flex-shrink-0"></i>
        </button>
        <ul x-show="open === 3" x-collapse x-cloak class="sidebar-sub space-y-0.5 text-xs">
            @if(auth()->check() && auth()->user()->can('transferir-inventario'))
            <li>
                <a href="/inventarios" title="Inventario"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('inventarios*') ? 'is-active' : '' }}">
                    <i class="fas fa-clipboard-list sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Inventario</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($puedeVerReportes)
    <li>
        <button type="button" @click="open === 4 ? open = null : open = 4" title="Reportes"
            class="sidebar-btn w-full flex items-center justify-between px-2.5 py-2 text-left rounded-lg text-sm {{ $openDefault === 4 ? 'is-open' : '' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <i class="fas fa-file-alt sidebar-ico"></i>
                <span class="sidebar-text">Reportes</span>
            </div>
            <i :class="{ 'rotate-90': open === 4 }" class="fas fa-chevron-right sidebar-chevron transition-transform duration-200 flex-shrink-0"></i>
        </button>
        <ul x-show="open === 4" x-collapse x-cloak class="sidebar-sub space-y-0.5 text-xs">
            @if(auth()->check() && auth()->user()->can('ver-presupuesto'))
            <li>
                <a href="/presupuesto" title="Presupuesto"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('presupuesto') || request()->is('presupuesto/') ? 'is-active' : '' }}">
                    <i class="fas fa-file-invoice sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Presupuesto</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('editar-conf-presupuesto'))
            <li>
                <a href="{{ route('presupuesto.configuracion') }}" title="Conf. presupuesto"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('presupuesto/configuracion*') ? 'is-active' : '' }}">
                    <i class="fas fa-sliders-h sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Conf. presupuesto</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-reportes'))
            <li>
                <a href="/reportes" title="Reporteador"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('reportes*') ? 'is-active' : '' }}">
                    <i class="fas fa-book sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Reporteador</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-informe'))
            <li>
                <a href="/informe" title="Informes"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('informe*') ? 'is-active' : '' }}">
                    <i class="fas fa-clipboard sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Informes</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($puedeVerAdministracion)
    <li class="sidebar-section-label" aria-hidden="true">Administración</li>
    @endif

    @if($puedeVerAdministracion)
    <li>
        <button type="button" @click="open === 5 ? open = null : open = 5" title="Administración"
            class="sidebar-btn w-full flex items-center justify-between px-2.5 py-2 text-left rounded-lg text-sm {{ $openDefault === 5 ? 'is-open' : '' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <i class="fas fa-file-invoice-dollar sidebar-ico"></i>
                <span class="sidebar-text">Administración</span>
            </div>
            <i :class="{ 'rotate-90': open === 5 }" class="fas fa-chevron-right sidebar-chevron transition-transform duration-200 flex-shrink-0"></i>
        </button>
        <ul x-show="open === 5" x-collapse x-cloak class="sidebar-sub space-y-0.5 text-xs">
            @if(auth()->check() && auth()->user()->can('ver-presupuestos'))
            <li>
                <a href="/cortes" title="Presupuestos Oficiales"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('cortes*') ? 'is-active' : '' }}">
                    <i class="fas fa-money-check-alt sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Presupuestos Oficiales</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-facturas'))
            <li>
                <a href="/facturas" title="Facturas"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('facturas*') ? 'is-active' : '' }}">
                    <i class="fas fa-money-check-alt sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Facturas</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($puedeVerSeguridad)
    <li class="sidebar-section-label" aria-hidden="true">Sistema</li>
    @endif

    @if($puedeVerSeguridad)
    <li>
        <button type="button" @click="open === 6 ? open = null : open = 6" title="Seguridad"
            class="sidebar-btn w-full flex items-center justify-between px-2.5 py-2 text-left rounded-lg text-sm {{ $openDefault === 6 ? 'is-open' : '' }}">
            <div class="flex items-center gap-2.5 min-w-0">
                <i class="fas fa-user-shield sidebar-ico"></i>
                <span class="sidebar-text">Seguridad</span>
            </div>
            <i :class="{ 'rotate-90': open === 6 }" class="fas fa-chevron-right sidebar-chevron transition-transform duration-200 flex-shrink-0"></i>
        </button>
        <ul x-show="open === 6" x-collapse x-cloak class="sidebar-sub space-y-0.5 text-xs">
            @if(auth()->check() && auth()->user()->can('ver-usuarios'))
            <li>
                <a href="/usuarios" title="Usuarios"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('usuarios*') ? 'is-active' : '' }}">
                    <i class="fas fa-users sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Usuarios</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-rol'))
            <li>
                <a href="/roles" title="Roles"
                    class="sidebar-link flex items-center gap-2 no-underline px-2.5 py-1.5 rounded-md {{ request()->is('roles*') ? 'is-active' : '' }}">
                    <i class="fas fa-shield-alt sidebar-ico sidebar-ico-sm"></i>
                    <span class="sidebar-text">Roles</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif
</ul>
