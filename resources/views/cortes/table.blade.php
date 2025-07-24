@push('third_party_stylesheets')
@include('layouts.datatables_css')
@endpush

<div class="d-flex justify-start gap-5 mb-4">
    <div id="createCorte"> Crear </div>
    <select id="insumoFilter" class="cursor-pointer">
        <option value="">Seleccionar insumo</option>
    </select>
    <select id="gerenciaFilter" class="cursor-pointer">
        <option value="">Seleccionar gerencia</option>
    </select>
    <select id="mesFilter" class="cursor-pointer">
        <option value="">Seleccionar mes</option>
    </select>
    <div id="showGraphic">Ver Graficas</div>
</div>

<table id="cortesTable" class="table table-bordered">
    <thead>
        <tr>
            <th>Insumo</th>
            <th>Mes</th>
            <th>Gerencia</th>
            <th>Acciones</th>
        </tr>
    </thead>
</table>

@push('third_party_scripts')
@include('layouts.datatables_js')
{!! $dataTable->scripts() !!}
<script>
    $(document).ready(function() {
        var table = $('#cortesTable') DataTable({
            searching: true,
            pageLength: 7,
            ajax: {
                url: '{{ route("cortes.index") }}',
                data: function(d) {
                    d.insumo = $('insumoFilter').val();
                    d.mes = $('mesFilter').val();
                    d.gerencia = $('gerenciaFilter').val();
                }
            }
        })
    });
</script>
@endpush