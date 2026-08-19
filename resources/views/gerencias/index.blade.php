@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Gerencias"
    icon="fa-user-tie"
    :create-url="route('gerencias.create')"
    create-permission="crear-gerencias"
>
    @include('gerencias.table')
</x-index-page>
@endsection
