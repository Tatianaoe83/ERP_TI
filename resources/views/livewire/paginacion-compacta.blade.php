@if ($paginator->hasPages())
<nav role="navigation" aria-label="Paginación" class="erp-page">
    @if ($paginator->onFirstPage())
        <span aria-hidden="true" class="erp-page__btn is-disabled">‹</span>
    @else
        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" class="erp-page__btn">‹</button>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="erp-page__gap">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="erp-page__btn is-current">{{ $page }}</span>
                @else
                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="erp-page__btn">{{ $page }}</button>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" class="erp-page__btn">›</button>
    @else
        <span aria-hidden="true" class="erp-page__btn is-disabled">›</span>
    @endif
</nav>
@endif
