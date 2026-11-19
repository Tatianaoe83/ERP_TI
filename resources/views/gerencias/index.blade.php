@extends('layouts.app')

@section('content')
@include('flash::message')

<x-index-page
    title="Gerencias"
    icon="fa-user-tie"
    :create-url="route('gerencias.create')"
    create-permission="crear-gerencias"
    create-label="+ Nueva gerencia"
>
    <div class="index-page__table-wrap table-responsive">
        <table class="table index-table w-full js-index-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Áreas</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($gerencias as $gerencia)
                    <tr>
                        <td>{{ $gerencia->NombreGerencia }}</td>
                        <td>{{ $gerencia->direccion->NombreDireccion ?? '—' }}</td>
                        <td>{{ $gerencia->departamentos_count }}</td>
                        <td>
                            @if($gerencia->estado)
                                <span class="index-badge index-badge--success">Activo</span>
                            @else
                                <span class="index-badge index-badge--muted">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <x-index-actions
                                :edit-url="route('gerencias.edit', $gerencia->GerenciaID)"
                                edit-permission="editar-gerencias"
                                :destroy-route="['gerencias.destroy', $gerencia->GerenciaID]"
                                destroy-permission="borrar-gerencias"
                                confirm-title="¿Está seguro de que desea borrar esta gerencia?"
                                success-title="Gerencia borrada"
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
