@props([
    'title',
    'icon' => 'fa-edit',
    'subtitle' => null,
    'backUrl' => null,
    'backLabel' => 'Volver',
])

<div {{ $attributes->merge(['class' => 'index-page crud-page']) }}>
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
