@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    id="auditorias-page"
    title="Auditorías"
    icon="fa-clipboard-check"
    subtitle="Auditorías de equipos tecnológicos"
    :card="false"
>
    <div class="index-page__card">
        <p class="dark:text-white">Módulo en construcción.</p>
    </div>
</x-index-page>
@endsection
