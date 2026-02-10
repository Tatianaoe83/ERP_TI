@extends('layouts.app')

@section('content')
<h3 class="text-xl font-extrabold text-[#101D49] dark:text-white mb-4">
    Departamentos Detalles
</h3>

<div class="content px-3">
    <div class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden
                dark:border-slate-800 dark:bg-slate-900">

        <div class="p-4 border-b border-slate-100 dark:border-slate-800">
            <div class="row">
                @include('departamentos.show_fields')
            </div>
        </div>

        <div class="p-4">
            <h4 class="text-lg font-extrabold text-slate-900 dark:text-slate-100 mb-2">
                Requerimientos seleccionados
            </h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                Solo lectura. Aquí se muestran únicamente los requerimientos marcados como seleccionados.
            </p>

            @php
                $totalSeleccionados = isset($requerimientosSeleccionados) ? $requerimientosSeleccionados->count() : 0;
            @endphp

            <div class="mb-4 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2
                        dark:border-slate-800 dark:bg-slate-950/40">
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Total:</span>
                <span class="text-sm font-extrabold text-slate-900 dark:text-slate-100">{{ $totalSeleccionados }}</span>
            </div>

            @if (empty($seleccionadosPorCategoria) || $seleccionadosPorCategoria->isEmpty())
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700
                            dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-200">
                    No hay requerimientos seleccionados para este departamento.
                </div>
            @else
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach ($seleccionadosPorCategoria as $categoria => $items)
                        <section class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden
                                        dark:border-slate-800 dark:bg-slate-900">
                            <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                                <div class="flex items-center justify-between gap-3">
                                    <h5 class="text-sm font-extrabold text-slate-800 dark:text-slate-100">
                                        {{ $categoria }}
                                    </h5>

                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-extrabold text-slate-600
                                                 dark:bg-slate-800 dark:text-slate-200">
                                        {{ $items->count() }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="grid gap-2 grid-cols-1 sm:grid-cols-2">
                                    @foreach ($items as $req)
                                        <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2
                                                    dark:border-slate-700 dark:bg-slate-800">
                                            <div class="mt-1 flex h-5 w-5 items-center justify-center rounded-md bg-indigo-600">
                                                <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-100 break-words">
                                                        {{ $req->nombre }}
                                                    </span>

                                                    @if (!empty($req->opcional))
                                                        <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-extrabold text-indigo-600
                                                                     dark:bg-indigo-500/10 dark:text-indigo-300">
                                                            Opcional
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('departamentos.index') }}" class="btn btn-danger">
                Volver
            </a>
        </div>
    </div>
</div>
@endsection
