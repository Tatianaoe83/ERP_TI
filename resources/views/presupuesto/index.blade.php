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
            <i class="fas fa-exclamation-triangle"></i> Datos Faltantes Detectados
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-white">Los siguientes registros de la Gerencia seleccionada <b>no tienen Fecha de Renovación</b>. Debes llenarlos antes de generar el reporte para asegurar que el presupuesto sea correcto.</p>
        
        <div class="table-responsive">
            <table class="table table-bordered table-dark table-striped">
                <thead>
                    <tr>
                        <th>Gerencia</th>
                        <th>Empleado</th>
                        <th>Artículo / Línea</th>
                        <th>Tipo</th>
                        <th>Fecha De Renovacion</th>
                    </tr>
                </thead>
                <tbody id="listaFaltantes">
                    <!-- Se llena con JS -->
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button>
        <a href="{{ route('inventarios.index') }}" class="btn btn-primary">Ir a Inventarios para corregir</a>
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
    const modal = new bootstrap.Modal(document.getElementById('modalFaltantes'));

    function validarYEnviar(tipoBoton) {
        const gerenciaId = document.getElementById('GerenciaID').value;
        console.log('Validando Gerencia:', gerenciaId);
        
        if (!gerenciaId) {
            alert('Por favor selecciona una Gerencia');
            return;
        }

        // Bloquear botones mientras valida
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
            console.log('Datos recibidos:', data);
            btnPdf.disabled = false;
            btnExcel.disabled = false;

            if (data.success === false) {
                alert('Error de validación: ' + (data.error || 'Desconocido'));
                console.error('Detalle del error:', data.trace);
                return;
            }

            if (data.count > 0) {
                console.log('Se encontraron faltantes:', data.count);
                let html = '';
                data.faltantes.forEach(f => {
                    html += `<tr>
                        <td>${f.NombreGerencia}</td>
                        <td>${f.NombreEmpleado}</td>
                        <td>${f.Articulo}</td>
                        <td>${f.Tipo}</td>
                        <td>${f.FechaRenovacion || 'Sin fecha'}</td>
                    </tr>`;
                });
                document.getElementById('listaFaltantes').innerHTML = html;
                modal.show();
            } else {
                // Si todo está bien, enviar el formulario
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