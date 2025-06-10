@extends('layouts.app')

@section('content')
<section class="section">
    <div class="section-header">
        <h3 class="page__heading">Generador de Reportes</h3>
    </div>
    <div class="section-body">
        @livewire('reportes-lista')
    </div>
</section>
@endsection