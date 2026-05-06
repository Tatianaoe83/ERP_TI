@extends('layouts.app')

@section('content')
<!--- <h3 class="text-[#101D49] dark:text-white">Presupuestos</h3> --->
<div class="row">

  <div class="col-12 col-md-12 col-lg-12">

    <h4 class="text-[#101D49] dark:text-white">Generar reportes de presupuestos</h4>
    <form enctype="multipart/form-data" action="{{ route('presupuesto.descargar') }}" method="POST" target="_blank" id="presupuestoForm">
      {{ csrf_field() }}

      <div class="flex flex-col gap-2">
        {!! Form::label('tipo', 'Tipo:', ['class' => 'text-[#101D49] dark:text-white']) !!}
        <select name="tipo" id="semestre" class="form-control" required>
          <option value="mens">Mensual</option>
          <option value="anual">Anual</option>

        </select>


        {!! Form::label('GerenciaID', 'Gerencia:', ['class' => 'text-[#101D49] dark:text-white']) !!}
        {!! Form::select('GerenciaID', $genusuarios->pluck('NombreGerencia','GerenciaID'), null, ['placeholder' => 'Seleccionar', 'class'=>'jz form-control', 'required','style' => 'width: 100%', ]) !!}

        <div>
          <button type="button" class="btn btn-success" id="btn-validar-pdf">Generar PDF</button>
          <button type="button" class="btn btn-primary" id="btn-validar-excel">Generar Excel</button>
          <input type="hidden" name="submitbutton" id="submitbutton">
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal de Advertencia de Fechas Faltantes -->
<div class="modal fade" id="modalFaltantes" tabindex="-1" aria-labelledby="modalFaltantesLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content dark:bg-[#101010]">
      <div class="modal-header">
        <h5 class="modal-title text-danger" id="modalFaltantesLabel">
            <i class="fas fa-exclamation-triangle"></i> Resumen de Gerencia
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-white">Se han detectado registros que requieren atención antes de proceder con el presupuesto detallado.</p>
        
        <div class="mt-4 p-3 bg-[#1a1a1a] rounded border border-secondary" id="infoAdicionalEmpleados">
            <h6 class="text-white mb-3 border-bottom pb-2">Información de Empleados (<span id="gerenciaNombre"></span>)</h6>
            
            <div class="grid grid-cols-1 gap-3">
                <p class="mb-0 text-white">
                    <strong>Total De Empleados:</strong> 
                    <span id="totalEmpleadosModal" class="badge bg-primary fs-6">0</span>
                </p>

                <p class="mb-0 text-white">
                    <strong>Empleados Con Insumos Sin Mes De Pago:</strong> 
                    <span id="sinMesPagoModal" class="badge bg-danger fs-6">0</span>
                </p>

                <p class="mb-0 text-white">
                    <strong>Empleados Sin Línea/Insumo con Fecha:</strong> 
                    <span id="sinAsignacionConFechaModal" class="badge bg-danger fs-6">0</span>
                </p>

                 <p class="mb-0 text-white">
                    <strong>Lineas Con Fecha De Renovacion Sin Empleado Asignado: </strong> 
                    <span id="lineasSinAsignarConFechaModal" class="badge bg-danger fs-6">0</span>
                </p>

                 <p class="mb-0 text-white">
                    <strong>Insumos Con Fecha De Renovacion Sin Empleado Asignado:</strong> 
                    <span id="insumosSinAsignarConFechaModal" class="badge bg-danger fs-6">0</span>
                </p>
            </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <a href="{{ route('inventarios.index') }}" class="btn btn-primary">Ir a Inventarios</a>
      </div>
    </div>
  </div>
</div>

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
            const sinAsignar = document.getElementById('sinAsignarModal');
            const lineasCat = document.getElementById('lineasCatalogoModal');
            const sinMesPago = document.getElementById('sinMesPagoModal');
            const sinAsignacionFecha = document.getElementById('sinAsignacionConFechaModal');

            if (totalEmp) totalEmp.innerText = data.totalEmpleados || 0;
            if (sinAsignar) sinAsignar.innerText = data.empleadosSinAsignar || 0;
            if (lineasCat) lineasCat.innerText = data.lineasCatalogoSinAsignar || 0;
            if (sinMesPago) sinMesPago.innerText = data.empleadosSinMesPago || 0;
            if (sinAsignacionFecha) sinAsignacionFecha.innerText = data.empleadosSinAsignacionConFecha || 0;

            // 🔴 VALIDACIÓN EXTENDIDA (bloquea PDF si hay cualquiera de estos casos)
            if (
                data.count > 0 || 
                data.empleadosSinAsignar > 0 ||
                data.empleadosSinMesPago > 0 ||
                data.empleadosSinAsignacionConFecha > 0
            ) {
                modal.show();

                setTimeout(() => {
                    submitButtonInput.value = tipoBoton;
                    form.submit();
                }, 1200); 
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

    btnPdf.addEventListener('click', () => validarYEnviar('pdf'));
    btnExcel.addEventListener('click', () => validarYEnviar('excel'));
});
</script>
@endsection