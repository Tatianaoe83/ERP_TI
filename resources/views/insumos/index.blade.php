@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Insumos"
    icon="fa-box"
    :create-url="route('insumos.create')"
    create-permission="crear-insumos"
>
    @include('insumos.table')
</x-index-page>
@endsection

@section('scripts')
@if(session('swal'))
<script>
    Swal.fire({
        icon: @json(session('swal.icon')),
        title: @json(session('swal.title')),
        text: @json(session('swal.text')),
        confirmButtonText: 'Aceptar',
        confirmButtonColor: '#101D49'
    });
</script>
@endif
@endsection
