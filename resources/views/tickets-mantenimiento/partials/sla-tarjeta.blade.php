@if(!empty($sla))
<div class="rounded-md px-2 py-1.5 border {{ $sla['estilo']['bg'] }} {{ $sla['estilo']['border'] }}">
    <div class="flex items-center justify-between gap-2 text-[10px] {{ $sla['estilo']['text'] }}">
        <span class="flex items-center gap-1 min-w-0">
            <i class="fas fa-hourglass-half flex-shrink-0"></i>
            <span class="truncate" title="{{ $sla['texto_transcurrido'] }}">{{ $sla['texto_transcurrido'] }}</span>
        </span>
        <span class="font-bold flex-shrink-0">{{ $sla['max_dias'] }}d máx</span>
    </div>
    <div class="flex items-center gap-1 mt-1 text-[10px] font-semibold {{ $sla['estilo']['text'] }}">
        <i class="fas fa-stopwatch flex-shrink-0"></i>
        <span>{{ $sla['texto_restante'] }}</span>
    </div>
    <div class="mt-1.5 h-1 rounded-full bg-black/5 dark:bg-white/10 overflow-hidden">
        <div class="h-full rounded-full {{ $sla['estilo']['bar'] }}"
            style="width: {{ min(100, $sla['porcentaje_uso']) }}%"></div>
    </div>
</div>
@endif
