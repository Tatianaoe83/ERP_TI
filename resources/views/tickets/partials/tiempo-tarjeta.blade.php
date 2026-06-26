@if(!empty($tiempo))
<div class="rounded-lg px-2.5 py-2 border {{ $tiempo['estilo']['bg'] }} {{ $tiempo['estilo']['border'] }}">
    <div class="flex items-center justify-between gap-2 mb-1.5">
        <span class="text-[10px] font-semibold uppercase tracking-wide {{ $tiempo['estilo']['text'] }} opacity-80">Tiempo</span>
        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $tiempo['estilo']['bg'] }} {{ $tiempo['estilo']['text'] }} ring-1 {{ $tiempo['estilo']['border'] }}">
            {{ $tiempo['porcentaje'] }}%
        </span>
    </div>
    <div class="h-1.5 rounded-full bg-black/5 dark:bg-white/10 overflow-hidden mb-1.5">
        <div class="h-full rounded-full transition-all {{ $tiempo['estilo']['bar'] }}"
            style="width: {{ $tiempo['porcentaje'] }}%"></div>
    </div>
    <p class="text-[11px] font-semibold {{ $tiempo['estilo']['text'] }} flex items-center gap-1">
        <i class="fas fa-stopwatch text-[10px] opacity-80"></i>
        {{ $tiempo['texto'] }}
    </p>
</div>
@endif
