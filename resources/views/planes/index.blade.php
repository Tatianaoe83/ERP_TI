@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Planes"
    icon="fa-mobile-alt"
    :create-url="route('planes.create')"
    create-permission="crear-planes"
>
    @include('planes.table')
</x-index-page>
@endsection
