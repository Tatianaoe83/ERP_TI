{{--
    Selects de la cascada Categoría > Grupo > Subgrupo del modal de ticket.
    Markup compartido por indexTicket (tablero) y modal-ticket (modal global del layout).

    Depende de las props que monta partials/ticket-cascada.blade.php
    (tiposList, subtiposList, tertiposList) sobre el x-data de ticketsModal().

    El :selected de cada <option> es el respaldo clave: la opción se auto-marca al
    crearse, así no depende de que el x-model de Alpine corra en el orden correcto.
--}}
<div>
    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
        Categoría <span class="text-red-500">*</span>
    </label>
    <select
        id="tipo-select"
        x-model="ticketTipoID"
        :disabled="selected.estatus === 'Cerrado'"
        class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
               border-gray-300 bg-gray-50 text-gray-900
               focus:border-blue-500 focus:ring-blue-500
               dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100
               disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
               dark:disabled:bg-gray-800 dark:disabled:text-gray-500">
        <option value="">Seleccione</option>
        <template x-for="t in tiposList" :key="'tipo-' + t.TipoID">
            <option :value="String(t.TipoID)"
                    :selected="String(t.TipoID) === String(ticketTipoID)"
                    x-text="t.NombreTipo"></option>
        </template>
    </select>
</div>

<div>
    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">
        Grupo <span class="text-red-500">*</span>
    </label>
    <select
        id="subtipo-select"
        x-model="ticketSubtipoID"
        :disabled="!ticketTipoID || selected.estatus === 'Cerrado'"
        class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
               border-gray-300 bg-gray-50 text-gray-900
               focus:border-blue-500 focus:ring-blue-500
               dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100
               disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
               dark:disabled:bg-gray-800 dark:disabled:text-gray-500">
        <option value="">Seleccione</option>
        <template x-for="s in subtiposList" :key="'subtipo-' + s.SubtipoID">
            <option :value="String(s.SubtipoID)"
                    :selected="String(s.SubtipoID) === String(ticketSubtipoID)"
                    x-text="s.NombreSubtipo"></option>
        </template>
    </select>
</div>

<div>
    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Subgrupo</label>
    <select
        id="tertipo-select"
        x-model="ticketTertipoID"
        :disabled="!ticketSubtipoID || selected.estatus === 'Cerrado'"
        class="w-full mt-1 rounded-md text-sm border shadow-sm transition-colors duration-200
               border-gray-300 bg-gray-50 text-gray-900
               focus:border-blue-500 focus:ring-blue-500
               dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100
               disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed
               dark:disabled:bg-gray-800 dark:disabled:text-gray-500">
        <option value="">Seleccione</option>
        <template x-for="tt in tertiposList" :key="'tertipo-' + tt.TertipoID">
            <option :value="String(tt.TertipoID)"
                    :selected="String(tt.TertipoID) === String(ticketTertipoID)"
                    x-text="tt.NombreTertipo"></option>
        </template>
    </select>
</div>
