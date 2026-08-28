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
    @php
        $nombreUsuario = trim((string) Auth::user()->name);
        $nombreCorto = \Illuminate\Support\Str::of(mb_strtolower($nombreUsuario))->title()->limit(28, '…');
    @endphp
    <div class="relative inline-block text-left">
        <div class="flex items-center gap-1.5 md:gap-2">
            @can('tickets.notificaciones')
            <button type="button" id="btnNotif"
                class="relative inline-flex items-center justify-center w-9 h-9 rounded-lg
                       text-slate-500 hover:text-[#101D49] hover:bg-slate-100
                       dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/10
                       transition-colors"
                title="Notificaciones"
                aria-label="Notificaciones">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11
                           a6.002 6.002 0 00-4-5.659V4
                           a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159
                           c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1
                           a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span id="badgeNotif"
                    class="absolute top-0.5 right-0.5
                           bg-[#f87171] text-white
                           text-[10px] font-semibold leading-none
                           rounded-full min-w-[16px] h-4 px-1
                           flex items-center justify-center"
                    style="display: none;"></span>
            </button>
            @endcan

            <button type="button" onclick="toggleDropdown()" id="dropdownbutton"
                class="flex items-center gap-2 max-w-[220px] h-9 pl-1 pr-2 rounded-lg
                       text-[#101D49] hover:bg-slate-100
                       dark:text-white dark:hover:bg-white/10
                       transition-colors">
                <span class="w-7 h-7 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-300">
                    <i class="fas fa-user text-[11px]"></i>
                </span>
                <span class="hidden md:block text-[13px] font-medium truncate leading-none">
                    {{ $nombreCorto }}
                </span>
            </button>
        </div>

        <div id="dropdownmenu"
            class="header-user-menu hidden absolute right-0 mt-1.5 z-[60] w-56
                   rounded-xl shadow-lg
                   transition-opacity duration-150 opacity-0">
            @if(session('sistema_activo'))
            <p class="header-user-menu__hint px-3 pt-2.5 pb-1 text-[11px] truncate">{{ ucfirst(session('sistema_activo')) }}</p>
            @endif
            <div class="header-theme-switch">
                <button type="button" id="theme-btn-light" class="header-theme-btn" onclick="setTheme('light')" title="Modo claro" aria-label="Modo claro">
                    <i class="fas fa-sun"></i>
                </button>
                <button type="button" id="theme-btn-dark" class="header-theme-btn" onclick="setTheme('dark')" title="Modo oscuro" aria-label="Modo oscuro">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <a href="{{ route('logout') }}"
                data-full-load="1"
                onclick="event.preventDefault(); event.stopImmediatePropagation(); this.style.pointerEvents='none'; try { if (window.Livewire && typeof Livewire.stop === 'function') Livewire.stop(); } catch (e) {} try { localStorage.clear(); } catch (e) {} document.getElementById('logout-form').submit();"
                class="header-user-menu__logout no-underline">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar sesión</span>
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

<style>
    .header-user-menu {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        padding: 0.25rem 0 0.4rem;
    }

    .header-user-menu__hint {
        margin: 0;
        color: #94a3b8;
    }

    .header-theme-switch {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.85rem;
        padding: 0.7rem 1rem 0.8rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .header-theme-btn {
        appearance: none;
        -webkit-appearance: none;
        width: 2.4rem;
        height: 2.4rem;
        margin: 0;
        padding: 0;
        border: 1px solid transparent;
        border-radius: 0.6rem;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        line-height: 1;
        box-shadow: none;
        outline: none;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .header-theme-btn:hover {
        background: #f1f5f9;
        color: #101D49;
    }

    .header-theme-btn.is-active {
        background: #eef2ff;
        border-color: #c7d2fe;
        color: #101D49;
    }

    .header-user-menu__logout {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        margin: 0.4rem 0.45rem 0.15rem;
        padding: 0.65rem 0.8rem;
        border-radius: 0.55rem;
        font-size: 0.8125rem;
        font-weight: 500;
        color: #475569;
    }

    .header-user-menu__logout:hover {
        background: #f8fafc;
        color: #101D49;
        text-decoration: none;
    }

    .dark .header-user-menu {
        background: #1C1F26;
        border-color: #2A2F3A;
        box-shadow: 0 14px 36px rgba(0, 0, 0, 0.5);
    }

    .dark .header-user-menu__hint {
        color: #94a3b8;
    }

    .dark .header-theme-switch {
        border-bottom-color: #2A2F3A;
    }

    .dark .header-theme-btn {
        color: #94a3b8;
    }

    .dark .header-theme-btn:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .dark .header-theme-btn.is-active {
        background: rgba(255, 255, 255, 0.12);
        border-color: #3d4654;
        color: #ffffff;
    }

    .dark .header-user-menu__logout {
        color: #cbd5e1;
    }

    .dark .header-user-menu__logout:hover {
        background: rgba(255, 255, 255, 0.06);
        color: #ffffff;
        text-decoration: none;
    }
</style>

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
        if (button && menu && !button.contains(e.target) && !menu.contains(e.target)) {
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
        if (typeof syncThemeButtons === 'function') syncThemeButtons();
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
        syncThemeButtons();
    }

    function syncThemeButtons() {
        const isDark = document.documentElement.classList.contains('dark');
        const lightBtn = document.getElementById('theme-btn-light');
        const darkBtn = document.getElementById('theme-btn-dark');
        if (lightBtn) lightBtn.classList.toggle('is-active', !isDark);
        if (darkBtn) darkBtn.classList.toggle('is-active', isDark);
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

    if (typeof syncThemeButtons === 'function') {
        syncThemeButtons();
    }
</script>