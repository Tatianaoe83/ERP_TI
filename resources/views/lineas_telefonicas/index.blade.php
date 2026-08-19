@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Líneas telefónicas"
    icon="fa-phone-alt"
    :create-url="route('lineasTelefonicas.create')"
    create-permission="crear-Lineastelefonicas"
>
    @include('lineas_telefonicas.table')
</x-index-page>
@endsection
