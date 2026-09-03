{{--
    Alerta de confirmación del módulo de Tareas.
    Compartida por livewire/tabla-tareas y livewire/productividad-tareas.

    Uso:  @include('partials.tareas-alerta', ['mensaje' => session('tareas_mensaje')])

    El estilo va en CSS propio con el patrón `.dark .selector`, NO con variantes
    `dark:` de Tailwind: esas clases no están en el bundle compilado
    (public/css/app.css) y la alerta quedaba ilegible en modo oscuro.
--}}
@php
    // 'error' pinta la alerta en rojo; cualquier otra cosa la deja en verde.
    $tipo = $tipo ?? 'exito';
    $esError = $tipo === 'error';
@endphp
@if (!empty($mensaje))
<div class="tareas-alerta {{ $esError ? 'tareas-alerta--error' : '' }}"
     role="{{ $esError ? 'alert' : 'status' }}"
     x-data="{ ver: true }" x-show="ver" x-transition.opacity>
    <span class="tareas-alerta__icono">
        <i class="fas {{ $esError ? 'fa-exclamation' : 'fa-check' }}"></i>
    </span>
    <span class="tareas-alerta__texto">{{ $mensaje }}</span>
    <button type="button" class="tareas-alerta__cerrar" @click="ver = false" aria-label="Cerrar">
        <i class="fas fa-times"></i>
    </button>
</div>
@endif

@once
<style>
    .tareas-alerta {
        display:flex; align-items:center; gap:.75rem;
        margin-bottom:1.25rem; padding:.85rem 1rem;
        border-radius:12px;
        border:1px solid #a7f3d0;
        background:#ecfdf5;
        color:#065f46;
        font-size:.9rem;
        box-shadow:0 1px 2px rgba(16,185,129,.08);
    }
    .tareas-alerta__icono {
        flex:0 0 auto;
        width:26px; height:26px; border-radius:999px;
        display:inline-flex; align-items:center; justify-content:center;
        background:#10b981; color:#fff; font-size:.7rem;
    }
    .tareas-alerta__texto { flex:1 1 auto; line-height:1.4; }
    .tareas-alerta__cerrar {
        flex:0 0 auto; border:0; background:transparent; cursor:pointer;
        color:inherit; opacity:.55; padding:.25rem; line-height:1;
        border-radius:6px; transition:opacity .15s ease, background .15s ease;
    }
    .tareas-alerta__cerrar:hover { opacity:1; background:rgba(6,95,70,.08); }

    .dark .tareas-alerta {
        border-color:#065f46;
        background:rgba(6,78,59,.35);
        color:#a7f3d0;
        box-shadow:none;
    }
    .dark .tareas-alerta__icono { background:#059669; color:#ecfdf5; }
    .dark .tareas-alerta__cerrar:hover { background:rgba(167,243,208,.12); }

    /* Variante de error. Cambia color e icono: no depende solo del color. */
    .tareas-alerta--error {
        border-color:#fecaca; background:#fef2f2; color:#991b1b;
        box-shadow:0 1px 2px rgba(239,68,68,.08);
    }
    .tareas-alerta--error .tareas-alerta__icono { background:#ef4444; color:#fff; }
    .tareas-alerta--error .tareas-alerta__cerrar:hover { background:rgba(153,27,27,.08); }
    .dark .tareas-alerta--error {
        border-color:#7f1d1d; background:rgba(127,29,29,.32); color:#fecaca; box-shadow:none;
    }
    .dark .tareas-alerta--error .tareas-alerta__icono { background:#dc2626; color:#fef2f2; }
    .dark .tareas-alerta--error .tareas-alerta__cerrar:hover { background:rgba(254,202,202,.12); }
</style>
@endonce
