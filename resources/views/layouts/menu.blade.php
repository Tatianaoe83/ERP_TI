<script src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<ul x-data="{ open: null }" class="flex flex-col gap-2 md:gap-3 mr-2 md:mr-9 ml-2 md:ml-0 {{ Request::is('*') ? 'active' : '' }}">
    <li>
        <a href="/tickets"
            class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white text-sm md:text-base">
            <i class="fas fa-desktop text-center w-5 md:w-auto text-base"></i>
            <span class="font-medium">Soporte</span>
        </a>
    </li>
    <li class="rounded-xl overflow-hidden">
        <button @click="open === 1 ? open = null : open = 1"
            class="w-full flex items-center justify-between px-3 md:px-3 py-2.5 md:py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white text-sm md:text-base">
            <div class="flex items-center gap-2 md:gap-2">
                <i class="fas fa-building text-center w-5 md:w-auto text-base"></i>
                <span>Empresa</span>
            </div>
            <i :class="{ 'rotate-90': open === 1 }" class="fas fa-chevron-right transition-transform duration-300 text-xs md:text-sm"></i>
        </button>
        <ul x-show="open === 1" x-collapse class="px-3 md:px-4 pt-1 space-y-1 text-xs md:text-sm">
            @if(auth()->user()->can('ver-unidadesdenegocio'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/unidadesDeNegocios">
                    <i class="fas fa-city text-center w-4 md:w-auto text-sm"></i>
                    <span>Unidades de negocio</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-gerencias'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/gerencias">
                    <i class="fas fa-user-tie text-center w-4 md:w-auto text-sm"></i>
                    <span>Gerencias</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-obras'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/obras">
                    <i class="fas fa-hard-hat text-center w-4 md:w-auto text-sm"></i>
                    <span>Obras</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-departamentos'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/departamentos">
                    <i class="fas fa-tags text-center w-4 md:w-auto text-sm"></i>
                    <span>Departamentos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-puestos'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/puestos">
                    <i class="fas fa-briefcase text-center w-4 md:w-auto text-sm"></i>
                    <span>Puestos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-empleados'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/empleados">
                    <i class="fas fa-user text-center w-4 md:w-auto text-sm"></i>
                    <span>Empleados</span>
                </a>
            </li>
            @endif
        </ul>
    </li>

    <li class="rounded-xl overflow-hidden">
        <button @click="open === 2 ? open = null : open = 2"
            class="w-full flex items-center justify-between px-3 md:px-3 py-2.5 md:py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white text-sm md:text-base">
            <div class="flex items-center gap-2 md:gap-2">
                <i class="fas fa-boxes text-center w-5 md:w-auto text-base"></i>
                <span>Activos</span>
            </div>
            <i :class="{ 'rotate-90': open === 2 }" class="fas fa-chevron-right transition-transform duration-300 text-xs md:text-sm"></i>
        </button>
        <ul x-show="open === 2" x-collapse class="px-3 md:px-4 pt-1 space-y-1 text-xs md:text-sm">
            @if(auth()->user()->can('ver-Lineastelefonicas'))
            <li>
                <a href="/lineasTelefonicas"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-phone-alt text-center w-4 md:w-auto text-sm"></i>
                    <span>Líneas</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-equipos'))
            <li>
                <a href="/equipos"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-laptop text-center w-4 md:w-auto text-sm"></i>
                    <span>Equipos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-insumos'))
            <li>
                <a href="/insumos"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-box text-center w-4 md:w-auto text-sm"></i>
                    <span>Insumos</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-categorias'))
            <li>
                <a href="/categorias"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-sitemap text-center w-4 md:w-auto text-sm"></i>
                    <span>Categorías</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-planes'))
            <li>
                <a href="/planes"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-mobile-alt text-center w-4 md:w-auto text-sm"></i>
                    <span>Planes</span>
                </a>
            </li>
            @endif
        </ul>
    </li>

    <li class="rounded-xl overflow-hidden">
        <button @click="open === 3 ? open = null : open = 3"
            class="w-full flex items-center justify-between px-3 md:px-3 py-2.5 md:py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white text-sm md:text-base">
            <div class="flex items-center gap-2 md:gap-2">
                <i class="fas fa-chart-line text-center w-5 md:w-auto text-base"></i>
                <span>Movimientos</span>
            </div>
            <i :class="{ 'rotate-90': open === 3 }" class="fas fa-chevron-right transition-transform duration-300 text-xs md:text-sm"></i>
        </button>
        <ul x-show="open === 3" x-collapse class="px-3 md:px-4 pt-1 space-y-1 text-xs md:text-sm">
            @if(auth()->user()->can('transferir-inventario'))
            <li>
                <a href="/inventarios"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-clipboard-list text-center w-4 md:w-auto text-sm"></i>
                    <span>Inventario</span>
                </a>
            </li>
            @endif
        </ul>
    </li>

    <li class="rounded-xl overflow-hidden">
        <button @click="open === 4 ? open = null : open = 4"
            class="w-full flex items-center justify-between px-3 md:px-3 py-2.5 md:py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white text-sm md:text-base">
            <div class="flex items-center gap-2 md:gap-2">
                <i class="fas fa-file-alt text-center w-5 md:w-auto text-base"></i>
                <span>Reportes</span>
            </div>
            <i :class="{ 'rotate-90': open === 4 }" class="fas fa-chevron-right transition-transform duration-300 text-xs md:text-sm"></i>
        </button>
        <ul x-show="open === 4" x-collapse class="px-3 md:px-4 pt-1 space-y-1 text-xs md:text-sm">
            @if(auth()->user()->can('ver-presupuesto'))
            <li>
                <a href="/presupuesto"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-file-invoice text-center w-4 md:w-auto text-sm"></i>
                    <span>Presupuesto</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-reportes'))
            <li>
                <a href="/reportes"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-book text-center w-4 md:w-auto text-sm"></i>
                    <span>Reporteador</span>
                </a>
            </li>
            @endif
            @if(auth()->user()->can('ver-informe'))
            <li>
                <a href="/informe"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-clipboard text-center w-4 md:w-auto text-sm"></i>
                    <span>Informes</span>
                </a>
            </li>
            @endif
        </ul>
    </li>

    <li class="rounded-xl overflow-hidden">
        <button @click="open === 5 ? open = null : open = 5"
            class="w-full flex items-center justify-between px-3 md:px-3 py-2.5 md:py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white text-sm md:text-base">
            <div class="flex items-center gap-2 md:gap-2">
                <i class="fas fa-file-invoice-dollar text-center w-5 md:w-auto text-base"></i>
                <span>Facturas</span>
            </div>
            <i :class="{ 'rotate-90': open === 5 }" class="fas fa-chevron-right transition-transform duration-300 text-xs md:text-sm"></i>
        </button>
        <ul x-show="open === 5" x-collapse class="px-3 md:px-4 pt-1 space-y-1 text-xs md:text-sm">
            <li>
                <a href="/cortes" class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-money-check-alt w-4 md:w-auto text-sm"></i>
                    @if(auth()->user()->can('ver-cortes'))
                    <span>Cortes de Insumo</span>
                    @endif
                </a>
            </li>
        </ul>
    </li>

    <li>
        @if(auth()->user()->can('ver-usuarios'))
        <a href="/usuarios"
            class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white text-sm md:text-base">
            <i class="fas fa-users text-center w-5 md:w-auto text-base"></i>
            <span class="font-medium">Usuarios</span>
        </a>
        @endif
    </li>

    <li>
        @if(auth()->user()->can('ver-rol'))
        <a href="/roles"
            class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white text-sm md:text-base">
            <i class="fas fa-shield-alt text-center w-5 md:w-auto text-base"></i>
            <span class="font-medium">Roles</span>
        </a>
        @endif
    </li>
</ul>