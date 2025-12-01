@push('third_party_stylesheets')
<!-- css -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
@endpush
<div class="flex flex-row gap-5 justify-around">
    <form action="formFilter" method="GET" id="formFilter" class="flex flex-row justify-start gap-3 mb-4 items-center">
        <div class="flex flex-col gap-2 items-center justify-center w-[300px]">
            <label for="gerenci_id" class="text-black dark:text-white">Gerencias</label>
            {!! Form::select('gerenci_id', ['' => 'Selecciona una opción'] + App\Models\Gerencia::all()->pluck('NombreGerencia', 'GerenciaID')->toArray(), null, [
            'class' => 'form-control jz',
            'style' => 'width: 100%',
            'id' => 'gerenci_id'
            ]) !!}

        </div>
        <div class="flex flex-col gap-2 items-center justify-center">
            <label for="mesFilter" class="text-black dark:text-white">Mes</label>
            <select id="mesFilter" class="cursor-pointer w-[150px] h-[48px] text-black rounded-sm transition focus:ring-2 focus:ring-blue-500 focus:outline-none text-center border border-gray-400">
                <option value="" disabled selected>Seleccionar mes</option>
                @foreach($meses as $mes)
                <option value="{{ $mes }}">{{ $mes }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-2 items-center justify-center">
            <label for="añoFilter" class="text-black dark:text-white">Año</label>
            <select id="añoFilter" class="cursor-pointer w-[150px] h-[48px] text-black rounded-sm transition focus:ring-2 focus:ring-blue-500 focus:outline-none text-center border border-gray-400">
                <option value="" disabled selected>Seleccionar año</option>
                @foreach($years as $año)
                <option value="{{ $año }}">{{ $año }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-[#6777ef] hover:scale-105 transition text-white w-[70px] h-[40px] rounded-md ">Filtrar</button>
    </form>
    <div id="gerenciaInfo" class="hidden mb-4 flex flex-col items-center">
        <div id="titleGerencia" class="text-lg text-black dark:text-white mb-2"></div>
        <div class="flex gap-3">
            <button id="crear" class="bg-[#6777ef] text-white px-4 py-2 rounded hover:scale-105 transition" data-toggle="modal" data-target="#modalFactura">Crear factura</button>
            <div class="modal fade text-black" id="modalFactura" tabindex="-1" role="dialog" aria-labelledby="modalFactura" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header justify-between">
                            <h5 class="modal-title" id="modalFacturaLabel">Crear Factura</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span>&times;</span>
                            </button>
                        </div>

                        <div class="p-6 flex flex-row gap-6 justify-around">
                            <div class="border border-gray-300 rounded-md w-[300px] p-4 flex flex-col gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Gerencia:</p>
                                    <span id="previewGerencia" class="text-gray-700 text-sm">--</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Mes:</p>
                                    <span id="previewMes" class="text-gray-700 text-sm">--</span>
                                </div>

                                <form action="{{ route('cortes.saveXML') }}" method="POST" id="formImagen" enctype="multipart/form-data" class="flex flex-col gap-3">
                                    @csrf
                                    <div>
                                        <input type="hidden" name="gerenci_id" id="modalGerenciaInput">
                                        <input type="hidden" name="mes" id="modalMesInput">
                                        <label for="inputImage" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Archivo</label>
                                        <input type="file" id="inputImage" name="imagen" accept=".xml" required
                                            class="w-full text-sm text-gray-900 border border-gray-300 rounded cursor-pointer bg-gray-50 focus:outline-none">
                                    </div>

                                    <button type="submit"
                                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-sm">
                                        Subir XML
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <button id="graficas" class="bg-[#6777ef] text-white px-4 py-2 rounded hover:scale-105 transition">Ver gráficas</button>
        </div>
    </div>
</div>

<table id="facturasTable" class="table table-bordered table-responsive">
    <thead>
        <tr>
            <th>Insumo</th>
            <th>Mes</th>
            <th>Costo</th>
        </tr>
    </thead>
    <tbody>

    </tbody>
</table>

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#facturasTable').DataTable({
            destroy: true,
            responsive: true,
            searching: true,
            processing: true,
            serverSide: true,
            pageLength: 12,
            ajax: {
                url: '{{ route("facturas.ver") }}',
                method: 'GET',
                data: function(d) {
                    d.mes = $('#mesFilter').val();
                    d.gerenci_id = $('#gerenci_id').val();
                    d.año = $('#añoFilter').val();
                },
                dataSrc: 'data'
            },
            columns: [{
                    data: 'NombreInsumo'
                },
                {
                    data: 'Mes'
                },
                {
                    data: 'Costo'
                },
            ],
        });

        $('#formFilter').on('submit', function(e) {
            e.preventDefault();

            const gerencia = $('#gerenci_id option:selected').text();

            if (gerencia && gerencia !== 'Selecciona una opción') {
                $('#titleGerencia').text('Gerencia: ' + gerencia);
                $('#gerenciaInfo').removeClass('hidden');
            } else {
                $('#titleGerencia').text('');
                $('#gerenciaInfo').add('hidden');
            }

            table.ajax.reload();
        });
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById('modalFactura');
        const abrir = document.getElementById('crear');

        abrir.addEventListener('click', () => {
            const gerenciaSel = $('#gerenci_id option:selected').text();
            const mesSel = $('#mesFilter').val();

            $('#modalGerenciaInput').val($('#gerenci_id').val());
            $('#modalMesInput').val($('#mesFilter').val());

            $('#modalGerencia').val(gerenciaSel);
            $('#modalMes').val(mesSel);

            $('#previewGerencia').text(gerenciaSel);
            $('#previewMes').text(mesSel);
            modal.classList.remove('hidden');
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });
</script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('inputImage');
        const token = document.querySelector('meta[name="csrf-token"]').content;

        input.addEventListener('change', async () => {
            if (!input.files.length) return;

            const formData = new FormData();
            formData.append('imagen', input.files[0]);

            try {
                const response = await fetch("{{ route('cortes.readXML') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();
                console.log(data.datos);
            } catch (error) {
                console.error(error);
            }
        });
    });
</script>
@endpush