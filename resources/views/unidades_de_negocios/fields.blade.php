<!-- Nombreempresa Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('NombreEmpresa', 'Nombre empresa:') !!}
    {!! Form::text('NombreEmpresa', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Rfc Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white" >
    {!! Form::label('RFC', 'Rfc:') !!}
    {!! Form::text('RFC', null, ['class' => 'form-control','maxlength' => 13,'maxlength' => 13,'required' => 'required']) !!}
</div>

<!-- Direccion Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('Direccion', 'Direccion:') !!}
    {!! Form::text('Direccion', null, ['class' => 'form-control','maxlength' => 150,'maxlength' => 150]) !!}
</div>

<!-- Numtelefono Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white" >
    {!! Form::label('NumTelefono', 'Num. telefono:') !!}
    {!! Form::text('NumTelefono', null, ['class' => 'form-control','maxlength' => 10,'maxlength' => 10,'required' => 'required']) !!}
</div>

<!-- Estado Field -->
<div class="col-sm-6 text-[#101D49] dark:text-white">
    {!! Form::label('estado', 'Es unidad de negocio:') !!}
    {!! Form::select('estado', [1 => 'Si', 0 => 'No'], null, ['class' => 'form-control']) !!}
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('RFC').closest('form');
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      var rfc = document.getElementById("RFC").value;
      var numero = document.getElementById("NumTelefono").value;

      var rfcRegex = /^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/i;
      if (!rfcRegex.test(rfc)) {
        Swal.fire({
          title: "Error",
          html: "RFC inválido.<br>Persona física: 13 caracteres (ej. XAXX010101NI4).<br>Persona moral: 12 caracteres (ej. EXT010101NI4).",
          icon: "error",
          confirmButtonText: "Aceptar"
        });
        return;
      }

      var soloDigitos = /^\d{10}$/;
      if (!soloDigitos.test(numero)) {
        Swal.fire({
          title: "Error",
          text: "El número de teléfono debe tener exactamente 10 dígitos numéricos.",
          icon: "error",
          confirmButtonText: "Aceptar"
        });
        return;
      }

      form.submit();
    });
  });
</script>