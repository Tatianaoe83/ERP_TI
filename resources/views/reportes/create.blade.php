@extends('layouts.app')

@section('content')
<section class="content-header" style="background-color: #ffffff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">   
    <div class="container-fluid">
        <h1 class="mb-3" style="color: #6c757d; font-size: 2rem;">Crear Reporte</h1>
    </div>
</section>

<div class="content px-3">
    @livewire('reporte')
</div>
@endsection