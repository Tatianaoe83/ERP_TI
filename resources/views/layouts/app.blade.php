<!DOCTYPE html>
<html class>

<head>
    <script>
        if (
            localStorage.getItem('theme') === 'dark' ||
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
        ) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>ERP TI Proser</title>
    <link rel="icon" href="{!! asset('img/mantenimiento.ico') !!}" />
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Bootstrap 4.1.1 -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Ionicons -->
    <link href="//fonts.googleapis.com/css?family=Lato&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/@fortawesome/fontawesome-free/css/all.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/iziToast.min.css') }}">
    <link href="{{ asset('assets/css/sweetalert.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" type="text/css" />


    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Alpine.js x-cloak styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Estilos para el modal de tickets */
        .ticket-description {
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            max-width: 100%;
        }

        /* Estilos responsivos para el sidebar m?vil */
        @media (max-width: 1023px) {
            #sidebar {
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
            }

            #mobile-overlay {
                backdrop-filter: blur(2px);
            }

            /* Scrollbar personalizado para el sidebar m?vil */
            #sidebar::-webkit-scrollbar {
                width: 6px;
            }

            #sidebar::-webkit-scrollbar-track {
                background: transparent;
            }

            #sidebar::-webkit-scrollbar-thumb {
                background: rgba(0, 0, 0, 0.2);
                border-radius: 3px;
            }

            #sidebar::-webkit-scrollbar-thumb:hover {
                background: rgba(0, 0, 0, 0.3);
            }
        }

        /* Mejoras para tablets */
        @media (min-width: 768px) and (max-width: 1023px) {
            #sidebar {
                width: 260px;
            }
        }

        /* Asegurar que el contenido principal no se desborde */
        main {
            min-width: 0;
        }

        /* Estilos para sidebar colapsado */
        #sidebar.sidebar-collapsed .sidebar-text {
            opacity: 0;
            width: 0;
            max-width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 0.3s ease, width 0.3s ease, max-width 0.3s ease;
        }

        #sidebar.sidebar-collapsed .fas,
        #sidebar.sidebar-collapsed .fa {
            margin: 0 auto;
        }

        #sidebar.sidebar-collapsed a,
        #sidebar.sidebar-collapsed button {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }

        #sidebar.sidebar-collapsed .flex.items-center {
            justify-content: center !important;
        }

        #sidebar.sidebar-collapsed .flex.items-center.justify-between {
            justify-content: center !important;
        }

        /* Ocultar chevron cuando est? colapsado */
        #sidebar.sidebar-collapsed .fa-chevron-right {
            display: none !important;
        }

        /* Ajustar submen?s cuando est? colapsado */
        #sidebar.sidebar-collapsed ul[x-show] {
            display: none !important;
        }

        /* Asegurar que los iconos se centren cuando est? colapsado */
        #sidebar.sidebar-collapsed li > a,
        #sidebar.sidebar-collapsed li > button {
            position: relative;
        }

        /* Asegurar que los elementos li y botones principales sean visibles */
        #sidebar.sidebar-collapsed li {
            display: block !important;
            visibility: visible !important;
        }

        #sidebar.sidebar-collapsed li.rounded-xl {
            overflow: visible !important;
        }

        #sidebar.sidebar-collapsed li button {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        #sidebar.sidebar-collapsed li a {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Asegurar que el contenedor del menú no oculte elementos */
        #sidebar.sidebar-collapsed ul {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.5rem !important;
        }

        /* Ajustar el gap cuando está colapsado */
        #sidebar.sidebar-collapsed .flex.flex-col.gap-2,
        #sidebar.sidebar-collapsed .flex.flex-col.gap-3 {
            gap: 0.5rem !important;
        }

        /* Mejoras de accesibilidad y touch targets en m?vil */
        @media (max-width: 767px) {
            #sidebar a,
            #sidebar button {
                min-height: 44px;
                touch-action: manipulation;
            }
        }

        /* Estilos responsivos para DataTables */
        @media (max-width: 767px) {
            /* Hacer que las tablas se adapten mejor en m?vil */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border: none;
            }

            .dataTables_wrapper {
                overflow-x: auto;
            }

            /* Ajustar botones de DataTables en m?vil */
            .dataTables_wrapper .dt-buttons {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .dataTables_wrapper .dt-buttons .btn {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
                margin: 0.25rem;
            }

            /* Ocultar algunos elementos en m?vil */
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_info {
                font-size: 0.875rem;
            }

            /* Ajustar paginaci?n en m?vil */
            .dataTables_wrapper .dataTables_paginate {
                font-size: 0.875rem;
            }

            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 0.25rem 0.5rem;
                margin: 0.125rem;
            }

            /* Mejorar la visualizaci?n de tablas responsive (modo tarjeta) */
            table.dataTable.dtr-inline.collapsed > tbody > tr > td.child,
            table.dataTable.dtr-inline.collapsed > tbody > tr > th.child,
            table.dataTable.dtr-inline.collapsed > tbody > tr > td.dataTables_empty {
                padding: 0.5rem !important;
            }

            table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"] > td:first-child:before,
            table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"] > th:first-child:before {
                top: 0.75rem;
                left: 0.5rem;
            }

            /* Ajustar headers en m?vil */
            .table thead th {
                font-size: 0.875rem;
                padding: 0.5rem;
                white-space: nowrap;
            }

            .table tbody td {
                font-size: 0.875rem;
                padding: 0.5rem;
            }
        }

        /* Estilos para modo responsive de DataTables (tarjetas) */
        table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"] > td:first-child:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"] > th:first-child:before {
            background-color: #101D49;
            border: 2px solid white;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.3);
        }

        table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"].parent > td:first-child:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"].parent > th:first-child:before {
            background-color: #dc3545;
        }

        /* Mejorar contraste en modo oscuro */
        .dark table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"] > td:first-child:before,
        .dark table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"] > th:first-child:before {
            background-color: #4a5568;
            border-color: #fff;
        }

        .dark table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"].parent > td:first-child:before,
        .dark table.dataTable.dtr-inline.collapsed > tbody > tr[role="row"].parent > th:first-child:before {
            background-color: #e53e3e;
        }

        /* Ajustar el contenedor de tablas */
        @media (max-width: 991px) {
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
            }
        }
    </style>

    @stack('styles')

    @yield('page_css')
    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('web/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('web/css/components.css')}}">
    @yield('page_css')
    @yield('scripts')

    @yield('css')
    @stack('third_party_stylesheets')
    @livewireStyles
</head>
@livewireScripts

<body class="transition-colors duration-500 ease-in-out">

    <div id="app">
        <nav class="bg-white h-[60px] md:h-[60px] text-white text-white border-b border-b-gray-300 rounded-md">
            @include('layouts.header')
        </nav>
        <div class="flex flex-1 min-h-[calc(100vh-60px)] md:min-h-[calc(100vh-60px)]">
            <!-- Overlay para m?vil -->
            <div id="mobile-overlay" 
                class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300 lg:hidden"
                onclick="toggleMobileMenu()"></div>
            
            <!-- Sidebar responsivo -->
            <aside id="sidebar" 
                class="fixed lg:static inset-y-0 left-0 z-50 bg-white w-[280px] lg:w-[300px] border-r border-gray-300 rounded-md dark:!bg-[#101010] transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out h-[calc(100vh-70px)] md:h-[calc(100vh-80px)] lg:h-auto overflow-y-auto">
                @include('layouts.sidebar')
            </aside>

            <main class="flex-1 p-3 md:p-6 dark:bg-[#101010] w-full lg:w-auto">
                @yield('content')
            </main>
        </div>
        <!-- <footer class="main-footer">
            @include('layouts.footer')
        </footer> -->
    </div>

    @include('profile.change_password')
    @include('profile.edit_profile')




</body>


<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.nicescroll.js') }}"></script>
<script src="{{ asset('assets/js/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/js/iziToast.min.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>


<!-- Template JS File -->
<script src="{{ asset('web/js/stisla.js') }}"></script>
<script src="{{ asset('web/js/scripts.js') }}"></script>
<script src="{{ mix('assets/js/profile.js') }}"></script>
<script src="{{ mix('assets/js/custom/custom.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

@stack('third_party_scripts')


@yield('scripts')

<script type="text/javascript">
    $(function() {
        $('input, textarea').keyup(function() {

            this.value = this.value.toUpperCase();
        });
    });
</script>




<!-- Script para inicializar los dropdowns en todas las p?ginas -->
<script type="text/javascript">
    $(document).ready(function() {
        // Delegaci?n de eventos para manejar los dropdowns correctamente
        $(document).on('click', '.dropdown-toggle', function(e) {
            e.preventDefault();
            var $parent = $(this).parent();
            $('.dropdown').not($parent).removeClass('show'); // Cierra otros dropdowns
            $('.dropdown-menu').not($parent.find('.dropdown-menu')).removeClass('show');

            $parent.toggleClass('show');
            $parent.find('.dropdown-menu').toggleClass('show');
        });

        // Cerrar dropdowns al hacer clic fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.dropdown').length) {
                $('.dropdown').removeClass('show');
                $('.dropdown-menu').removeClass('show');
            }
        });

        // Asegurar que Select2 tambi?n se inicialice correctamente
        $(document).ready(function() {
            $('.jz').select2();
            $('.jz1').select2({
                dropdownParent: "#editModal",
                width: '100%'
            });
        });

        $('#myTab a').on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
        });

    });
</script>


<script type="text/javascript">
    let loggedInUser = @json(\Illuminate\Support\Facades\Auth::user());
    let loginUrl = '{{ route('login') }}';
    // Loading button plugin (removed from BS4)
    (function($) {
        $.fn.button = function(action) {
            if (action === 'loading' && this.data('loading-text')) {
                this.data('original-text', this.html()).html(this.data('loading-text')).prop('disabled', true);
            }
            if (action === 'reset' && this.data('original-text')) {
                this.html(this.data('original-text')).prop('disabled', false);
            }
        };
    }(jQuery));

    // Script global para hacer todas las tablas DataTables responsivas
    $(document).ready(function() {
        // Funci?n para reconfigurar tablas responsive
        function recalcResponsiveTables() {
            if (typeof $.fn.dataTable !== 'undefined' && $.fn.dataTable.isDataTable) {
                $('.dataTable').each(function() {
                    if ($.fn.DataTable.isDataTable(this)) {
                        var table = $(this).DataTable();
                        if (table.responsive && typeof table.responsive.recalc === 'function') {
                            try {
                                table.responsive.recalc();
                            } catch(e) {
                            }
                        }
                    }
                });
            }
        }

        // Recalcular despu?s de que se carguen las tablas
        setTimeout(recalcResponsiveTables, 1000);

        // Recalcular cuando se redimensiona la ventana
        var resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(recalcResponsiveTables, 250);
        });

        // Recalcular cuando cambia la orientaci?n del dispositivo
        window.addEventListener('orientationchange', function() {
            setTimeout(recalcResponsiveTables, 500);
        });
    });
</script>

<!-- Script para men? m?vil responsivo y colapso de sidebar -->
<script>
    function toggleMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            // Abrir men?
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            // Cerrar men?
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    // Funci?n para colapsar/expandir sidebar en desktop
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const toggleIcon = document.getElementById('sidebar-toggle-icon');
        const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
        
        if (isCollapsed) {
            // Expandir sidebar
            sidebar.classList.remove('sidebar-collapsed');
            sidebar.classList.remove('lg:w-[80px]');
            sidebar.classList.add('lg:w-[300px]');
            toggleIcon.classList.remove('fa-chevron-right');
            toggleIcon.classList.add('fa-chevron-left');
            // Guardar estado
            localStorage.setItem('sidebarCollapsed', 'false');
        } else {
            // Colapsar sidebar
            sidebar.classList.add('sidebar-collapsed');
            sidebar.classList.remove('lg:w-[300px]');
            sidebar.classList.add('lg:w-[80px]');
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');
            // Guardar estado
            localStorage.setItem('sidebarCollapsed', 'true');
        }
    }

    // Event listener para el bot?n hamburguesa
    document.addEventListener('DOMContentLoaded', function() {
        const menuButton = document.getElementById('mobile-menu-button');
        if (menuButton) {
            menuButton.addEventListener('click', toggleMobileMenu);
        }

        // Event listener para el bot?n de colapso en desktop
        const sidebarToggleButton = document.getElementById('sidebar-toggle-button');
        if (sidebarToggleButton) {
            sidebarToggleButton.addEventListener('click', toggleSidebar);
        }

        // Restaurar estado del sidebar al cargar la p?gina
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
        if (sidebarCollapsed === 'true' && window.innerWidth >= 1024) {
            const sidebar = document.getElementById('sidebar');
            const toggleIcon = document.getElementById('sidebar-toggle-icon');
            sidebar.classList.add('sidebar-collapsed');
            sidebar.classList.remove('lg:w-[300px]');
            sidebar.classList.add('lg:w-[80px]');
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');
        }

        // Cerrar men? al hacer clic en un enlace (solo en m?vil)
        const menuLinks = document.querySelectorAll('#sidebar a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 1024) {
                    setTimeout(toggleMobileMenu, 150);
                }
            });
        });

        // Cerrar men? al redimensionar ventana si es desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('mobile-overlay');
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            } else {
                // En m?vil, restaurar ancho normal
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.classList.remove('lg:w-[80px]');
                sidebar.classList.add('lg:w-[300px]');
            }
        });
    });
</script>

</html>