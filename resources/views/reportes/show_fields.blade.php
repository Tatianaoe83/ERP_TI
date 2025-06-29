<!-- Datos del Reporte -->
<div class="col-sm-12">
    <div class="table-responsive">
        <table id="reporte-dinamico" class="table table-bordered table-striped table-hover table-sm mb-0">
            @if (count($resultado) > 0)
            <thead>
                <tr>
                    @foreach (array_keys((array)$resultado[0]) as $col)
                    <th class="px-3 py-2">{{ ucfirst($col) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($resultado as $fila)
                <tr>
                    @foreach ((array)$fila as $valor)
                    <td class="px-3 py-2 small">{{ $valor }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
            @else
            <tr>
                <td colspan="100%" class="text-center text-muted">No se encontraron datos para este reporte.</td>
            </tr>
            @endif
        </table>
    </div>
</div> 