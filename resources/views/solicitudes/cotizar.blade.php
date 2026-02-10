@extends('layouts.app')

@section('content')
<div
    x-data="cotizarPagina({{ $solicitud->SolicitudID }}, '{{ route('tickets.index') }}')"
    x-init="init()"
    class="px-2 w-full max-w-6xl mx-auto py-4">
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <!-- Header -->
    <div class="dark:bg-slate-900 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('tickets.index') }}"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-sm font-medium no-underline">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">
                        Cotización - Solicitud #{{ $solicitud->SolicitudID }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">PRECIO SIN IVA INCLUIDO</p>
                </div>
                <div class="text-sm text-slate-600 dark:text-slate-300" x-show="!cargando">
                    Total de equipos: <span class="font-semibold" x-text="equipos.length"></span>
                </div>
            </div>
            <div class="text-lg font-bold text-slate-900 dark:text-slate-100" x-show="!cargando">
                Total General: <span x-text="'$' + totalGeneral.toLocaleString('es-MX', { minimumFractionDigits: 2 })"></span>
            </div>
        </div>
    </div>

    <!-- Información de la Solicitud: Requerimientos y Descripción del motivo -->
    <div class="dark:bg-slate-900 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-5 mb-4">
        <h2 class="text-base font-semibold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-500 dark:text-blue-400"></i>
            Información de la Solicitud
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Descripción del motivo</label>
                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $solicitud->DescripcionMotivo ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Requerimientos</label>
                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ $solicitud->Requerimientos ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div x-show="cargando" class="text-center py-12">
        <i class="fas fa-spinner fa-spin text-4xl text-slate-400"></i>
        <p class="mt-2 text-slate-600 dark:text-slate-400">Cargando cotizaciones...</p>
    </div>

    <div x-show="tieneCotizacionesEnviadas && !cargando" x-cloak class="mb-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
        <div class="flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-500 dark:text-blue-400"></i>
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <strong>Cotizaciones enviadas:</strong> Ya se enviaron al gerente. Puedes agregar o editar cotizaciones si es necesario.
            </p>
        </div>
    </div>

    <div x-show="!cargando" x-cloak class="space-y-4">
        <template x-for="(equipo, eqIndex) in equipos" :key="equipo.id">
            <div class="bg-gray-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div
                    @click="equipo.abierto = !equipo.abierto"
                    class="flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-gray-200/50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-2">
                        <i class="fas text-slate-500 transition-transform" :class="equipo.abierto ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        <span class="font-semibold text-slate-900 dark:text-slate-100" x-text="equipo.nombre || 'Nuevo Equipo'"></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-600 dark:text-slate-400">
                            Cotizaciones: <span x-text="cotizacionesConPrecio(equipo).length"></span>
                            Total: <span x-text="'$' + totalEquipo(equipo).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></span>
                        </span>
                        <button
                            @click.stop="eliminarEquipo(eqIndex)"
                            x-show="equipos.length > 1"
                            class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                            title="Eliminar equipo">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div x-show="equipo.abierto" x-collapse class="border-t border-slate-200 dark:border-slate-700">
                    <div class="p-4">
                        <div class="mb-3 flex flex-wrap gap-3">
                            <div class="flex-1 min-w-[160px]">
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nombre del equipo</label>
                                <input type="text" x-model="equipo.nombre" placeholder="Ej. Mouse, Laptop"
                                    class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="w-24">
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Cantidad</label>
                                <input type="number" min="1" x-model.number="equipo.cantidad"
                                    class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-lg">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-100 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">Unidad</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">Proveedor</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">No. Parte</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-600 dark:text-slate-300 uppercase">Descripción</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-slate-600 dark:text-slate-300 uppercase w-20">Cantidad</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase w-32">Precio Unit.</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase w-32">Envío</th>
                                        <th class="px-3 py-2 text-right text-xs font-medium text-slate-600 dark:text-slate-300 uppercase w-28">Total</th>
                                        <th class="px-3 py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <template x-for="(cot, cotIndex) in equipo.cotizaciones" :key="cotIndex">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                            <td class="px-3 py-2">
                                                <input type="text" x-model="equipo.unidad" placeholder="Pieza"
                                                    class="w-full px-2 py-1 text-sm border border-slate-300 dark:border-slate-600 rounded dark:bg-slate-900 text-slate-900 dark:text-slate-100">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" x-model="cot.proveedor" placeholder="Proveedor"
                                                    class="w-full px-2 py-1 text-sm border border-slate-300 dark:border-slate-600 rounded dark:bg-slate-900 text-slate-900 dark:text-slate-100">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" x-model="cot.numeroParte" placeholder="No. Parte"
                                                    class="w-full px-2 py-1 text-sm border border-slate-300 dark:border-slate-600 rounded dark:bg-slate-900 text-slate-900 dark:text-slate-100">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" x-model="cot.descripcion" :placeholder="equipo.nombre || 'Descripción'"
                                                    class="w-full px-2 py-1 text-sm border border-slate-300 dark:border-slate-600 rounded dark:bg-slate-900 text-slate-900 dark:text-slate-100">
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span x-text="equipo.cantidad"></span>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="flex items-center justify-end gap-1">
                                                    <span class="text-slate-500">$</span>
                                                    <input type="number" step="0.01" min="0" x-model.number="cot.precioUnitario" placeholder="0"
                                                        @input="cot.total = ((equipo.cantidad * (parseFloat(cot.precioUnitario) || 0)) + (parseFloat(cot.costoEnvio) || 0)).toFixed(2)"
                                                        class="w-full px-2 py-1 text-sm text-right border border-slate-300 dark:border-slate-600 rounded dark:bg-slate-900 text-slate-900 dark:text-slate-100">
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="flex items-center justify-end gap-1">
                                                    <span class="text-slate-500">$</span>
                                                    <input type="number" step="0.01" min="0" x-model.number="cot.costoEnvio" placeholder="0"
                                                        @input="cot.total = ((equipo.cantidad * (parseFloat(cot.precioUnitario) || 0)) + (parseFloat(cot.costoEnvio) || 0)).toFixed(2)"
                                                        class="w-full px-2 py-1 text-sm text-right border border-slate-300 dark:border-slate-600 rounded dark:bg-slate-900 text-slate-900 dark:text-slate-100">
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-right font-medium text-slate-900 dark:text-slate-100">
                                                $<span x-text="((equipo.cantidad * (parseFloat(cot.precioUnitario) || 0)) + (parseFloat(cot.costoEnvio) || 0)).toFixed(2)"></span>
                                            </td>
                                            <td class="px-3 py-2">
                                                <button type="button" @click="eliminarCotizacion(equipo, cotIndex)"
                                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="equipo.cotizaciones.length === 0">
                                        <td colspan="8" class="px-4 py-6 text-center text-slate-500 dark:text-slate-400">
                                            No hay cotizaciones para este equipo. Haz clic en &quot;Agregar Cotización&quot;.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" @click="agregarCotizacion(equipo)"
                            class="mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-plus mr-2"></i> Agregar Cotización
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex justify-center pt-2">
            <button type="button" @click="agregarEquipo()"
                class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i> Agregar Equipo
            </button>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 pt-6 border-t border-slate-200 dark:border-slate-700 mt-6">

            <div class="flex gap-2 flex-wrap">
                <button type="button" x-show="tieneCotizacionesGuardadas"
                    @click="enviarAlGerente()"
                    class="px-4 py-2 bg-violet-600 hover:bg-violet-700 dark:bg-violet-700 dark:hover:bg-violet-600 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-envelope mr-2"></i> Enviar al Gerente
                </button>
                <button type="button" @click="guardar()"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-save mr-2"></i> Guardar Cotizaciones
                </button>
            </div>
        </div>
    </div>
</div>

@verbatim
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('cotizarPagina', (solicitudId, ticketsUrl = '/tickets') => ({
            solicitudId,
            ticketsUrl,
            cargando: true,
            equipos: [],
            nextId: 1,
            tieneCotizacionesGuardadas: false,
            tieneCotizacionesEnviadas: false,

            async init() {
                await this.cargar();
            },

            get totalGeneral() {
                return this.equipos.reduce((sum, eq) => sum + this.totalEquipo(eq), 0);
            },

            totalEquipo(equipo) {
                return equipo.cotizaciones.reduce((s, c) => {
                    const u = parseFloat(c.precioUnitario) || 0;
                    const envio = parseFloat(c.costoEnvio) || 0;
                    return s + (equipo.cantidad * u) + envio;
                }, 0);
            },

            cotizacionesConPrecio(equipo) {
                return equipo.cotizaciones.filter(c => (parseFloat(c.precioUnitario) || 0) > 0);
            },

            nuevoEquipo(nombre = 'Nuevo Equipo') {
                return {
                    id: this.nextId++,
                    abierto: true,
                    nombre,
                    numeroParte: '',
                    cantidad: 1,
                    unidad: 'PIEZA',
                    cotizaciones: []
                };
            },

            nuevaCotizacion(equipo) {
                return {
                    proveedor: '',
                    numeroParte: equipo.numeroParte || '',
                    descripcion: equipo.nombre || '',
                    precioUnitario: '',
                    costoEnvio: 0,
                    total: '0.00'
                };
            },

            agregarEquipo() {
                this.equipos.push(this.nuevoEquipo());
            },

            eliminarEquipo(index) {
                this.equipos.splice(index, 1);
            },

            agregarCotizacion(equipo) {
                equipo.cotizaciones.push(this.nuevaCotizacion(equipo));
            },

            eliminarCotizacion(equipo, index) {
                equipo.cotizaciones.splice(index, 1);
            },

            async cargar() {
                this.cargando = true;
                try {
                    const res = await fetch(`/solicitudes/${this.solicitudId}/cotizaciones`);
                    const data = await res.json().catch(() => ({}));
                    const proveedores = data.proveedores || [];
                    const productos = data.productos || [];
                    this.tieneCotizacionesEnviadas = !!data.tieneCotizacionesEnviadas;
                    this.tieneCotizacionesGuardadas = productos.length > 0;

                    if (productos.length) {
                        this.equipos = productos.map(p => {
                            const nombreEq = (p.nombreEquipo || p.descripcion || '').trim() || (p.descripcion || '');
                            const eq = this.nuevoEquipo(nombreEq);
                            eq.numeroParte = p.numeroParte || '';
                            eq.cantidad = Math.max(1, parseInt(p.cantidad) || 1);
                            eq.unidad = (p.unidad || 'PIEZA').trim() || 'PIEZA';
                            const precios = p.precios || {};
                            const descripciones = p.descripciones || {};
                            const numeroPartes = p.numeroPartes || {};
                            eq.cotizaciones = proveedores.map(prov => {
                                const u = precios[prov];
                                const uv = (typeof u === 'number' ? u : parseFloat(u)) || 0;
                                if (uv <= 0) return null;
                                return {
                                    proveedor: prov,
                                    numeroParte: (numeroPartes[prov] ?? p.numeroParte ?? '').trim() || (p.numeroParte || ''),
                                    descripcion: (descripciones[prov] || p.descripcion || '').trim() || (p.descripcion || ''),
                                    precioUnitario: uv,
                                    total: (eq.cantidad * uv).toFixed(2)
                                };
                            }).filter(Boolean);
                            if (eq.cotizaciones.length === 0) eq.cotizaciones.push(this.nuevaCotizacion(eq));
                            return eq;
                        });
                    } else {
                        this.equipos = [this.nuevoEquipo()];
                        this.equipos[0].cotizaciones.push(this.nuevaCotizacion(this.equipos[0]));
                    }
                } catch (e) {
                    console.error(e);
                    this.equipos = [this.nuevoEquipo()];
                    this.equipos[0].cotizaciones.push(this.nuevaCotizacion(this.equipos[0]));
                }
                this.cargando = false;
            },

            buildPayload() {
                const proveedoresSet = new Set();
                const productos = [];

                this.equipos.forEach(equipo => {
                    const descBase = (equipo.nombre || '').trim();
                    if (!descBase) return;
                    const precios = {};
                    const descripciones = {};
                    const numerosParte = {};
                    equipo.cotizaciones.forEach(c => {
                        const prov = (c.proveedor || '').trim();
                        const u = parseFloat(c.precioUnitario) || 0;
                        if (!prov || u <= 0) return;
                        proveedoresSet.add(prov);
                        precios[prov] = {
                            precio_unitario: u,
                            costo_envio: parseFloat(c.costoEnvio) || 0
                        };
                        const d = (c.descripcion || '').trim() || descBase;
                        descripciones[prov] = d;
                        const np = (c.numeroParte || '').trim() || (equipo.numeroParte || '').trim();
                        numerosParte[prov] = np;
                    });
                    if (Object.keys(precios).length) {
                        productos.push({
                            cantidad: Math.max(1, parseInt(equipo.cantidad) || 1),
                            numero_parte: (equipo.numeroParte || '').trim(),
                            descripcion: descBase,
                            nombre_equipo: descBase,
                            descripciones,
                            numeros_parte: numerosParte,
                            unidad: (equipo.unidad || 'PIEZA').trim() || 'PIEZA',
                            precios,
                            tiempo_entrega: {},
                            observaciones: {}
                        });
                    }
                });

                const proveedores = Array.from(proveedoresSet);
                return {
                    proveedores,
                    productos
                };
            },

            async guardar() {
                const {
                    proveedores,
                    productos
                } = this.buildPayload();
                if (!productos.length) {
                    Swal.fire('Aviso', 'Agrega al menos un equipo con nombre y al menos una cotización con precio.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Guardando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                try {
                    const res = await fetch(`/solicitudes/${this.solicitudId}/guardar-cotizaciones`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            proveedores,
                            productos
                        })
                    });
                    const data = await res.json().catch(() => ({}));
                    Swal.close();
                    if (data.success) {
                        this.tieneCotizacionesGuardadas = true;
                        await Swal.fire('Éxito', data.message || 'Cotizaciones guardadas.', 'success');
                        await this.cargar();
                    } else {
                        Swal.fire('Error', data.message || 'Error al guardar.', 'error');
                    }
                } catch (e) {
                    Swal.close();
                    Swal.fire('Error', 'Error al guardar las cotizaciones.', 'error');
                }
            },

            async enviarAlGerente() {
                const ok = await Swal.fire({
                    title: '¿Enviar cotizaciones al gerente?',
                    text: 'Se enviará un correo para que elija el ganador de cada equipo.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, enviar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0F766E'
                }).then(r => r.isConfirmed);
                if (!ok) return;

                Swal.fire({
                    title: 'Enviando...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                try {
                    const res = await fetch(`/solicitudes/${this.solicitudId}/enviar-cotizaciones-gerente`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({})
                    });
                    const data = await res.json().catch(() => ({}));
                    Swal.close();
                    if (data.success) {
                        await Swal.fire('Éxito', data.message || 'Enviado al gerente.', 'success');
                        window.location.href = this.ticketsUrl;
                    } else {
                        Swal.fire('Error', data.message || 'Error al enviar.', 'error');
                    }
                } catch (e) {
                    Swal.close();
                    Swal.fire('Error', 'Error al enviar.', 'error');
                }
            }
        }));
    });
</script>
@endverbatim
@endsection