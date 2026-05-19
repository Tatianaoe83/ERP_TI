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

.tab-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
    width: 100%;
    animation: fadeTab .65s ease;
}

.dark .tab-card {
    background: #09152F;
}

.dark .form-control {
    background: #374151 !important;
    border-color: #4B5563 !important;
    color: white !important;
}

.dark .form-control::placeholder {
    color: #D1D5DB !important;
}

@keyframes fadeTab {
    from {
        opacity: 0;
        transform: translateY(8px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
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

    <!-- Tabs -->
    <div class="w-full mb-6">

        <div
            class="flex items-center border-b border-gray-200 dark:border-gray-700 w-full"
            role="tablist">

            <!-- TAB BUTTON 1 -->
            <button
                @click="cambiarTab(1)"
                :class="tab === 1
                    ? 'text-blue-600 border-blue-600'
                    : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 px-4 py-3 text-sm font-medium transition-all duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent">

                <i class="fas fa-file-invoice"></i>

                <span>Presupuestos</span>
            </button>

            <!-- TAB BUTTON 2 -->
            @can('tickets.ver-productividad')
            <button
                @click="cambiarTab(2)"
                :class="tab === 2
                    ? 'text-blue-600 border-blue-600'
                    : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 px-4 py-3 text-sm font-medium transition-all duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent">

                <i class="fas fa-boxes"></i>

                <span>Inventarios</span>
            </button>
            @endcan

        </div>
    </div>

    <!-- CONTENIDO -->
    <div class="w-full">

        <!-- TAB PRESUPUESTOS -->
        <template x-if="tab === 1">

           <div
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="w-full"
    >

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

                        <div class="flex flex-col gap-5">

                            <!-- Tipo -->
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

                            <!-- Gerencia -->
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

                            <!-- Buttons -->
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

        </template>

        <!-- TAB INVENTARIOS -->
        @can('tickets.ver-productividad')

        <template x-if="tab === 2">

            <div
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="w-full"
    >

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

                        <div class="flex flex-col gap-5">

                            <!-- Tipo -->
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

                            <!-- Gerencia -->
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

                            <!-- Buttons -->
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

        </template>

        @endcan

    </div>

</div>

@include('presupuesto.modal')
<script>
document.addEventListener('DOMContentLoaded', function() {

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

});


const inventarioForm = document.getElementById('inventarioForm');

const btnPdfInv = document.getElementById('btn-pdf-inventario');
const btnExcelInv = document.getElementById('btn-excel-inventario');

const submitInv = document.getElementById('submitbutton_inventario');

btnPdfInv.addEventListener('click', function() {

    const gerencia = document.getElementById('GerenciaID_inventario').value;

    if (!gerencia) {
        alert('Por favor selecciona una Gerencia');
        return;
    }

    submitInv.value = 'pdf';
    inventarioForm.submit();
});

btnExcelInv.addEventListener('click', function() {

    const gerencia = document.getElementById('GerenciaID_inventario').value;

    if (!gerencia) {
        alert('Por favor selecciona una Gerencia');
        return;
    }

    submitInv.value = 'excel';
    inventarioForm.submit();
});
</script>

@endsection