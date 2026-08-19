@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Obras"
    icon="fa-hard-hat"
    :create-url="route('obras.create')"
    create-permission="crear-obras"
>
    @include('obras.table')
</x-index-page>
@endsection
