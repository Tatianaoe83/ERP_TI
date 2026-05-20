@php
    $tickets = $metricasProductividad['tickets_detallados'] ?? [];
@endphp

<div wire:ignore.self class= "modal fade"
    id="modalProductividad"
    tabindex="-1"
    aria-labelledby="modalProductividadLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg bg-white dark:bg-[#0B1120]">

            <!-- Header -->
            <div class="modal-header border-0 px-4 py-3 text-lg bg-gradient-to-r from-blue-600 to-indigo-600">

                <div>
                    <h5 class="modal-title font-bold mb-0">
                        Tickets
                    </h5>

                    <small class="text-blue-100 text-sm">
                        Información detallada de tickets totales y cerrados
                    </small>
                </div>

                <button type="button"
                    class="btn-close btn-close-white shadow-none"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <!-- Body -->
            <div class="modal-body p-4 bg-gray-50 dark:bg-[#0F172A]">

                <!-- Controles -->
                <div class="flex justify-between items-center mb-3">

                    <div class="flex items-center gap-2 ">

                        <span>Mostrar</span>

                        <select id="registrosPorPagina"
                                class="form-select form-select-sm w-auto  border-secondary">

                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>

                        </select>

                        <span>registros</span>

                    </div>

                    <!-- Buscador -->
                    <input type="text"
                        id="buscarTicket"
                        placeholder="Buscar..."
                        class="form-control form-control-sm w-25 border-secondary">

                </div>

                <!-- Tabla -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>DESCRIPCIÓN</th>
                                <th>CLASIFICACIÓN</th>
                                <th>SOLICITANTE</th>
                                <th>RESOLUTOR</th>
                            </tr>
                        </thead>

                        <tbody id="tbodyTickets">

                            @foreach($tickets as $ticket)

                                <tr class="fila-ticket">

                                    <td>{{ $ticket->TicketID }}</td>

                                    <td>{{ $ticket->Descripcion }}</td>

                                    <td>
                                        <span class="badge bg-primary">
                                            {{ $ticket->Clasificacion ?? 'Sin asignar' }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $ticket->empleado->NombreEmpleado ?? 'Sin solicitante' }}
                                    </td>

                                    <td>
                                        {{ $ticket->responsableTI->NombreEmpleado ?? 'Sin asignar' }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                <!-- Footer tabla -->
                <div class="flex justify-between items-center mt-3">

                    <div id="infoTabla">
                        Mostrando registros
                    </div>

                    <div class="flex gap-2">

                        <button class="btn btn-sm btn-primary"
                                id="btnAnterior">

                            Anterior

                        </button>

                        <button class="btn btn-sm btn-primary"
                                id="btnSiguiente">

                            Siguiente

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
<script>

function inicializarTablaTickets() {

    const modal = document.getElementById('modalProductividad');

        if (!modal) {
            return;
        }

        const filas = Array.from(
            modal.querySelectorAll('.fila-ticket')
        );

    const selectCantidad = document.getElementById('registrosPorPagina');

    const buscador = document.getElementById('buscarTicket');

    const btnAnterior = document.getElementById('btnAnterior');

    const btnSiguiente = document.getElementById('btnSiguiente');

    const infoTabla = document.getElementById('infoTabla');

    if (
        !selectCantidad ||
        !buscador ||
        !btnAnterior ||
        !btnSiguiente ||
        !infoTabla
    ) {
        return;
    }

    let paginaActual = 1;

    let registrosPorPagina = parseInt(selectCantidad.value);

    let filasFiltradas = [...filas];

    function renderTabla() {

        const totalPaginas =
            Math.ceil(filasFiltradas.length / registrosPorPagina) || 1;

        if (paginaActual > totalPaginas) {

            paginaActual = totalPaginas;

        }

        filas.forEach(f => f.style.display = 'none');

        const inicio =
            (paginaActual - 1) * registrosPorPagina;

        const fin =
            inicio + registrosPorPagina;

        const pagina =
            filasFiltradas.slice(inicio, fin);

        pagina.forEach(f => f.style.display = '');

        const desde =
            filasFiltradas.length > 0
                ? inicio + 1
                : 0;

        const hasta =
            Math.min(fin, filasFiltradas.length);

        infoTabla.innerHTML =
            `Mostrando ${desde} a ${hasta} de ${filasFiltradas.length} registros`;

        btnAnterior.disabled =
            paginaActual === 1;

        btnSiguiente.disabled =
            fin >= filasFiltradas.length;

    }

    selectCantidad.onchange = function () {

        registrosPorPagina = parseInt(this.value);

        paginaActual = 1;

        renderTabla();

    };

    buscador.onkeyup = function () {

        const texto = this.value.toLowerCase();

        filasFiltradas = filas.filter(f =>
            f.innerText.toLowerCase().includes(texto)
        );

        paginaActual = 1;

        renderTabla();

    };

    btnAnterior.onclick = function () {

        if (paginaActual > 1) {

            paginaActual--;

            renderTabla();

        }

    };

    btnSiguiente.onclick = function () {

        const totalPaginas =
            Math.ceil(filasFiltradas.length / registrosPorPagina);

        if (paginaActual < totalPaginas) {

            paginaActual++;

            renderTabla();

        }

    };

    renderTabla();

}

document.addEventListener('DOMContentLoaded', function () {

    inicializarTablaTickets();

});

</script>