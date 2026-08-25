<div class="index-actions">
    @php
        $tipoPersona = strtoupper((string) ($tipo_persona ?? 'FISICA'));
        $esExtraordinario = $tipoPersona === 'EXTRAORDINARIO';
    @endphp
    @if($activo ?? true)
        @can('asignar-inventario')
        <a href="{{ route('inventarios.edit', $id) }}" class="index-action index-action--edit" title="Asignar inventario">
            <i class="fas fa-laptop-medical"></i>
        </a>
        @endcan
        @if(!$esExtraordinario)
            @can('transferir-inventario')
            <a href="{{ route('inventarios.transferir', $id) }}" class="index-action index-action--warning" title="Transferir inventario">
                <i class="fas fa-exchange-alt"></i>
            </a>
            @endcan
            @can('cartas-inventario')
            <a href="{{ route('inventarios.cartas', $id) }}" class="index-action index-action--view" title="Cartas responsivas">
                <i class="fas fa-print"></i>
            </a>
            @endcan
        @endif
    @else
        <span class="text-muted small">—</span>
    @endif
</div>
