@extends('layouts.app')

@section('content')

<style>
[x-cloak] {
    display: none !important;
}

.presupuesto-container {
    width: 100%;
    min-height: 100vh;
}

/* WRAPPER TABS */
.tabs-wrapper {
    position: relative;
    min-height: 430px;
}

/* PANEL */
.tab-panel {
    position: absolute;
    inset: 0;
    width: 100%;
}

/* ESTADOS */
.tab-hidden {
    opacity: 0;
    pointer-events: none;
    transform: translateY(10px);
}

.tab-visible {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}

/* ANIMACION */
.tab-animate {
    transition:
        opacity .30s ease,
        transform .30s ease;
}

/* CARD */
.tab-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
    width: 100%;
}

.dark .tab-card {
    background: #09152F;
}

/* INPUTS */
.dark .form-control {
    background: #374151 !important;
    border-color: #4B5563 !important;
    color: white !important;
}

.dark .form-control::placeholder {
    color: #D1D5DB !important;
}

/* TABS */
.tab-button {
    flex: 1;
    padding: 14px;
    font-size: 14px;
    font-weight: 500;
    transition: all .25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border-bottom: 2px solid transparent;
}

.tab-active {
    color: #2563EB;
    border-color: #2563EB;
}

.tab-inactive {
    color: #6B7280;
}

.tab-inactive:hover {
    color: #374151;
}
</style>

<div
    x-data="{
        tab: 1,

        cambiarTab(numeroTab) {
            this.tab = numeroTab;
        }
    }"
    class="presupuesto-container px-4 py-4"
>

    <!-- TABS -->
    <div class="w-full mb-6">

        <div
            class="flex items-center border-b border-gray-200 dark:border-gray-700 w-full"
            role="tablist"
        >

            <!-- TAB 1 -->
            <button
                @click="cambiarTab(1)"
                :class="tab === 1
                    ? 'tab-active'
                    : 'tab-inactive'"
                class="tab-button"
            >

                <i class="fas fa-file-invoice"></i>

                <span>Presupuestos</span>

            </button>

            <!-- TAB 2 -->
            @can('tickets.ver-productividad')

            <button
                @click="cambiarTab(2)"
                :class="tab === 2
                    ? 'tab-active'
                    : 'tab-inactive'"
                class="tab-button"
            >

                <i class="fas fa-boxes"></i>

                <span>Inventarios</span>

            </button>

            @endcan

        </div>

    </div>

    <!-- CONTENEDOR -->
    <div class="tabs-wrapper">

        <!-- ========================= -->
        <!-- TAB PRESUPUESTOS -->
        <!-- ========================= -->

        <div
            :class="tab === 1
                ? 'tab-visible'
                : 'tab-hidden'"
            class="tab-panel tab-animate"
            x-cloak
        >

            <div class="tab-card">

                <h4 class="text-[#101D49] dark:text-white text-2xl font-semibold mb-4">
                    Generar reportes de presupuestos
                </h4>

                <!-- Leyenda tipo de persona -->
                <div class="flex items-start gap-3 p-4 mb-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-lg">
                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-lg mt-0.5"></i>
                    <div>
                        <p class="text-sm text-blue-800 dark:text-blue-200 font-medium mb-1">
                            Tipos de persona incluidos en este reporte:
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-100">
                                <i class="fas fa-user mr-1.5"></i>
                                FÍSICA
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-800 text-purple-800 dark:text-purple-100">
                                <i class="fas fa-user-plus mr-1.5"></i>
                                EXTRAORDINARIO
                            </span>
                        </div>
                    </div>
                </div>

                <form
                    enctype="multipart/form-data"
                    action="{{ route('presupuesto.descargar') }}"
                    method="POST"
                    target="_blank"
                    id="presupuestoForm"
                >

                    @csrf

                    <input type="hidden" name="modo" value="presupuesto">

                    <div class="flex flex-col gap-5">

                        <!-- TIPO -->
                        <div>

                            <label class="text-[#101D49] dark:text-white block mb-2 font-medium">
                                Tipo
                            </label>

                            <select
                                name="tipo"
                                id="semestre"
                                class="form-control"
                                required
                            >
                                <option value="mens">Mensual</option>
                                <option value="anual">Anual</option>
                            </select>

                        </div>

                        <!-- GERENCIA -->
                        <div>

                            <label class="text-[#101D49] dark:text-white block mb-2 font-medium">
                                Gerencia
                            </label>

                            {!! Form::select(
                                'GerenciaID',
                                $genusuarios->pluck('NombreGerencia','GerenciaID'),
                                null,
                                [
                                    'placeholder' => 'Seleccionar',
                                    'class'=>'jz form-control',
                                    'id' => 'GerenciaID',
                                    'required',
                                    'style' => 'width:100%'
                                ]
                            ) !!}

                        </div>

                        <!-- BOTONES -->
                        <div class="flex flex-wrap gap-3 pt-2">

                            <button
                                type="button"
                                class="btn btn-success"
                                id="btn-validar-pdf"
                            >
                                Generar PDF
                            </button>

                            <button
                                type="button"
                                class="btn btn-primary"
                                id="btn-validar-excel"
                            >
                                Generar Excel
                            </button>

                            <input
                                type="hidden"
                                name="submitbutton"
                                id="submitbutton"
                            >

                        </div>

                    </div>

                </form>

            </div>

        </div>

        <!-- ========================= -->
        <!-- TAB INVENTARIOS -->
        <!-- ========================= -->

        @can('tickets.ver-productividad')

        <div
            :class="tab === 2
                ? 'tab-visible'
                : 'tab-hidden'"
            class="tab-panel tab-animate"
            x-cloak
        >

            <div class="tab-card">

                <h4 class="text-[#101D49] dark:text-white text-2xl font-semibold mb-4">
                    Generar reportes de inventarios
                </h4>

                <!-- Leyenda tipo de persona -->
                <div class="flex items-start gap-3 p-4 mb-6 bg-emerald-50 dark:bg-emerald-900/20 border-l-4 border-emerald-500 rounded-lg">
                    <i class="fas fa-info-circle text-emerald-600 dark:text-emerald-400 text-lg mt-0.5"></i>
                    <div>
                        <p class="text-sm text-emerald-800 dark:text-emerald-200 font-medium mb-1">
                            Tipos de persona incluidos en este reporte:
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-100">
                                <i class="fas fa-user mr-1.5"></i>
                                FÍSICA
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-teal-100 dark:bg-teal-800 text-teal-800 dark:text-teal-100">
                                <i class="fas fa-user-tag mr-1.5"></i>
                                REFERENCIADO
                            </span>
                        </div>
                    </div>
                </div>

                <form
                    enctype="multipart/form-data"
                    action="{{ route('presupuesto.descargar') }}"
                    method="POST"
                    target="_blank"
                    id="inventarioForm"
                >

                    @csrf

                    <input type="hidden" name="modo" value="inventario">

                    <div class="flex flex-col gap-5">

                        <!-- TIPO -->
                        <div>

                            <label class="text-[#101D49] dark:text-white block mb-2 font-medium">
                                Tipo
                            </label>

                            <select
                                name="tipo"
                                id="semestre_inventario"
                                class="form-control"
                                required
                            >
                                <option value="mens">Mensual</option>
                                <option value="anual">Anual</option>
                            </select>

                        </div>

                        <!-- GERENCIA -->
                        <div>

                            <label class="text-[#101D49] dark:text-white block mb-2 font-medium">
                                Gerencia
                            </label>

                            {!! Form::select(
                                'GerenciaID',
                                $genusuarios->pluck('NombreGerencia','GerenciaID'),
                                null,
                                [
                                    'placeholder' => 'Seleccionar',
                                    'class'=>'jz form-control',
                                    'id' => 'GerenciaID_inventario',
                                    'required',
                                    'style' => 'width:100%'
                                ]
                            ) !!}

                        </div>

                        <!-- BOTONES -->
                        <div class="flex flex-wrap gap-3 pt-2">

                            <button
                                type="button"
                                class="btn btn-success"
                                id="btn-pdf-inventario"
                            >
                                Generar PDF
                            </button>

                            <button
                                type="button"
                                class="btn btn-primary"
                                id="btn-excel-inventario"
                            >
                                Generar Excel
                            </button>

                            <input
                                type="hidden"
                                name="submitbutton"
                                id="submitbutton_inventario"
                            >

                        </div>

                    </div>

                </form>

            </div>

        </div>

        @endcan

    </div>

</div>

@include('presupuesto.modal')

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ====================================
    // PRESUPUESTOS
    // ====================================

    const form = document.getElementById('presupuestoForm');

    const btnPdf = document.getElementById('btn-validar-pdf');
    const btnExcel = document.getElementById('btn-validar-excel');

    const submitButtonInput = document.getElementById('submitbutton');

    const modalElement = document.getElementById('modalFaltantes');

    const modal = new bootstrap.Modal(modalElement);

    function validarYEnviar(tipoBoton) {

        const gerenciaId = document.getElementById('GerenciaID').value;

        if (!gerenciaId) {

            alert('Por favor selecciona una Gerencia');

            return;
        }

        btnPdf.disabled = true;
        btnExcel.disabled = true;

        fetch('{{ route("presupuesto.verificar") }}', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },

            body: JSON.stringify({
                GerenciaID: gerenciaId
            })

        })
        .then(response => response.json())

        .then(data => {

            btnPdf.disabled = false;
            btnExcel.disabled = false;

            if (data.success === false) {

                alert('Error de validación: ' + (data.error || 'Desconocido'));

                return;
            }

            const totalEmp = document.getElementById('totalEmpleadosModal');

            const sinMesPagoMensual = document.getElementById('sinMesPagoMensualModal');

            const sinMesPagoAnual = document.getElementById('sinMesPagoAnualModal');

            const lineasConFecha = document.getElementById('lineasSinAsignarConFechaModal');

            const insumosConFecha = document.getElementById('insumosSinAsignarConFechaModal');

            if (totalEmp) {
                totalEmp.innerText = data.totalEmpleados || 0;
            }

            if (sinMesPagoMensual) {
                sinMesPagoMensual.innerText = data.empleadosSinMesPagoMensual || 0;
            }

            if (sinMesPagoAnual) {
                sinMesPagoAnual.innerText = data.empleadosSinMesPagoAnual || 0;
            }

            if (lineasConFecha) {
                lineasConFecha.innerText = data.lineasSinAsignarConFecha || 0;
            }

            if (insumosConFecha) {
                insumosConFecha.innerText = data.insumosSinAsignarConFecha || 0;
            }

            const tieneFaltantes = (

                data.empleadosSinMesPagoMensual > 0 ||
                data.empleadosSinMesPagoAnual > 0 ||
                data.lineasSinAsignarConFecha > 0 ||
                data.insumosSinAsignarConFecha > 0

            );

            if (tieneFaltantes) {

                modal.show();

            } else {

                submitButtonInput.value = tipoBoton;

                form.submit();
            }

        })
        .catch(error => {

            console.error('Error:', error);

            btnPdf.disabled = false;
            btnExcel.disabled = false;

            alert('Ocurrió un error al validar los datos.');

        });

    }

    btnPdf.addEventListener('click', function() {
        validarYEnviar('pdf');
    });

    btnExcel.addEventListener('click', function() {
        validarYEnviar('excel');
    });

    // ====================================
    // INVENTARIOS
    // ====================================

    const inventarioForm = document.getElementById('inventarioForm');

    const btnPdfInv = document.getElementById('btn-pdf-inventario');

    const btnExcelInv = document.getElementById('btn-excel-inventario');

    const submitInv = document.getElementById('submitbutton_inventario');

    if (btnPdfInv) {

        btnPdfInv.addEventListener('click', function() {

            const gerencia = document.getElementById('GerenciaID_inventario').value;

            if (!gerencia) {

                alert('Por favor selecciona una Gerencia');

                return;
            }

            submitInv.value = 'pdf';

            inventarioForm.submit();

        });

    }

    if (btnExcelInv) {

        btnExcelInv.addEventListener('click', function() {

            const gerencia = document.getElementById('GerenciaID_inventario').value;

            if (!gerencia) {

                alert('Por favor selecciona una Gerencia');

                return;
            }

            submitInv.value = 'excel';

            inventarioForm.submit();

        });

    }

});
</script>

@endsection