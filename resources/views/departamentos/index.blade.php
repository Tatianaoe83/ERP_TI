@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Departamentos"
    icon="fa-tags"
    :create-url="route('departamentos.create')"
    create-permission="crear-departamentos"
>
    @include('departamentos.table')
</x-index-page>
@endsection
