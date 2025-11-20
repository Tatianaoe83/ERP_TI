<!-- Nombreobra Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NombreObra', 'Nombre obra:') !!}
    <p>{{ $obras->NombreObra }}</p>
</div>

<!-- Direccion Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Direccion', 'Direccion:') !!}
    <p>{{ $obras->Direccion }}</p>
</div>

<!-- Encargadodeobra Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('EncargadoDeObra', 'Encargado de obra:') !!}
    <p>{{ $obras->EncargadoDeObra }}</p>
</div>

<!-- Unidadnegocioid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('UnidadNegocioID', 'Unidad negocio:') !!}
    <p>{{ $obras->unidadesdenegocio->NombreEmpresa }}</p>
</div>

<!-- Estado Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Estado', 'Es obra:') !!}
    <p>
        @if($obras->estado)
            <span class="badge badge-success">Si</span>
        @else
            <span class="badge badge-danger">No</span>
        @endif
    </p>
</div>