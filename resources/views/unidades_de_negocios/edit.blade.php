@extends('layouts.app')

@section('content')
<x-crud-page title="Editar unidad de negocio" icon="fa-city" subtitle="Actualiza los datos" :back-url="route('unidadesDeNegocios.index')">
    @include('adminlte-templates::common.errors')
    {!! Form::model($unidadesDeNegocio, ['route' => ['unidadesDeNegocios.update', $unidadesDeNegocio->UnidadNegocioID], 'method' => 'patch']) !!}
    <div class="row crud-form">
        @include('unidades_de_negocios.fields')
    </div>
    <div class="crud-page__actions">
        <button type="submit" class="index-page__btn-primary">Guardar</button>
        <a href="{{ route('unidadesDeNegocios.index') }}" class="crud-page__btn-ghost">Cancelar</a>
    </div>
    {!! Form::close() !!}
</x-crud-page>
@endsection
