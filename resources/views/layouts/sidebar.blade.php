<aside id="sidebar-wrapper">
    <div class="sidebar-brand">
        <a href="{{ url('/') }}" class="block">
            <img src="{{ asset('img/logo.png') }}" alt="Infyom Logo"
                class="app-header-logo mx-auto mt-4" width="50%" />
        </a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
        <a href="{{ url('/') }}" class="block">
            <img src="{{ asset('img/LogoAzul.png') }}" alt=""
                class="w-[35px] mx-auto mt-3" width="30%" />
        </a>
    </div>
    <ul class="sidebar-menu">
        @include('layouts.menu')
    </ul>
</aside>