@if(!empty($sla))
<div class="rounded-lg px-2.5 py-2 border {{ $sla['estilo']['bg'] }} {{ $sla['estilo']['border'] }}">
    <div class="flex items-center justify-between gap-2 mb-1.5">
        <span class="text-[10px] font-semibold uppercase tracking-wide {{ $sla['estilo']['text'] }} opacity-80">SLA</span>
        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $sla['estilo']['bg'] }} {{ $sla['estilo']['text'] }} ring-1 {{ $sla['estilo']['border'] }}">
            {{ min(100, $sla['porcentaje_uso']) }}%
        </span>
    </div>
    <div class="h-1.5 rounded-full bg-black/5 dark:bg-white/10 overflow-hidden mb-1.5">
        <div class="h-full rounded-full transition-all {{ $sla['estilo']['bar'] }}"
            style="width: {{ min(100, $sla['porcentaje_uso']) }}%"></div>
    </div>
    <div class="flex items-center justify-between gap-2 text-[10px] {{ $sla['estilo']['text'] }}">
        <span class="truncate" title="{{ $sla['texto_transcurrido'] }}">{{ $sla['texto_transcurrido'] }}</span>
        <span class="font-bold shrink-0">{{ $sla['max_dias'] }}d máx</span>
    </div>
    <p class="mt-1 text-[11px] font-semibold {{ $sla['estilo']['text'] }} flex items-center gap-1">
        <i class="fas fa-stopwatch text-[10px] opacity-80"></i>
        {{ $sla['texto_restante'] }}
    </p>
</div>
@endif
