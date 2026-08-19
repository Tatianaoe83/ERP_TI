@extends('layouts.app')

@section('content')
<style>
[x-cloak] { display: none !important; }

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
.info-banner__title {
    font-weight: 700;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin: 0 0 0.25rem;
}
.info-banner__text {
    font-size: 0.8125rem;
    margin: 0;
    opacity: 0.9;
    line-height: 1.4;
}
</style>

<div data-app-tabset>
<x-index-page
    title="Presupuesto"
    icon="fa-file-invoice"
    subtitle="Genera reportes de presupuestos e inventarios"
    :show-count="false"
    :card="false"
>
    <x-slot name="tabs">
        <div class="app-tabs" role="tablist">
            <button type="button" data-app-tab="1" class="app-tabs__btn is-active">
                <i class="fas fa-file-invoice"></i>
                <span>Presupuestos</span>
            </button>
            @can('tickets.ver-productividad')
            <button type="button" data-app-tab="2" class="app-tabs__btn">
                <i class="fas fa-boxes"></i>
                <span>Inventarios</span>
            </button>
            @endcan
        </div>
    </x-slot>

    <div data-app-panel="1">
        <div class="info-banner">
            <div>
                <p class="info-banner__title">Tipos de empleados incluidos</p>
                <p class="info-banner__text">Este reporte solo toma en cuenta empleados tipo <strong>FÍSICA</strong> y <strong>EXTRAORDINARIO</strong>.</p>
            </div>
        </div>

        <div class="index-page__card crud-page__card">
            <h4 class="index-page__title" style="font-size:1.1rem;margin-bottom:1rem;">Generar reportes de presupuestos</h4>
            <form enctype="multipart/form-data" action="{{ route('presupuesto.descargar') }}" method="POST" target="_blank" id="presupuestoForm">
                @csrf
                <input type="hidden" name="modo" value="presupuesto">
                <div class="row crud-form">
                    <div class="col-sm-6">
                        <label>Tipo</label>
                        <select name="tipo" id="semestre" class="form-control" required>
                            <option value="mens">Mensual</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label>Gerencia</label>
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
                </div>
                <div class="crud-page__actions">
                    <button type="button" class="index-page__btn-primary" id="btn-validar-pdf">Generar PDF</button>
                    <button type="button" class="index-page__btn-secondary" id="btn-validar-excel">Generar Excel</button>
                    <input type="hidden" name="submitbutton" id="submitbutton">
                </div>
            </form>
        </div>
    </div>

    @can('tickets.ver-productividad')
    <div data-app-panel="2" hidden>
        <div class="info-banner">
            <div>
                <p class="info-banner__title">Tipos de empleados incluidos</p>
                <p class="info-banner__text">Este reporte solo toma en cuenta empleados tipo <strong>FÍSICA</strong> y <strong>REFERENCIADO</strong>.</p>
            </div>
        </div>

        <div class="index-page__card crud-page__card">
            <h4 class="index-page__title" style="font-size:1.1rem;margin-bottom:1rem;">Generar reportes de inventarios</h4>
            <form enctype="multipart/form-data" action="{{ route('presupuesto.descargar') }}" method="POST" target="_blank" id="inventarioForm">
                @csrf
                <input type="hidden" name="modo" value="inventario">
                <div class="row crud-form">
                    <div class="col-sm-6">
                        <label>Tipo</label>
                        <select name="tipo" id="semestre_inventario" class="form-control" required>
                            <option value="mens">Mensual</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label>Gerencia</label>
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
                </div>
                <div class="crud-page__actions">
                    <button type="button" class="index-page__btn-primary" id="btn-pdf-inventario">Generar PDF</button>
                    <button type="button" class="index-page__btn-secondary" id="btn-excel-inventario">Generar Excel</button>
                    <input type="hidden" name="submitbutton" id="submitbutton_inventario">
                </div>
            </form>
        </div>
    </div>
    @endcan
</x-index-page>
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
            body: JSON.stringify({ GerenciaID: gerenciaId })
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

            if (totalEmp) totalEmp.innerText = data.totalEmpleados || 0;
            if (sinMesPagoMensual) sinMesPagoMensual.innerText = data.empleadosSinMesPagoMensual || 0;
            if (sinMesPagoAnual) sinMesPagoAnual.innerText = data.empleadosSinMesPagoAnual || 0;
            if (lineasConFecha) lineasConFecha.innerText = data.lineasSinAsignarConFecha || 0;
            if (insumosConFecha) insumosConFecha.innerText = data.insumosSinAsignarConFecha || 0;

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

    btnPdf.addEventListener('click', function() { validarYEnviar('pdf'); });
    btnExcel.addEventListener('click', function() { validarYEnviar('excel'); });

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
