<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>ERP TI Proser</title>
    <link rel="icon" href="{!! asset('img/mantenimiento.ico') !!}" />
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 4.1.1 -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Ionicons -->
    <link href="//fonts.googleapis.com/css?family=Lato&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/css/@fortawesome/fontawesome-free/css/all.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ asset('assets/css/iziToast.min.css') }}">
    <link href="{{ asset('assets/css/sweetalert.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" type="text/css" />


    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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

<body>

    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <div class="navbar-bg"></div>
            <nav class="navbar navbar-expand-lg main-navbar">
                @include('layouts.header')

            </nav>
            <div class="main-sidebar main-sidebar-postion">
                @include('layouts.sidebar')
            </div>
            <!-- Main Content -->
            <div class="main-content">
                @yield('content')
            </div>
            <footer class="main-footer">
                @include('layouts.footer')
            </footer>
        </div>
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




<!-- Script para inicializar los dropdowns en todas las páginas -->
<script type="text/javascript">
    $(document).ready(function() {
        // Delegación de eventos para manejar los dropdowns correctamente
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

        // Asegurar que Select2 también se inicialice correctamente
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
    let loggedInUser = @json(\Illuminate\ Support\ Facades\ Auth::user());
    let loginUrl = '{{ route('
    login ') }}';
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
</script>

</html>