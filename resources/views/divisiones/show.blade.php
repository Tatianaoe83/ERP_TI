@extends('layouts.app')

@section('content')
<x-crud-page title="División" icon="fa-layer-group" :back-url="route('divisiones.index')">
    <div class="col-sm-12 text-[#101D49] dark:text-white">
        {!! Form::label('NombreDivision', 'Nombre división:') !!}
        <p>{{ $division->NombreDivision }}</p>
    </div>
</x-crud-page>
@endsection
