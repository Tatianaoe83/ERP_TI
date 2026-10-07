@extends('layouts.app')

@section('content')
<x-crud-page title="Dirección" icon="fa-sitemap" :back-url="route('direcciones.index')">
    <div class="col-sm-12 text-[#101D49] dark:text-white">
        {!! Form::label('NombreDireccion', 'Nombre dirección:') !!}
        <p>{{ $direccion->NombreDireccion }}</p>
    </div>
    <div class="col-sm-12 text-[#101D49] dark:text-white">
        {!! Form::label('UnidadNegocioID', 'Unidad de negocio:') !!}
        <p>{{ $direccion->unidadesdenegocio->NombreEmpresa ?? '—' }}</p>
    </div>
</x-crud-page>
@endsection
