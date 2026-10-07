@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Divisiones"
    icon="fa-layer-group"
    :create-url="route('divisiones.create')"
    create-permission="crear-unidadesdenegocio"
    create-label="+ Nueva división"
>
    <div class="index-page__table-wrap table-responsive">
        <table class="table index-table w-full js-index-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Unidades de negocio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($divisiones as $division)
                    <tr>
                        <td>{{ $division->NombreDivision }}</td>
                        <td>{{ $division->unidadesdenegocio_count }}</td>
                        <td><span class="index-badge index-badge--success">Activo</span></td>
                        <td>
                            <x-index-actions
                                :edit-url="route('divisiones.edit', $division->DivisionID)"
                                edit-permission="editar-unidadesdenegocio"
                                :destroy-route="['divisiones.destroy', $division->DivisionID]"
                                destroy-permission="borrar-unidadesdenegocio"
                                confirm-title="¿Está seguro de que desea borrar esta división?"
                                success-title="División borrada"
                            />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-index-page>
@endsection

@include('layouts.partials.index-client-table')
