@extends('layouts.app')

@section('content')
<div class="content px-3">
    @include('flash::message')

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="mb-0 text-[#101D49] dark:text-white">Mantenimientos</h3>

            @can('editar-mantenimientos')
            <form action="{{ route('mantenimientos.generar') }}" method="POST" class="d-flex flex-wrap gap-2 align-items-center">
                @csrf
                <label for="fecha_inicio" class="mb-0 text-sm text-[#101D49] dark:text-white">Fecha inicial</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" style="max-width: 180px;" value="{{ now()->toDateString() }}">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-magic mr-1"></i> Generar programación
                </button>
            </form>
            @endcan
        </div>

        <div class="card-body">
            @livewire('mantenimientos-table')
        </div>
    </div>
</div>
@endsection
