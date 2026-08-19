@extends('layouts.app')

@section('content')
<x-crud-page title="Nuevo reporte" icon="fa-book" subtitle="Completa los datos" :back-url="route('reportes.index')">
    @livewire('reporte')
</x-crud-page>
@endsection
