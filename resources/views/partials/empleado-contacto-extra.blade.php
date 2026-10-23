<div class="flex items-center gap-3" x-show="selected.telefono">
    <div class="text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center"><i class="fas fa-phone-alt"></i></div>
    <div class="text-sm text-gray-700 dark:text-gray-300">
        <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">Teléfono:</span>
        <span x-text="selected.telefono" class="font-mono"></span>
    </div>
</div>
<div class="flex items-center gap-3" x-show="selected.jefe">
    <div class="text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center"><i class="fas fa-user-tie"></i></div>
    <div class="text-sm text-gray-700 dark:text-gray-300">
        <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">Jefe directo:</span>
        <span x-text="selected.jefe"></span>
    </div>
</div>
<div class="flex items-center gap-3" x-show="selected.direccion">
    <div class="text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center"><i class="fas fa-landmark"></i></div>
    <div class="text-sm text-gray-700 dark:text-gray-300">
        <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">Dirección:</span>
        <span x-text="selected.direccion"></span>
    </div>
</div>
<div class="flex items-center gap-3" x-show="selected.unidad">
    <div class="text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center"><i class="fas fa-city"></i></div>
    <div class="text-sm text-gray-700 dark:text-gray-300">
        <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">Unidad:</span>
        <span x-text="selected.unidad"></span>
    </div>
</div>
<div class="flex items-center gap-3" x-show="selected.division">
    <div class="text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center"><i class="fas fa-layer-group"></i></div>
    <div class="text-sm text-gray-700 dark:text-gray-300">
        <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">División:</span>
        <span x-text="selected.division"></span>
    </div>
</div>
<div class="flex items-center gap-3" x-show="selected.centro_costo">
    <div class="text-gray-400 dark:text-gray-500 flex-shrink-0 w-4 text-center"><i class="fas fa-coins"></i></div>
    <div class="text-sm text-gray-700 dark:text-gray-300">
        <span class="font-bold text-xs text-gray-500 dark:text-gray-500 uppercase mr-1">Centro de costo:</span>
        <span x-text="selected.centro_costo"></span>
    </div>
</div>
