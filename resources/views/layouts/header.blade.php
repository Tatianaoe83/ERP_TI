<div class="px-3 md:px-4 py-2 md:py-3 flex justify-between items-center dark:bg-[#101010]">
    <div class="flex items-center gap-2 md:gap-4">
        <!-- Botón hamburguesa para móvil -->
        <button id="mobile-menu-button" 
            class="lg:hidden w-10 h-10 flex items-center justify-center text-[#101D49] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors active:scale-95">
            <i class="fas fa-bars text-xl"></i>
        </button>
        
        <!-- Botón para colapsar sidebar en desktop -->
        <button id="sidebar-toggle-button" 
            class="hidden lg:flex w-10 h-10 items-center justify-center text-[#101D49] dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors active:scale-95">
            <i id="sidebar-toggle-icon" class="fas fa-chevron-left text-xl"></i>
        </button>
        
        <div class="relative h-9 md:h-11 w-32 md:w-40 block">
            <a href="{{ url('/') }}">
                <img src="{{ asset('img/LogoAzul2.png') }}"
                    alt="Logo claro"
                    class="h-full w-auto object-contain absolute top-0 left-0 transition hover:cursor-pointer hover:scale-105 dark:hidden" />
            </a>

            <a href="{{ url('/') }}">
                <img src="{{ asset('img/LogoBlanco.png') }}" alt="Logo oscuro"
                    class="h-full w-auto object-contain absolute top-0 left-0 transition hover:cursor-pointer hover:scale-105 hidden dark:block" />
            </a>
        </div>
    </div>

    @auth
    <div class="relative inline-block text-left dark:bg-[#101010]">
        <div class="flex items-center gap-2 md:gap-3">
            {{-- Notificaciones en header --}}
            @can('tickets.notificaciones')
            <button type="button" id="btnNotif"
                class="relative inline-flex items-center justify-center w-10 h-10 md:w-11 md:h-11 rounded-full
                       text-[#ff6600] bg-orange-50 hover:bg-orange-100
                       dark:bg-orange-950/40 dark:hover:bg-orange-900/50
                       ring-1 ring-orange-200 dark:ring-orange-800
                       transition active:scale-95 select-none"
                title="Notificaciones"
                aria-label="Notificaciones">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-6 h-6 md:w-7 md:h-7"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11
                           a6.002 6.002 0 00-4-5.659V4
                           a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159
                           c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1
                           a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span id="badgeNotif"
                    class="absolute -top-1 -right-1
                           bg-red-500 text-white
                           text-xs md:text-sm font-extrabold leading-none
                           rounded-full min-w-[22px] h-[22px] md:min-w-[26px] md:h-[26px] px-1.5
                           flex items-center justify-center
                           shadow-md ring-2 ring-white dark:ring-[#101010]
                           animate-pulse"
                    style="display: none;">
                </span>
            </button>
            @endcan

            <div class="hidden md:flex flex-col justify-center text-right leading-tight">
                <span class="text-[15px] font-semibold text-[#101D49] dark:text-white">
                    {{ Auth::user()->name }}
                </span>
                @if(session('sistema_activo'))
                <span class="text-[13px] text-gray-600 dark:text-gray-400 font-medium">
                    Sistema: {{ ucfirst(session('sistema_activo')) }}
                </span>
                @endif
            </div>

            <button onclick="toggleDropdown()" id="dropdownbutton"
                class="w-10 h-10 md:w-[46px] md:h-[46px] flex items-center justify-center bg-[#f1f5f9] text-[#101D49] rounded-full transition ring-1 ring-gray-300 dark:bg-white dark:text-[#101010] hover:scale-110 md:hover:scale-125 active:scale-95">
                <i class="fas fa-user text-base md:text-[17px]"></i>
            </button>
        </div>


        <div id="dropdownmenu"
            class="hidden absolute right-0 mt-2 bg-white border border-gray-200 rounded-md z-[60] w-56 md:w-64 transition-all duration-300 opacity-0 dark:rounded-md dark:bg-[#1a1a1a] dark:border-gray-700">
            <div
                class="flex items-center justify-between px-3 py-2 border-b border-gray-300 dark:bg-[#101010] dark:border-[#444]">
                <button class="text-[#101D49] hover:scale-125 transition dark:text-white" onclick="setTheme('light')">
                    <i class="fas fa-sun"></i>
                </button>
                <button class="text-[#101D49] hover:scale-125 transition dark:text-white" onclick="setTheme('dark')">
                    <i class="fas fa-moon"></i>
                </button>
                <!--<button class="text-[#101D49] hover:scale-125 transition dark:text-white" onclick="setTheme('system')">
                    <i class="fas fa-desktop"></i>
                </button>-->
            </div>

            <a href="{{ url('logout') }}"
                onclick="event.preventDefault(); localStorage.clear(); document.getElementById('logout-form').submit();"
                class="no-underline flex items-center gap-2 px-2 py-3 text-sm text-[#101D49] hover:scale-105 hover:bg-red-500 hover:text-white hover:rounded-md dark:bg-[#101010] dark:hover:bg-red-500">
                <i class="fas fa-sign-out-alt dark:text-white"></i>
                <span class="dark:text-white">Cerrar sesión</span>
            </a>
            <form id="logout-form" action="{{ url('/logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>

    @can('tickets.notificaciones')
        @include('layouts.notifications_blade_sidebar')
    @endcan
    @endauth
</div>

<script>
    function toggleDropdown() {
        const menu = document.getElementById("dropdownmenu");

        if (menu.classList.contains("hidden")) {
            menu.classList.remove("hidden");
            void menu.offsetWidth;
            menu.classList.remove("opacity-0", "scale-95");
            menu.classList.add("opacity-100", "scale-100");
        } else {
            menu.classList.remove("opacity-100", "scale-100");
            menu.classList.add("opacity-0", "scale-95");

            setTimeout(() => {
                menu.classList.add("hidden");
            }, 300);
        }
    }


    document.addEventListener("click", function(e) {
        const button = document.getElementById("dropdownbutton");
        const menu = document.getElementById("dropdownmenu");
        if (!button.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.add("hidden");
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        const theme = localStorage.getItem('theme');
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (theme === 'light') {
            document.documentElement.classList.remove('dark');
        }
    });

    let _themeTransitionTimer = null;

    function applyTheme(mode) {
        const root = document.documentElement;
        if (mode === 'dark') {
            root.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else if (mode === 'light') {
            root.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    }

    function setTheme(mode) {
        const root = document.documentElement;

        // Crossfade GPU: un solo snapshot, fluido sin importar el tamaño del DOM
        if (document.startViewTransition) {
            document.startViewTransition(() => applyTheme(mode));
            return;
        }

        // Fallback (navegadores sin View Transitions): transición de color CSS
        root.classList.add('theme-transition');
        applyTheme(mode);
        clearTimeout(_themeTransitionTimer);
        _themeTransitionTimer = setTimeout(() => {
            root.classList.remove('theme-transition');
        }, 320);
    }
</script>