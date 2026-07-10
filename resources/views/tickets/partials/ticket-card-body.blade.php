@php
    $prioridad = $ticket['prioridad'] ?? null;
    $columna = $columna ?? '';
    $descripcionTarjeta = $ticket['descripcion_tarjeta']
        ?? \Illuminate\Support\Str::limit($ticket['descripcion'] ?? '', 120);
@endphp

<div class="flex items-start justify-between gap-2 mb-2.5">
    <div class="flex items-center gap-2 min-w-0">
        <span class="inline-flex items-center justify-center min-w-[2rem] h-6 px-1.5 rounded-md bg-gray-100 dark:bg-gray-800 text-[11px] font-bold text-gray-600 dark:text-gray-300">
            #{{ $ticket['id'] }}
        </span>
        @if(!empty($ticket['categoria']))
        <span class="inline-flex items-center gap-1 max-w-[7rem] truncate text-[10px] text-gray-500 dark:text-gray-400" title="{{ $ticket['categoria'] }}">
            <i class="fas fa-tag opacity-70"></i>{{ $ticket['categoria'] }}
        </span>
        @endif
    </div>
    @if(!empty($prioridad))
    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full shrink-0
        @if($prioridad=='Baja') bg-green-100 text-green-700 ring-1 ring-green-200 dark:bg-green-900/30 dark:text-green-300 dark:ring-green-800
        @elseif($prioridad=='Media') bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:ring-yellow-800
        @else bg-red-100 text-red-800 ring-1 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800
        @endif">
        {{ $prioridad }}
    </span>
    @endif
</div>

<div class="flex items-start justify-between gap-2 mb-1">
    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 line-clamp-2 leading-snug flex-1 min-w-0">
        {{ $descripcionTarjeta }}
    </p>
    @if($columna === 'proceso')
        @include('tickets.partials.notificacion-badge', ['notificaciones' => $ticket['notificaciones'] ?? 0])
    @endif
</div>

<div class="pt-2.5 mt-auto border-t border-gray-100 dark:border-gray-700/80 space-y-2">
    <div class="flex items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400">
        <span class="flex items-center gap-1.5 min-w-0" title="{{ $ticket['empleado']['nombre'] ?? '' }}">
            <span class="flex items-center justify-center w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 shrink-0">
                <i class="fas fa-user text-[9px]"></i>
            </span>
            <span class="truncate font-medium text-gray-700 dark:text-gray-300">{{ $ticket['empleado_corto'] ?: 'Sin empleado' }}</span>
        </span>
        <span class="flex items-center gap-1 shrink-0 text-gray-400" title="{{ \Carbon\Carbon::parse($ticket['created_at'])->format('d/m/Y H:i') }}">
            <i class="fas fa-clock text-[9px]"></i>
            {{ \Carbon\Carbon::parse($ticket['created_at'])->diffForHumans(null, true) }}
        </span>
    </div>

    @if(!empty($ticket['tiempo']))
        @include('tickets.partials.tiempo-tarjeta', ['tiempo' => $ticket['tiempo']])
    @endif

    @if($columna === 'proceso' && !empty($ticket['responsable_nombre']))
    <div class="flex items-center gap-1.5 text-[11px] px-2 py-1 rounded-md bg-blue-50/80 dark:bg-blue-900/15 text-blue-700 dark:text-blue-300 border border-blue-100/80 dark:border-blue-800/50">
        <i class="fas fa-user-tie text-[10px] opacity-80"></i>
        <span class="truncate font-medium">{{ $ticket['responsable_nombre'] }}</span>
    </div>
    @endif
</div>
