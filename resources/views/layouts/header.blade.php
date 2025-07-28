<div class="px-4 py-3 flex justify-between items-center dark:bg-[#101010]">
    <div class="flex items-center gap-4">
        <div class="relative h-11 w-40 block">
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
        <div class="flex items-center gap-4">
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
                class="w-[46px] h-[46px] flex items-center justify-center bg-[#f1f5f9] text-[#101D49] rounded-full transition ring-1 ring-gray-300 dark:bg-white dark:text-[#101010] hover:scale-125 transition">
                <i class="fas fa-user text-[17px]"></i>
            </button>
        </div>


        <div id="dropdownmenu"
            class="hidden absolute right-0 mt-2 bg-white border border-gray-200 rounded-md z-50 w-64 transition-all duration-300 opacity-0 dark:rounded-md">
            <div
                class="flex items-center justify-between px-3 py-2 border-b border-gray-300 dark:bg-[#101010] dark:border-[#444]">
                <button class="text-[#101D49] hover:scale-125 transition dark:text-white" onclick="setTheme('light')">
                    <i class="fas fa-sun"></i>
                </button>
                <button class="text-[#101D49] hover:scale-125 transition dark:text-white" onclick="setTheme('dark')">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="text-[#101D49] hover:scale-125 transition dark:text-white" onclick="setTheme('system')">
                    <i class="fas fa-desktop"></i>
                </button>
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

    function setTheme(mode) {
        if (mode === 'dark') {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else if (mode === 'light') {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    }
</script>