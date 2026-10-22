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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@500;600;700&family=Fira+Sans:wght@300;400;500;600;700&display=swap">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <!-- Bootstrap 4.1.1 -->
    <link href="{{ mix('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ mix('assets/css/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{ mix('assets/css/iziToast.min.css') }}">
    <link href="{{ mix('assets/css/sweetalert.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ mix('assets/css/select2.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Alpine.js x-cloak styles -->
    <style>
        [x-cloak] { display: none !important; }

        .vista-switch__btn.is-vista-active {
            background-color: #2563EB !important;
            color: #fff !important;
        }

        #app-topbar {
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            height: 3px;
            z-index: 80;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        #app-topbar.is-on {
            opacity: 1;
        }
        #app-topbar > span {
            display: block;
            height: 100%;
            width: 0;
            background: #101D49;
            box-shadow: 0 0 10px rgba(16, 29, 73, 0.35);
            transition: width 0.2s ease-out;
        }
        .dark #app-topbar > span {
            background: #60a5fa;
            box-shadow: 0 0 10px rgba(96, 165, 250, 0.45);
        }

        a.app-tabs__btn {
            text-decoration: none;
        }

        /* SweetAlert siempre por encima de los modales (z-[9999]) */
        .swal2-container { z-index: 100000 !important; }

        /* Labels de formularios: Stisla usa #34395e y no se ve en modo oscuro */
        html.dark .form-group > label,
        html.dark .form-group .control-label,
        html.dark .index-page__filters label,
        html.dark .mant-filters label,
        html.dark .modal-content label,
        html.dark .modal-body label,
        html.dark .mant-modal-card label {
            color: #e5e7eb !important;
        }
        html.dark .text-muted,
        html.dark .small.text-muted {
            color: #9ca3af !important;
        }

        /* Select2 (el dropdown se pega a body, fuera de la página) */
        html.dark .select2-container--default .select2-selection--single,
        html.dark .select2-container--default .select2-selection--multiple {
            background-color: #111827 !important;
            border-color: #374151 !important;
            color: #f9fafb !important;
        }
        html.dark .select2-container--default .select2-selection--single .select2-selection__rendered,
        html.dark .select2-container--default .select2-selection--single .select2-selection__placeholder,
        html.dark .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            color: #e5e7eb !important;
        }
        html.dark .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #9ca3af transparent transparent transparent !important;
        }
        html.dark .select2-dropdown,
        html.dark .select2-container--default .select2-dropdown {
            background-color: #1f2937 !important;
            border-color: #374151 !important;
            color: #f9fafb !important;
        }
        html.dark .select2-search--dropdown .select2-search__field,
        html.dark .select2-search__field,
        html.dark .select2-container--default .select2-search--dropdown .select2-search__field,
        html.dark .select2-container--default .select2-search--inline .select2-search__field {
            background-color: #111827 !important;
            color: #f9fafb !important;
            border: 1px solid #374151 !important;
            outline: none !important;
        }
        html.dark .select2-search--dropdown .select2-search__field::placeholder,
        html.dark .select2-search__field::placeholder {
            color: #9ca3af !important;
        }
        html.dark .select2-results__option {
            color: #e5e7eb !important;
            background-color: transparent !important;
        }
        html.dark .select2-results__option[aria-selected="true"] {
            background-color: #374151 !important;
            color: #fff !important;
        }
        html.dark .select2-container--default .select2-results__option--highlighted[aria-selected],
        html.dark .select2-container--default .select2-results__option--highlighted,
        html.dark .select2-results__option--highlighted {
            background-color: #15803d !important;
            color: #f8fafc !important;
        }
        html.dark .select2-results__message {
            color: #9ca3af !important;
        }

        .index-search {
            position: relative;
        }
        .index-search__icon {
            position: absolute;
            left: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.75rem;
            line-height: 1;
            pointer-events: none;
            z-index: 2;
            color: #94a3b8;
        }
        html.dark .index-search__icon {
            color: #9ca3af;
        }
        .index-search .form-control,
        .index-page__filters .index-search .form-control,
        .index-page .dataTables_filter input,
        .index-page__card .dataTables_filter input,
        html.dark .index-search .form-control,
        html.dark .index-page .dataTables_filter input,
        html.dark .index-page__card .dataTables_filter input,
        html.dark .index-page .dataTables_wrapper .dataTables_filter input {
            padding-left: 2.35rem !important;
        }

        #app-download-overlay {
            position: fixed;
            inset: 0;
            z-index: 200000;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.55);
            padding: 1.25rem;
        }
        #app-download-overlay[hidden] {
            display: none !important;
        }
        .app-download-card {
            background: #fff;
            color: #111827;
            border-radius: 1rem;
            padding: 1.75rem 1.6rem 1.5rem;
            text-align: center;
            min-width: 260px;
            max-width: 22rem;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
        }
        .dark .app-download-card {
            background: #1f2937;
            color: #f9fafb;
        }
        .app-download-spinner {
            width: 3rem;
            height: 3rem;
            margin: 0 auto 1rem;
            border: 4px solid #e5e7eb;
            border-top-color: #2563EB;
            border-radius: 50%;
            animation: app-download-spin 0.75s linear infinite;
        }
        .dark .app-download-spinner {
            border-color: #374151;
            border-top-color: #60a5fa;
        }
        .app-download-card p {
            margin: 0;
        }
        .app-download-card .app-download-hint {
            margin-top: 0.4rem;
            font-size: 0.8rem;
            color: #6b7280;
        }
        .dark .app-download-card .app-download-hint {
            color: #9ca3af;
        }
        @keyframes app-download-spin {
            to { transform: rotate(360deg); }
        }
        
        /* Estilos para el modal de tickets */
        .ticket-description {
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            max-width: 100%;
        }

        /* Modal Ajustar métricas — mismo patrón que catálogos / inventario */
        .metricas-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(17, 24, 39, 0.72);
        }
        .metricas-modal__dialog {
            background: #fff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 0.85rem !important;
            overflow: hidden;
        }
        html.dark .metricas-modal__dialog {
            background: #1C1F26 !important;
            border-color: #2A2F3A !important;
        }
        .metricas-modal__header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 1rem;
            width: 100%;
            padding: 0.85rem 1.1rem !important;
            background: #101D49 !important;
            border-bottom: 1px solid #0c1638 !important;
        }
        html.dark .metricas-modal__header {
            background: #161920 !important;
            border-bottom-color: #2A2F3A !important;
        }
        .metricas-modal__title {
            margin: 0 !important;
            flex: 1 1 auto;
            min-width: 0;
            color: #fff !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
        }
        .metricas-modal__close {
            flex: 0 0 auto;
            margin-left: auto !important;
            width: 1.75rem;
            height: 1.75rem;
            padding: 0;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #fff !important;
            opacity: 0.85;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .metricas-modal__close:hover,
        .metricas-modal__close:focus {
            opacity: 1;
            background: rgba(255, 255, 255, 0.12);
        }
        .metricas-modal__body {
            background: #fff !important;
        }
        html.dark .metricas-modal__body {
            background: #1C1F26 !important;
        }
        .metricas-modal__footer {
            background: #f9fafb !important;
            border-top: 1px solid #e5e7eb !important;
        }
        html.dark .metricas-modal__footer {
            background: #161920 !important;
            border-top-color: #2A2F3A !important;
            color: #d1d5db !important;
        }
        .metricas-modal__body .form-control {
            background: #fff !important;
            border: 1px solid #e5e7eb !important;
            color: #111827 !important;
            border-radius: 0.55rem !important;
            min-height: 2.4rem;
        }
        html.dark .metricas-modal__body .form-control {
            background: #111827 !important;
            border-color: #374151 !important;
            color: #f9fafb !important;
        }
        html.dark .metricas-modal__body .form-control:focus {
            border-color: #2563EB !important;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25) !important;
        }
        html.dark .metricas-modal .index-page__note {
            background: rgba(30, 58, 95, 0.35) !important;
            border-color: #1e3a5f !important;
        }
        html.dark .metricas-modal .index-page__note p,
        html.dark .metricas-modal .index-table tbody td {
            color: #e5e7eb !important;
        }
        html.dark .metricas-modal .index-table thead th {
            color: #9ca3af !important;
            border-bottom-color: #374151 !important;
        }
        .metricas-modal .index-page__note {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
            background: #eff6ff !important;
            border: 1px solid #dbeafe !important;
            border-radius: 0.85rem;
        }
        .metricas-modal .index-page__btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #101D49 !important;
            color: #fff !important;
            border: none !important;
            border-radius: 0.6rem;
            padding: 0.6rem 1.05rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
        }
        .metricas-modal .index-page__btn-primary:hover {
            background: #0c1638 !important;
            color: #fff !important;
        }
        .metricas-modal .index-page__btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .metricas-modal .index-page__btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #fff !important;
            color: #374151 !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 0.6rem;
            padding: 0.6rem 1.05rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
        }
        html.dark .metricas-modal .index-page__btn-secondary {
            background: #111827 !important;
            border-color: #374151 !important;
            color: #e5e7eb !important;
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
                width: 240px;
            }
        }

        /* Asegurar que el contenido principal no se desborde */
        main {
            min-width: 0;
        }

        /* ——— Sidebar UX ——— */
        #sidebar .sidebar-section-label {
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9ca3af;
            padding: 0.9rem 0.75rem 0.3rem;
            line-height: 1;
        }

        .dark #sidebar .sidebar-section-label {
            color: #6b7280;
        }

        #sidebar .sidebar-link,
        #sidebar .sidebar-btn {
            transition: background-color 0.15s ease, color 0.15s ease;
            line-height: 1.25;
            color: #374151;
            font-weight: 500;
        }

        #sidebar .sidebar-link:hover,
        #sidebar .sidebar-btn:hover {
            background-color: #f3f4f6;
            color: #111827;
        }

        #sidebar .sidebar-link.is-active {
            background-color: #eff6ff;
            color: #1d4ed8;
            font-weight: 600;
        }

        #sidebar .sidebar-link.is-active .sidebar-ico {
            color: #2563eb;
        }

        #sidebar .sidebar-btn.is-open {
            background-color: transparent;
            color: #111827;
        }

        .dark #sidebar .sidebar-link,
        .dark #sidebar .sidebar-btn {
            color: #e5e7eb;
        }

        .dark #sidebar .sidebar-link:hover,
        .dark #sidebar .sidebar-btn:hover {
            background-color: rgba(255, 255, 255, 0.06);
            color: #fff;
        }

        .dark #sidebar .sidebar-link.is-active {
            background-color: rgba(37, 99, 235, 0.18);
            color: #93c5fd;
        }

        .dark #sidebar .sidebar-btn.is-open {
            background-color: transparent;
            color: #fff;
        }

        #sidebar .sidebar-ico {
            width: 1.15rem;
            min-width: 1.15rem;
            text-align: center;
            flex-shrink: 0;
            font-size: 0.9rem;
            line-height: 1;
            color: #6b7280;
        }

        #sidebar .sidebar-link:hover .sidebar-ico,
        #sidebar .sidebar-btn:hover .sidebar-ico {
            color: inherit;
        }

        #sidebar .sidebar-ico-sm {
            font-size: 0.78rem;
            width: 1rem;
            min-width: 1rem;
        }

        #sidebar .sidebar-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        #sidebar .sidebar-sub {
            position: relative;
            margin: 0.15rem 0 0.35rem 1.15rem;
            padding: 0.15rem 0 0.15rem 0.7rem;
            border-left: 1px solid #e5e7eb;
        }

        .dark #sidebar .sidebar-sub {
            border-left-color: #374151;
        }

        #sidebar .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #sidebar .sidebar-chevron {
            font-size: 0.65rem;
            color: #9ca3af;
        }

        /* Estilos para sidebar colapsado */
        #sidebar.sidebar-collapsed .sidebar-section-label {
            display: none !important;
        }

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

        #sidebar.sidebar-collapsed li.rounded-xl,
        #sidebar.sidebar-collapsed li.rounded-lg {
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
        #sidebar.sidebar-collapsed .flex.flex-col.gap-3,
        #sidebar.sidebar-collapsed .sidebar-nav {
            gap: 0.35rem !important;
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

        /* Kanban dark: tarjetas oscuras, chips de tiempo sin tocar */
        .dark .kanban-root {
            background-color: #0F1116 !important;
        }

        html.dark .kanban-root .bg-gray-100\/80,
        html.dark .kanban-root .bg-gray-100 {
            background-color: #161920 !important;
        }

        html.dark .kanban-root .bg-white\/70 {
            background-color: #1C1F26 !important;
        }

        html.dark .kanban-root .ticket-kanban-card,
        html.dark .kanban-root .group.bg-white {
            background-color: #1C1F26 !important;
            border-color: #2A2F3A !important;
        }

        html.dark .kanban-root .ticket-kanban-card > p,
        html.dark .kanban-root .ticket-kanban-card .text-gray-900 {
            color: #f3f4f6 !important;
        }

        html.dark .kanban-root .ticket-kanban-card .text-gray-700,
        html.dark .kanban-root .ticket-kanban-card .text-gray-500,
        html.dark .kanban-root .ticket-kanban-card .text-gray-400,
        html.dark .kanban-root .ticket-kanban-card .text-gray-600 {
            color: #d1d5db !important;
        }

        /* Chips de tiempo / SLA en tarjetas (Tailwind no ve las clases dark: del PHP) */
        .kpi-chip {
            border-radius: 0.5rem;
            padding: 0.5rem 0.65rem;
            border: 1px solid;
        }
        .kpi-chip--normal,
        .kpi-chip--cumplido {
            background: #f0fdf4;
            color: #166534;
            border-color: #bbf7d0;
        }
        .kpi-chip--por_vencer,
        .kpi-chip--en_riesgo {
            background: #fefce8;
            color: #a16207;
            border-color: #fde68a;
        }
        .kpi-chip--agotado,
        .kpi-chip--vencido,
        .kpi-chip--incumplido {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        .kpi-chip--en_tiempo {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }
        html.dark .kpi-chip--normal,
        html.dark .kpi-chip--cumplido {
            background: rgba(22, 163, 74, 0.18) !important;
            border-color: rgba(74, 222, 128, 0.35) !important;
            color: #86efac !important;
        }
        html.dark .kpi-chip--normal *,
        html.dark .kpi-chip--cumplido * {
            color: #86efac !important;
        }
        html.dark .kpi-chip--por_vencer,
        html.dark .kpi-chip--en_riesgo {
            background: rgba(202, 138, 4, 0.18) !important;
            border-color: rgba(250, 204, 21, 0.35) !important;
            color: #fde68a !important;
        }
        html.dark .kpi-chip--por_vencer *,
        html.dark .kpi-chip--en_riesgo * {
            color: #fde68a !important;
        }
        html.dark .kpi-chip--agotado,
        html.dark .kpi-chip--vencido,
        html.dark .kpi-chip--incumplido {
            background: rgba(185, 28, 28, 0.28) !important;
            border-color: rgba(248, 113, 113, 0.4) !important;
            color: #fecaca !important;
        }
        html.dark .kpi-chip--agotado *,
        html.dark .kpi-chip--vencido *,
        html.dark .kpi-chip--incumplido * {
            color: #fecaca !important;
        }
        html.dark .kpi-chip--en_tiempo {
            background: rgba(37, 99, 235, 0.18) !important;
            border-color: rgba(96, 165, 250, 0.4) !important;
            color: #93c5fd !important;
        }
        html.dark .kpi-chip--en_tiempo * {
            color: #93c5fd !important;
        }
        html.dark .sol-status.bg-white {
            background: rgba(88, 28, 135, 0.4) !important;
            color: #e9d5ff !important;
            border-color: rgba(168, 85, 247, 0.45) !important;
        }

        .sol-status {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.2;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        html.dark .sol-status--revision {
            background: rgba(88, 28, 135, 0.4) !important;
            color: #e9d5ff !important;
            border-color: rgba(168, 85, 247, 0.45) !important;
        }
        html.dark .sol-status--cancelada,
        html.dark .sol-status--rechazada {
            background: rgba(127, 29, 29, 0.45) !important;
            color: #fecaca !important;
            border-color: rgba(248, 113, 113, 0.4) !important;
        }
        html.dark .sol-status--pendiente,
        html.dark .sol-status--default {
            background: rgba(55, 65, 81, 0.7) !important;
            color: #e5e7eb !important;
            border-color: rgba(156, 163, 175, 0.35) !important;
        }
        html.dark .sol-status--aprobada {
            background: rgba(6, 95, 70, 0.4) !important;
            color: #6ee7b7 !important;
            border-color: rgba(52, 211, 153, 0.35) !important;
        }
        html.dark .sol-status--enviadas {
            background: rgba(30, 64, 175, 0.4) !important;
            color: #93c5fd !important;
            border-color: rgba(96, 165, 250, 0.4) !important;
        }
        .sol-approvals {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            min-height: 1.5rem;
        }
        .sol-approvals i {
            font-size: 0.95rem;
            line-height: 1;
        }

        .alert-exceso {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }
        html.dark .alert-exceso {
            background: #7f1d1d !important;
            border-left-color: #f87171 !important;
            color: #fee2e2 !important;
        }
        html.dark .alert-exceso h3,
        html.dark .alert-exceso p,
        html.dark .alert-exceso span,
        html.dark .alert-exceso div {
            color: #fee2e2 !important;
        }
        html.dark .alert-exceso .text-red-600,
        html.dark .alert-exceso .text-red-700,
        html.dark .alert-exceso .text-red-800 {
            color: #fecaca !important;
        }

        :root {
            --bg-table: #ffffff;
            --bg-head: #f1f5f9;
            --bg-row: #ffffff;
            --bg-row-hover: #f3f4f6;
            --border-soft: #e5e7eb;
            --text-main: #111827;
            --text-muted: #6b7280;
        }

        .dark {
            --bg-table: #1c1f26;
            --bg-head: #242933;
            --bg-row: #1f2937;
            --bg-row-hover: #273244;
            --border-soft: #2a2f3a;
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
        }

        /* ===== Cambio de tema vía View Transitions (crossfade GPU, sin repaint por nodo) ===== */
        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation-duration: 300ms;
            animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Fallback para navegadores sin View Transitions: transición de color simple */
        .theme-transition,
        .theme-transition * {
            transition: background-color 300ms ease,
                        border-color 300ms ease,
                        color 300ms ease !important;
        }

        /* ===== Scrollbar global moderno (theme-aware) ===== */
        :root {
            --sb-thumb: #c7ccd6;
            --sb-thumb-hover: #a8afbd;
            --sb-track: transparent;
        }

        .dark {
            --sb-thumb: #3a4150;
            --sb-thumb-hover: #4c5566;
            --sb-track: transparent;
        }

        /* Firefox */
        * {
            scrollbar-width: thin;
            scrollbar-color: var(--sb-thumb) var(--sb-track);
        }

        /* WebKit (Chrome/Edge/Safari) */
        *::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        *::-webkit-scrollbar-track {
            background: var(--sb-track);
        }

        *::-webkit-scrollbar-thumb {
            background: var(--sb-thumb);
            border-radius: 8px;
            border: 2px solid transparent;
            background-clip: content-box;
        }

        *::-webkit-scrollbar-thumb:hover {
            background: var(--sb-thumb-hover);
            background-clip: content-box;
        }

        *::-webkit-scrollbar-corner {
            background: transparent;
        }



    
    </style>

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('web/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('web/css/components.css')}}">
    <style>
        body .alert:not(.alert-exceso):not(.insight-bar) {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            margin-bottom: 1rem;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid transparent;
            font-size: 0.92rem;
            font-weight: 500;
            line-height: 1.45;
            box-shadow: 0 1px 2px rgba(16, 29, 73, 0.05);
        }
        body .alert:not(.alert-exceso):not(.insight-bar) > i {
            margin-top: 0.12rem;
            font-size: 1.05rem;
            flex: 0 0 auto;
        }
        body .alert .app-alert__body,
        body .alert p {
            flex: 1;
            min-width: 0;
        }
        body .alert p { margin: 0; }
        body .alert .close,
        body .alert .btn-close {
            position: static;
            float: none;
            margin: 0 0 0 auto;
            padding: 0;
            background: transparent;
            border: 0;
            color: inherit;
            font-size: 1.25rem;
            line-height: 1;
            opacity: 0.65;
            cursor: pointer;
            text-shadow: none;
        }
        body .alert .close:hover,
        body .alert .btn-close:hover { opacity: 1; }
        body .alert:not(.alert-light) a { color: inherit; text-decoration: underline; }

        body .alert.alert-success { background: #ecfdf3; border-color: #86efac; color: #14532d; }
        body .alert.alert-success > i { color: #15803d; }
        body .alert.alert-danger { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
        body .alert.alert-danger > i { color: #dc2626; }
        body .alert.alert-warning { background: #fffbeb; border-color: #fde68a; color: #78350f; }
        body .alert.alert-warning > i { color: #d97706; }
        body .alert.alert-info { background: #eff6ff; border-color: #bfdbfe; color: #1e3a8a; }
        body .alert.alert-info > i { color: #2563eb; }
        body .alert.alert-primary { background: #eef2ff; border-color: #c7d2fe; color: #1e1b4b; }
        body .alert.alert-primary > i { color: #3730a3; }
        body .alert.alert-secondary,
        body .alert.alert-dark,
        body .alert.alert-light { background: #f8fafc; border-color: #e5e7eb; color: #1f2937; }

        html.dark body .alert.alert-success { background: rgba(22, 101, 52, 0.35); border-color: #166534; color: #d1fae5; }
        html.dark body .alert.alert-success > i { color: #4ade80; }
        html.dark body .alert.alert-danger { background: rgba(127, 29, 29, 0.4); border-color: #991b1b; color: #fee2e2; }
        html.dark body .alert.alert-danger > i { color: #f87171; }
        html.dark body .alert.alert-warning { background: rgba(120, 53, 15, 0.4); border-color: #92400e; color: #fef3c7; }
        html.dark body .alert.alert-warning > i { color: #fbbf24; }
        html.dark body .alert.alert-info { background: rgba(30, 58, 138, 0.4); border-color: #1e40af; color: #dbeafe; }
        html.dark body .alert.alert-info > i { color: #60a5fa; }
        html.dark body .alert.alert-primary { background: rgba(49, 46, 129, 0.4); border-color: #3730a3; color: #e0e7ff; }
        html.dark body .alert.alert-primary > i { color: #a5b4fc; }
        html.dark body .alert.alert-secondary,
        html.dark body .alert.alert-dark,
        html.dark body .alert.alert-light { background: #1f2937; border-color: #374151; color: #e5e7eb; }

        .iziToast-wrapper-topRight {
            top: 68px !important;
            right: 14px !important;
        }
        .iziToast {
            border-radius: 0.75rem !important;
            font-family: inherit !important;
        }
        html.dark .iziToast {
            background: #1f2937 !important;
            border: 1px solid #374151 !important;
            color: #e5e7eb !important;
        }
        html.dark .iziToast > .iziToast-body .iziToast-title,
        html.dark .iziToast > .iziToast-body .iziToast-message {
            color: #e5e7eb !important;
        }
        html.dark .iziToast > .iziToast-close {
            background: transparent !important;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/ux-pro-max.css') }}?v=20261022d">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    @livewireStyles
</head>

<body class="bg-gray-100 dark:bg-[#0F1116] text-gray-800 dark:text-gray-200 transition-colors duration-500 ease-in-out">
    {{-- CSS por página: se re-inyecta en cada navegación SPA (AppNav.swapPage) --}}
    <div id="app-page-styles">
        @stack('styles')
        @yield('page_css')
        @yield('css')
        @stack('third_party_stylesheets')
    </div>
    <link rel="stylesheet" href="{{ asset('css/ux-pro-max.css') }}?v=20261022d">
    <div id="app-topbar" aria-hidden="true"><span></span></div>
    <div id="app-download-overlay" hidden>
        <div class="app-download-card" role="status" aria-live="polite">
            <div class="app-download-spinner" aria-hidden="true"></div>
            <p id="app-download-msg" class="fw-semibold">Generando Excel...</p>
            <p class="app-download-hint">La descarga iniciará automáticamente.</p>
        </div>
    </div>
    @livewireScripts
    <div id="app" class="h-screen flex flex-col overflow-hidden">
        <nav class="shrink-0 bg-white dark:bg-[#1C1F26] h-[60px] md:h-[60px] dark:text-gray-200 border-b border-b-gray-300 dark:border-b-[#2A2F3A] rounded-md transition-colors">            @include('layouts.header')
        </nav>
        <div class="flex flex-1 min-h-0 overflow-hidden">
            <!-- Overlay para m?vil -->
            <div id="mobile-overlay" 
                class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300 lg:hidden"
                onclick="toggleMobileMenu()"></div>
            
            <!-- Sidebar responsivo -->
            <aside id="sidebar" 
                class="fixed lg:static inset-y-0 left-0 z-50 bg-white w-[260px] lg:w-[260px] border-r border-gray-300 rounded-md dark:!bg-[#101010] transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out h-[calc(100vh-70px)] bg-white dark:bg-[#1C1F26] border-r border-gray-300 dark:border-[#2A2F3A] md:h-[calc(100vh-80px)] lg:h-auto overflow-y-auto">
                @include('layouts.sidebar')
            </aside>

            <main id="app-main" class="flex-1 min-w-0 overflow-y-auto p-3 md:p-6 dark:bg-[#101010] w-full lg:w-auto py-1">
                @yield('content')
            </main>
        </div>
        <!-- <footer class="main-footer">
            @include('layouts.footer')
        </footer> -->
    </div>

    <div id="app-shell-extras">
    @include('partials.modal-vista-archivo')
    @include('profile.change_password')
    @include('profile.edit_profile')
    @include('partials.modal-detalle-solicitud')
    @livewire('tabla-solicitudes', ['soloModal' => true], key('tabla-solicitudes-asignacion-global'))

    {{-- Panel de ticket global: solo fuera de /tickets (ahí el tablero ya lo monta) --}}
    @unless(request()->is('tickets'))
        @include('partials.tickets-modal-engine')
        <div x-data="ticketsModal(true)">
            @include('partials.modal-ticket')
        </div>
    @endunless

    {{-- Panel de mantenimiento global: solo fuera de /tickets-mantenimiento (ahí el tablero ya lo monta) --}}
    @unless(request()->is('tickets-mantenimiento'))
        @include('partials.mantenimiento-modal-engine')
        <div x-data="mantenimientoModal(true)">
            @include('partials.modal-mantenimiento')
        </div>
    @endunless
</div>

<script src="{{ mix('assets/js/jquery.min.js') }}"></script>
<script src="{{ mix('assets/js/jquery.nicescroll.js') }}"></script>
<script src="{{ mix('assets/js/popper.min.js') }}"></script>
<script src="{{ mix('assets/js/bootstrap.min.js') }}"></script>
<script src="{{ mix('assets/js/sweetalert.min.js') }}"></script>
<script src="{{ mix('assets/js/iziToast.min.js') }}"></script>
<script>
    window.AppAlert = (function () {
        ['success', 'error', 'warning', 'info'].forEach(function (method) {
            if (!window.iziToast || typeof iziToast[method] !== 'function' || iziToast[method].__appAlert) return;
            var original = iziToast[method].bind(iziToast);
            var wrapped = function (opts) {
                opts = opts || {};
                opts.position = 'topRight';
                if (opts.timeout == null) opts.timeout = 4200;
                opts.transitionIn = opts.transitionIn || 'fadeInDown';
                opts.transitionOut = opts.transitionOut || 'fadeOutUp';
                opts.pauseOnHover = true;
                opts.progressBar = opts.progressBar !== false;
                opts.close = true;
                return original(opts);
            };
            wrapped.__appAlert = true;
            iziToast[method] = wrapped;
        });

        function mostrar(root) {
            if (!window.iziToast) return;
            var scope = root && root.querySelectorAll ? root : document;
            Array.prototype.forEach.call(scope.querySelectorAll('.app-flash'), function (node) {
                if (node.getAttribute('data-shown') === '1') return;
                node.setAttribute('data-shown', '1');
                var level = node.getAttribute('data-level') || 'success';
                var method = (level === 'danger' || level === 'error') ? 'error' : level;
                if (typeof iziToast[method] !== 'function') method = 'info';
                var titulo = method === 'error' ? 'No se pudo' : (method === 'warning' ? 'Atención' : (method === 'info' ? 'Aviso' : 'Listo'));
                iziToast[method]({
                    title: titulo,
                    message: node.innerHTML
                });
                node.remove();
            });
        }

        function cerrarListas() {
            if (window.jQuery && jQuery.fn && typeof jQuery.fn.select2 === 'function') {
                jQuery('select.select2-hidden-accessible').each(function () {
                    try { jQuery(this).select2('close'); } catch (e) {}
                });
            }
            if (document.activeElement && typeof document.activeElement.blur === 'function') {
                document.activeElement.blur();
            }
        }

        function avisar(tipo, titulo, mensaje) {
            cerrarListas();
            if (!window.iziToast || typeof iziToast[tipo] !== 'function') {
                window.alert((titulo ? titulo + '. ' : '') + (mensaje || ''));
                return;
            }
            iziToast[tipo]({ title: titulo, message: mensaje || '' });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { mostrar(document); });
        } else {
            mostrar(document);
        }

        return {
            mostrar: mostrar,
            ok: function (mensaje, titulo) { avisar('success', titulo || 'Listo', mensaje); },
            fallo: function (mensaje, titulo) { avisar('error', titulo || 'No se pudo', mensaje); },
            aviso: function (mensaje, titulo) { avisar('info', titulo || 'Aviso', mensaje); },
            cuidado: function (mensaje, titulo) { avisar('warning', titulo || 'Atención', mensaje); }
        };
    })();
</script>
<script src="{{ mix('assets/js/select2.min.js') }}"></script>
<script src="{{ asset('web/js/stisla.js') }}"></script>
<script src="{{ asset('web/js/scripts.js') }}"></script>
<script src="{{ mix('assets/js/profile.js') }}"></script>
<script src="{{ mix('assets/js/custom/custom.js') }}"></script>
<script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>

<div id="app-page-scripts">
@stack('third_party_scripts')
@yield('scripts')
</div>

</body>

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

        window.iniciarSelects = function (root) {
            if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.select2 !== 'function') return;
            var $scope = root ? jQuery(root) : jQuery(document);
            $scope.find('select').addBack('select').each(function () {
                var $el = jQuery(this);
                if ($el.hasClass('select2-hidden-accessible') || $el.is('[data-no-select2]')) return;
                if ($el.closest('template').length) return;
                var size = parseInt($el.attr('size') || '0', 10);
                if (size > 1) return;
                var full = $el.hasClass('form-control') || $el.hasClass('jz1') || $el.hasClass('w-full');
                var compact = !full && ($el.hasClass('border-0') || $el.hasClass('bg-transparent'));
                var opts = {
                    width: full ? '100%' : (compact ? (Math.max($el.outerWidth() || 0, 88) + 'px') : 'resolve'),
                    minimumResultsForSearch: $el.find('option').length > 15 ? 0 : Infinity,
                    dropdownAutoWidth: compact
                };
                if (compact) opts.containerCssClass = 'select2-compact';
                if ($el.hasClass('jz1') && jQuery('#editModal').length) {
                    opts.dropdownParent = jQuery('#editModal');
                } else {
                    var $modal = $el.closest('.modal');
                    if ($modal.length) opts.dropdownParent = $modal;
                }
                $el.select2(opts);
                $el.off('select2:select.erp select2:clear.erp').on('select2:select.erp select2:clear.erp', function () {
                    this.dispatchEvent(new Event('input', { bubbles: true }));
                    this.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        };

        $(document).ready(function () {
            window.iniciarSelects(document);
        });

        var selectsTimer = null;
        var selectsObs = new MutationObserver(function (cambios) {
            var haySelect = false;
            cambios.forEach(function (cambio) {
                Array.prototype.forEach.call(cambio.addedNodes || [], function (nodo) {
                    if (!haySelect && nodo.nodeType === 1 && nodo.tagName !== 'SPAN' && (nodo.tagName === 'SELECT' || (nodo.querySelector && nodo.querySelector('select:not(.select2-hidden-accessible)')))) {
                        haySelect = true;
                    }
                });
            });
            if (!haySelect) return;
            clearTimeout(selectsTimer);
            selectsTimer = setTimeout(function () { window.iniciarSelects(document); }, 80);
        });
        selectsObs.observe(document.body, { childList: true, subtree: true });

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
            sidebar.classList.add('lg:w-[260px]');
            toggleIcon.classList.remove('fa-chevron-right');
            toggleIcon.classList.add('fa-chevron-left');
            // Guardar estado
            localStorage.setItem('sidebarCollapsed', 'false');
        } else {
            // Colapsar sidebar
            sidebar.classList.add('sidebar-collapsed');
            sidebar.classList.remove('lg:w-[260px]');
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
            sidebar.classList.remove('lg:w-[260px]');
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
                sidebar.classList.add('lg:w-[260px]');
            }
        });
    });
</script>

<script>
    window.AppTabs = {
        show: function (root, tab) {
            if (!root) return;
            var id = String(tab);
            root.querySelectorAll('[data-app-tab]').forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-app-tab') === id);
            });
            root.querySelectorAll('[data-app-panel]').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-app-panel') !== id;
            });
            root.dispatchEvent(new CustomEvent('app-tab-change', { bubbles: true, detail: { tab: id } }));
        }
    };
    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-app-tab]');
        if (!btn) return;
        var root = btn.closest('[data-app-tabset]');
        if (!root) return;
        var href = btn.getAttribute('href');
        if (btn.tagName === 'A' && href && href !== '#') {
            return;
        }
        event.preventDefault();
        window.AppTabs.show(root, btn.getAttribute('data-app-tab'));
    });

    window.AppVistas = {
        show: function (root, vista, options) {
            if (!root || !vista) return;
            options = options || {};
            root.querySelectorAll('[data-vista-btn]').forEach(function (btn) {
                btn.classList.toggle('is-vista-active', btn.getAttribute('data-vista-btn') === vista);
            });
            root.querySelectorAll('[data-vista-panel]').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-vista-panel') !== vista;
            });
            var key = root.getAttribute('data-vista-storage');
            if (key && !options.skipStorage) {
                try { localStorage.setItem(key, vista); } catch (e) {}
            }
            var evt = root.getAttribute('data-vista-event');
            if (evt && window.Livewire && !options.skipEmit) {
                window.Livewire.emit(evt, vista);
            }
            if (window.Alpine && typeof Alpine.$data === 'function') {
                try {
                    var data = Alpine.$data(root);
                    if (data) data.vista = vista;
                } catch (e) {}
            }
        }
    };
    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-vista-btn]');
        if (!btn) return;
        var root = btn.closest('[data-vista-root]');
        if (!root) return;
        event.preventDefault();
        window.AppVistas.show(root, btn.getAttribute('data-vista-btn'));
    });
    document.querySelectorAll('[data-vista-root]').forEach(function (root) {
        var key = root.getAttribute('data-vista-storage');
        var saved = null;
        try { saved = key ? localStorage.getItem(key) : null; } catch (e) {}
        window.AppVistas.show(root, saved || 'kanban', {
            skipStorage: true,
            skipEmit: false
        });
    });

    window.AppTopbar = (function () {
        var bar = document.getElementById('app-topbar');
        var fill = bar ? bar.querySelector('span') : null;
        var timer = null;
        var value = 0;

        function setWidth(pct) {
            value = pct;
            if (fill) fill.style.width = pct + '%';
        }

        return {
            start: function () {
                if (!bar || !fill) return;
                clearInterval(timer);
                fill.style.transition = '';
                bar.classList.add('is-on');
                setWidth(12);
                timer = setInterval(function () {
                    if (value >= 82) return;
                    setWidth(value + Math.max(0.6, (82 - value) * 0.08));
                }, 180);
            },
            done: function () {
                if (!bar || !fill) return;
                clearInterval(timer);
                setWidth(100);
                setTimeout(function () {
                    bar.classList.remove('is-on');
                    fill.style.transition = 'none';
                    setWidth(0);
                    requestAnimationFrame(function () {
                        fill.style.transition = '';
                    });
                }, 220);
            }
        };
    })();

    window.AppDownload = (function () {
        var overlay = document.getElementById('app-download-overlay');
        var msgEl = document.getElementById('app-download-msg');
        var busy = false;
        var objectUrl = null;

        function isFileUrl(href) {
            if (!href) return false;
            if (/\.(pdf|xlsx|xls|csv|zip|docx?)(\?|$)/i.test(href)) return true;
            return /(export[-_]?pdf|export[-_]?excel|exportPdf|exportExcel|exportar-excel|exportarAsignados|comparativa\/exportar|inventarios\/[^/]+\/exportar\/|exportar-reporte)/i.test(href);
        }

        function isExcelUrl(href) {
            if (!href) return false;
            if (/\.xlsx(\?|$)/i.test(href)) return true;
            return /(export[-_]?excel|exportExcel|exportar-excel|exportarAsignados|comparativa\/exportar|inventarios\/[^/]+\/exportar\/|exportar-reporte-mensual-excel|export-estatus-licencias-excel|export-equipos-asignados-excel|export-lineas-asignadas-excel)/i.test(href);
        }

        function show(label) {
            if (msgEl) msgEl.textContent = 'Generando ' + (label || 'Excel') + '...';
            if (overlay) overlay.hidden = false;
        }

        function hide() {
            if (overlay) overlay.hidden = true;
        }

        function setMessage(text) {
            if (msgEl) msgEl.textContent = text;
        }

        function filenameFromHeader(header, fallback) {
            if (!header) return fallback;
            var star = header.match(/filename\*=UTF-8''([^;]+)/i);
            if (star && star[1]) {
                try { return decodeURIComponent(star[1].trim()); } catch (e) { return star[1].trim(); }
            }
            var quoted = header.match(/filename="([^"]+)"/i);
            if (quoted && quoted[1]) return quoted[1];
            var plain = header.match(/filename=([^;]+)/i);
            return plain && plain[1] ? plain[1].trim() : fallback;
        }

        function saveBlob(blob, filename) {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = objectUrl;
            a.download = filename || 'reporte.xlsx';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(function () {
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }
            }, 60000);
        }

        function fail() {
            hide();
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo generar el archivo',
                    text: 'Inténtalo de nuevo o recarga la página.'
                });
            } else {
                alert('No se pudo generar el archivo.');
            }
        }

        function downloadResponse(res, fallbackName) {
            var type = res.headers.get('Content-Type') || '';
            var name = filenameFromHeader(res.headers.get('Content-Disposition'), fallbackName);
            if (!res.ok) throw new Error('http');
            return res.blob().then(function (blob) {
                if (/text\/html/i.test(type)) throw new Error('html');
                saveBlob(blob, name);
                hide();
            });
        }

        function fromUrl(url, label) {
            if (!url || busy) return Promise.resolve();
            busy = true;
            show(label || 'Excel');
            return fetch(url, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (res) {
                return downloadResponse(res, 'reporte.xlsx');
            }).catch(fail).then(function () {
                busy = false;
            });
        }

        function fromForm(form, label) {
            if (!form || busy) return Promise.resolve();
            busy = true;
            show(label || form.getAttribute('data-label') || 'Excel');
            var method = (form.getAttribute('method') || 'POST').toUpperCase();
            var opts = {
                method: method,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            };
            if (method !== 'GET') {
                opts.body = new FormData(form);
            }
            return fetch(form.action, opts).then(function (res) {
                return downloadResponse(res, 'reporte.xlsx');
            }).catch(fail).then(function () {
                busy = false;
            });
        }

        document.addEventListener('click', function (event) {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (event.button && event.button !== 0) return;
            var anchor = event.target.closest('a[href]');
            if (!anchor || anchor.dataset.skipExcelSpinner === '1') return;
            if (anchor.classList.contains('export-direct')) return;
            var href = anchor.getAttribute('href') || anchor.href || '';
            var match = anchor.classList.contains('js-excel-download') || isExcelUrl(href);
            if (!match) return;
            event.preventDefault();
            event.stopPropagation();
            var label = anchor.getAttribute('data-download-label') || anchor.getAttribute('title') || 'Excel';
            fromUrl(anchor.href, label);
        });

        return {
            show: show,
            hide: hide,
            setMessage: setMessage,
            fromUrl: fromUrl,
            fromForm: fromForm,
            isFileUrl: isFileUrl,
            isExcelUrl: isExcelUrl
        };
    })();

    window.AppNav = (function () {
        var loading = false;

        var executedSrc = Object.create(null);

        Array.prototype.forEach.call(document.scripts, function (s) {
            if (s.src) executedSrc[s.src] = true;
        });

        function shouldIgnore(anchor, event) {
            if (!anchor || !anchor.getAttribute('href')) return true;
            if (anchor.target && anchor.target !== '_self') return true;
            if (anchor.hasAttribute('download')) return true;
            if (anchor.dataset.fullLoad === '1') return true;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return true;
            if (event.button && event.button !== 0) return true;
            var href = anchor.getAttribute('href');
            if (href.charAt(0) === '#' || href.indexOf('javascript:') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return true;
            if (/\.(pdf|xlsx|xls|csv|zip|docx?|png|jpe?g|gif)(\?|$)/i.test(href)) return true;
            if (anchor.classList.contains('js-excel-download') || anchor.classList.contains('export-direct')) return true;
            if (window.AppDownload && window.AppDownload.isFileUrl(href)) return true;
            return false;
        }

        function isJsScript(el) {
            var type = (el.getAttribute('type') || 'text/javascript').toLowerCase();
            return type === '' ||
                type === 'text/javascript' ||
                type === 'application/javascript' ||
                type === 'module';
        }

        function scriptAlreadyLoaded(src) {
            return !!executedSrc[src];
        }

        function skipVendorIfPresent(src) {
            if (!src) return false;
            if (/jquery\.dataTables(\.min)?\.js/i.test(src) && window.jQuery && jQuery.fn && typeof jQuery.fn.DataTable === 'function') {
                return true;
            }
            if (/\/chart(\.umd)?(\.min)?\.js/i.test(src) && typeof window.Chart === 'function') {
                return true;
            }
            if (/jquery\.min\.js/i.test(src) && window.jQuery) {
                return true;
            }
            if (/select2(\.min)?\.js/i.test(src) && window.jQuery && jQuery.fn && jQuery.fn.select2) {
                return true;
            }
            return false;
        }

        function runScripts(root) {
            if (!root) return Promise.resolve();
            var scripts = Array.prototype.slice.call(root.querySelectorAll('script'));
            return scripts.reduce(function (chain, old) {
                return chain.then(function () {
                    try {
                        if (!isJsScript(old)) return;
                        if (old.src) {
                            if (scriptAlreadyLoaded(old.src) || skipVendorIfPresent(old.src)) {
                                executedSrc[old.src] = true;
                                old.remove();
                                return;
                            }
                            return new Promise(function (resolve) {
                                var s = document.createElement('script');
                                s.src = old.src;
                                s.onload = function () {
                                    executedSrc[old.src] = true;
                                    resolve();
                                };
                                s.onerror = resolve;
                                document.body.appendChild(s);
                                old.remove();
                            });
                        }
                        var code = old.textContent || '';
                        if (!code.trim()) return;
                        // AppNav reinyecta scripts: const/let globales no se pueden redeclarar
                        code = code.replace(/(^|\n)([ \t]*)(const|let) /g, '$1$2var ');
                        if (code.indexOf('function ticketsModal') !== -1 && typeof window.ticketsModal === 'function') {
                            if (window.Alpine && typeof Alpine.data === 'function') {
                                try { Alpine.data('ticketsModal', window.ticketsModal); } catch (e) {}
                            }
                            old.remove();
                            return;
                        }
                        if (code.indexOf('function mantenimientoModal') !== -1 && typeof window.mantenimientoModal === 'function') {
                            if (window.Alpine && typeof Alpine.data === 'function') {
                                try { Alpine.data('mantenimientoModal', window.mantenimientoModal); } catch (e) {}
                            }
                            old.remove();
                            return;
                        }
                        if (code.indexOf('function inicializarGraficasEmpleados') !== -1 && typeof window.inicializarGraficas === 'function') {
                            old.remove();
                            return;
                        }
                        if (code.indexOf('function inicializarGraficasMantenimiento') !== -1 && typeof window.inicializarGraficasMantenimiento === 'function') {
                            old.remove();
                            return;
                        }
                        var s = document.createElement('script');
                        s.textContent = code;
                        if (old.parentNode) old.parentNode.replaceChild(s, old);
                        else old.remove();
                    } catch (err) {
                        try { old.remove(); } catch (e2) {}
                    }
                });
            }, Promise.resolve());
        }

        function restoreSidebarCollapsed() {
            var sidebar = document.getElementById('sidebar');
            var toggleIcon = document.getElementById('sidebar-toggle-icon');
            if (!sidebar) return;
            var collapsed = false;
            try { collapsed = localStorage.getItem('sidebarCollapsed') === 'true'; } catch (e) {}
            if (collapsed && window.innerWidth >= 1024) {
                sidebar.classList.add('sidebar-collapsed');
                sidebar.classList.remove('lg:w-[260px]');
                sidebar.classList.add('lg:w-[80px]');
                if (toggleIcon) {
                    toggleIcon.classList.remove('fa-chevron-left');
                    toggleIcon.classList.add('fa-chevron-right');
                }
            }
        }

        function bootPage(roots) {
            roots.forEach(function (root) {
                if (!root || !window.Alpine || typeof Alpine.initTree !== 'function') return;
                try { Alpine.initTree(root); } catch (e) {}
            });
            var sidebar = document.getElementById('sidebar');
            if (sidebar && window.Alpine && typeof Alpine.initTree === 'function') {
                try { Alpine.initTree(sidebar); } catch (e) {}
            }
            if (window.Livewire && typeof Livewire.restart === 'function') {
                try { Livewire.restart(); } catch (e) {}
            }
            if (window.AppVistas) {
                document.querySelectorAll('[data-vista-root]').forEach(function (root) {
                    var key = root.getAttribute('data-vista-storage');
                    var saved = null;
                    try { saved = key ? localStorage.getItem(key) : null; } catch (err) {}
                    window.AppVistas.show(root, saved || 'kanban', {
                        skipStorage: true,
                        skipEmit: false
                    });
                });
            }
            if (typeof window.iniciarSelects === 'function') {
                try { window.iniciarSelects(document); } catch (e) {}
            }
            restoreSidebarCollapsed();
            if (window.AppAlert) window.AppAlert.mostrar(document.getElementById('app-main'));
            setTimeout(function () {
                if (typeof inicializarGraficas === 'function') inicializarGraficas();
                if (typeof inicializarGraficasEmpleados === 'function') inicializarGraficasEmpleados();
                if (typeof inicializarGraficasMantenimiento === 'function') inicializarGraficasMantenimiento();
            }, 80);
        }

        function copySection(doc, id) {
            var cur = document.getElementById(id);
            var next = doc.getElementById(id);
            if (!cur || !next) return null;
            cur.innerHTML = next.innerHTML;
            return cur;
        }

        function alpineDestroy(root) {
            if (!root || !window.Alpine || typeof Alpine.destroyTree !== 'function') return;
            try { Alpine.destroyTree(root); } catch (e) {}
        }

        function alpineMute(fn) {
            if (window.Alpine && typeof Alpine.mutateDom === 'function') {
                Alpine.mutateDom(fn);
                return;
            }
            fn();
        }

        // CSS por página vive en <head> con [data-appnav-style]. En cada navegación
        // SPA se diffea contra el <head> del documento nuevo: se quitan los que
        // sobran y se agregan los que faltan (los compartidos no se recargan → sin flash).
        function styleKey(n) {
            if (n.tagName === 'LINK') return 'L:' + (n.getAttribute('href') || '');
            return 'S:' + (n.textContent || '').length + ':' + (n.textContent || '').slice(0, 120);
        }
        function collectPageStyleNodes(rootDoc) {
            var box = rootDoc.getElementById('app-page-styles');
            if (!box) return [];
            return Array.prototype.slice.call(box.querySelectorAll('link[rel="stylesheet"], style'));
        }
        function syncPageStyles(doc) {
            var desired = collectPageStyleNodes(doc);
            var desiredKeys = {};
            desired.forEach(function (n) { desiredKeys[styleKey(n)] = true; });

            var current = Array.prototype.slice.call(
                document.head.querySelectorAll('[data-appnav-style]')
            );
            var currentKeys = {};
            current.forEach(function (el) {
                var k = styleKey(el);
                if (desiredKeys[k]) { currentKeys[k] = true; }
                else { el.parentNode.removeChild(el); }
            });

            desired.forEach(function (n) {
                var k = styleKey(n);
                if (currentKeys[k]) return;
                var el;
                if (n.tagName === 'LINK') {
                    var href = n.getAttribute('href');
                    if (!href) return;
                    el = document.createElement('link');
                    el.rel = 'stylesheet';
                    el.href = href;
                } else {
                    el = document.createElement('style');
                    el.textContent = n.textContent || '';
                }
                el.setAttribute('data-appnav-style', '1');
                document.head.appendChild(el);
            });
        }
        // Hoist inicial: mueve el CSS de la primera carga (en #app-page-styles del body)
        // al <head> con el mismo tag, para que las navegaciones siguientes diffeen bien.
        (function initHoistPageStyles() {
            var box = document.getElementById('app-page-styles');
            if (!box) return;
            collectPageStyleNodes(document).forEach(function (n) {
                var el;
                if (n.tagName === 'LINK') {
                    el = document.createElement('link');
                    el.rel = 'stylesheet';
                    el.href = n.getAttribute('href') || '';
                } else {
                    el = document.createElement('style');
                    el.textContent = n.textContent || '';
                }
                el.setAttribute('data-appnav-style', '1');
                document.head.appendChild(el);
            });
            box.parentNode.removeChild(box);
        })();

        // swapPage solo reemplaza main/sidebar/extras. Lo que otras librerías cuelgan
        // directo del <body> (backdrop de Bootstrap, dropdowns de Select2, SweetAlert,
        // overlays) sobrevive a la navegación y tapa la vista nueva: ya no se puede dar clic.
        function limpiarRestosDePagina() {
            try {
                if (window.jQuery) {
                    window.jQuery('.modal.show').modal('hide');
                    window.jQuery('.select2-hidden-accessible').select2('close');
                }
            } catch (e) {}
            if (window.Swal && typeof Swal.close === 'function') {
                try { Swal.close(); } catch (e) {}
            }
            document.querySelectorAll('.modal-backdrop, body > .select2-container, body > .swal2-container').forEach(function (el) {
                el.remove();
            });
            document.body.classList.remove('modal-open', 'swal2-shown', 'swal2-height-auto', 'overflow-hidden');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.documentElement.style.removeProperty('overflow');
            var mobileOverlay = document.getElementById('mobile-overlay');
            if (mobileOverlay && window.innerWidth >= 1024) mobileOverlay.classList.add('hidden');
            if (window.AppDownload) window.AppDownload.hide();
        }

        function swapPage(html, href, push) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            limpiarRestosDePagina();
            var main = document.getElementById('app-main');
            var nextMain = doc.getElementById('app-main') || doc.querySelector('main');
            if (!main || !nextMain) {
                window.location.assign(href);
                return Promise.resolve();
            }
            if (typeof destruirGraficasProductividad === 'function') {
                try { destruirGraficasProductividad(); } catch (e) {}
            }
            if (typeof destruirGraficasMantenimiento === 'function') {
                try { destruirGraficasMantenimiento(); } catch (e) {}
            }

            var extrasEl = document.getElementById('app-shell-extras');
            var scriptsEl = document.getElementById('app-page-scripts');
            var sidebarEl = document.getElementById('sidebar');
            alpineDestroy(main);
            alpineDestroy(extrasEl);
            alpineDestroy(scriptsEl);
            alpineDestroy(sidebarEl);

            var extras = null;
            var pageScripts = null;
            // CSS de la página primero, para que el contenido nuevo ya renderice con estilos
            syncPageStyles(doc);
            alpineMute(function () {
                main.innerHTML = nextMain.innerHTML;
                copySection(doc, 'sidebar');
                extras = copySection(doc, 'app-shell-extras');
                pageScripts = copySection(doc, 'app-page-scripts');
            });

            var csrf = doc.querySelector('meta[name="csrf-token"]');
            var currentCsrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf && currentCsrf) currentCsrf.setAttribute('content', csrf.getAttribute('content'));
            document.title = doc.title || document.title;
            if (push) history.pushState({ appNav: true }, '', href);

            var chain = runScripts(main);
            if (extras) chain = chain.then(function () { return runScripts(extras); });
            if (pageScripts) chain = chain.then(function () { return runScripts(pageScripts); });
            return chain.then(function () { bootPage([main, extras, pageScripts]); });
        }

        function visit(href, push) {
            if (loading) return;
            loading = true;
            window.AppTopbar.start();
            fetch(href, {
                headers: {
                    'Accept': 'text/html',
                    'X-App-Nav': '1'
                },
                credentials: 'same-origin'
            }).then(function (res) {
                if (!res.ok) throw new Error('http ' + res.status);
                return res.text();
            }).then(function (html) {
                return swapPage(html, href, push);
            }).catch(function (err) {
                console.warn('AppNav', err);
            }).then(function () {
                loading = false;
                window.AppTopbar.done();
            });
        }

        document.addEventListener('click', function (event) {
            var anchor = event.target.closest('a[href]');
            if (shouldIgnore(anchor, event)) return;
            var url;
            try { url = new URL(anchor.href, window.location.href); } catch (e) { return; }
            if (url.origin !== window.location.origin) return;
            if (url.pathname === window.location.pathname && url.search === window.location.search) return;

            event.preventDefault();
            event.stopImmediatePropagation();
            if (window.innerWidth < 1024 && anchor.closest('#sidebar')) {
                try { toggleMobileMenu(); } catch (e) {}
            }
            visit(url.href, true);
        }, true);

        window.addEventListener('popstate', function () {
            visit(window.location.href, false);
        });

        return { visit: visit };
    })();

</script>
<script src="{{ asset('assets/js/alpine-collapse.min.js') }}"></script>
<script src="{{ asset('assets/js/alpine.min.js') }}"></script>

</html>