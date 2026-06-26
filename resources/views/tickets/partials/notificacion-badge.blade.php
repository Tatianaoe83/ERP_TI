<div class="ticket-notificacion-wrap relative flex-shrink-0 w-6 h-6 mt-0.5 flex items-center justify-center">
    <span class="material-symbols-outlined text-blue-500 leading-none" style="font-size: 24px;">notifications</span>
    @if(($notificaciones ?? 0) > 0)
    <span class="ticket-notificacion-badge absolute top-0 right-0 translate-x-1/2 -translate-y-1/2 bg-red-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center">
        {{ min($notificaciones, 9) }}
    </span>
    @endif
</div>
