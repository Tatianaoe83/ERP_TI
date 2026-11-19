@if (($ultima ?? 1) > 1)
<nav class="erp-page" aria-label="Paginación">
    @if ($pagina <= 1)
        <span class="erp-page__btn is-disabled" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
    @else
        <button type="button" wire:click="{{ $wireAnterior }}" class="erp-page__btn" aria-label="Página anterior">
            <i class="fas fa-chevron-left"></i>
        </button>
    @endif

    <span class="erp-page__gap">{{ $pagina }} / {{ $ultima }}</span>

    @if ($pagina >= $ultima)
        <span class="erp-page__btn is-disabled" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
    @else
        <button type="button" wire:click="{{ $wireSiguiente }}" class="erp-page__btn" aria-label="Página siguiente">
            <i class="fas fa-chevron-right"></i>
        </button>
    @endif
</nav>
@endif
