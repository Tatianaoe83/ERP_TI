<script src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

{{-- resources/views/livewire/tickets-kanban-updater.blade.php --}}

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
@endphp

<ul x-data="{ open: null }" class="flex flex-col gap-2 md:gap-3 mr-2 md:mr-9 ml-2 md:ml-0 {{ Request::is('*') ? 'active' : '' }}">
    @if($user && $user->can('ver-soporte'))
        <li>
            <a href="/tickets"
                class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white text-sm md:text-base">
                <i class="fas fa-desktop text-center w-5 md:w-auto text-base"></i>
                <span class="font-medium sidebar-text">Soporte</span>
            </a>
        </li>
    @endif

    @if(auth()->check() && auth()->user()->can('ver-mantenimientos'))
        <li>
            <a href="/mantenimientos" class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white text-sm md:text-base">
                <i class="fas fa-tools w-4 md:w-auto text-sm"></i>
                <span class="sidebar-text">Mantenimientos</span>
            </a>
        </li>
    @endif
  
    @if($puedeVerEmpresa)
    <li class="rounded-xl overflow-hidden">
        <button @click="open === 1 ? open = null : open = 1"
            class="w-full flex items-center justify-between px-3 md:px-3 py-2.5 md:py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white text-sm md:text-base">
            <div class="flex items-center gap-2 md:gap-2">
                <i class="fas fa-building text-center w-5 md:w-auto text-base"></i>
                <span class="sidebar-text">Empresa</span>
            </div>
            <i :class="{ 'rotate-90': open === 1 }" class="fas fa-chevron-right transition-transform duration-300 text-xs md:text-sm"></i>
        </button>
        <ul x-show="open === 1" x-collapse class="px-3 md:px-4 pt-1 space-y-1 text-xs md:text-sm">
            @if(auth()->check() && auth()->user()->can('ver-unidadesdenegocio'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/unidadesDeNegocios">
                    <i class="fas fa-city text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Unidades de negocio</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-gerencias'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/gerencias">
                    <i class="fas fa-user-tie text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Gerencias</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-obras'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/obras">
                    <i class="fas fa-hard-hat text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Obras</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-departamentos'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/departamentos">
                    <i class="fas fa-tags text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Departamentos</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-puestos'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/puestos">
                    <i class="fas fa-briefcase text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Puestos</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-empleados'))
            <li>
                <a class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white"
                    href="/empleados">
                    <i class="fas fa-user text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Empleados</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($puedeVerActivos)
    <li class="rounded-xl overflow-hidden">
        <button @click="open === 2 ? open = null : open = 2"
            class="w-full flex items-center justify-between px-3 md:px-3 py-2.5 md:py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white text-sm md:text-base">
            <div class="flex items-center gap-2 md:gap-2">
                <i class="fas fa-boxes text-center w-5 md:w-auto text-base"></i>
                <span class="sidebar-text">Activos</span>
            </div>
            <i :class="{ 'rotate-90': open === 2 }" class="fas fa-chevron-right transition-transform duration-300 text-xs md:text-sm"></i>
        </button>
        <ul x-show="open === 2" x-collapse class="px-3 md:px-4 pt-1 space-y-1 text-xs md:text-sm">
            @if(auth()->check() && auth()->user()->can('ver-Lineastelefonicas'))
            <li>
                <a href="/lineasTelefonicas"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-phone-alt text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Líneas</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-equipos'))
            <li>
                <a href="/equipos"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-laptop text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Equipos</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-insumos'))
            <li>
                <a href="/insumos"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-box text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Insumos</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-categorias'))
            <li>
                <a href="/categorias"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-sitemap text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Categorías</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-planes'))
            <li>
                <a href="/planes"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-mobile-alt text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Planes</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($puedeVerMovimientos)
    <li class="rounded-xl overflow-hidden">
        <button @click="open === 3 ? open = null : open = 3"
            class="w-full flex items-center justify-between px-3 md:px-3 py-2.5 md:py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white text-sm md:text-base">
            <div class="flex items-center gap-2 md:gap-2">
                <i class="fas fa-chart-line text-center w-5 md:w-auto text-base"></i>
                <span class="sidebar-text">Movimientos</span>
            </div>
            <i :class="{ 'rotate-90': open === 3 }" class="fas fa-chevron-right transition-transform duration-300 text-xs md:text-sm"></i>
        </button>
        <ul x-show="open === 3" x-collapse class="px-3 md:px-4 pt-1 space-y-1 text-xs md:text-sm">
            @if(auth()->check() && auth()->user()->can('transferir-inventario'))
            <li>
                <a href="/inventarios"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-clipboard-list text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Inventario</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($puedeVerReportes)
    <li class="rounded-xl overflow-hidden">
        <button @click="open === 4 ? open = null : open = 4"
            class="w-full flex items-center justify-between px-3 md:px-3 py-2.5 md:py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white text-sm md:text-base">
            <div class="flex items-center gap-2 md:gap-2">
                <i class="fas fa-file-alt text-center w-5 md:w-auto text-base"></i>
                <span class="sidebar-text">Reportes</span>
            </div>
            <i :class="{ 'rotate-90': open === 4 }" class="fas fa-chevron-right transition-transform duration-300 text-xs md:text-sm"></i>
        </button>
        <ul x-show="open === 4" x-collapse class="px-3 md:px-4 pt-1 space-y-1 text-xs md:text-sm">
            @if(auth()->check() && auth()->user()->can('ver-presupuesto'))
            <li>
                <a href="/presupuesto"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-file-invoice text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Presupuesto</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-reportes'))
            <li>
                <a href="/reportes"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-book text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Reporteador</span>
                </a>
            </li>
            @endif
            @if(auth()->check() && auth()->user()->can('ver-informe'))
            <li>
                <a href="/informe"
                    class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-clipboard text-center w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Informes</span>
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($puedeVerAdministracion)
    <li class="rounded-xl overflow-hidden">
        <button @click="open === 5 ? open = null : open = 5"
            class="w-full flex items-center justify-between px-3 md:px-3 py-2.5 md:py-2 text-left text-[#101D49] font-medium hover:bg-[#101D49] hover:text-white transition rounded-xl dark:text-white text-sm md:text-base">
            <div class="flex items-center gap-2 md:gap-2">
                <i class="fas fa-file-invoice-dollar text-center w-5 md:w-auto text-base"></i>
                <span class="sidebar-text">Administracion</span>
            </div>
            <i :class="{ 'rotate-90': open === 5 }" class="fas fa-chevron-right transition-transform duration-300 text-xs md:text-sm"></i>
        </button>
        <ul x-show="open === 5" x-collapse class="px-3 md:px-4 pt-1 space-y-1 text-xs md:text-sm">
            <li>
                @if(auth()->check() && auth()->user()->can('ver-presupuestos'))
                <a href="/cortes" class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-money-check-alt w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Presupuestos Oficiales</span>
                </a>
                @endif
            </li>
            <li>
                @if(auth()->check() && auth()->user()->can('ver-facturas'))
                <a href="/facturas" class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white">
                    <i class="fas fa-money-check-alt w-4 md:w-auto text-sm"></i>
                    <span class="sidebar-text">Facturas</span>
                </a>
                @endif
            </li>
        </ul>
    </li>
    @endif

    <li>
        @if(auth()->check() && auth()->user()->can('ver-usuarios'))
        <a href="/usuarios"
            class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white text-sm md:text-base">
            <i class="fas fa-users text-center w-5 md:w-auto text-base"></i>
            <span class="font-medium sidebar-text">Usuarios</span>
        </a>
        @endif
    </li>

    <li>
        @if(auth()->check() && auth()->user()->can('ver-rol'))
        <a href="/roles"
            class="flex items-center gap-2 md:gap-2 no-underline text-[#101D49] hover:text-white hover:bg-[#101D49] px-3 md:px-2 py-2 md:py-1 rounded-lg transition dark:text-white text-sm md:text-base">
            <i class="fas fa-shield-alt text-center w-5 md:w-auto text-base"></i>
            <span class="font-medium sidebar-text">Roles</span>
        </a>
        @endif
    </li>
</ul>
{{-- Botón de notificaciones --}}
<div class="relative inline-block mt-6">
    <div id="btnNotif"
         class="select-none flex items-center gap-3 px-4 py-3 rounded-lg bg-gray-100 dark:bg-gray-800 cursor-pointer">

        <span class="material-symbols-outlined text-[#ff6600] leading-none" style="font-size: 24px;">
            notifications
        </span>

        <span id="badgeNotif" class="absolute bottom-2 left-4
                    bg-red-500 text-white
                    text-[10px] font-bold
                    rounded-full w-4 h-4
                    flex items-center justify-center"
                    style="display: none;">
        </span>

        <span class="select-none sidebar-text text-base font-medium">
            Notificaciones
        </span>
    </div>
</div>

{{-- Tooltip de notificaciones --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const boton = document.getElementById('btnNotif');

    // ── Crear el tooltip y montarlo en <body> ──
    const tooltip = document.createElement('div');
    tooltip.id = 'tooltipNotif';
    tooltip.style.cssText = 'display:none; position:fixed; z-index:99999; width:24rem; max-height:80vh; overflow-y:auto;';
    tooltip.className = 'bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg';

    tooltip.innerHTML = `
        <div class="font-semibold p-3 border-b bg-gray-100 dark:bg-gray-800 dark:border-gray-700" style="font-weight:600;">
            NOTIFICACIONES
        </div>
        <div class="p-3 space-y-3">
            <div class="text-sm text-gray-500 dark:text-gray-400 text-center py-2">Cargando notificaciones...</div>
        </div>
    `;

    document.body.appendChild(tooltip);

    // ── Helpers de LocalStorage para ocultación instantánea (Optimistic UI) ──
    window.marcarTicketComoLeido = function(ticketId) {
        if (!ticketId) return;
        let readTickets = JSON.parse(localStorage.getItem('readTickets') || '[]');
        if (!readTickets.includes(ticketId.toString())) {
            readTickets.push(ticketId.toString());
            localStorage.setItem('readTickets', JSON.stringify(readTickets));
        }
        actualizarNotificaciones();
    };

    window.marcarSolicitudComoLeida = function(solicitudId) {
        if (!solicitudId) return;
        let readSolicitudes = JSON.parse(localStorage.getItem('readSolicitudes') || '[]');
        if (!readSolicitudes.includes(solicitudId.toString())) {
            readSolicitudes.push(solicitudId.toString());
            localStorage.setItem('readSolicitudes', JSON.stringify(readSolicitudes));
        }
        actualizarNotificaciones();
    };

    window.marcarChatComoLeido = function(ticketId) {
        if (!ticketId) return;
        let readChats = JSON.parse(localStorage.getItem('readChats') || '[]');
        if (!readChats.includes(ticketId.toString())) {
            readChats.push(ticketId.toString());
            localStorage.setItem('readChats', JSON.stringify(readChats));
        }
        actualizarNotificaciones();
    };

    // ── Acción para abrir la notificación de un ticket ──
    window.abrirNotificacionTicket = function(ticketId) {
        window.marcarTicketComoLeido(ticketId);
        
        fetch('/tickets/marcar-leidos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ ticket_id: ticketId })
        }).catch(err => console.error("Error al marcar leídos en DB:", err));

        if (window.location.pathname.endsWith('/tickets')) {
            const card = document.querySelector(`[data-ticket-id="${ticketId}"]`);
            if (card) card.click();
        } else {
            window.location.href = `/tickets?ticket_id=${ticketId}`;
        }
    };

    // ── Acción para abrir la notificación de un chat ──
    window.abrirNotificacionChat = function(ticketId) {
        window.marcarChatComoLeido(ticketId);
        
        fetch('/tickets/marcar-leidos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ ticket_id: ticketId })
        }).catch(err => console.error("Error al marcar chat leído en DB:", err));

        if (window.location.pathname.endsWith('/tickets')) {
            const card = document.querySelector(`[data-ticket-id="${ticketId}"]`);
            if (card) card.click();
        } else {
            window.location.href = `/tickets?ticket_id=${ticketId}`;
        }
    };

    // ── Acción para abrir la notificación de una solicitud ──
    window.abrirNotificacionSolicitud = function(solicitudId) {
        window.marcarSolicitudComoLeida(solicitudId);
        
        if (window.location.pathname.endsWith('/tickets')) {
            const buttons = document.querySelectorAll('button');
            for (let btn of buttons) {
                const clickAttr = btn.getAttribute('@click') || btn.getAttribute('x-on:click');
                if (clickAttr && clickAttr.includes(`abrirModal(${solicitudId})`)) {
                    btn.click();
                    break;
                }
            }
        } else {
            window.location.href = `/tickets?solicitud_id=${solicitudId}`;
        }
    };

    // ── Auto-abrir desde URL si se redirecciona ──
    if (window.location.pathname.endsWith('/tickets')) {
        const urlParams = new URLSearchParams(window.location.search);
        const ticketId = urlParams.get('ticket_id');
        if (ticketId) {
            setTimeout(() => {
                const card = document.querySelector(`[data-ticket-id="${ticketId}"]`);
                if (card) card.click();
            }, 1000);
        }
        const solicitudId = urlParams.get('solicitud_id');
        if (solicitudId) {
            setTimeout(() => {
                const buttons = document.querySelectorAll('button');
                for (let btn of buttons) {
                    const clickAttr = btn.getAttribute('@click') || btn.getAttribute('x-on:click');
                    if (clickAttr && clickAttr.includes(`abrirModal(${solicitudId})`)) {
                        btn.click();
                        break;
                    }
                }
            }, 1000);
        }
    }

    // Interceptar clics globales en la app (Tarjetas del Kanban)
    document.addEventListener('click', function (e) {
        const card = e.target.closest('[data-ticket-id]');
        if (card) {
            const ticketId = card.getAttribute('data-ticket-id');
            if (ticketId) window.marcarTicketComoLeido(ticketId);
        }

        const btn = e.target.closest('button');
        if (btn) {
            const clickAttr = btn.getAttribute('@click') || btn.getAttribute('x-on:click');
            if (clickAttr && clickAttr.includes('abrirModal')) {
                const match = clickAttr.match(/abrirModal\(\s*(\d+)\s*\)/);
                if (match) window.marcarSolicitudComoLeida(match[1]);
            }
        }
    });

    // ── Función para actualizar las notificaciones mediante Polling AJAX ──
    // ── Función para actualizar las notificaciones mediante Polling AJAX ──
// ── Función para actualizar las notificaciones mediante Polling AJAX ──
   function actualizarNotificaciones() {
    const tooltip = document.getElementById('tooltipNotif');

    fetch('/notificaciones-panel')
        .then(res => res.json())
        .then(data => {
            const readTickets    = JSON.parse(localStorage.getItem('readTickets')    || '[]');
            const readSolicitudes = JSON.parse(localStorage.getItem('readSolicitudes') || '[]');
            const readChats      = JSON.parse(localStorage.getItem('readChats')      || '[]');

            let conteoNoLeidos = 0;
            let listaNotificaciones = [];

            // 1. TICKETS NUEVOS
            if (data.tickets_nuevos) {
                data.tickets_nuevos.forEach(t => {
                    if (!t || !t.TicketID) return;
                    if (readTickets.includes(t.TicketID.toString())) return; // ← filtro
                    conteoNoLeidos++;
                    listaNotificaciones.push({
                        timestamp: t.timestamp || 0,
                        html: `<div class="text-sm font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 p-1.5 rounded transition" 
                                onclick="abrirNotificacionTicket('${t.TicketID}')">
                            Se ha creado el ticket <strong>#${t.TicketID}</strong> por <strong>${t.empleado}</strong> (${t.created_at}).
                        </div>`
                    });
                });
            }

            // 2. SOLICITUDES PENDIENTES
            if (data.solicitudes_pendientes) {
                data.solicitudes_pendientes.forEach(s => {
                    if (!s || !s.SolicitudID) return;
                    if (readSolicitudes.includes(s.SolicitudID.toString())) return; // ← filtro
                    conteoNoLeidos++;
                    listaNotificaciones.push({
                        timestamp: s.timestamp || 0,
                        html: `<div class="text-sm font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 p-1.5 rounded transition" 
                                onclick="abrirNotificacionSolicitud('${s.SolicitudID}')">
                            Se ha creado la solicitud <strong>#${s.SolicitudID}</strong> por <strong>${s.empleado}</strong> (${s.created_at}).
                        </div>`
                    });
                });
            }

            // 3. MENSAJES DE CHAT
            if (data.mensajes_nuevos) {
                data.mensajes_nuevos.forEach(m => {
                    if (!m || m.ticket_id === undefined || m.ticket_id === null) return;
                    const chatTicketIdStr = String(m.ticket_id);
                    if (readChats.includes(chatTicketIdStr)) return; // ← filtro
                    conteoNoLeidos += parseInt(m.total || 1, 10);
                    listaNotificaciones.push({
                        timestamp: m.timestamp || 0,
                        html: `<div class="text-sm font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-700 p-1.5 rounded transition" 
                                onclick="abrirNotificacionChat('${chatTicketIdStr}')">
                            Tienes <strong>${m.total}</strong> mensaje(s) nuevo(s) en el chat del ticket <strong>#${chatTicketIdStr}</strong> (${m.created_at}).
                        </div>`
                    });
                });
            }

            // --- RENDERIZADO DEL BADGE ---
            const badge = document.getElementById('badgeNotif');
            if (badge) {
                if (conteoNoLeidos > 0) {
                    badge.textContent = conteoNoLeidos;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }

            // Ordenar por fecha desc
            listaNotificaciones.sort((a, b) => b.timestamp - a.timestamp);

            // Inyectar en el Tooltip
            if (tooltip) {
                const tooltipContenedor = tooltip.querySelector('.space-y-3');
                if (tooltipContenedor) {
                    let html = '';
                    listaNotificaciones.forEach(item => html += item.html);
                    if (html === '') {
                        html = `<div class="text-sm text-gray-500 dark:text-gray-400 text-center py-2">No hay notificaciones nuevas</div>`;
                    }
                    tooltipContenedor.innerHTML = html;
                }
            }
        })
        .catch(err => console.error("Error al procesar notificaciones:", err));
}

    // Inicializar polling
    actualizarNotificaciones();
    setInterval(actualizarNotificaciones, 5000);

    // ── Posicionar el tooltip respecto al botón ──
    function posicionarTooltip() {
        if (tooltip.style.display === 'none') return;

        const rect = boton.getBoundingClientRect();
        const tooltipW = tooltip.offsetWidth;
        const tooltipH = tooltip.offsetHeight;
        const viewW = window.innerWidth;
        const viewH = window.innerHeight;

        let top, left;

        left = rect.right + 8;
        if (left + tooltipW > viewW - 8) {
            left = rect.left - tooltipW - 8;
        }
        if (left < 8) left = 8;

        top = rect.top + (rect.height / 2) - (tooltipH / 2);
        if (top + tooltipH > viewH - 8) top = viewH - tooltipH - 8;
        if (top < 8) top = 8;

        tooltip.style.top  = top + 'px';
        tooltip.style.left = left + 'px';
    }

    // Toggle del tooltip (Sin alterar el badge)
    boton.addEventListener('click', function (e) {
        e.stopPropagation();
        const isHidden = tooltip.style.display === 'none';
        tooltip.style.display = isHidden ? 'block' : 'none';
        if (isHidden) {
            posicionarTooltip();
        }
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', function (e) {
        if (!tooltip.contains(e.target) && !boton.contains(e.target)) {
            tooltip.style.display = 'none';
        }
    });

    window.addEventListener('resize', posicionarTooltip);
});
</script>