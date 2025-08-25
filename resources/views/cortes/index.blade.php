@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-5 bg-white rounded-md h-[200px] w-90 hover:bg-gray-500 border border-gray-500">
    <div class="flex items-center justify-center text-2xl gap-5 text-black font-bold">Generar corte anual</div>
    <div class="flex items-center justify-center">
        <div class="flex flex-row gap-3 items-center">
            <Span class="text-lg text-black">Seleccionar gerencia</Span>
            <form action="{{route('cortes.index')}}" method="GET">
                <div class="flex flex-col gap-3 items-center">
                    <select name="gerenciaID" id="gerenciaID" class="w-300 h-[40px] cursor-pointer text-black border border-gray-800 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                        <option value="" disabled selected>Selecciona un opcion</option>
                        @foreach($gerencia as $gerencias)
                        <option value="{{$gerencias->GerenciaID}}">{{$gerencias->NombreGerencia}}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-40 h-10 text-white text-lg rounded-md bg-[#6777ef] hover:scale-105 transition">Generar corte</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="bg-white rounded-md border border-gray-500 mt-5 p-5">
    @include('cortes.table')
    <div class="card-footer clearfix"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if (session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: '{{ session('success') }}',
        timer: 3000,
        timerProgressBar: true
    });
</script>
@elseif (session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: '¡Error!',
        text: '{{ session('error') }}',
        timer: 3000,
        timerProgressBar: true
    });
</script>
@endif
@endsection