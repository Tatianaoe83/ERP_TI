<!-- Numtelefonico Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NumTelefonico', 'Num telefonico:') !!}
    <p>{{ $lineasTelefonicas->NumTelefonico }}</p>
</div>

<!-- Planid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('PlanID', 'Plan:') !!}
    <p>{{ $lineasTelefonicas->planes->NombrePlan }}</p>
</div>

<!-- Cuentapadre Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('CuentaPadre', 'Cuenta padre:') !!}
    <p>{{ $lineasTelefonicas->CuentaPadre }}</p>
</div>

<!-- Cuentahija Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('CuentaHija', 'Cuenta hija:') !!}
    <p>{{ $lineasTelefonicas->CuentaHija }}</p>
</div>

<!-- Tipolinea Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('TipoLinea', 'Tipo linea:') !!}
    <p>{{ $lineasTelefonicas->TipoLinea }}</p>
</div>

<!-- Obraid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('ObraID', 'Obra:') !!}
    <p>{{ $lineasTelefonicas->obras->NombreObra }}</p>
</div>

<!-- Fechafianza Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('FechaFianza', 'Fecha fianza:') !!}
    <p>{{ $lineasTelefonicas->FechaFianza }}</p>
</div>

<!-- Costofianza Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('CostoFianza', 'Costo fianza:') !!}
    <p>{{ $lineasTelefonicas->CostoFianza }}</p>
</div>

<!-- Activo Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Activo', 'Activo:') !!}

    <p>{{ $lineasTelefonicas->Activo == '1' ? 'Activo' : ($lineasTelefonicas->Activo == '0' ? 'No Activo ' : '') }}</p>

</div>

<!-- Disponible Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Disponible', 'Disponible:') !!}
    <p>{{ $lineasTelefonicas->Disponible }}</p>
</div>

<!-- Montorenovacionfianza Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('MontoRenovacionFianza', 'Monto renovacion fianza:') !!}
    <p>{{ $lineasTelefonicas->MontoRenovacionFianza }}</p>
</div>