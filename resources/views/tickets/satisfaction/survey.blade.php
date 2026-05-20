<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Califica tu experiencia · Ticket #{{ $calificacion->ticket?->TicketID ?? '' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        .fade-enter {
            animation: fadeIn 0.3s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4 sm:p-6 text-slate-800">

    @php
        $fields = [
            'fastness' => ['label' => 'Rapidez', 'desc' => '¿Qué tan rápido se atendió tu solicitud?'],
            'attention' => ['label' => 'Atención', 'desc' => '¿Cómo fue el trato recibido por parte del equipo?'],
            'resolution' => ['label' => 'Resolución', 'desc' => '¿Se resolvió correctamente tu problema?'],
        ];

        $ticket = $calificacion->ticket;
        $respTI = $ticket?->responsableTI;
        $nFull = $respTI?->NombreEmpleado ?? '';
        $nParts = explode(' ', $nFull);
        $nApel = $nParts[0] ?? '';
        $nNombre = $nParts[2] ?? $nParts[1] ?? '';

        $answered = collect(['fastness', 'attention', 'resolution'])
            ->filter(fn($f) => $calificacion->{$f} !== null)
            ->count();
        $total = 3;
        $allCompleted = $calificacion->isCompleted();
    @endphp

    <div class="w-full max-w-5xl mx-auto bg-white rounded-3xl shadow-xl shadow-slate-200/50 overflow-hidden flex flex-col md:flex-row min-h-[600px]"
        x-data="{
        currentStep: 0,
        totalSteps: 3,
        showCelebration: {{ $allCompleted ? 'true' : 'false' }},
        showCommentStep: false,
        fields: ['fastness', 'attention', 'resolution'],
        answered: {{ json_encode(array_filter(['fastness' => $calificacion->fastness, 'attention' => $calificacion->attention, 'resolution' => $calificacion->resolution], fn($v) => $v !== null)) }},
        isExpired: {{ $isExpired ? 'true' : 'false' }},
        isSubmitting: false,
        commentText: '',
        commentSubmitting: false,
        
        init() {
            if (Object.keys(this.answered).length === this.totalSteps) {
                this.showCelebration = true;
                return;
            }
            for (let i = 0; i < this.fields.length; i++) {
                if (!this.answered[this.fields[i]]) {
                    this.currentStep = i;
                    break;
                }
            }
        },
        
        async rate(field, rating, url) {
            if (this.isExpired || this.answered[field] || this.isSubmitting) return;
            
            this.isSubmitting = true;
            this.answered[field] = rating; // optimistic update
            
            try {
                let response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    if (Object.keys(this.answered).length === this.totalSteps) {
                        setTimeout(() => { this.showCommentStep = true; }, 400);
                    } else {
                        setTimeout(() => { this.nextStep(); }, 400);
                    }
                } else {
                    delete this.answered[field];
                    alert('Hubo un error al guardar tu calificación. Por favor, intenta de nuevo.');
                }
            } catch (err) {
                console.error(err);
                delete this.answered[field];
                alert('Error de conexión.');
            } finally {
                this.isSubmitting = false;
            }
        },
        
        nextStep() {
            if (this.currentStep < this.totalSteps - 1) {
                this.currentStep++;
            } else if (Object.keys(this.answered).length === this.totalSteps) {
                this.showCommentStep = true;
            }
        },
        
        prevStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },

        async submitComment() {
            this.commentSubmitting = true;
            const comment = this.commentText.trim();
            try {
                let response = await fetch('{{ route('tickets.satisfaction.comment', ['survey' => $calificacion->uuid]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ user_comment: comment || null })
                });
                if (response.ok) {
                    this.showCommentStep = false;
                    this.showCelebration = true;
                } else {
                    alert('Error al guardar el comentario. Intenta de nuevo.');
                }
            } catch (err) {
                console.error(err);
                alert('Error de conexión.');
            } finally {
                this.commentSubmitting = false;
            }
        },

        skipComment() {
            this.showCommentStep = false;
            this.showCelebration = true;
        }
     }" x-cloak>

        {{-- Left Side: Info --}}
        <div class="w-full md:w-5/12 p-8 md:p-12 flex flex-col justify-between bg-slate-900 text-white">
            <div>
                <div class="flex flex-col gap-4 mb-10">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-xs font-semibold tracking-wide w-fit">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Ticket #{{ $ticket?->TicketID ?? '—' }}
                    </div>

                    @if($ticket?->responsableTI?->NombreEmpleado)
                        <div class="text-sm text-slate-400">
                            Te atendió: <strong class="text-white">{{ $ticket->responsableTI->NombreEmpleado }}</strong>
                        </div>
                    @endif
                </div>

                <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                    Tu opinión es fundamental para mejorar la calidad de nuestro servicio de soporte TI.
                </p>

                <div class="mt-10">
                    @if ($ticket?->Descripcion)
                        <div>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">
                                Incidencia reportada
                            </h3>
                            <p class="text-sm text-slate-300 leading-relaxed italic border-l-2 border-slate-700 pl-4">
                                "{{ $ticket->Descripcion }}"
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Side: Survey --}}
        <div class="w-full md:w-7/12 p-8 md:p-12 flex flex-col relative bg-white">

            {{-- Progress Indicator --}}
            <div class="mb-12">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Progreso</span>
                    <span class="text-sm font-bold text-slate-700"
                        x-text="`${Object.keys(answered).length} / 3`"></span>
                </div>
                <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden relative">
                    <div class="absolute top-0 left-0 h-full bg-blue-600 transition-all duration-500 ease-out rounded-full"
                        :style="`width: ${(Object.keys(answered).length / 3) * 100}%`">
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <template x-for="(f, idx) in fields">
                        <button @click="currentStep = idx" :class="{
                                'bg-blue-600': answered[f],
                                'bg-slate-200': !answered[f],
                                'ring-2 ring-blue-200 ring-offset-2': currentStep === idx
                            }"
                            class="flex-1 h-1.5 rounded-full transition-all duration-300 cursor-pointer focus:outline-none"></button>
                    </template>
                </div>
            </div>

            @if ($isExpired)
                <div
                    class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl border border-red-100 text-sm font-medium flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Este enlace ha expirado. Ya no es posible registrar ni modificar calificaciones para este
                        ticket.</span>
                </div>
            @endif

            {{-- Survey Steps --}}
            <div x-show="!showCelebration && !showCommentStep" class="flex-1 flex flex-col justify-center relative min-h-[300px]">
                @foreach ($fields as $fieldKey => $fieldInfo)
                    @php
                        $index = array_search($fieldKey, array_keys($fields));
                    @endphp
                    <div x-show="currentStep === {{ $index }}" x-transition:enter="fade-enter"
                        class="absolute inset-0 flex flex-col justify-center bg-white z-10">

                        <div class="text-center mb-10">
                            <h2 class="text-2xl font-bold text-slate-800 mb-3">{{ $fieldInfo['label'] }}</h2>
                            <p class="text-slate-500 font-medium">{{ $fieldInfo['desc'] }}</p>
                        </div>

                        <div class="flex flex-col items-center justify-center">
                            {{-- Stars Container --}}
                            <div class="flex justify-center gap-2 sm:gap-4 mb-6" x-data="{ hoverRating: 0 }">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button"
                                        @click.prevent="rate('{{ $fieldKey }}', {{ $i }}, '{{ $signedUrls[$fieldKey][$i] ?? '#' }}')"
                                        @mouseenter="hoverRating = {{ $i }}" @mouseleave="hoverRating = 0"
                                        :disabled="isSubmitting || isExpired || answered['{{ $fieldKey }}']"
                                        class="focus:outline-none transition-transform duration-200"
                                        :class="{'hover:scale-110': !isExpired && !answered['{{ $fieldKey }}'], 'cursor-not-allowed opacity-50': isExpired || answered['{{ $fieldKey }}']}"
                                        title="{{ $i }} estrella{{ $i > 1 ? 's' : '' }}">
                                        <svg class="w-12 h-12 sm:w-16 sm:h-16 transition-all duration-200"
                                            :class="(hoverRating > 0 ? hoverRating >= {{ $i }} : (answered['{{ $fieldKey }}'] || 0) >= {{ $i }}) ? 'text-yellow-400 drop-shadow-md' : 'text-slate-200'"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </button>
                                @endfor
                            </div>

                            <div
                                class="flex justify-between w-full max-w-[280px] sm:max-w-[360px] text-xs font-semibold uppercase tracking-wider text-slate-400">
                                <span>Deficiente</span>
                                <span>Excelente</span>
                            </div>
                        </div>

                        {{-- Status Message --}}
                        <div class="mt-8 text-center min-h-[24px]">
                            <span x-show="answered['{{ $fieldKey }}']"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-green-600 bg-green-50 px-3 py-1 rounded-full">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Calificado: <span x-text="answered['{{ $fieldKey }}']"></span>/5
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Comment Step --}}
            <div x-show="showCommentStep && !showCelebration" x-transition:enter="fade-enter"
                class="flex-1 flex flex-col justify-center absolute inset-0 bg-white z-20 p-8 md:p-12">

                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-50 text-blue-500 rounded-full mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800 mb-2">Comentario adicional <span class="text-slate-400 font-normal text-lg">(opcional)</span></h2>
                    <p class="text-slate-500 text-sm">¿Tienes algo más que compartir sobre la atención recibida?</p>
                </div>

                <div class="max-w-md mx-auto w-full">
                    <textarea
                        x-model="commentText"
                        :disabled="commentSubmitting"
                        maxlength="2000"
                        rows="4"
                        placeholder="Escribe tu comentario aquí..."
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none text-sm text-slate-700 placeholder-slate-400 transition"
                    ></textarea>
                    <div class="flex justify-end mt-1">
                        <span class="text-xs text-slate-400" x-text="`${commentText.length}/2000`"></span>
                    </div>
                </div>

                <div class="flex gap-3 justify-center mt-6">
                    <button @click="skipComment()" :disabled="commentSubmitting"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors focus:outline-none disabled:opacity-50">
                        Omitir
                    </button>
                    <button @click="submitComment()" :disabled="commentSubmitting"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm focus:outline-none flex items-center gap-2 disabled:opacity-60">
                        <svg x-show="commentSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="commentSubmitting ? 'Enviando...' : 'Enviar y finalizar'"></span>
                    </button>
                </div>
            </div>

            {{-- Celebration / Summary --}}
            <div x-show="showCelebration" x-transition:enter="fade-enter"
                class="flex-1 flex flex-col justify-center text-center absolute inset-0 bg-white z-20 p-8 md:p-12">

                <div
                    class="inline-flex items-center justify-center w-20 h-20 bg-green-100 text-green-600 rounded-full mx-auto mb-6">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-slate-800 mb-4">¡Gracias por tu tiempo!</h2>
                <p class="text-slate-500 mb-8 max-w-md mx-auto leading-relaxed">
                    Hemos registrado tus calificaciones correctamente. Tu opinión nos ayuda a ofrecerte un mejor
                    servicio.
                </p>

                <div class="grid grid-cols-1 gap-3 max-w-sm mx-auto w-full">
                    @foreach ($fields as $fieldKey => $fieldInfo)
                        <div class="flex items-center justify-between p-4 rounded-xl border border-slate-100 bg-slate-50">
                            <span class="font-semibold text-slate-700 text-sm">{{ $fieldInfo['label'] }}</span>
                            <div class="flex items-center gap-1 text-yellow-400">
                                <span class="text-sm font-bold text-slate-700 mr-2"
                                    x-text="(answered['{{ $fieldKey }}'] || 0) + '/5'"></span>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Navigation Controls --}}
            <div x-show="!showCelebration && !showCommentStep"
                class="mt-8 pt-6 border-t border-slate-100 flex justify-between items-center z-10">
                <button @click="prevStep()" :class="currentStep === 0 ? 'invisible' : ''"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors focus:outline-none">
                    Anterior
                </button>
                <button @click="nextStep()" x-show="answered[fields[currentStep]]" x-transition
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm focus:outline-none flex items-center gap-2">
                    Siguiente
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <span x-show="!answered[fields[currentStep]]" class="px-5 py-2.5 text-sm text-slate-400 font-medium">
                    Selecciona una calificación
                </span>
            </div>

        </div>
    </div>

</body>

</html>