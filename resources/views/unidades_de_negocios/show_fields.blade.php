<!-- Nombreempresa Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NombreEmpresa', 'Nombre empresa:') !!}
    <p>{{ $unidadesDeNegocio->NombreEmpresa }}</p>
</div>

<!-- Rfc Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('RFC', 'Rfc:') !!}
    <p>{{ $unidadesDeNegocio->RFC }}</p>
</div>

<!-- Direccion Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Direccion', 'Direccion:') !!}
    <p>{{ $unidadesDeNegocio->Direccion }}</p>
</div>

<!-- Numtelefono Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NumTelefono', 'Num. telefono:') !!}
    <p>{{ $unidadesDeNegocio->NumTelefono }}</p>
</div>

<!-- Estado Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Estado', 'Es unidad de negocio:') !!}
    <p>
        @if($unidadesDeNegocio->estado)
            <span class="badge badge-success">Si</span>
        @else
            <span class="badge badge-danger">No</span>
        @endif
    </p>
</div>