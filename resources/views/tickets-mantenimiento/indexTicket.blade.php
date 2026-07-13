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

<div
    x-data="mantenimientoModal()"
    x-init="init()"
    class="mantenimiento-container space-y-4 w-full max-w-full overflow-x-hidden min-h-screen p-6">

    <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-2 mb-4">
        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">
            <i class="fas fa-tools mr-2 text-blue-500"></i>Mantenimientos de Compras
        </h2>
        <div class="flex items-center gap-2 w-full sm:w-auto justify-center sm:justify-end">
            <span class="text-xs sm:text-sm text-gray-500 font-medium hidden sm:inline">Vista:</span>
            <div class="flex items-center gap-1 border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-lg p-1">
                <button @click="vista = 'kanban'; localStorage.setItem('mantenimientoVista', 'kanban')"
                    :class="vista === 'kanban' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all flex items-center gap-1 sm:gap-2">
                    <i class="fas fa-columns text-xs"></i><span class="hidden sm:inline">Kanban</span>
                </button>
                <button @click="vista = 'lista'; localStorage.setItem('mantenimientoVista', 'lista')"
                    :class="vista === 'lista' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all flex items-center gap-1 sm:gap-2">
                    <i class="fas fa-list text-xs"></i><span class="hidden sm:inline">Lista</span>
                </button>
                <button @click="vista = 'tabla'; localStorage.setItem('mantenimientoVista', 'tabla')"
                    :class="vista === 'tabla' ? 'bg-[#2563EB] text-white' : 'text-[#9CA3AF] hover:text-[#E5E7EB]'"
                    class="px-2 sm:px-3 py-1.5 rounded-md text-xs sm:text-sm font-medium transition-all flex items-center gap-1 sm:gap-2">
                    <i class="fas fa-table text-xs"></i><span class="hidden sm:inline">Tabla</span>
                </button>
            </div>
        </div>
    </div>

    <div x-show="vista === 'kanban'" x-transition>@livewire('mantenimiento-tickets-kanban-updater')</div>
    <div x-show="vista === 'lista'" x-transition>@livewire('mantenimiento-tickets-lista-updater')</div>
    <div x-show="vista === 'tabla'" x-transition>@livewire('mantenimiento-tickets-tabla-updater')</div>

    {{-- Mismo modal que monta el layout para el resto de las vistas --}}
    @include('partials.modal-mantenimiento')
</div>

@include('partials.mantenimiento-modal-engine')
