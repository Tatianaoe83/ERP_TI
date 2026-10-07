@props(['activo' => true])

@if($activo)
    <span class="cat-org__estado"><i class="fas fa-circle"></i> Activo</span>
@else
    <span class="cat-org__estado cat-org__estado--off"><i class="fas fa-circle"></i> Inactivo</span>
@endif
