@extends('layouts.app')

@section('content')
@include('inventarios.partials.tipo-persona-styles')
<style>
[x-cloak] { display: none !important; }

.rep-scope {
    margin-bottom: 1rem;
    padding: 0.8rem 1rem 0.9rem;
    border-radius: 0.75rem;
    border: 1px solid #dbeafe;
    background: #f8fafc;
}
.dark .rep-scope {
    background: rgba(30, 41, 59, 0.6);
    border-color: #334155;
}
.rep-scope__head {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.25rem 0.6rem;
    margin-bottom: 0.65rem;
}
.rep-scope__head h4 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 700;
    color: #101D49;
}
.rep-scope__head h4 i {
    margin-right: 0.35rem;
    color: #3b82f6;
}
.rep-scope__head span {
    font-size: 12px;
    color: #64748b;
}
.dark .rep-scope__head h4 { color: #f1f5f9; }
.dark .rep-scope__head span { color: #94a3b8; }
.rep-scope__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 0.6rem;
}
.rep-scope__card {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    padding: 0.6rem 0.75rem;
    border-radius: 0.6rem;
    border: 1px solid #e2e8f0;
    border-left: 3px solid var(--rep-accent, #94a3b8);
    background: #fff;
}
.dark .rep-scope__card {
    background: #0f172a;
    border-color: #334155;
    border-left-color: var(--rep-accent, #94a3b8);
}
.rep-scope__card--fisica { --rep-accent: #3b82f6; }
.rep-scope__card--referenciado { --rep-accent: #8b5cf6; }
.rep-scope__card--extraordinario { --rep-accent: #f97316; }
.rep-scope__card-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
}
.rep-scope__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.3rem;
}
.rep-scope__row-label {
    min-width: 3.9rem;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #047857;
}
.rep-scope__row-label--off { color: #94a3b8; }
.dark .rep-scope__row-label { color: #6ee7b7; }
.dark .rep-scope__row-label--off { color: #64748b; }
.rep-scope .inv-chip {
    padding: 0.16rem 0.5rem;
    font-size: 10.5px;
}
.rep-scope .inv-chip-off {
    background: transparent;
    color: #94a3b8;
    border: 1px dashed #cbd5e1;
    text-decoration: line-through;
}
.dark .rep-scope .inv-chip-off { color: #64748b; border-color: #475569; }
.dark .rep-scope .inv-chip-stock { background: rgba(4, 120, 87, 0.18); color: #6ee7b7; border-color: rgba(52, 211, 153, 0.4); }
.dark .rep-scope .inv-chip-extra { background: rgba(194, 65, 12, 0.18); color: #fdba74; border-color: rgba(251, 146, 60, 0.45); }
.dark .rep-scope .inv-chip-share { background: rgba(29, 78, 216, 0.2); color: #93c5fd; border-color: rgba(96, 165, 250, 0.45); }
</style>

<div data-app-tabset>
<x-index-page
    title="Presupuesto"
    icon="fa-file-invoice"
    subtitle="Genera reportes de presupuestos e inventarios"
    :show-count="false"
    :card="false"
>
    <x-slot name="headerActions">
        @can('editar-conf-presupuesto')
            <a href="{{ route('presupuesto.configuracion') }}" class="index-page__btn-secondary">
                <i class="fas fa-sliders-h"></i> Conf. presupuesto
            </a>
        @endcan
    </x-slot>
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
        <section class="rep-scope" aria-labelledby="rep-scope-presupuesto">
            <div class="rep-scope__head">
                <h4 id="rep-scope-presupuesto"><i class="fas fa-info-circle" aria-hidden="true"></i>Qué incluye este reporte</h4>
                <span>Solo empleados activos · equipos, insumos y líneas</span>
            </div>
            <div class="rep-scope__grid">
                <article class="rep-scope__card rep-scope__card--fisica">
                    <div class="rep-scope__card-head">
                        <span class="inv-tipo-badge inv-tipo-fisica">Física</span>
                    </div>
                    <div class="rep-scope__row">
                        <span class="rep-scope__row-label">Entra</span>
                        <span class="inv-chip inv-chip-share">Compartido</span><span class="inv-chip inv-chip-extra">Extra</span>
                    </div>
                    <div class="rep-scope__row">
                        <span class="rep-scope__row-label rep-scope__row-label--off">No entra</span>
                        <span class="inv-chip inv-chip-off">Stock</span>
                    </div>
                </article>
                <article class="rep-scope__card rep-scope__card--referenciado">
                    <div class="rep-scope__card-head">
                        <span class="inv-tipo-badge inv-tipo-referenciado">Referenciado</span>
                    </div>
                    <div class="rep-scope__row">
                        <span class="rep-scope__row-label">Entra</span>
                        <span class="inv-chip inv-chip-extra">Extra</span><span class="inv-chip inv-chip-share">Compartido</span>
                    </div>
                    <div class="rep-scope__row">
                        <span class="rep-scope__row-label rep-scope__row-label--off">No entra</span>
                        <span class="inv-chip inv-chip-off">Stock</span>
                    </div>
                </article>
                <article class="rep-scope__card rep-scope__card--extraordinario">
                    <div class="rep-scope__card-head">
                        <span class="inv-tipo-badge inv-tipo-extraordinario">Extraordinario</span>
                    </div>
                    <div class="rep-scope__row">
                        <span class="rep-scope__row-label">Entra</span>
                        <span class="inv-chip inv-chip-extra">Extra</span>
                    </div>
                </article>
            </div>
        </section>

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
        <section class="rep-scope" aria-labelledby="rep-scope-inventario">
            <div class="rep-scope__head">
                <h4 id="rep-scope-inventario"><i class="fas fa-info-circle" aria-hidden="true"></i>Qué incluye este reporte</h4>
                <span>Solo empleados activos · equipos, insumos y líneas</span>
            </div>
            <div class="rep-scope__grid">
                <article class="rep-scope__card rep-scope__card--fisica">
                    <div class="rep-scope__card-head">
                        <span class="inv-tipo-badge inv-tipo-fisica">Física</span>
                    </div>
                    <div class="rep-scope__row">
                        <span class="rep-scope__row-label">Entra</span>
                        <span class="inv-chip inv-chip-stock">Stock</span><span class="inv-chip inv-chip-share">Compartido</span>
                    </div>
                    <div class="rep-scope__row">
                        <span class="rep-scope__row-label rep-scope__row-label--off">No entra</span>
                        <span class="inv-chip inv-chip-off">Extra</span>
                    </div>
                </article>
                <article class="rep-scope__card rep-scope__card--referenciado">
                    <div class="rep-scope__card-head">
                        <span class="inv-tipo-badge inv-tipo-referenciado">Referenciado</span>
                    </div>
                    <div class="rep-scope__row">
                        <span class="rep-scope__row-label">Entra</span>
                        <span class="inv-chip inv-chip-stock">Stock</span><span class="inv-chip inv-chip-share">Compartido</span>
                    </div>
                    <div class="rep-scope__row">
                        <span class="rep-scope__row-label rep-scope__row-label--off">No entra</span>
                        <span class="inv-chip inv-chip-off">Extra</span>
                    </div>
                </article>
            </div>
        </section>

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
(function () {
    function valorGerencia(id) {
        var el = document.getElementById(id);
        if (!el) return '';
        if (window.jQuery && jQuery.fn && jQuery.fn.select2 && jQuery(el).hasClass('select2-hidden-accessible')) {
            return jQuery(el).val() || '';
        }
        return el.value || '';
    }

    function mostrarModalFaltantes() {
        var el = document.getElementById('modalFaltantes');
        if (!el || !window.bootstrap || !bootstrap.Modal) return;
        bootstrap.Modal.getOrCreateInstance(el).show();
    }

    function validarYEnviar(tipoBoton) {
        var form = document.getElementById('presupuestoForm');
        var btnPdf = document.getElementById('btn-validar-pdf');
        var btnExcel = document.getElementById('btn-validar-excel');
        var submitButtonInput = document.getElementById('submitbutton');
        var gerenciaId = valorGerencia('GerenciaID');

        if (!form || !submitButtonInput) return;
        if (!gerenciaId) {
            alert('Por favor selecciona una Gerencia');
            return;
        }

        if (btnPdf) btnPdf.disabled = true;
        if (btnExcel) btnExcel.disabled = true;

        var csrf = document.querySelector('meta[name="csrf-token"]');
        fetch('{{ route("presupuesto.verificar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '{{ csrf_token() }}'
            },
            body: JSON.stringify({ GerenciaID: gerenciaId })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (btnPdf) btnPdf.disabled = false;
            if (btnExcel) btnExcel.disabled = false;

            if (data.success === false) {
                alert('Error de validación: ' + (data.error || 'Desconocido'));
                return;
            }

            var totalEmp = document.getElementById('totalEmpleadosModal');
            var sinMesPagoMensual = document.getElementById('sinMesPagoMensualModal');
            var sinMesPagoAnual = document.getElementById('sinMesPagoAnualModal');
            var lineasConFecha = document.getElementById('lineasSinAsignarConFechaModal');
            var insumosConFecha = document.getElementById('insumosSinAsignarConFechaModal');

            if (totalEmp) totalEmp.innerText = data.totalEmpleados || 0;
            if (sinMesPagoMensual) sinMesPagoMensual.innerText = data.empleadosSinMesPagoMensual || 0;
            if (sinMesPagoAnual) sinMesPagoAnual.innerText = data.empleadosSinMesPagoAnual || 0;
            if (lineasConFecha) lineasConFecha.innerText = data.lineasSinAsignarConFecha || 0;
            if (insumosConFecha) insumosConFecha.innerText = data.insumosSinAsignarConFecha || 0;

            var tieneFaltantes = (
                data.empleadosSinMesPagoMensual > 0 ||
                data.empleadosSinMesPagoAnual > 0 ||
                data.lineasSinAsignarConFecha > 0 ||
                data.insumosSinAsignarConFecha > 0
            );

            if (tieneFaltantes) {
                mostrarModalFaltantes();
            } else {
                submitButtonInput.value = tipoBoton;
                if (tipoBoton === 'excel' && window.AppDownload) {
                    window.AppDownload.fromForm(form, 'Excel');
                } else {
                    form.submit();
                }
            }
        })
        .catch(function (error) {
            console.error('Error:', error);
            if (btnPdf) btnPdf.disabled = false;
            if (btnExcel) btnExcel.disabled = false;
            alert('Ocurrió un error al validar los datos.');
        });
    }

    function enviarInventario(tipoBoton) {
        var inventarioForm = document.getElementById('inventarioForm');
        var submitInv = document.getElementById('submitbutton_inventario');
        var gerencia = valorGerencia('GerenciaID_inventario');

        if (!inventarioForm || !submitInv) return;
        if (!gerencia) {
            alert('Por favor selecciona una Gerencia');
            return;
        }
        submitInv.value = tipoBoton;
        if (tipoBoton === 'excel' && window.AppDownload) {
            window.AppDownload.fromForm(inventarioForm, 'Excel');
        } else {
            inventarioForm.submit();
        }
    }

    function bindOnce(el, event, handler) {
        if (!el || el.dataset.bound === '1') return;
        el.dataset.bound = '1';
        el.addEventListener(event, handler);
    }

    function initPresupuestoPage() {
        bindOnce(document.getElementById('btn-validar-pdf'), 'click', function () { validarYEnviar('pdf'); });
        bindOnce(document.getElementById('btn-validar-excel'), 'click', function () { validarYEnviar('excel'); });
        bindOnce(document.getElementById('btn-pdf-inventario'), 'click', function () { enviarInventario('pdf'); });
        bindOnce(document.getElementById('btn-excel-inventario'), 'click', function () { enviarInventario('excel'); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPresupuestoPage);
    } else {
        initPresupuestoPage();
    }
})();
</script>
@endsection
