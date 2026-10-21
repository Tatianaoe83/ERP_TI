<div class="aud-historial__acciones">
    @php
        $tipoPersona = strtoupper((string) ($tipo_persona ?? 'FISICA'));
        $esExtraordinario = $tipoPersona === 'EXTRAORDINARIO';
    @endphp
    @if($activo ?? true)
        @can('asignar-inventario')
        <a href="{{ route('inventarios.edit', $id) }}" class="aud-btn aud-btn--primary aud-btn--sm" title="Asignar inventario">
            <i class="fas fa-laptop-medical" aria-hidden="true"></i> Asignar
        </a>
        @endcan
        @if(!$esExtraordinario)
            @can('transferir-inventario')
            <a href="{{ route('inventarios.transferir', $id) }}" class="aud-btn aud-btn--ghost aud-btn--sm" title="Transferir inventario">
                <i class="fas fa-exchange-alt" aria-hidden="true"></i> Transferir
            </a>
            @endcan
            @can('cartas-inventario')
            <a href="{{ route('inventarios.cartas', $id) }}" class="aud-btn aud-btn--ghost aud-btn--sm" title="Cartas responsivas">
                <i class="fas fa-print" aria-hidden="true"></i> Cartas
            </a>
            @endcan
        @endif
    @else
        <span class="aud-muted">—</span>
    @endif
</div>
