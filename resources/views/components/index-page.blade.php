@props([
    'title',
    'icon' => 'fa-th-large',
    'createUrl' => null,
    'createPermission' => null,
    'createLabel' => '+ Nuevo',
])

@php
    $canCreate = $createUrl && (
        empty($createPermission) || (auth()->check() && auth()->user()->can($createPermission))
    );
@endphp

<div {{ $attributes->merge(['class' => 'index-page']) }}>
    <div class="index-page__header">
        <div class="index-page__heading">
            <span class="index-page__icon" aria-hidden="true">
                <i class="fas {{ $icon }}"></i>
            </span>
            <div>
                <h1 class="index-page__title">{{ $title }}</h1>
                <span class="index-page__count">0 registros</span>
            </div>
        </div>
        @if($canCreate)
            <a href="{{ $createUrl }}" class="index-page__btn-primary">
                {{ $createLabel }}
            </a>
        @endif
    </div>

    @isset($filters)
        <div class="index-page__filters">
            {{ $filters }}
        </div>
    @endisset

    <div class="index-page__card">
        {{ $slot }}
    </div>
</div>
