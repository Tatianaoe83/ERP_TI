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

.dark .tab-inactive:hover {
    color: #D1D5DB;
}

/* BANNERS INFORMATIVOS */
.info-banner {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1.25rem;
    margin-bottom: 1.25rem;
    border-radius: 0.75rem;
    border-left: 4px solid #6366f1;
    background: #eef2ff;
    font-size: 0.85rem;
    color: #4338ca;
}
.dark .info-banner {
    background: #1e1b4b;
    border-left-color: #818cf8;
    color: #a5b4fc;
}

.info-banner__content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
}

.info-banner__title {
    font-weight: 700;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin: 0;
}

.info-banner__text {
    font-size: 0.8125rem;
    margin: 0;
    opacity: 0.9;
    line-height: 1.4;
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

            <div class="info-banner">
                <div class="info-banner__content">
                    <p class="info-banner__title">Tipos de empleados incluidos</p>
                    <p class="info-banner__text">Este reporte solo toma en cuenta empleados tipo <strong>FÍSICA</strong> y <strong>EXTRAORDINARIO</strong>.</p>
                </div>
            </div>

            <div class="tab-card">

                <h4 class="text-[#101D49] dark:text-white text-2xl font-semibold mb-6">
                    Generar reportes de presupuestos
                </h4>

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

            <div class="info-banner">
                <div class="info-banner__content">
                    <p class="info-banner__title">Tipos de empleados incluidos</p>
                    <p class="info-banner__text">Este reporte solo toma en cuenta empleados tipo <strong>FÍSICA</strong> y <strong>REFERENCIADO</strong>.</p>
                </div>
            </div>

            <div class="tab-card">

                <h4 class="text-[#101D49] dark:text-white text-2xl font-semibold mb-6">
                    Generar reportes de inventarios
                </h4>

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