@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de plan" icon="fa-mobile-alt" subtitle="Solo lectura" :back-url="route('planes.index')">
    <div class="row crud-show">
        @include('planes.show_fields')
    </div>
</x-crud-page>
@endsection
