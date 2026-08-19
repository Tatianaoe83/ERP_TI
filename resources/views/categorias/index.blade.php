@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Categorías"
    icon="fa-sitemap"
    :create-url="route('categorias.create')"
    create-permission="crear-categorias"
>
    @include('categorias.table')
</x-index-page>
@endsection
