@extends('layouts.app')

@section('content')
<div class="flex gap-2 justify-around flex-wrap">
    @if(auth()->user()->can('ver-usuarios') or auth()->user()->can('crear-usuarios') or auth()->user()->can('editar-usuarios') or auth()->user()->can('borrar-usuarios'))
    <a href="/usuarios" class="group block no-underline shadow-md dark:shadow-md">
        <div class="bg-[#f0fdf4] dark:bg-[#101010] h-[360px] w-[350px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-900 hover:shadow-md">
            <div class="flex justify-between items-start">
                <div class="bg-blue-600 h-[70px] w-[70px] text-white p-2 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-3xl"></i>
                </div>
            </div>

            <div>
                <div class="text-xl font-semibold text-[#101D49] dark:text-white mt-1">Usuarios</div>
                <div class="text-lg text-[#101D49] dark:text-gray-300">Gestión de usuarios del sistema</div>
            </div>

            <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-300 mt-2">
                <span class="text-[#101D49] dark:text-white">Ver más</span>
                <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 group-hover:scale-125 text-[#101D49] dark:text-white"></i>
            </div>
        </div>
    </a>
    @endif

    @if(auth()->user()->can('ver-rol') or auth()->user()->can('crear-rol') or auth()->user()->can('editar-rol') or auth()->user()->can('borrar-rol'))
    <a href="/roles" class="group block no-underline shadow-md dark:shadow-md">
        <div class=" bg-[#f0fdf4] dark:bg-[#101010] h-[360px] w-[350px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:bg-green-100 dark:hover:bg-green-900 hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div class="bg-green-600 h-[70px] w-[70px] text-white p-2 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-3xl"></i>
                </div>
            </div>

            <div>
                <div class="text-xl font-semibold text-[#101D49] dark:text-white mt-1">Roles</div>
                <div class="text-lg text-[#101D49] dark:text-gray-300">Administración de permisos</div>
            </div>

            <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-300 mt-2">
                <span class="text-[#101D49] dark:text-white">Ver más</span>
                <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 group-hover:scale-125 text-[#101D49] dark:text-white"></i>
            </div>
        </div>
    </a>
    @endif

    <a href="/presupuesto" class="group block no-underline shadow-md dark:shadow-md">
        <div class=" group bg-[#f0fdf4] dark:bg-[#101010] h-[360px] w-[350px] rounded-xl flex flex-col justify-between p-4 cursor-pointer hover:bg-red-100 dark:hover:bg-red-900 hover:shadow-md">
            <div class="flex justify-between items-start">
                <div class="bg-red-600 h-[70px] w-[70px] text-white p-2 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-invoice-dollar text-3xl"></i>
                </div>
            </div>

            <div>
                <div class="text-xl font-semibold text-[#101D49] dark:text-white mt-1">Presupuestos</div>
                <div class="text-lg text-[#101D49] dark:text-gray-300">Informe de presupuesto de gerencias</div>
            </div>

            <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-300 mt-2">
                <span class="text-[#101D49] dark:text-white">Ver más</span>
                <i class="fas fa-arrow-right transform transition-transform duration-300 group-hover:translate-x-1 group-hover:scale-125 text-[#101D49] dark:text-white"></i>
            </div>
        </div>
    </a>
</div>
@endsection