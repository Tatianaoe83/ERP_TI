@if(!empty($resultado) && count($resultado) > 0)
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tabla-preview" class="table table-bordered table-striped table-hover table-sm mb-0">
                <thead class="table-light text-secondary small">
                    <tr>
                        @foreach(array_keys((array)$resultado[0]) as $col)
                        <th class="px-3 py-2">{{ ucfirst(str_replace('_', ' ', $col)) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($resultado as $fila)
                    <tr>
                        @foreach((array)$fila as $valor)
                        <td class="px-3 py-2 small">{{ $valor }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="text-center py-4">
    <span class="text-muted">No hay datos para mostrar en la vista previa del reporte.</span>
</div>
@endif