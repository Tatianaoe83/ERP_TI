@php
    $tipoActual = old('tipo_persona', isset($empleados) ? $empleados->tipo_persona : '');
    $puestoSel = old('PuestoID', isset($empleados) ? $empleados->PuestoID : '');
    $opcionesPuesto = collect($orgPuestos ?? [])->map(function ($puesto) {
        $puesto['etiqueta'] = $puesto['puestoNombre'].' — '.$puesto['areaNombre'].' — '.$puesto['gerenciaNombre'].' — '.$puesto['direccionNombre'];

        return $puesto;
    })->sortBy('etiqueta', SORT_NATURAL | SORT_FLAG_CASE)->values();
    $obraSel = old('ObraID', isset($empleados) ? $empleados->ObraID : '');
    $centroSel = old('CentroCostoID', isset($empleados) ? $empleados->CentroCostoID : '');
    $jefeSel = old('JefeDirectoID', isset($empleados) ? $empleados->JefeDirectoID : '');
@endphp

<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('tipo_persona', 'Tipo de persona:') !!}
    {!! Form::select('tipo_persona', [
        'FISICA' => 'Física',
        'EXTRAORDINARIO' => 'Extraordinario',
        'REFERENCIADO' => 'Referenciado',
    ], $tipoActual, ['class' => 'form-control jz empleado-select', 'placeholder' => 'Seleccionar', 'id' => 'tipo_persona', 'required' => true]) !!}
</div>

<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreEmpleado', 'Nombre:') !!}
    {!! Form::text('NombreEmpleado', null, ['class' => 'form-control', 'maxlength' => 100, 'required' => true]) !!}
</div>

<div class="col-sm-12 mt-2 mb-3">
    <p id="empleado-tipo-nota" class="mb-0 text-sm text-slate-500"></p>
</div>

<div class="col-sm-12" id="empleado-estructura">
    <div class="row">
        <div class="col-sm-12 text-[#101D49] dark:text-white mb-3">
            <label for="PuestoID">Puesto:</label>
            <select id="PuestoID" name="PuestoID" class="form-control jz empleado-select">
                <option value="">Seleccionar</option>
                @foreach ($opcionesPuesto as $puesto)
                    <option value="{{ $puesto['puesto'] }}" {{ (string) $puestoSel === (string) $puesto['puesto'] ? 'selected' : '' }}>{{ $puesto['etiqueta'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 text-[#101D49] dark:text-white mb-3">
            <label for="ObraID">Ubicación:</label>
            <select id="ObraID" name="ObraID" class="form-control jz empleado-select">
                <option value="">Sin ubicación</option>
                @foreach ($obrasCatalogo as $obra)
                    <option value="{{ $obra->ObraID }}" {{ (string) $obraSel === (string) $obra->ObraID ? 'selected' : '' }}>{{ $obra->NombreObra }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 text-[#101D49] dark:text-white mb-3">
            <label for="CentroCostoID">Centro de costos:</label>
            <select id="CentroCostoID" name="CentroCostoID" class="form-control jz empleado-select">
                <option value="">Sin centro de costos</option>
                @foreach ($centrosCatalogo as $centro)
                    <option value="{{ $centro->CentroCostoID }}" {{ (string) $centroSel === (string) $centro->CentroCostoID ? 'selected' : '' }}>{{ $centro->NombreCentro }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 text-[#101D49] dark:text-white mb-3">
            <label for="JefeDirectoID">Jefe inmediato:</label>
            <select id="JefeDirectoID" name="JefeDirectoID" class="form-control jz empleado-select">
                <option value="">Sin jefe inmediato</option>
                @foreach ($jefesCatalogo as $jefe)
                    <option value="{{ $jefe->EmpleadoID }}" {{ (string) $jefeSel === (string) $jefe->EmpleadoID ? 'selected' : '' }}>{{ $jefe->NombreEmpleado }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 text-[#101D49] dark:text-white mb-3">
            <label for="NumTelefono">Teléfono:</label>
            <input type="text" name="NumTelefono" id="NumTelefono" class="form-control" maxlength="10" minlength="10" pattern="[0-9]{10}" title="Debe contener exactamente 10 dígitos" value="{{ old('NumTelefono', isset($empleados) ? $empleados->NumTelefono : '') }}">
        </div>
        <div class="col-sm-6 text-[#101D49] dark:text-white mb-3">
            <label for="Correo">Correo:</label>
            <input type="text" name="Correo" id="Correo" class="form-control" maxlength="150" value="{{ old('Correo', isset($empleados) ? $empleados->Correo : '') }}">
        </div>
    </div>
</div>

@php
    $estadoEmpleado = 1;
    if (isset($empleados)) {
        $estadoEmpleado = (int) ($empleados->getAttributes()['Estado'] ?? ($empleados->Estado ? 1 : 0));
    }
    $estadoEmpleado = (int) old('Estado', $estadoEmpleado);
@endphp
<input type="hidden" name="Estado" value="{{ $estadoEmpleado }}">

@once
<style>
    .empleado-select + .select2-container { width: 100% !important; display: block; }
    .empleado-select + .select2-container .select2-selection--single {
        height: 2.75rem !important;
        border-radius: 0.55rem !important;
        display: flex;
        align-items: center;
    }
    .empleado-select + .select2-container .select2-selection__rendered {
        line-height: 2.75rem !important;
        padding-left: 0.9rem !important;
        padding-right: 2.5rem !important;
        width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .empleado-select + .select2-container .select2-selection__arrow {
        height: 2.75rem !important;
        width: 2.1rem !important;
        right: 0.15rem;
    }
    .empleado-select + .select2-container .select2-selection__arrow b {
        margin-top: -2px;
    }
    .select2-results__option .emp-opt {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
        line-height: 1.3;
        padding: 0.15rem 0;
        white-space: normal;
    }
    .select2-results__option .emp-opt__nombre { font-weight: 650; }
    .select2-results__option .emp-opt__ruta { font-size: 0.75rem; color: #6b7280; }
    html.dark .select2-results__option .emp-opt__ruta { color: #9ca3af; }
</style>
@endonce

<script>
(function (listo) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', listo);
    } else {
        listo();
    }
})(function () {
    var tipo = document.getElementById('tipo_persona');
    var nota = document.getElementById('empleado-tipo-nota');
    var bloque = document.getElementById('empleado-estructura');

    function refrescarSelect(select) {
        if (window.jQuery && jQuery(select).hasClass('select2-hidden-accessible')) {
            jQuery(select).trigger('change.select2');
        }
    }

    function formatoPuesto(state) {
        if (!state.id) {
            return state.text;
        }
        var partes = String(state.text).split(' — ');
        var caja = document.createElement('span');
        caja.className = 'emp-opt';
        var nombre = document.createElement('span');
        nombre.className = 'emp-opt__nombre';
        nombre.textContent = partes[0];
        var ruta = document.createElement('span');
        ruta.className = 'emp-opt__ruta';
        ruta.textContent = partes.slice(1).join(' · ');
        caja.appendChild(nombre);
        if (ruta.textContent) {
            caja.appendChild(ruta);
        }
        return caja;
    }

    function formatoPuestoCorto(state) {
        if (!state.id) {
            return state.text;
        }
        return String(state.text).split(' — ')[0];
    }

    function iniciarSelects() {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) {
            return;
        }
        jQuery('.empleado-select').each(function () {
            var $el = jQuery(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            var opciones = { width: '100%' };
            if (this.id === 'PuestoID') {
                opciones.templateResult = formatoPuesto;
                opciones.templateSelection = formatoPuestoCorto;
            }
            $el.select2(opciones);
        });
    }

    function aplicarTipo() {
        var valor = (tipo.value || '').toUpperCase();
        var esFisica = valor === 'FISICA';
        bloque.style.display = esFisica ? '' : 'none';
        bloque.querySelectorAll('input, select').forEach(function (campo) {
            campo.disabled = !esFisica;
            refrescarSelect(campo);
        });
        document.getElementById('PuestoID').required = esFisica;
        document.getElementById('ObraID').required = esFisica;
        if (valor === 'EXTRAORDINARIO' || valor === 'REFERENCIADO') {
            nota.textContent = 'Este tipo solo requiere el nombre.';
        } else if (esFisica) {
            nota.textContent = 'El puesto ya incluye área, gerencia, dirección, unidad y división. El jefe inmediato puede quedar vacío.';
        } else {
            nota.textContent = 'Seleccione el tipo de persona para continuar.';
        }
    }

    if (window.jQuery) {
        jQuery(tipo).on('change', aplicarTipo);
    } else {
        tipo.addEventListener('change', aplicarTipo);
    }

    iniciarSelects();
    aplicarTipo();
});
</script>
