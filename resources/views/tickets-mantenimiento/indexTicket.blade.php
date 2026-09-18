@php
    $responsables = \App\Models\TicketMantenimiento::obtenerResponsables();
    $responsableId = array_key_first($responsables) ?? '';
    $responsableNombre = $responsables[$responsableId] ?? 'Sin responsable';
@endphp
<style>
    .mantenimiento-container ::-webkit-scrollbar { width: 8px; height: 8px; }
    .mantenimiento-container ::-webkit-scrollbar-track { background: #f3f4f6; border-radius: 4px; }
    .mantenimiento-container ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    .dark .mantenimiento-container ::-webkit-scrollbar-track { background: #1C1F26; }
    .dark .mantenimiento-container ::-webkit-scrollbar-thumb { background: #2A2F3A; }
    .dark select { background-color: #374151 !important; color: #ffffff !important; border-color: #4b5563 !important; }
</style>

{{-- El x-data / data-vista-root está en tickets-mantenimiento/index.blade.php: los botones de
     vista se pintan junto a las pestañas y tienen que compartir esa raíz. --}}
<div class="space-y-4 w-full max-w-full overflow-x-hidden">
    <div data-vista-panel="kanban">@livewire('mantenimiento-tickets-kanban-updater')</div>
    <div data-vista-panel="lista" hidden>@livewire('mantenimiento-tickets-lista-updater')</div>
    <div data-vista-panel="tabla" hidden>@livewire('mantenimiento-tickets-tabla-updater')</div>

    {{-- Mismo modal que monta el layout para el resto de las vistas --}}
    @include('partials.modal-mantenimiento')
</div>

@include('partials.mantenimiento-modal-engine')
