{{--
    Paginador del tablero de mantenimientos.
    $pagina / $ultima: posición actual y total de páginas.
    $wireAnterior / $wireSiguiente: llamadas Livewire que mueven la página.
--}}
@if (($ultima ?? 1) > 1)
<div class="flex items-center justify-center gap-2 px-3 py-2 border-t border-gray-200 dark:border-[#2A2F3A] bg-gray-50 dark:bg-[#1C1F26]">
    <button type="button"
        wire:click="{{ $wireAnterior }}"
        @if ($pagina <= 1) disabled @endif
        class="px-2 py-1 rounded-md text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-[#2A2F3A] disabled:opacity-30 disabled:cursor-default transition-colors"
        aria-label="Página anterior">
        <i class="fas fa-chevron-left text-xs"></i>
    </button>

    <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 tabular-nums">
        {{ $pagina }} / {{ $ultima }}
    </span>

    <button type="button"
        wire:click="{{ $wireSiguiente }}"
        @if ($pagina >= $ultima) disabled @endif
        class="px-2 py-1 rounded-md text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-[#2A2F3A] disabled:opacity-30 disabled:cursor-default transition-colors"
        aria-label="Página siguiente">
        <i class="fas fa-chevron-right text-xs"></i>
    </button>
</div>
@endif
