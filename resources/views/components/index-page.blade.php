@props([
    'title',
    'icon' => 'fa-th-large',
    'createUrl' => null,
    'createPermission' => null,
    'createLabel' => '+ Nuevo',
    'subtitle' => null,
    'showCount' => true,
    'card' => true,
])

@php
    $canCreate = $createUrl && (
        empty($createPermission) || (auth()->check() && auth()->user()->can($createPermission))
    );
    $countText = $showCount ? ($subtitle ?: '0 registros') : $subtitle;
    $hasHeaderActions = $canCreate || isset($headerActions);
@endphp

<div {{ $attributes->merge(['class' => 'index-page']) }}>
    <div class="index-page__header">
        <div class="index-page__heading">
            <span class="index-page__icon" aria-hidden="true">
                <i class="fas {{ $icon }}"></i>
            </span>
            <div>
                <h1 class="index-page__title">{{ $title }}</h1>
                @if($countText)
                    <span class="index-page__count">{{ $countText }}</span>
                @endif
            </div>
        </div>
        @if($hasHeaderActions)
            <div class="index-page__header-actions">
                {{ $headerActions ?? '' }}
                @if($canCreate)
                    <a href="{{ $createUrl }}" class="index-page__btn-primary">
                        {{ $createLabel }}
                    </a>
                @endif
            </div>
        @endif
    </div>

    @isset($tabs)
        <div class="index-page__tabs">
            {{ $tabs }}
        </div>
    @endisset

    @isset($filters)
        <div class="index-page__filters">
            {{ $filters }}
        </div>
    @endisset

    @if($card)
        <div class="index-page__card">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
