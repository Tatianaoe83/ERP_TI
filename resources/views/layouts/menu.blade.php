<script src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<ul x-data="{ open: null }" class="flex flex-col gap-3 mr-9 {{ Request::is('*') ? 'active' : '' }}">
    <li class="rounded-xl overflow-hidden">
        <button @click="open === 1 ? open = null : open = 1"
            class="w-full flex items-center justify-between px-3 py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white">
            <div class="flex items-center gap-2">
                <i class="fas fa-building text-center"></i>
                <span>Empresa</span>
            </div>
            <i :class="{ 'rotate-90': open === 1 }" class="fas fa-chevron-right transition-transform duration-300"></i>
        </button>
        <ul x-show="open === 1" x-collapse class="px-4 pt-1 space-y-1 text-sm">
            @if(auth()->user()->can('ver-unidadesdenegocio'))
            <li>
                <a class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white"
                    href="/unidadesDeNegocios">
                    <i class="fas fa-city text-center"></i>
                    <span>Unidades de negocio</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-gerencias'))
            <li>
                <a class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white"
                    href="/gerencias">
                    <i class="fas fa-user-tie text-center"></i>
                    <span>Gerencias</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-obras'))
            <li>
                <a class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white"
                    href="/obras">
                    <i class="fas fa-hard-hat text-center"></i>
                    <span>Obras</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-departamentos'))
            <li>
                <a class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white"
                    href="/departamentos">
                    <i class="fas fa-tags text-center"></i>
                    <span>Departamentos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-puestos'))
            <li>
                <a class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white"
                    href="/puestos">
                    <i class="fas fa-briefcase text-center"></i>
                    <span>Puestos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-empleados'))
            <li>
                <a class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white"
                    href="/empleados">
                    <i class="fas fa-user text-center"></i>
                    <span>Empleados</span>
                </a>
            </li>
            @endif
        </ul>
    </li>

    <li class="rounded-xl overflow-hidden">
        <button @click="open === 2 ? open = null : open = 2"
            class="w-full flex items-center justify-between px-3 py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white">
            <div class="flex items-center gap-2">
                <i class="fas fa-boxes text-center"></i>
                <span>Activos</span>
            </div>
            <i :class="{ 'rotate-90': open === 2 }" class="fas fa-chevron-right transition-transform duration-300"></i>
        </button>
        <ul x-show="open === 2" x-collapse class="px-4 pt-1 space-y-1 text-sm">
            @if(auth()->user()->can('ver-Lineastelefonicas'))
            <li>
                <a href="/lineasTelefonicas"
                    class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-phone-alt text-center"></i>
                    <span>Líneas</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-equipos'))
            <li>
                <a href="/equipos"
                    class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-laptop text-center"></i>
                    <span>Equipos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-insumos'))
            <li>
                <a href="/insumos"
                    class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-box text-center"></i>
                    <span>Insumos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-categorias'))
            <li>
                <a href="/categorias"
                    class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-sitemap text-center"></i>
                    <span>Categorías</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-planes'))
            <li>
                <a href="/planes"
                    class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-mobile-alt text-center"></i>
                    <span>Planes</span>
                </a>
            </li>
            @endif
        </ul>
    </li>

    <li class="rounded-xl overflow-hidden">
        <button @click="open === 3 ? open = null : open = 3"
            class="w-full flex items-center justify-between px-3 py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white">
            <div class="flex items-center gap-2">
                <i class="fas fa-chart-line text-center"></i>
                <span>Movimientos</span>
            </div>
            <i :class="{ 'rotate-90': open === 3 }" class="fas fa-chevron-right transition-transform duration-300"></i>
        </button>
        <ul x-show="open === 3" x-collapse class="px-4 pt-1 space-y-1 text-sm">
            @if(auth()->user()->can('transferir-inventario'))
            <li>
                <a href="/inventarios"
                    class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-clipboard-list text-center"></i>
                    <span>Inventario</span>
                </a>
            </li>
            @endif
        </ul>
    </li>

    <li class="rounded-xl overflow-hidden">
        <button @click="open === 4 ? open = null : open = 4"
            class="w-full flex items-center justify-between px-3 py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white">
            <div class="flex items-center gap-2">
                <i class="fas fa-file-alt text-center"></i>
                <span>Reportes</span>
            </div>
            <i :class="{ 'rotate-90': open === 4 }" class="fas fa-chevron-right transition-transform duration-300"></i>
        </button>
        <ul x-show="open === 4" x-collapse class="px-4 pt-1 space-y-1 text-sm">
            @if(auth()->user()->can('ver-presupuesto'))
            <li>
                <a href="/presupuesto"
                    class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-file-invoice text-center"></i>
                    <span>Presupuesto</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-reportes'))
            <li>
                <a href="/reportes"
                    class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-book text-center"></i>
                    <span>Reporteador</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-informe'))
            <li>
                <a href="/informe"
                    class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-clipboard text-center"></i>
                    <span>Informes</span>
                </a>
            </li>
            @endif
        </ul>
    </li>

    <li>
        @if(auth()->user()->can('ver-usuarios'))
        <a href="/usuarios"
            class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
            <i class="fas fa-users text-center"></i>
            <span>Usuarios</span>
        </a>
        @endif
    </li>

    <li>
        @if(auth()->user()->can('ver-rol'))
        <a href="/roles"
            class="flex items-center gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-2 py-1 rounded-lg transition dark:text-white">
            <i class="fas fa-shield-alt text-center"></i>
            <span>Roles</span>
        </a>
        @endif
    </li>
</ul>