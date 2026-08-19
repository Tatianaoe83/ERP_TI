@props([
    'showUrl' => null,
    'showPermission' => null,
    'editUrl' => null,
    'editPermission' => null,
    'destroyRoute' => null,
    'destroyPermission' => null,
    'confirmTitle' => '¿Está seguro de que desea borrar este registro?',
    'successTitle' => 'Registro borrado',
])

@php
    $user = auth()->user();
    $canShow = $showUrl && (empty($showPermission) || ($user && $user->can($showPermission)));
    $canEdit = $editUrl && (empty($editPermission) || ($user && $user->can($editPermission)));
    $canDestroy = $destroyRoute && (empty($destroyPermission) || ($user && $user->can($destroyPermission)));
    $destroyUrl = null;
    if ($canDestroy) {
        $destroyUrl = is_array($destroyRoute)
            ? route($destroyRoute[0], $destroyRoute[1] ?? null)
            : $destroyRoute;
    }
@endphp

<div class="index-actions">
    @if($canShow)
        <a href="{{ $showUrl }}" class="index-action index-action--view" title="Ver">
            <i class="fas fa-eye"></i>
        </a>
    @endif

    @if($canEdit)
        <a href="{{ $editUrl }}" class="index-action index-action--edit" title="Editar">
            <i class="fas fa-edit"></i>
        </a>
    @endif

    {{ $slot }}

    @if($canDestroy)
        <form method="POST" action="{{ $destroyUrl }}" class="index-action-form">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="index-action index-action--delete index-action-confirm"
                title="Eliminar"
                data-confirm-title="{{ $confirmTitle }}"
                data-success-title="{{ $successTitle }}">
                <i class="fas fa-trash-alt"></i>
            </button>
        </form>
    @endif
</div>
