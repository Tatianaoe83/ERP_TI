@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Equipos"
    icon="fa-laptop"
    :create-url="route('equipos.create')"
    create-permission="crear-equipos"
>
    @include('equipos.table')
</x-index-page>
@endsection
