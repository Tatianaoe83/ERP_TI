@extends('emails.solicitudes.layout')

@section('contenido')
    @include('emails.solicitudes.partials.datos', [
        'filas' => $filas ?? [],
        'bloques' => $bloques ?? [],
    ])
@endsection
