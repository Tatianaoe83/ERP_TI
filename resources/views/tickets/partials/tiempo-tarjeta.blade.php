@if(!empty($tiempo))
<div class="kpi-chip kpi-chip--{{ $tiempo['estado'] ?? 'normal' }}">
    <div class="flex items-center justify-between gap-2 mb-1.5">
        <span class="text-[10px] font-semibold uppercase tracking-wide opacity-80">Tiempo</span>
        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded ring-1 ring-current/30">
            {{ $tiempo['porcentaje'] }}%
        </span>
    </div>
    <div class="h-1.5 rounded-full bg-black/10 dark:bg-white/15 overflow-hidden mb-1.5">
        <div class="h-full rounded-full transition-all {{ $tiempo['estilo']['bar'] ?? 'bg-current' }}"
            style="width: {{ $tiempo['porcentaje'] }}%"></div>
    </div>
    <p class="text-[11px] font-semibold flex items-center gap-1">
        <i class="fas fa-stopwatch text-[10px] opacity-80"></i>
        {{ $tiempo['texto'] }}
    </p>
</div>
@endif
