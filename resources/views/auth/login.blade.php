@extends('layouts.auth_app')
@section('title')
Admin Login
@endsection
@section('content')
<div class="text-white flex flex-col w-full px-4 sm:px-6 h-auto lg:h-[550px]">
    <div class="pt-1 block">
        <img src="{{ asset('img/LogoBlanco.png') }}" class="w-full max-w-[250px] sm:max-w-[300px] h-auto" alt="Logo">
    </div>
    <div class="flex flex-col justify-center flex-1 space-y-4 sm:space-y-6 mb-2 pt-4 sm:pt-0">
        <h3 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-white">Sistema de gestión de <br class="hidden sm:block"> tecnologías de la información</h3>
        <p class="text-gray-300 text-sm sm:text-base leading-relaxed max-w">
            Sistema especializado para la gestión eficiente de información del departamento de TI, incluyendo activos, documentación técnica,
            usuarios, etc., permitiendo un control centralizado, actualizado y accesible.
        </p>
        <div class="flex space-x-2 pt-2 sm:pt-4">
            <div class="w-12 h-1 bg-white"></div>
            <div class="w-8 h-1 bg-gray-400"></div>
            <div class="w-6 h-1 bg-gray-600"></div>
        </div>
    </div>
</div>
@endsection