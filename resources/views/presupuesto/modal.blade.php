<!-- MODAL -->
<div class="modal fade"
    id="modalFaltantes"
    tabindex="-1"
    aria-labelledby="modalFaltantesLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content dark:bg-[#101010] bg-white">

            <div class="modal-header border-gray-200 dark:border-secondary">

                <h5 class="modal-title text-danger" id="modalFaltantesLabel">
                    <i class="fas fa-exclamation-triangle"></i>
                    Resumen de Validación de Gerencia
                </h5>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <p class="text-[#101D49] dark:text-gray-300">
                    Se han detectado datos incompletos en los inventarios.
                    Para visualizar el presupuesto detallado,
                    es necesario corregir los siguientes puntos:
                </p>

                <div class="mt-4 p-4 bg-gray-100 dark:bg-[#1a1a1a] rounded border border-gray-200 dark:border-secondary text-[#101D49] dark:text-gray-200"
                    id="infoAdicionalEmpleados">

                    <h6 class="mb-3 border-bottom border-gray-300 dark:border-secondary pb-2 text-[#101D49] dark:text-white">
                        <i class="fas fa-users"></i>
                        Estado de Empleados e Insumos
                    </h6>

                    <div class="d-flex flex-column gap-3">

                        <p class="mb-0 d-flex justify-content-between align-items-center text-[#101D49] dark:text-gray-200">
                            <strong>Total De Empleados:</strong>

                            <span id="totalEmpleadosModal"
                                class="badge bg-success fs-6">
                                0
                            </span>
                        </p>

                        <p class="mb-0 d-flex justify-content-between align-items-center text-[#101D49] dark:text-gray-200">
                            <strong>
                                Empleados Con Insumos Mensuales
                                Con Fecha De Renovacion
                                Sin Mes de Pago
                            </strong>

                            <span id="sinMesPagoMensualModal"
                                class="badge bg-danger fs-6">
                                0
                            </span>
                        </p>

                        <p class="mb-0 d-flex justify-content-between align-items-center text-[#101D49] dark:text-gray-200">
                            <strong>
                                Empleados Con Insumos Anuales
                                Con Fecha De Renovacion
                                Sin Mes de Pago
                            </strong>

                            <span id="sinMesPagoAnualModal"
                                class="badge bg-danger fs-6">
                                0
                            </span>
                        </p>

                        <p class="mb-0 d-flex justify-content-between align-items-center text-[#101D49] dark:text-gray-200">
                            <strong>
                                Lineas Telefonicas Disponibles
                                Con Fecha De Renovacion
                                Sin Empleado Asignado:
                            </strong>

                            <span id="lineasSinAsignarConFechaModal"
                                class="badge bg-danger fs-6">
                                0
                            </span>
                        </p>

                        <p class="mb-0 d-flex justify-content-between align-items-center text-[#101D49] dark:text-gray-200">
                            <strong>
                                Insumos Disponibles
                                Con Fecha De Renovacion
                                Sin Empleado Asignado:
                            </strong>

                            <span id="insumosSinAsignarConFechaModal"
                                class="badge bg-danger fs-6">
                                0
                            </span>
                        </p>

                    </div>

                </div>

            </div>

            <div class="modal-footer border-gray-200 dark:border-secondary">

                <button type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal">
                    Cerrar
                </button>

                <a href="{{ route('inventarios.index') }}"
                    class="btn btn-primary">

                    <i class="fas fa-edit"></i>
                    Corregir en Inventarios
                </a>

            </div>

        </div>

    </div>

</div>
