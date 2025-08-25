@extends('layouts.app')
@section('content')

<body class="min-h-screen py-8 px-4">
    <div class="flex justify-start mb-6">
        <div class="relative grid grid-cols-3 gap-10 items-center rounded-xl bg-gray-200 border rounded-full" role="tablist" aria-label="tabs">
            <div class="absolute indicator top-0 bottom-0 left-0 rounded-xl bg-[#101D49] transition-all duration-300"></div>

            <button role="tab" aria-selected="true" aria-controls="panel-1" id="tab-1" tabindex="0"
                class="tab relative block rounded-xl px-4 py-2 text-white">
                <span class="">Tickets</span>
            </button>
            <button role="tab" aria-selected="false" aria-controls="panel-2" id="tab-2" tabindex="1"
                class="tab relative block rounded-xl px-4 py-2 text-black">
                <span class="">Solicitudes</span>
            </button>
            <button role="tab" aria-selected="false" aria-controls="panel-3" id="tab-3" tabindex="2"
                class="tab relative block rounded-xl px-4 py-2 text-black">
                <span class="">Productividad</span>
            </button>
        </div>
    </div>

    <div class="mt-6">
        <div id="panel-1" class="tab-panel transition-all duration-500 opacity-100 translate-x-0">
            <div class="grid grid-cols-3 gap-4 max-h-[36rem] overflow-y-auto">

                <div class="rounded-xl border flex flex-col">
                    <h2 class="text-2xl font-bold bg-[#101D49] text-white rounded-md p-3 border-b">Abierto</h2>
                    <div class="rounded-xl flex flex-col">
                        @foreach($tickets->get('Pendiente', collect()) as $ticket)
                        <div class="bg-white p-3 rounded-xl border mt-3 mx-4 cursor-pointer hover:scale-105 transition duration-300">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold text-red-600 bg-red-200 px-2 py-1 rounded-md">
                                    {{ $ticket->Prioridad }}
                                </span>
                                <span class="text-black text-sm font-medium">#{{ $ticket->TicketID }}</span>
                            </div>
                            <h3 class="font-semibold text-gray-900 text-sm mb-2">
                                {{ Str::limit($ticket->Descripcion, 40) }}
                            </h3>
                            <div class="flex justify-between items-center text-xs text-black">
                                <span class="text-sm font-medium">
                                    {{ $ticket->EmpleadoID }}
                                </span>
                                <span class="text-xs">
                                    {{ $ticket->created_at->format('h:i a') }} - {{ $ticket->created_at->format('d/m/Y') }}
                                </span>
                                <span class="text-base">
                                    <i class="fas fa-paperclip"></i>
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border flex flex-col">
                    <h2 class="text-2xl font-bold bg-[#101D49] text-white rounded-md p-3 border-b">En Proceso</h2>
                    <div class="rounded-xl flex flex-col">
                        @foreach($tickets->get('En progreso', collect()) as $ticket)
                        {{-- mismo contenido de arriba --}}
                        <div class="bg-white p-3 rounded-xl border mt-3 mx-4 cursor-pointer hover:scale-105 transition duration-300">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold text-red-600 bg-red-200 px-2 py-1 rounded-md">
                                    {{ $ticket->Prioridad }}
                                </span>
                                <span class="text-black text-sm font-medium">#{{ $ticket->TicketID }}</span>
                            </div>
                            <h3 class="font-semibold text-gray-900 text-sm mb-2">
                                {{ Str::limit($ticket->Descripcion, 40) }}
                            </h3>
                            <div class="flex justify-between items-center text-xs text-black">
                                <span class="text-sm font-medium">
                                    {{ $ticket->EmpleadoID }}
                                </span>
                                <span class="text-xs">
                                    {{ $ticket->created_at->format('h:i a') }} - {{ $ticket->created_at->format('d/m/Y') }}
                                </span>
                                <span class="text-base">
                                    <i class="fas fa-paperclip"></i>
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border flex flex-col">
                    <h2 class="text-2xl font-bold bg-[#101D49] text-white rounded-md p-3 border-b">Cerrado</h2>
                    <div class="rounded-xl flex flex-col">
                        @foreach($tickets->get('Cerrado', collect()) as $ticket)
                        {{-- mismo contenido --}}
                        <div class="bg-white p-3 rounded-xl border mt-3 mx-4 cursor-pointer hover:scale-105 transition duration-300">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold text-red-600 bg-red-200 px-2 py-1 rounded-md">
                                    {{ $ticket->Prioridad }}
                                </span>
                                <span class="text-black text-sm font-medium">#{{ $ticket->TicketID }}</span>
                            </div>
                            <h3 class="font-semibold text-gray-900 text-sm mb-2">
                                {{ Str::limit($ticket->Descripcion, 40) }}
                            </h3>
                            <div class="flex justify-between items-center text-xs text-black">
                                <span class="text-sm font-medium">
                                    {{ $ticket->EmpleadoID }}
                                </span>
                                <span class="text-xs">
                                    {{ $ticket->created_at->format('h:i a') }} - {{ $ticket->created_at->format('d/m/Y') }}
                                </span>
                                <span class="text-base">
                                    <i class="fas fa-paperclip"></i>
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div id="panel-2" class="tab-panel absolute transition-all duration-500 opacity-0 -translate-x-10 pointer-events-none">Contenido del segundo tab</div>
        <div id="panel-3" class="tab-panel absolute transition-all duration-500 opacity-0 -translate-x-10 pointer-events-none">Contenido del segundo tab</div>
    </div>


</body>
<script>
    let tabs = document.querySelectorAll(".tab");
    let indicator = document.querySelector(".indicator");
    let panels = document.querySelectorAll(".tab-panel");

    const setIndicator = (tab) => {
        indicator.style.width = tab.getBoundingClientRect().width + 'px';
        indicator.style.left = (tab.getBoundingClientRect().left - tab.parentElement.getBoundingClientRect().left) + 'px';
    };

    setIndicator(tabs[0]);

    tabs.forEach((tab, index) => {
        tab.addEventListener("click", () => {
            setIndicator(tab);

            tabs.forEach((t, i) => {
                if (i === index) {
                    t.classList.remove("text-black");
                    t.classList.add("text-white");
                } else {
                    t.classList.remove("text-white");
                    t.classList.add("text-black");
                }
            });

            panels.forEach((panel, i) => {
                if (i === index) {
                    panel.classList.remove("opacity-0", "translate-x-10", "pointer-events-none");
                    panel.classList.add("opacity-100", "translate-x-0");
                } else {
                    panel.classList.remove("opacity-100", "translate-x-0");
                    panel.classList.add("opacity-0", "translate-x-10", "pointer-events-none");
                }
            });
        });
    });
</script>

</html>
@endsection