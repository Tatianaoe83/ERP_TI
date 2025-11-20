@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-5 bg-white rounded-md p-5 w-full border border-gray-300">
    <div class="flex items-center justify-between">
        <div class="bg-white border boder-gray-300 w-full rounded-md p-6 justify-center items-center flex flex-col">
            <h2 class="text-2xl font-bold text-black">Generar corte anual</h2>

            <div class="flex items-center gap-3">
                <label for="gerenciaID" class="text-lg text-black">Seleccionar gerencia</label>
                <select name="gerenciaID" id="gerenciaID"
                    class="w-72 h-[40px] cursor-pointer text-black border border-gray-800 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    <option value="" disabled selected>Selecciona una opción</option>
                    @foreach($gerencia as $g)
                    <option value="{{ $g->GerenciaID }}">{{ $g->NombreGerencia }}</option>
                    @endforeach
                </select>

                <button type="button" id="enviar"
                    class="px-4 h-10 text-white text-sm rounded-md bg-[#6777ef] hover:scale-105 transition">
                    Preview Corte
                </button>

                <button type="button" id="guardar"
                    class="px-4 h-10 text-white text-sm bg-[#6777ef] rounded-md bg-emerald-600 hover:scale-105 transition hover:bg-green-500 transition-all">
                    Guardar Corte
                </button>
                <a class="click-me" href="/facturas">Click me</a>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <table id="tabla" class="table table-hover table-striped table-responsive w-full">
            <thead>
                <tr>
                    <th>Insumo</th>
                    <th>Monto base</th>
                    <th>Montos Distintos</th>
                    <th>Margen (%)</th>
                    <th>Precio c/margen</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('third_party_scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(function() {
        const currencyFmt = new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            maximumFractionDigits: 2
        });

        const table = $('#tabla').DataTable({
            destroy: true,
            searching: true,
            processing: true,
            serverSide: true,
            pageLength: 25,
            deferLoading: 0,
            ajax: {
                url: '{{ route("cortes.ver") }}',
                type: 'GET',
                data: function(d) {
                    d.gerenciaID = $('#gerenciaID').val();
                },
                dataSrc: function(json) {
                    if (!json || !Array.isArray(json.data)) return [];
                    return json.data.map(r => ({
                        NombreInsumo: r.NombreInsumo,
                        MontosPorMes: r.MontosPorMes || [],
                        Distintos: r.Distintos || [],
                        SelectedIndex: 0,
                        Margen: 0
                    }));
                },
                error: function(xhr) {
                    console.error('DT AJAX error', xhr.status, xhr.responseText);
                }
            },
            columns: [{
                    data: 'NombreInsumo',
                    className: 'fw-semibold'
                },

                {
                    data: null,
                    className: 'text-end',
                    render: function(row) {
                        const montos = row.Distintos || [];
                        const idx = Number(row.SelectedIndex) || 0;

                        if (montos.length <= 1) {
                            const val = Number(montos[0] || 0);
                            return `<span>${currencyFmt.format(val)}</span>`;
                        }

                        const options = montos.map((m, i) => {
                            const sel = i === idx ? 'selected' : '';
                            return `<option value="${i}" ${sel}>${currencyFmt.format(Number(m)||0)}</option>`;
                        }).join('');

                        return `<select class="cursor-pointer border rounded-md monto-select">${options}</select>`;
                    }
                },

                {
                    data: 'Distintos',
                    className: 'text-center',
                    orderable: false,
                    render: function(distintos) {
                        const count = distintos?.length || 0;
                        const badgeClass = count > 1 ? 'text-bg-warning' : 'text-bg-secondary';
                        return `<span class="badge ${badgeClass} rounded-pill">${count > 1 ? count + ' Montos Distintos' : 'Monto Único'}</span>`;
                    }
                },

                {
                    data: 'Margen',
                    className: 'text-center',
                    orderable: false,
                    render: function(margen) {
                        const val = Number(margen) || 0;
                        return `
                        <div class="d-inline-flex align-items-center justify-content-center margen-editor" style="min-width:180px;gap:.5rem;">
                            <div class="input-group input-group-sm" style="width:110px;">
                                <input type="number" min="0" max="100" step="0.1" class="text-end margen-input" value="${val}">
                                <div class="input-group-append"><span class="input-group-text">%</span></div>
                            </div>
                        </div>
                    `;
                    }
                },

                {
                    data: null,
                    className: 'text-end fw-semibold',
                    render: function(row) {
                        const margen = Number(row.Margen) || 0;
                        const idx = Number(row.SelectedIndex) || 0;
                        const base = Number(row.Distintos?.[idx] ?? 0);
                        const calc = base * (1 + (margen / 100));
                        return currencyFmt.format(calc);
                    }
                }
            ],
        });

        $('#tabla').on('change', '.monto-select', function() {
            const $tr = $(this).closest('tr');
            const row = table.row($tr);
            const data = row.data();
            data.SelectedIndex = Number($(this).val()) || 0;
            row.data(data).invalidate();
        });

        $('#tabla').on('change', '.margen-input', function() {
            const $tr = $(this).closest('tr');
            const row = table.row($tr);
            const data = row.data();

            let num = Number(String($(this).val()).replace(',', '.'));
            if (isNaN(num) || num < 0) num = 0;
            if (num > 100) num = 100;
            $(this).val(num);

            data.Margen = num;
            row.data(data).invalidate();
        });

        $('#enviar').on('click', function() {
            const val = $('#gerenciaID').val();
            if (!val) {
                Swal.fire({
                    icon: 'error',
                    title: 'Selecciona una gerencia',
                    timer: 1600,
                    showConfirmButton: false,
                    timerProgressBar: true
                });
                return;
            }
            table.ajax.reload(null, false);
        });

        $('#guardar').on('click', async function() {
            const gerenciaID = $('#gerenciaID').val();
            if (!gerenciaID) {
                Swal.fire({
                    icon: 'error',
                    title: 'Selecciona una gerencia',
                    timer: 1600,
                    showConfirmButton: false,
                    timerProgressBar: true
                });
                return;
            }

            const rows = table.rows({
                search: 'applied'
            }).data().toArray();
            const payload = [];

            for (const r of rows) {
                const margen = Math.min(100, Math.max(0, Number(r.Margen) || 0));
                for (const mp of (r.MontosPorMes || [])) {
                    const costo = Number(mp.Costo) || 0;
                    const mes = Number(mp.Mes) || 0;
                    if (costo <= 0 || mes < 1 || mes > 12) continue;

                    const costoTotal = +(costo * (1 + margen / 100)).toFixed(2);

                    payload.push({
                        NombreInsumo: String(r.NombreInsumo || 'SIN_NOMBRE'),
                        Mes: mes,
                        Costo: +costo.toFixed(2),
                        Margen: +margen.toFixed(2),
                        CostoTotal: costoTotal,
                        GerenciaID: Number(gerenciaID)
                    });
                }
            }

            if (!payload.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tabla sin datos generados',
                    timer: 1400,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
                return;
            }

            try {
                const res = await fetch(`{{ route('cortes.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': `{{ csrf_token() }}`
                    },
                    body: JSON.stringify({
                        rows: payload
                    })
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || 'Error al guardar');

                Swal.fire({
                    icon: 'success',
                    title: `Corte Generado Correctamente`,
                    timer: 1800,
                    showConfirmButton: false,
                    timerProgressBar: true,
                }).then(() => {
                    window.location.reload();
                });
            } catch (e) {
                console.error(e);
                Swal.fire({
                    icon: 'error',
                    title: 'Solicitud Rechazada',
                    text: e.message || 'Error desconocido',
                    timerProgressBar: true
                });
            }
        });
    });
</script>
@endpush