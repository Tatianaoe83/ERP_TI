<!-- Nombregerencia Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NombreGerencia', 'Nombre gerencia:') !!}
    <p>{{ $gerencia->NombreGerencia }}</p>
</div>

<!-- Unidadnegocioid Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('UnidadNegocioID', 'Unidad negocio:') !!}
    <p>{{ $gerencia->unidadesdenegocio->NombreEmpresa}}</p>
</div>

<!-- Nombregerente Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('NombreGerente', 'Nombre gerente:') !!}
    <p>{{ $gerencia->NombreGerente ?? 'Sin gerente asignado'}}</p>
</div>

<!-- Estado Field -->
<div class="col-sm-12 text-[#101D49] dark:text-white">
    {!! Form::label('Estado', 'Es gerencia:') !!}
    <p>
        @if($gerencia->estado)
            <span class="badge badge-success">Si</span>
        @else
            <span class="badge badge-danger">No</span>
        @endif
    </p>
</div>