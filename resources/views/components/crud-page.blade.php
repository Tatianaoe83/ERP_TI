@props([
    'title',
    'icon' => 'fa-edit',
    'subtitle' => null,
    'backUrl' => null,
    'backLabel' => 'Volver',
])

<div {{ $attributes->merge(['class' => 'index-page crud-page']) }}>
    <style>
        .dark .crud-page label,
        .dark .crud-page .control-label,
        .dark .crud-page abbr {
            color: #d1d5db !important;
        }
        .dark .crud-page .form-control,
        .dark .crud-page .form-select,
        .dark .crud-page select.form-control,
        .dark .crud-page textarea.form-control,
        .dark .crud-page input.form-control {
            background-color: #111827 !important;
            border-color: #374151 !important;
            color: #f9fafb !important;
            caret-color: #f9fafb;
        }
        .dark .crud-page .form-control::placeholder {
            color: #9ca3af !important;
        }
        .dark .crud-page .form-control option,
        .dark .crud-page select option {
            background-color: #111827;
            color: #f9fafb;
        }
        .dark .crud-page .select2-container--default .select2-selection--single,
        .dark .crud-page .select2-container--default .select2-selection--multiple {
            background-color: #111827 !important;
            border-color: #374151 !important;
            color: #f9fafb !important;
            min-height: 2.5rem;
        }
        .dark .crud-page .select2-container--default .select2-selection--single .select2-selection__rendered,
        .dark .crud-page .select2-container--default .select2-selection--multiple .select2-selection__rendered,
        .dark .crud-page .select2-container--default .select2-search--inline .select2-search__field {
            color: #f9fafb !important;
        }
        .dark .crud-page .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #1e3a8a !important;
            border-color: #3b82f6 !important;
            color: #eff6ff !important;
        }
        .dark .select2-container--default .select2-dropdown,
        .dark .select2-dropdown {
            background-color: #111827 !important;
            border-color: #374151 !important;
        }
        .dark .select2-container--default .select2-results__option {
            color: #f9fafb !important;
        }
        .dark .select2-container--default .select2-results__option--highlighted[aria-selected],
        .dark .select2-container--default .select2-results__option--highlighted {
            background-color: #1d4ed8 !important;
            color: #fff !important;
        }
        .dark .select2-search__field {
            background-color: #111827 !important;
            color: #f9fafb !important;
        }
    </style>
    <div class="index-page__header">
        <div class="index-page__heading">
            <span class="index-page__icon" aria-hidden="true">
                <i class="fas {{ $icon }}"></i>
            </span>
            <div>
                <h1 class="index-page__title">{{ $title }}</h1>
                @if($subtitle)
                    <span class="index-page__count">{{ $subtitle }}</span>
                @endif
            </div>
        </div>
        @if($backUrl)
            <a href="{{ $backUrl }}" class="crud-page__btn-ghost">{{ $backLabel }}</a>
        @endif
    </div>

    <div class="index-page__card crud-page__card">
        {{ $slot }}
    </div>
</div>
