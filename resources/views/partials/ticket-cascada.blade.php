{{--
    Cascada Categoría > Grupo > Subgrupo del modal de ticket.

    Fuente única compartida por los dos modales que existen en el proyecto:
      - resources/views/tickets/indexTicket.blade.php   (tablero de /tickets)
      - resources/views/partials/tickets-modal-engine.blade.php (modal global del layout)

    El catálogo llega completo desde el servidor ($catalogoTickets, inyectado por el
    View Composer en AppServiceProvider), así que las <option> existen desde el render
    y ya no hay fetch asíncrono compitiendo con el x-model de Alpine.

    Incluir ANTES del <script> que define ticketsModal().
--}}
<script>
    // Idempotente: en /tickets lo incluye el tablero y en el resto el layout.
    // Si por alguna razón se incluye dos veces, la primera definición gana.
    if (!window.aplicarCascadaTickets) {

        window.__catalogoTickets = @json($catalogoTickets ?? ['tipos' => [], 'subtipos' => [], 'tertipos' => []]);

        /**
         * Inyecta la cascada en el objeto x-data de un modal de ticket.
         *
         * Se usa Object.defineProperties + getOwnPropertyDescriptors (no spread) porque
         * el spread EVALÚA los getters y copiaría el valor en vez del getter, matando
         * la reactividad de subtiposList / tertiposList.
         *
         * @param {Object} base  el objeto que retorna ticketsModal()
         * @returns {Object} el mismo objeto, con la cascada montada
         */
        window.aplicarCascadaTickets = function (base) {
            const catalogo = window.__catalogoTickets || { tipos: [], subtipos: [], tertipos: [] };

            const cascada = {
                tiposList: catalogo.tipos || [],
                catalogoSubtipos: catalogo.subtipos || [],
                catalogoTertipos: catalogo.tertipos || [],

                // true mientras se restauran los datos de un ticket: evita que los
                // $watch borren la selección que acaba de llegar del servidor.
                _cascadaCargando: false,
                _cascadaWatchers: false,

                // Listas derivadas: se recalculan de forma SÍNCRONA al cambiar el padre.
                get subtiposList() {
                    if (!this.ticketTipoID) return [];
                    return this.catalogoSubtipos.filter(
                        s => String(s.TipoID) === String(this.ticketTipoID)
                    );
                },

                get tertiposList() {
                    if (!this.ticketSubtipoID) return [];
                    return this.catalogoTertipos.filter(
                        t => String(t.SubtipoID) === String(this.ticketSubtipoID)
                    );
                },

                /**
                 * Registra los watchers que limpian los hijos cuando el usuario cambia el padre.
                 * Llamar desde init(). El guard evita doble registro si init() corre repetido
                 * (pasa cuando hay x-init="init()" y Alpine además auto-llama init()).
                 */
                iniciarCascada() {
                    if (this._cascadaWatchers) return;
                    this._cascadaWatchers = true;

                    this.$watch('ticketTipoID', () => {
                        if (this._cascadaCargando) return;
                        this.ticketSubtipoID = '';
                        this.ticketTertipoID = '';
                    });

                    this.$watch('ticketSubtipoID', () => {
                        if (this._cascadaCargando) return;
                        this.ticketTertipoID = '';
                    });
                },

                /**
                 * Reset SÍNCRONO al abrir el modal, antes de mostrarlo. Sin esto se alcanza
                 * a ver la categoría del ticket anterior mientras llega el fetch del nuevo.
                 */
                limpiarCascada() {
                    this._cascadaCargando = true;
                    this.ticketTipoID = '';
                    this.ticketSubtipoID = '';
                    this.ticketTertipoID = '';
                },

                /**
                 * Red de seguridad tras cargar el ticket: Alpine no re-aplica x-model cuando
                 * cambian las <option>, así que reasignamos el value ya que x-for renderizó.
                 * Libera _cascadaCargando para que los cambios del usuario vuelvan a disparar
                 * los watchers.
                 */
                restaurarCascada() {
                    this.$nextTick(() => {
                        const tipoSel = document.getElementById('tipo-select');
                        const subSel  = document.getElementById('subtipo-select');
                        const terSel  = document.getElementById('tertipo-select');

                        if (tipoSel && this.ticketTipoID)     tipoSel.value = this.ticketTipoID;
                        if (subSel  && this.ticketSubtipoID)  subSel.value  = this.ticketSubtipoID;
                        if (terSel  && this.ticketTertipoID)  terSel.value  = this.ticketTertipoID;

                        this._cascadaCargando = false;
                    });
                },
            };

            return Object.defineProperties(
                base,
                Object.getOwnPropertyDescriptors(cascada)
            );
        };
    }
</script>
