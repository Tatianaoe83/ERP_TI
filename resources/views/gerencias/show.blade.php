@extends('layouts.app')

@section('content')
<x-crud-page title="Detalle de gerencia" icon="fa-user-tie" subtitle="Solo lectura" :back-url="route('gerencias.index')">
    <div class="row crud-show">
        @include('gerencias.show_fields')
    </div>
</x-crud-page>
@endsection
