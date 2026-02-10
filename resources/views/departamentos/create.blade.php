@extends('layouts.app')

@section('content')
<h3 class="text-xl font-extrabold text-[#101D49] dark:text-white mb-4">
    Crear Departamento
</h3>

<div class="content px-3">

    @include('adminlte-templates::common.errors')

    {!! Form::open(['route' => 'departamentos.store']) !!}

    <div class="flex flex-col gap-6">

        {{-- DATOS DEL DEPARTAMENTO --}}
        <div class="row">
            @include('departamentos.fields')
        </div>

        {{-- PERFIL DE REQUERIMIENTOS --}}
        <div>
            <h4 class="text-lg font-extrabold text-[#101D49] dark:text-white mb-2">
                Perfil de Requerimientos
            </h4>

            <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                Selecciona los recursos que aplican al departamento.
            </p>

            @foreach ($requerimientos as $categoria => $items)
            <div class="mb-6">
                <h5 class="font-semibold text-slate-700 dark:text-slate-200 mb-3">
                    {{ $categoria }}
                </h5>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    @foreach ($items as $idx => $item)
                    @php
                    $id = 'req-' . md5($categoria . $item['nombre'] . $idx);
                    @endphp

                    <div
                        class="req-card cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700
                               bg-slate-50 dark:bg-slate-900 px-4 py-3 transition-all duration-200 hover:shadow-sm"
                        data-target="{{ $id }}"
                        data-checked="0"
                        role="button"
                        tabindex="0"
                        aria-pressed="false"
                        style="transform: translateZ(0);">
                        {{-- CHECKBOX REAL --}}
                        <input
                            id="{{ $id }}"
                            type="checkbox"
                            name="requerimientos[]"
                            value="{{ $item['nombre'] }}"
                            class="req-checkbox"
                            style="position:absolute; left:-9999px; width:1px; height:1px;" />

                        <div class="flex items-center gap-3">
                            {{-- CHECK VISUAL --}}
                            <div
                                class="req-box flex h-5 w-5 items-center justify-center rounded-md border border-slate-300 dark:border-slate-600"
                                style="background:#ffffff;">
                                <svg
                                    class="req-check h-3 w-3"
                                    style="display:none; color:#ffffff; transform: scale(0.6); opacity: 0;"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>

                            {{-- TEXTO --}}
                            <div class="flex flex-col min-w-0">
                                <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                    {{ $item['nombre'] }}
                                </span>

                                @if (!empty($item['opcional']))
                                <span class="text-xs font-bold" style="color:#6366F1;">
                                    Opcional
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        {{-- BOTONES --}}
        <div class="flex gap-3">
            {!! Form::submit('Guardar', ['class' => 'btn btn-primary']) !!}
            <a href="{{ route('departamentos.index') }}" class="btn btn-danger">
                Cancelar
            </a>
        </div>

    </div>

    {!! Form::close() !!}
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        function pop(card, isOn) {
            card.animate(
                [{
                        transform: 'scale(1)'
                    },
                    {
                        transform: isOn ? 'scale(1.015)' : 'scale(0.99)'
                    },
                    {
                        transform: 'scale(1)'
                    }
                ], {
                    duration: 160,
                    easing: 'ease-out'
                }
            );

            const check = card.querySelector('.req-check');
            if (!check) return;

            if (isOn) {
                check.animate(
                    [{
                            transform: 'scale(0.6)',
                            opacity: 0
                        },
                        {
                            transform: 'scale(1.15)',
                            opacity: 1
                        },
                        {
                            transform: 'scale(1)',
                            opacity: 1
                        }
                    ], {
                        duration: 180,
                        easing: 'ease-out'
                    }
                );
            }
        }

        function applyState(card, checked) {
            const box = card.querySelector('.req-box');
            const check = card.querySelector('.req-check');
            const cb = card.querySelector('.req-checkbox');

            cb.checked = checked;
            card.dataset.checked = checked ? '1' : '0';
            card.setAttribute('aria-pressed', checked ? 'true' : 'false');

            if (checked) {
                card.style.borderColor = '#6366F1';
                card.style.background = 'rgba(99,102,241,.08)';

                box.style.borderColor = '#4F46E5';
                box.style.background = '#4F46E5';

                check.style.display = 'block';
                check.style.opacity = '1';
                check.style.transform = 'scale(1)';
                pop(card, true);
            } else {
                card.style.borderColor = '';
                card.style.background = '';

                box.style.borderColor = '';
                box.style.background = '#ffffff';

                check.style.display = 'none';
                check.style.opacity = '0';
                check.style.transform = 'scale(0.6)';
                pop(card, false);
            }
        }

        function toggleCard(card) {
            const now = card.dataset.checked === '1';
            applyState(card, !now);
        }

        document.querySelectorAll('.req-card').forEach(card => {

            card.addEventListener('click', () => {
                toggleCard(card);
            });

            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleCard(card);
                }
            });

            const cb = card.querySelector('.req-checkbox');
            cb.addEventListener('change', () => applyState(card, cb.checked));
        });
    });
</script>
@endsection