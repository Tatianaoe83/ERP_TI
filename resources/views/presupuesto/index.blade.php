@extends('layouts.app')

@section('content')
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
          <!-- <button type="button" class="btn btn-warning" id="btn-test">Ver reporte test</button> -->
          <input type="hidden" name="submitbutton" id="submitbutton">
        </div>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalFaltantes" tabindex="-1" aria-labelledby="modalFaltantesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content dark:bg-[#101010] bg-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-danger" id="modalFaltantesLabel">
                    <i class="fas fa-exclamation-triangle"></i> Resumen de Validación de Gerencia
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary dark:text-gray-300">
                    Se han detectado datos incompletos en los inventarios. Para visualizar el presupuesto detallado, es necesario corregir los siguientes puntos:
                </p>
                
                <div class="mt-4 p-4 bg-[#1a1a1a] rounded border border-secondary" id="infoAdicionalEmpleados">
                    <h6 class="text-white mb-3 border-bottom border-secondary pb-2">
                        <i class="fas fa-users"></i> Estado de Empleados e Insumos
                    </h6>
                    
                    <div class="d-flex flex-column gap-3">
                        <p class="mb-0 text-white d-flex justify-content-between align-items-center">
                            <strong>Total De Empleados:</strong> 
                            <span id="totalEmpleadosModal" class="badge bg-success fs-6">0</span>
                        </p>

                        <!-- Línea Mensual Separada -->
                        <p class="mb-0 text-white d-flex justify-content-between align-items-center">
                            <strong>Empleados Con Insumos Mensuales Con Fecha De Renovacion Sin Mes de Pago</strong> 
                            <span id="sinMesPagoMensualModal" class="badge bg-danger fs-6">0</span>
                        </p>

                        <!-- Línea Anual Separada -->
                        <p class="mb-0 text-white d-flex justify-content-between align-items-center">
                            <strong>Empleados Con Insumos Anuales Con Fecha De Renovacion Sin Mes de Pago</strong> 
                            <span id="sinMesPagoAnualModal" class="badge bg-danger fs-6">0</span>
                        </p>

                        <p class="mb-0 text-white d-flex justify-content-between align-items-center">
                            <strong>Lineas Telefonicas Disponibles Con Fecha De Renovacion Sin Empleado Asignado: </strong> 
                            <span id="lineasSinAsignarConFechaModal" class="badge bg-danger fs-6">0</span>
                        </p>

                        <p class="mb-0 text-white d-flex justify-content-between align-items-center">
                            <strong>Insumos Disponibles Con Fecha De Renovacion Sin Empleado Asignado:</strong> 
                            <span id="insumosSinAsignarConFechaModal" class="badge bg-danger fs-6">0</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="{{ route('inventarios.index') }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Corregir en Inventarios
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('presupuestoForm');
    const btnPdf = document.getElementById('btn-validar-pdf');
    const btnExcel = document.getElementById('btn-validar-excel');
    //const btnTest = document.getElementById('btn-test');

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
        //(btnTest.disabled = true;

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
            //btnTest.disabled = false;

            if (data.success === false) {
                alert('Error de validación: ' + (data.error || 'Desconocido'));
                return;
            }

            // Referencias modal
            const totalEmp = document.getElementById('totalEmpleadosModal');
            const sinMesPagoMensual = document.getElementById('sinMesPagoMensualModal');
            const sinMesPagoAnual = document.getElementById('sinMesPagoAnualModal');
            const lineasConFecha = document.getElementById('lineasSinAsignarConFechaModal');
            const insumosConFecha = document.getElementById('insumosSinAsignarConFechaModal');

            // Pintar datos
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

            // Validación general
            const tieneFaltantes = (
                data.empleadosSinMesPagoMensual > 0 ||
                data.empleadosSinMesPagoAnual > 0 ||
                data.lineasSinAsignarConFecha > 0 ||
                data.insumosSinAsignarConFecha > 0
            );

            // BOTÓN TEST:
            // Muestra modal PERO genera PDF de todas formas
            // if (tipoBoton === 'test') {

               // if (tieneFaltantes) {
                //    modal.show();
                //}

                //submitButtonInput.value = 'pdf';
                ///form.submit();

                //return;
            //}

            // BOTONES NORMALES
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
            btnTest.disabled = false;

            alert('Ocurrió un error al validar los datos.');
        });
    }

    // Eventos botones
    btnPdf.addEventListener('click', function() {
        validarYEnviar('pdf');
    });

    btnExcel.addEventListener('click', function() {
        validarYEnviar('excel');
    });
// BOTÓN TEST:
   // btnTest.addEventListener('click', function() {
     //   validarYEnviar('test');
    //});

});
</script>
@endsection