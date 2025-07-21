@extends('layouts.auth_app')
@section('title')
Admin Login
@endsection
@section('content')
<div class="text-white flex flex-col w-full px-6 h-[550px]">
    <div class="pt-1 block">
        <img src="{{ asset('img/LogoBlanco.png') }}" width="300" alt="Logo">
    </div>
    <div class="flex flex-col justify-center flex-1 space-y-6 mb-2">
        <h3 class="text-4xl font-semibold text-white">Sistema de gestión de <br> tecnologías de la información</h3>
        <p class="text-gray-300 text-md leading-relaxed max-w">
            Sistema especializado para la gestión eficiente de información del departamento de TI, incluyendo activos, documentación técnica,
            usuarios, etc., permitiendo un control centralizado, actualizado y accesible.
        </p>
        <div class="flex space-x-2 pt-4">
            <div class="w-12 h-1 bg-white"></div>
            <div class="w-8 h-1 bg-gray-400"></div>
            <div class="w-6 h-1 bg-gray-600"></div>
        </div>
    </div>
</div>
@endsection