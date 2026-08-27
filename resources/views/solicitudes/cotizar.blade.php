@extends('layouts.app')

@section('content')
<style>
    [x-cloak] { display: none !important; }
    .cotizar-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.85rem;
        padding: 1rem 1.05rem 0.9rem;
        box-shadow: 0 1px 2px rgba(16, 29, 73, 0.06);
    }
    .cotizar-card h2 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--index-navy, #101d49);
    }
    .cotizar-card__box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 0.7rem;
        padding: 0.75rem;
        color: #334155;
        font-size: 0.75rem;
        line-height: 1.5;
    }
    .cotizar-card__box p { margin: 0; color: inherit; }
    .cotizar-card__label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin: 0 0 0.45rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
    }
    .dark .cotizar-card {
        background: #1f2937;
        border-color: #374151;
        box-shadow: none;
    }
    .dark .cotizar-card h2 { color: #fff; }
    .dark .cotizar-card__box {
        background: rgba(17, 24, 39, 0.65);
        border-color: #374151;
        color: #d1d5db;
    }
    .dark .cotizar-card__label { color: #9ca3af; }
    .cotizar-table thead th {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #6b7280;
        background: #f3f4f6;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }
    .dark .cotizar-table thead th {
        color: #9ca3af;
        background: #242933;
        border-bottom-color: #374151;
    }
</style>

<x-index-page
    title="Cotización #{{ $solicitud->SolicitudID }}"
    icon="fa-file-invoice-dollar"
    subtitle="Precios sin IVA"
    :show-count="false"
    :card="false"
    x-data="cotizarPagina({{ $solicitud->SolicitudID }}, '{{ route('tickets.index') }}')"
>
    <x-slot name="headerActions">
        <span class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
            <i class="fas fa-layer-group text-xs"></i>
            <span class="font-semibold" x-text="propuestas.length"></span> propuestas
        </span>
        <a href="{{ route('tickets.index') }}" class="index-page__btn-secondary">Volver</a>
    </x-slot>

    <div x-show="cargando" class="text-center py-16">
        <div class="inline-flex flex-col items-center gap-4">
            <div class="relative">
                <i class="fas fa-spinner fa-spin text-5xl text-slate-400"></i>
                <div class="absolute inset-0 blur-xl bg-slate-400/20 rounded-full"></div>
            </div>
            <p class="text-slate-600 dark:text-slate-400 font-medium">Cargando cotizaciones...</p>
        </div>
    </div>

    <div x-show="!cargando" x-cloak class="grid grid-cols-1 xl:grid-cols-12 gap-4 md:gap-5 lg:gap-6 items-start">
        
        <div class="xl:col-span-3 space-y-4 md:space-y-5">

            <div class="cotizar-card">
                <div class="flex items-center gap-3 mb-3 md:mb-4">
                    <span class="index-page__icon" aria-hidden="true">
                        <i class="fas fa-lightbulb"></i>
                    </span>
                    <h2>¿Cómo funciona?</h2>
                </div>

                <div class="cotizar-card__box space-y-2">
                    <p><strong>1.</strong> Crea <strong>Propuestas</strong> (diferentes opciones)</p>
                    <p><strong>2.</strong> Agrega <strong>Productos</strong> en cada propuesta</p>
                    <p><strong>3.</strong> Registra <strong>Cotizaciones</strong> de proveedores</p>
                    <p><strong>4.</strong> Guarda y envía al gerente</p>
                </div>
            </div>

            <div class="cotizar-card">
                <div class="flex items-center gap-3 mb-3 md:mb-4">
                    <span class="index-page__icon" aria-hidden="true">
                        <i class="fas fa-clipboard-list"></i>
                    </span>
                    <h2>Detalles</h2>
                </div>

                <div class="space-y-3">
                    <div class="cotizar-card__box">
                        <div class="cotizar-card__label">
                            <i class="fas fa-align-left"></i>
                            Motivo
                        </div>
                        <p class="whitespace-pre-wrap">{{ $solicitud->DescripcionMotivo ?? 'N/A' }}</p>
                    </div>
                    <div class="cotizar-card__box">
                        <div class="cotizar-card__label">
                            <i class="fas fa-list-check"></i>
                            Requerimientos
                        </div>
                        <p class="whitespace-pre-wrap">{{ $solicitud->Requerimientos ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

        </div>

        <div class="xl:col-span-9 space-y-4 md:space-y-5">

            @if(($solicitud->Estatus ?? '') === 'Re-cotizar' && !empty($recotizarMotivo ?? ''))
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl md:rounded-2xl p-4 md:p-5 border-2 border-amber-300 dark:border-amber-700/50 shadow-lg">
                <div class="flex items-start gap-3 md:gap-4">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg md:rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center flex-shrink-0 shadow-lg">
                        <i class="fas fa-redo-alt text-white text-base md:text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm md:text-base font-bold text-amber-900 dark:text-amber-200 mb-1">Re-cotizar propuestas</h3>
                        @if(!empty($recotizarPropuestas ?? []))
                        <p class="text-xs md:text-sm text-amber-800 dark:text-amber-300 mb-2">
                            El gerente solicitó re-cotizar las propuestas: <strong>{{ implode(', ', array_map(fn($n) => 'Propuesta ' . $n, $recotizarPropuestas ?? [])) }}</strong>
                        </p>
                        @endif
                        <p class="text-xs md:text-sm text-amber-800 dark:text-amber-300 leading-relaxed">
                            <strong>Motivo del gerente:</strong> {{ $recotizarMotivo }}
                        </p>
                    </div>
                </div>
            </div>
            @endif
            
            <div x-show="tieneCotizacionesEnviadas" x-cloak class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl md:rounded-2xl p-4 md:p-5 border border-blue-200 dark:border-blue-700/50 shadow-lg">
                <div class="flex items-start gap-3 md:gap-4">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg md:rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center flex-shrink-0 shadow-lg">
                        <i class="fas fa-paper-plane text-white text-base md:text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm md:text-base font-bold text-blue-900 dark:text-blue-200 mb-1">Cotizaciones Enviadas</h3>
                        <p class="text-xs md:text-sm text-blue-700 dark:text-blue-300 leading-relaxed">
                            Las propuestas han sido enviadas al gerente para su revisión. Aún puedes editarlas si es necesario.
                        </p>
                    </div>
                </div>
            </div>

            <!-- PROPUESTAS -->
            <template x-for="(propuesta, propIndex) in propuestas" :key="propuesta.id">
            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-300 dark:border-slate-600 overflow-hidden shadow-sm">

                <!-- Header de Propuesta -->
                <div class="bg-[#101d49] dark:bg-[#1e3a5f] px-5 md:px-6 py-4 md:py-5">
                    <div class="flex items-center justify-between gap-4 md:gap-6">
                        <div class="flex-1 min-w-0 max-w-md">
                            <input type="text" x-model="propuesta.nombre" placeholder="Nombre de la propuesta"
                                class="w-full font-semibold text-base md:text-lg px-4 py-2.5 border-2 border-slate-500/30 rounded-xl bg-slate-800/50 dark:bg-slate-900/50 text-slate-50 placeholder-slate-400 focus:ring-2 focus:ring-slate-400 focus:border-slate-400 transition-all shadow-inner backdrop-blur-sm">
                        </div>

                        <!-- Metricas & Acciones -->
                        <div class="flex items-center gap-4 md:gap-6">
                            <div class="flex items-center gap-3 bg-slate-800/40 dark:bg-slate-900/40 rounded-xl px-4 py-2 backdrop-blur-sm border border-slate-600/30">
                                <div class="text-right">
                                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-medium mb-0.5">Cantidad</p>
                                    <input type="number" min="1" x-model.number="propuesta.cantidad"
                                        class="w-16 px-2 py-1 text-sm border border-slate-500/30 rounded-lg bg-slate-700/50 dark:bg-slate-800/50 text-slate-50 focus:ring-2 focus:ring-slate-400 focus:border-slate-400 transition-all text-center font-bold shadow-inner">
                                </div>
                            </div>

                            <!-- Stats -->
                            <div class="hidden sm:flex items-center bg-slate-800/40 dark:bg-slate-900/40 rounded-xl px-5 py-2.5 backdrop-blur-sm border border-slate-600/30">
                                <div class="text-center">
                                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-medium mb-0.5">Productos</p>
                                    <p class="text-lg font-bold text-slate-50" x-text="propuesta.productos.length"></p>
                                </div>
                                
                                <div class="w-px h-8 bg-slate-600/50"></div>
                            
                            </div>

                            <div class="flex sm:hidden flex-col items-end gap-1 bg-slate-800/40 dark:bg-slate-900/40 rounded-xl px-4 py-2 backdrop-blur-sm border border-slate-600/30 min-w-[100px]">
                                <p class="text-[10px] text-slate-400"><span class="font-bold text-slate-200" x-text="propuesta.productos.length"></span> prod.</p>
                                <p class="text-sm font-bold text-emerald-400" x-text="'$' + totalPropuesta(propuesta).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></p>
                            </div>

                            <button
                                @click="eliminarPropuesta(propIndex)"
                                x-show="propuestas.length > 1"
                                class="text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-all p-2.5 rounded-lg border border-transparent hover:border-red-500/30"
                                title="Eliminar propuesta">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PRODUCTOS de la Propuesta -->
                <div class="p-4 md:p-5 space-y-3 md:space-y-4">
                    <template x-for="(producto, prodIndex) in propuesta.productos" :key="producto.id">
                        <div class="bg-slate-100 dark:bg-slate-700 rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden">

                            <!-- Header del Producto -->
                            <div
                                @click="producto.abierto = !producto.abierto"
                                class="flex items-center justify-between px-3 md:px-4 py-2.5 md:py-3 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-600 transition-all">
                                <div class="flex items-center gap-2 md:gap-3 min-w-0 flex-1">
                                    <i class="fas transition-transform text-slate-600 dark:text-slate-300 text-sm" :class="producto.abierto ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                    <i class="fas fa-box text-slate-600 dark:text-slate-300 text-sm"></i>
                                    <span class="font-medium text-sm md:text-base text-slate-900 dark:text-slate-100 truncate" x-text="producto.nombre || 'Nuevo Producto'"></span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 bg-slate-200 dark:bg-slate-600 px-2 py-0.5 rounded flex-shrink-0"><span x-text="propuesta.cantidad"></span> <span x-text="producto.unidad"></span></span>
                                </div>
                                <div class="flex items-center gap-2 md:gap-4 flex-shrink-0">
                                    <span class="text-xs md:text-sm text-slate-600 dark:text-slate-300">
                                        <span x-text="cotizacionesConPrecio(producto).length"></span> cot. •
                                    </span>
                                    <button
                                        @click.stop="eliminarProducto(propuesta, prodIndex)"
                                        x-show="propuesta.productos.length > 1"
                                        class="text-slate-500 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 transition-colors p-1.5 hover:bg-slate-300 dark:hover:bg-slate-500 rounded"
                                        title="Eliminar producto">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Detalles del Producto -->
                            <div x-show="producto.abierto" x-collapse class="border-t border-slate-300 dark:border-slate-600">
                                <div class="p-3 md:p-4 bg-slate-50 dark:bg-slate-800">
                                    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Nombre del producto</label>
                                            <input type="text" x-model="producto.nombre" placeholder="Ej. Laptop HP Pavilion, Mouse Logitech"
                                                class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-slate-400 focus:border-slate-400 transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1.5">Unidad</label>
                                            <select x-model="producto.unidad"
                                                class="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 focus:border-slate-400 transition-all">
                                                <option value="PIEZA">PIEZA</option>
                                                <option value="LOTE">LOTE</option>
                                                <option value="SERVICIO">SERVICIO</option>
                                                <option value="KIT">KIT</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Tabla de Cotizaciones -->
                                    <div class="overflow-x-auto border border-slate-200 dark:border-slate-600 rounded-lg -mx-1">
                                        <table class="cotizar-table min-w-full text-xs md:text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="px-2 md:px-3 py-2 md:py-2.5 text-left">Proveedor</th>
                                                    <th class="px-2 md:px-3 py-2 md:py-2.5 text-left">No. Parte</th>
                                                    <th class="px-2 md:px-3 py-2 md:py-2.5 text-left">Descripción</th>
                                                    <th class="px-2 md:px-3 py-2 md:py-2.5 text-right">Precio Unit.</th>
                                                    <th class="px-2 md:px-3 py-2 md:py-2.5 text-right">Envío</th>
                                                    <th class="px-2 md:px-3 py-2 md:py-2.5 text-right">Total</th>
                                                    <th class="px-2 md:px-3 py-2 md:py-2.5 text-center w-12 md:w-16"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200 dark:divide-slate-600 bg-slate-100 dark:bg-slate-700">
                                                <template x-for="(cot, cotIndex) in producto.cotizaciones" :key="cotIndex">
                                                    <tr class="hover:bg-slate-200 dark:hover:bg-slate-600/50 transition-colors">
                                                        <td class="px-2 md:px-3 py-1.5 md:py-2">
                                                            <input type="text" x-model="cot.proveedor" placeholder="Proveedor"
                                                                class="w-full px-2 py-1.5 text-xs md:text-sm border border-slate-300 dark:border-slate-500 rounded bg-slate-50 dark:bg-slate-600 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-1 focus:ring-slate-400 focus:border-slate-400">
                                                        </td>
                                                        <td class="px-2 md:px-3 py-1.5 md:py-2">
                                                            <input type="text" x-model="cot.numeroParte" placeholder="#"
                                                                class="w-full px-2 py-1.5 text-xs md:text-sm border border-slate-300 dark:border-slate-500 rounded bg-slate-50 dark:bg-slate-600 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-1 focus:ring-slate-400 focus:border-slate-400">
                                                        </td>
                                                        <td class="px-2 md:px-3 py-1.5 md:py-2">
                                                            <input type="text" x-model="cot.descripcion" placeholder="Descripción"
                                                                class="w-full px-2 py-1.5 text-xs md:text-sm border border-slate-300 dark:border-slate-500 rounded bg-slate-50 dark:bg-slate-600 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-1 focus:ring-slate-400 focus:border-slate-400">
                                                        </td>
                                                        <td class="px-2 md:px-3 py-1.5 md:py-2">
                                                            <input type="number" step="0.01" min="0" x-model.number="cot.precioUnitario" placeholder="0.00"
                                                                @input="cot.total = ((propuesta.cantidad * (parseFloat(cot.precioUnitario) || 0)) + (parseFloat(cot.costoEnvio) || 0)).toFixed(2)"
                                                                class="w-full px-2 py-1.5 text-xs md:text-sm border border-slate-300 dark:border-slate-500 rounded bg-slate-50 dark:bg-slate-600 text-slate-900 dark:text-slate-100 text-right focus:ring-1 focus:ring-slate-400 focus:border-slate-400">
                                                        </td>
                                                        <td class="px-2 md:px-3 py-1.5 md:py-2">
                                                            <input type="number" step="0.01" min="0" x-model.number="cot.costoEnvio" placeholder="0.00"
                                                                @input="cot.total = ((propuesta.cantidad * (parseFloat(cot.precioUnitario) || 0)) + (parseFloat(cot.costoEnvio) || 0)).toFixed(2)"
                                                                class="w-full px-2 py-1.5 text-xs md:text-sm border border-slate-300 dark:border-slate-500 rounded bg-slate-50 dark:bg-slate-600 text-slate-900 dark:text-slate-100 text-right focus:ring-1 focus:ring-slate-400 focus:border-slate-400">
                                                        </td>
                                                        <td class="px-2 md:px-3 py-1.5 md:py-2 text-right font-semibold text-xs md:text-sm text-slate-900 dark:text-slate-100">
                                                            <span x-text="'$' + ((propuesta.cantidad * (parseFloat(cot.precioUnitario) || 0)) + (parseFloat(cot.costoEnvio) || 0)).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                                                        </td>
                                                        <td class="px-2 md:px-3 py-1.5 md:py-2 text-center">
                                                            <button @click="eliminarCotizacion(producto, cotIndex)"
                                                                class="text-slate-500 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 p-1 hover:bg-slate-300 dark:hover:bg-slate-500 rounded transition-all text-xs md:text-sm">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </template>
                                                <tr x-show="producto.cotizaciones.length === 0">
                                                    <td colspan="7" class="px-3 py-6 md:py-8 text-center text-slate-500 dark:text-slate-400 text-xs md:text-sm">
                                                        Sin cotizaciones. Agrega una nueva cotización.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <button type="button" @click="agregarCotizacion(producto)"
                                        class="mt-3 px-3 md:px-4 py-1.5 md:py-2 bg-slate-600 hover:bg-slate-700 dark:bg-slate-600 dark:hover:bg-slate-500 text-white text-xs md:text-sm font-medium rounded-lg transition-all shadow-sm">
                                        <i class="fas fa-plus mr-2"></i> Agregar Cotización
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Botón Agregar Producto dentro de la propuesta -->
                    <div class="flex justify-center pt-2">
                        <button type="button" @click="agregarProducto(propuesta)"
                            class="px-4 md:px-5 py-2 md:py-2.5 bg-slate-600 hover:bg-slate-700 dark:bg-slate-600 dark:hover:bg-slate-500 text-white text-xs md:text-sm font-medium rounded-lg transition-all shadow-sm">
                            <i class="fas fa-plus mr-2"></i> Agregar Producto
                        </button>
                    </div>
                </div>
            </div>
        </template>

            <!-- Botón Agregar Nueva Propuesta -->
            <div class="flex justify-center pt-3">
                <button type="button" @click="agregarPropuesta()"
                    class="px-5 md:px-6 py-2.5 md:py-3 bg-slate-700 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-sm md:text-base font-semibold rounded-lg transition-all shadow-md">
                    <i class="fas fa-layer-group mr-2"></i> Nueva Propuesta
                </button>
            </div>

            <!-- Botones de acción -->
            <div class="flex flex-wrap items-center justify-center sm:justify-end gap-2 md:gap-3 pt-4 md:pt-6 border-t border-slate-200 dark:border-slate-600 mt-4 md:mt-6">
                    <button type="button" @click="guardar()" class="index-page__btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" x-show="tieneCotizacionesGuardadas"
                        @click="enviarAlGerente()"
                        class="index-page__btn-primary">
                        <i class="fas fa-paper-plane"></i> Enviar al Gerente
                    </button>
            </div>

        </div>
        <!-- FIN COLUMNA DERECHA -->
        
    </div>
    <!-- FIN GRID PRINCIPAL -->
</x-index-page>

@verbatim
<script>
    (function () {
        function cotizarPagina(solicitudId, ticketsUrl) {
            ticketsUrl = ticketsUrl || '/tickets';
            return {
            solicitudId: solicitudId,
            ticketsUrl: ticketsUrl,
            cargando: true,
            propuestas: [],
            nextId: 1,
            tieneCotizacionesGuardadas: false,
            tieneCotizacionesEnviadas: false,
            

            async init() {
                await this.cargar();
            },

            get totalGeneral() {
                return this.propuestas.reduce((sum, prop) => sum + this.totalPropuesta(prop), 0);
            },

            totalPropuesta(propuesta) {
                return propuesta.productos.reduce((sum, prod) => sum + this.totalProducto(prod, propuesta), 0);
            },

            totalProducto(producto, propuesta) {
                const cantidad = propuesta ? propuesta.cantidad : 1;
                return producto.cotizaciones.reduce((s, c) => {
                    const u = parseFloat(c.precioUnitario) || 0;
                    const envio = parseFloat(c.costoEnvio) || 0;
                    return s + (cantidad * u) + envio;
                }, 0);
            },

            cotizacionesConPrecio(producto) {
                return producto.cotizaciones.filter(c => (parseFloat(c.precioUnitario) || 0) > 0);
            },

            nuevaPropuesta(numero) {
                return {
                    id: this.nextId++,
                    numero: numero,
                    nombre: `Propuesta ${numero}`,
                    cantidad: 1,
                    productos: []
                };
            },

            nuevoProducto(nombre = 'Nuevo Producto') {
                return {
                    id: this.nextId++,
                    abierto: true,
                    nombre,
                    unidad: 'PIEZA',
                    cotizaciones: []
                };
            },

            nuevaCotizacion(producto) {
                const ultima = producto.cotizaciones?.at(0);
                
                return {
                    proveedor: '',
                    numeroParte: ultima ? ultima.numeroParte : '',
                    descripcion: ultima ? ultima.descripcion : '',
                    precioUnitario: '',
                    costoEnvio: 0,
                    total: '0.00'
                };
            },

            agregarPropuesta() {
                const numero = this.propuestas.length + 1;
                const prop = this.nuevaPropuesta(numero);
                const prod = this.nuevoProducto();
                prod.cotizaciones.push(this.nuevaCotizacion(prod));
                prop.productos.push(prod);
                this.propuestas.push(prop);
            },

            eliminarPropuesta(index) {
                this.propuestas.splice(index, 1);
                // Renumerar propuestas
                this.propuestas.forEach((prop, i) => {
                    prop.numero = i + 1;
                    if (!prop.nombre || prop.nombre.match(/^Propuesta \d+$/)) {
                        prop.nombre = `Propuesta ${i + 1}`;
                    }
                });
            },

            agregarProducto(propuesta) {
                const prod = this.nuevoProducto();
                prod.cotizaciones.push(this.nuevaCotizacion(prod));
                propuesta.productos.push(prod);
            },

            eliminarProducto(propuesta, index) {
                propuesta.productos.splice(index, 1);
            },

            agregarCotizacion(producto) {
                producto.cotizaciones.push(this.nuevaCotizacion(producto));
            },

            eliminarCotizacion(producto, index) {
                producto.cotizaciones.splice(index, 1);
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
                        // Agrupar productos por NumeroPropuesta
                        const propuestasMap = {};

                        productos.forEach(p => {
                            const numProp = p.numeroPropuesta || 1;

                            if (!propuestasMap[numProp]) {
                                propuestasMap[numProp] = [];
                            }

                            propuestasMap[numProp].push(p);
                        });

                        // Crear estructura de propuestas
                        this.propuestas = Object.keys(propuestasMap).sort((a, b) => a - b).map((numProp) => {
                            const productosEnPropuesta = propuestasMap[numProp];
                            const prop = this.nuevaPropuesta(parseInt(numProp));
                            prop.nombre = `Propuesta ${numProp}`;

                            // Ordenar productos por NumeroProducto
                            productosEnPropuesta.sort((a, b) => (a.numeroProducto || 0) - (b.numeroProducto || 0));

                            // Obtener la cantidad del primer producto para la propuesta
                            prop.cantidad = productosEnPropuesta.length > 0 ? Math.max(1, parseInt(productosEnPropuesta[0].cantidad) || 1) : 1;

                            prop.productos = productosEnPropuesta.map(p => {
                                const prod = this.nuevoProducto(p.nombreEquipo || p.descripcion || 'Producto');
                                prod.unidad = (p.unidad || 'PIEZA').trim() || 'PIEZA';

                                const precios = p.precios || {};
                                const descripciones = p.descripciones || {};
                                const numeroPartes = p.numeroPartes || {};

                                prod.cotizaciones = proveedores.map(prov => {
                                    const precioData = precios[prov];
                                    const uv = typeof precioData === 'object' && precioData !== null ?
                                        (parseFloat(precioData.precio_unitario) || 0) :
                                        (typeof precioData === 'number' ? precioData : parseFloat(precioData) || 0);
                                    const costoEnvio = typeof precioData === 'object' && precioData !== null ?
                                        (parseFloat(precioData.costo_envio) || 0) :
                                        0;
                                    if (uv <= 0) return null;
                                    return {
                                        proveedor: prov,
                                        numeroParte: (numeroPartes[prov] ?? p.numeroParte ?? '').trim() || (p.numeroParte || ''),
                                        descripcion: (descripciones[prov] || p.descripcion || '').trim() || (p.descripcion || ''),
                                        precioUnitario: uv,
                                        costoEnvio: costoEnvio,
                                        total: ((prop.cantidad * uv) + costoEnvio).toFixed(2)
                                    };
                                }).filter(Boolean);

                                if (prod.cotizaciones.length === 0) {
                                    prod.cotizaciones.push(this.nuevaCotizacion(prod));
                                }

                                return prod;
                            });

                            return prop;
                        });
                    } else {
                        // Inicializar con una propuesta vacía
                        this.agregarPropuesta();
                    }
                } catch (e) {
                    console.error(e);
                    this.agregarPropuesta();
                }
                this.cargando = false;
            },

            buildPayload() {
                const proveedoresSet = new Set();
                const productos = [];

                this.propuestas.forEach(propuesta => {
                    let contadorProducto = 1; // Contador por propuesta

                    propuesta.productos.forEach((producto) => {
                        const nombreProd = (producto.nombre || '').trim();
                        if (!nombreProd) return;

                        const precios = {};
                        const descripciones = {};
                        const numerosParte = {};

                        producto.cotizaciones.forEach(c => {
                            const prov = (c.proveedor || '').trim();
                            const u = parseFloat(c.precioUnitario) || 0;
                            if (!prov || u <= 0) return;

                            proveedoresSet.add(prov);
                            precios[prov] = {
                                precio_unitario: u,
                                costo_envio: parseFloat(c.costoEnvio) || 0
                            };
                            descripciones[prov] = (c.descripcion || '').trim() || nombreProd;
                            numerosParte[prov] = (c.numeroParte || '').trim();
                        });

                        if (Object.keys(precios).length) {
                            productos.push({
                                numero_propuesta: propuesta.numero,
                                numero_producto: contadorProducto++,
                                cantidad: Math.max(1, parseInt(propuesta.cantidad) || 1),
                                numero_parte: '',
                                descripcion: nombreProd,
                                nombre_equipo: nombreProd,
                                descripciones,
                                numeros_parte: numerosParte,
                                unidad: (producto.unidad || 'PIEZA').trim() || 'PIEZA',
                                precios,
                                tiempo_entrega: {},
                                observaciones: {}
                            });
                        }
                    });
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
                    Swal.fire('Aviso', 'Agrega al menos una propuesta con productos y cotizaciones con precio.', 'warning');
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
                    text: 'Se enviará un correo para que elija 1 ganador por propuesta.',
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
        };
        }

        window.cotizarPagina = cotizarPagina;

        function registrarCotizarPagina() {
            if (!window.Alpine || typeof Alpine.data !== 'function') return;
            Alpine.data('cotizarPagina', window.cotizarPagina);
        }

        if (window.Alpine) {
            registrarCotizarPagina();
        } else {
            document.addEventListener('alpine:init', registrarCotizarPagina);
        }
    })();
</script>
@endverbatim
@endsection