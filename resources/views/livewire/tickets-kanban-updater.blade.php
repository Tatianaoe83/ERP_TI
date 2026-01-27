<div wire:poll.30s="actualizarDatos" 
     wire:loading.class="opacity-50"
     style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;">
    <!-- Componente invisible que actualiza los datos cada 30 segundos para la vista Kanban -->
    <!-- Los eventos se emiten a Alpine.js para actualizar la UI -->
    <!-- Usamos position absolute en lugar de display none para que Livewire pueda hacer polling -->
    <span wire:loading class="text-xs text-gray-500">Actualizando...</span>
</div>
