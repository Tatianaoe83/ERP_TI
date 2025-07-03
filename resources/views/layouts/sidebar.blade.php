<aside id="sidebar-wrapper" class="bg-gray-900 text-white w-64 min-h-screen flex flex-col items-center py-6">

    <div class="sidebar-brand mb-6">
        <a href="{{ url('/') }}" class="block">
            <img src="{{ asset('img/logo.png') }}" alt="Infyom Logo"
                class="app-header-logo mx-auto mt-4" width="50%"/>
        </a>
    </div>

    <div class="sidebar-brand sidebar-brand-sm hidden">
        <a href="{{ url('/') }}" class="block">
            <img src="{{ asset('img/logo2.png') }}" alt=""
                class="w-[45px] mx-auto mt-3" width="50%"/>
        </a>
    </div>

    <ul class="sidebar-menu w-full px-4 mt-4">
        @include('layouts.menu')
    </ul>
</aside>