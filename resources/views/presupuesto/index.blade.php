@extends('layouts.app')

@section('content')

<div x-data="{
    tab: 1,
    cambiarTab(numeroTab) {
        this.tab = numeroTab;
    }
}" class="px-2 w-full max-w-full overflow-x-hidden">

    <!-- Tabs -->
    <div class="w-full mb-2">
        <div
            class="flex items-center border-b border-gray-200 w-full"
            role="tablist">

            <button
                @click="cambiarTab(1)"
                :class="tab === 1 ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 relative px-4 py-3 text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent">

                <i :class="tab === 1 ? 'fas fa-ticket-alt text-xs text-blue-600' : 'fas fa-ticket-alt text-xs text-gray-500'"></i>
                <span>Presupuestos</span>
            </button>

            @can('tickets.ver-productividad')
            <button
                @click="cambiarTab(2)"
                :class="tab === 2 ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700'"
                class="flex-1 relative px-4 py-3 text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 border-b-2 border-transparent">

                <i :class="tab === 2 ? 'fas fa-chart-line text-xs text-blue-600' : 'fas fa-chart-line text-xs text-gray-500'"></i>
                <span>Inventarios</span>
            </button>
            @endcan

        </div>
    </div>

    <!-- TAB 1 -->
    <div x-show="tab === 1" x-transition>

        <div class="row">

            <div class="col-12 col-md-12 col-lg-12">

                <h4 class="text-[#101D49] dark:text-white mt-4">
                    Generar reportes de presupuestos
                </h4>

                <form enctype="multipart/form-data"
                    action="{{ route('presupuesto.descargar') }}"
                    method="POST"
                    target="_blank"
                    id="presupuestoForm">

                    {{ csrf_field() }}

                    <div class="flex flex-col gap-2">

                        {!! Form::label('tipo', 'Tipo:', ['class' => 'text-[#101D49] dark:text-white']) !!}

                        <select name="tipo" id="semestre" class="form-control mb-4" required>
                            <option value="mens">Mensual</option>
                            <option value="anual">Anual</option>
                        </select>



                        {!! Form::label('GerenciaID', 'Gerencia:', ['class' => 'text-[#101D49] dark:text-white']) !!}

                        {!! Form::select(
                            'GerenciaID',
                            $genusuarios->pluck('NombreGerencia','GerenciaID'),
                            null,
                            [
                                'placeholder' => 'Seleccionar',
                                'class'=>'jz form-control',
                                'required',
                                'style' => 'width: 100%',
                            ]
                        ) !!}

                        <div class="mt-4">
                            <button type="button" class="btn btn-success" id="btn-validar-pdf">
                                Generar PDF
                            </button>

                            <button type="button" class="btn btn-primary" id="btn-validar-excel">
                                Generar Excel
                            </button>

                            <input type="hidden" name="submitbutton" id="submitbutton">
                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

   <!-- TAB 2 -->
@can('tickets.ver-productividad')
<div x-show="tab === 2" x-transition>

    <div class="row">

        <div class="col-12 col-md-12 col-lg-12">

            <h4 class="text-[#101D49] dark:text-white mt-4">
                Generar reportes de inventarios
            </h4>

            <form enctype="multipart/form-data"
                action="{{ route('presupuesto.descargar') }}"
                method="POST"
                target="_blank"
                id="inventarioForm">

                {{ csrf_field() }}

                <div class="flex flex-col gap-2">

                    {!! Form::label('tipo_inv', 'Tipo:', ['class' => 'text-[#101D49] dark:text-white']) !!}

                    <select name="tipo"
                        id="semestre_inventario"
                        class="form-control mb-4"
                        required>

                        <option value="mens">Mensual</option>
                        <option value="anual">Anual</option>

                    </select>
                  

                    {!! Form::label('GerenciaID_inv', 'Gerencia:', ['class' => 'text-[#101D49] dark:text-white']) !!}

                    {!! Form::select(
                        'GerenciaID',
                        $genusuarios->pluck('NombreGerencia','GerenciaID'),
                        null,
                        [
                            'placeholder' => 'Seleccionar',
                            'class'=>'jz form-control',
                            'id' => 'GerenciaID_inventario',
                            'required',
                            'style' => 'width: 100%',
                        ]
                    ) !!}

                    <div class="mt-4">

                        <button type="button"
                            class="btn btn-success"
                            id="btn-pdf-inventario">
                            Generar PDF
                        </button>

                        <button type="button"
                            class="btn btn-primary"
                            id="btn-excel-inventario">
                            Generar Excel
                        </button>

                        <input type="hidden"
                            name="submitbutton"
                            id="submitbutton_inventario">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan


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