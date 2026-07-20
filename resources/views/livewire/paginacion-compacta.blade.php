@if ($paginator->hasPages())
<nav role="navigation" aria-label="Paginación" class="flex items-center justify-center gap-1 flex-wrap">
    {{-- Anterior --}}
    @if ($paginator->onFirstPage())
        <span aria-hidden="true" class="px-2 py-1 rounded-md text-gray-300 cursor-default select-none">‹</span>
    @else
        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')"
            class="px-2 py-1 rounded-md text-gray-500 hover:bg-gray-100 transition-colors">‹</button>
    @endif

    {{-- Números --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="px-1 text-gray-400 select-none">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="px-2.5 py-1 rounded-md bg-blue-600 text-white text-xs font-semibold select-none">{{ $page }}</span>
                @else
                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                        class="px-2.5 py-1 rounded-md text-gray-500 hover:bg-gray-100 text-xs transition-colors">{{ $page }}</button>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Siguiente --}}
    @if ($paginator->hasMorePages())
        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')"
            class="px-2 py-1 rounded-md text-gray-500 hover:bg-gray-100 transition-colors">›</button>
    @else
        <span aria-hidden="true" class="px-2 py-1 rounded-md text-gray-300 cursor-default select-none">›</span>
    @endif
</nav>
@endif
