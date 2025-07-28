<div class="position-relative w-100">
    <input type="text" wire:model="valor" class="form-control" placeholder="Buscar..." wire:blur="$set('sugerencias', [])">

    @if(!empty($sugerencias))
    <ul class=" position-absolute w-100 bg-white border z-50 mt-1 list-group shadow overflow-auto"
        style="max-height: 200px; cursor: pointer;">
        @foreach($sugerencias as $item)
        <li wire:click="seleccionar('{{ $item }}')"
            class="list-group-item list-group-item-action"
            style="cursor: pointer;">
            {{ $item }}
        </li>
        @endforeach
    </ul>
    @endif
</div>