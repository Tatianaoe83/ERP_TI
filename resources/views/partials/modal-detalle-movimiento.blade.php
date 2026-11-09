<div x-data="{
    modalAbierto: false,
    cargando: false,
    movimiento: null,
    init() {
        window.__abrirModalMovimiento = (id) => this.abrir(id);
    },
    abrir(id) {
        this.modalAbierto = true;
        this.cargando = true;
        this.movimiento = null;
        fetch('/movimientos-erp/' + id + '/datos', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then((r) => {
                if (!r.ok) throw new Error('No se pudo cargar');
                return r.json();
            })
            .then((data) => {
                this.movimiento = data;
                this.cargando = false;
            })
            .catch(() => {
                this.cargando = false;
                this.modalAbierto = false;
            });
    },
    cerrar() {
        this.modalAbierto = false;
        this.movimiento = null;
    }
}">
    <div x-show="modalAbierto"
        x-cloak
        class="fixed inset-0 bg-slate-900/70 overflow-y-auto"
        style="z-index: 100000;"
        @keydown.escape.window="cerrar()"
        @click.self="cerrar()">
        <div class="relative top-16 mx-auto p-5 w-11/12 max-w-3xl shadow-xl rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 mb-16">
            <div class="flex justify-between items-center pb-3 border-b border-slate-200 dark:border-slate-700 mb-4">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                    Movimiento ERP
                    <span x-show="movimiento" x-text="'#' + movimiento?.SolicitudMovimientoID" class="text-slate-400 ml-1 font-normal"></span>
                </h3>
                <button type="button" @click="cerrar()" class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div x-show="cargando" class="text-center py-10">
                <i class="fas fa-spinner fa-spin text-2xl text-slate-400 mb-2 block"></i>
                <p class="text-sm text-slate-500">Cargando...</p>
            </div>

            <div x-show="!cargando && movimiento" class="space-y-6">
                <div class="grid grid-cols-2 gap-3 p-4 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                    <div>
                        <p class="text-xs text-slate-500 mb-0.5">Solicitó</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100" x-text="movimiento?.solicitante || 'N/A'"></p>
                        <p class="text-xs text-slate-500" x-text="movimiento?.correo"></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-0.5">Proyecto</p>
                        <p class="text-sm text-slate-900 dark:text-slate-100" x-text="movimiento?.obra || 'N/A'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-0.5">Estatus</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100" x-text="movimiento?.Estatus"></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-0.5">Folio</p>
                        <p class="text-sm text-slate-900 dark:text-slate-100" x-text="movimiento?.Folio || 'N/A'"></p>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wide mb-3">Flujo</h4>
                    <div class="flex items-stretch mb-4 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                        <template x-for="(paso, index) in movimiento?.pasos || []" :key="'mov-pipe-' + index">
                            <div class="flex-1 px-4 py-3 text-center"
                                :class="{
                                    'bg-emerald-50 dark:bg-emerald-900/20': paso.status === 'approved',
                                    'bg-red-50 dark:bg-red-900/20': paso.status === 'rejected',
                                    'bg-amber-50 dark:bg-amber-900/10': paso.status === 'pending',
                                    'border-r border-slate-200 dark:border-slate-700': index < (movimiento?.pasos?.length || 1) - 1
                                }">
                                <span class="text-xl block"
                                    :class="{
                                        'text-emerald-500': paso.status === 'approved',
                                        'text-red-500': paso.status === 'rejected',
                                        'text-amber-400': paso.status === 'pending'
                                    }">
                                    <i class="fas" :class="paso.status === 'approved' ? 'fa-check-circle' : (paso.status === 'rejected' ? 'fa-times-circle' : 'fa-clock')"></i>
                                </span>
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 block" x-text="paso.stageLabel"></span>
                                <span class="text-[10px] font-medium" x-text="paso.statusLabel"></span>
                            </div>
                        </template>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(paso, index) in movimiento?.pasos || []" :key="'mov-det-' + index">
                            <div class="px-4 py-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200" x-text="paso.stageLabel"></p>
                                <p class="text-xs text-slate-500 mt-1" x-show="paso.approverNombre" x-text="'Firma: ' + paso.approverNombre"></p>
                                <p class="text-xs text-slate-700 dark:text-slate-200 mt-1" x-show="paso.decidedByNombre" x-text="(paso.stage === 'control' ? 'Aplicó en Neodata: ' : 'Decidió: ') + paso.decidedByNombre"></p>
                                <p class="text-xs text-slate-500" x-show="paso.decidedAt" x-text="paso.decidedAt"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <p class="text-xs text-slate-500 mb-1">Mensaje</p>
                    <p class="text-sm text-slate-900 dark:text-slate-100" x-text="movimiento?.Justificacion"></p>
                </div>

                <div class="overflow-x-auto">
                    <table class="table index-table w-full">
                        <thead>
                            <tr>
                                <th>Cargo</th>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(linea, index) in movimiento?.lineas || []" :key="'lin-' + index">
                                <tr>
                                    <td x-text="linea.cargo"></td>
                                    <td x-text="linea.codigo"></td>
                                    <td x-text="linea.descripcion"></td>
                                    <td x-text="linea.cantidad"></td>
                                    <td x-text="'$' + linea.importe"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <form x-show="movimiento?.Estatus === 'Aprobada'" method="POST" :action="'/movimientos-erp/' + (movimiento?.SolicitudMovimientoID || '') + '/aplicar'" class="p-4 rounded-lg border border-slate-200 dark:border-slate-700">
                    @csrf
                    <p class="text-sm mb-3">Confirma que ya se hizo en Neodata. El folio es el del ERP y queda con tu usuario.</p>
                    <div class="flex flex-wrap gap-2 items-end">
                        <div>
                            <label class="block text-xs font-semibold uppercase mb-1">Folio</label>
                            <input type="text" name="Folio" class="form-control" required maxlength="40" :value="movimiento?.Folio || ''">
                        </div>
                        <button type="submit" class="index-page__btn-primary cursor-pointer">Marcar aplicada</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
