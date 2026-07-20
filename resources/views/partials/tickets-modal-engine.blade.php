@php
    // Default para vistas que no pasan $ticketsStatus (el modal global del layout).
    // En /tickets llega con datos reales; en otras vistas, contadores en cero.
    // Mantenimiento usa otro esquema (pendiente, en_proceso, …): no reutilizar esa variable.
    $defaultTicketsStatus = ['nuevos' => [], 'proceso' => [], 'resueltos' => []];
    $esTicketsSoporte = isset($ticketsStatus)
        && is_array($ticketsStatus)
        && array_key_exists('nuevos', $ticketsStatus)
        && array_key_exists('proceso', $ticketsStatus)
        && array_key_exists('resueltos', $ticketsStatus);
    $ticketsStatus = $esTicketsSoporte
        ? $ticketsStatus
        : $defaultTicketsStatus;
@endphp
<script>
    function ticketsModal(soloPanel = false) {
        return {
            // soloPanel = true cuando es la instancia global del layout (sin tablero):
            // evita los fetch/intervalos del tablero, solo sirve el panel de detalle.
            soloPanel: soloPanel,
            vista: 'kanban',
            mostrar: false,
            selected: {},
            mensajes: [],
            nuevoMensaje: '',
            cargando: false,
            sincronizando: false,
            buscandoCorreos: false,
            guardandoCorreos: false,
            estadisticas: null,
            respuestaManual: {
                nombre: '',
                correo: '',
                mensaje: ''
            },
            mostrarProcesarRespuesta: false,
            // Variables para el editor de correo
            mostrarCc: false,
            mostrarBcc: false,
            prioridadCorreo: 'normal',
            asuntoCorreo: '',
            correoCc: '',
            correoBcc: '',
            tinyMCEInstance: null, // Instancia del editor TinyMCE
            archivosAdjuntos: [], // Array para almacenar los archivos seleccionados
            // URL base para archivos de storage (asset evita problemas con public/storage)
            storageBaseUrl: '{{ asset("storage") }}',
            // Variables para detalles del ticket
            ticketPrioridad: '',
            ticketEstatus: '',
            ticketClasificacion: '',
            ticketResponsableTI: '',
            ticketTipoID: '',
            ticketSubtipoID: '',
            ticketTertipoID: '',
            guardandoTicket: false,
            // Variables de paginación
            paginaLista: {
                'nuevos': 1,
                'proceso': 1,
                'resueltos': 1
            },
            paginaTabla: 1,
            elementosPorPagina: 5,
            // Variables de ordenamiento
            ordenColumna: 'fecha',
            ordenDireccion: 'desc',
            ticketsLista: {
                'nuevos': {{ count($ticketsStatus['nuevos']) }},
                'proceso': {{ count($ticketsStatus['proceso']) }},
                'resueltos': {{ count($ticketsStatus['resueltos']) }}
            },
            ultimaActualizacionTickets: {
                'nuevos': {{ count($ticketsStatus['nuevos']) }},
                'proceso': {{ count($ticketsStatus['proceso']) }},
                'resueltos': {{ count($ticketsStatus['resueltos']) }}
            },
            ticketsTabla: [],
            // Variable para rastrear tickets que se están moviendo manualmente
            ticketsMoviendose: new Set(),
            // Variables para métricas
            mostrarModalMetricas: false,
            metricasTipos: [],
            cargandoMetricas: false,
            guardandoMetricas: false,
            // Variables para tickets excedidos
            mostrarPopupExcedidos: false,
            ticketsExcedidos: [],
            timerPopupExcedidos: null,
            intervaloContadorPopup: null,
            intervaloExcedidos5min: null,
            tiempoRestantePopup: 10,
            cargandoExcedidos: false,
            intervaloVerificacionExcedidos: null,
            // Variables para verificación automática de mensajes nuevos
            intervaloVerificacionMensajes: null,
            ultimoMensajeId: 0,

            init() {
                // Los datos de ticketsLista ya están inicializados desde el servidor
                // Preparar datos de tabla solo cuando la vista inicial realmente lo necesita.
                if (this.vista === 'tabla' && typeof this.prepararDatosTabla === 'function') {
                    this.prepararDatosTabla();
                }
             
                this.mostrar = false;
                this.selected = {};
                this.mensajes = [];
                this.nuevoMensaje = '';
                this.asuntoCorreo = '';
                
                // Escuchar eventos de Livewire para actualizaciones automáticas
                // Siempre actualizar el DOM de la vista que emitió el evento (pasamos 'kanban'/'lista'/'tabla')
                // para que el movimiento de tarjetas ocurra aunque this.vista haya cambiado antes del rAF
                Livewire.on('tickets-actualizados-kanban', (datos) => {
                    this.procesarActualizacionTickets(datos, 'kanban');
                });
                
                Livewire.on('tickets-actualizados-lista', (datos) => {
                    this.procesarActualizacionTickets(datos, 'lista');
                });
                
                Livewire.on('tickets-actualizados-tabla', (datos) => {
                    this.procesarActualizacionTickets(datos, 'tabla');
                });
                
                // Función para procesar las actualizaciones. vistaOrigen: 'kanban'|'lista'|'tabla' indica
                // qué DOM actualizar (mover tarjetas), para que no falle si el usuario cambió de vista antes del rAF
                this.procesarActualizacionTickets = (datos, vistaOrigen) => {
                    
                    if (datos && datos.ticketsExcedidos) {
                        this.ticketsExcedidos = datos.ticketsExcedidos;
                        // No abrir el popup aquí: Livewire actualiza cada 3s/30s.
                        // El popup solo se abre cada 5 min vía verificarTicketsExcedidos().
                    }
                    if (datos && datos.tiemposProgreso) {
                        // Actualizar indicadores de tiempo usando la función auxiliar
                        this.actualizarIndicadoresTiempoEnDOM(datos.tiemposProgreso);
                    }
                    // Verificar si hay cambios en los tickets usando el hash
                    if (datos && datos.ticketsStatus && datos.hash) {
                        // Determinar la propiedad de hash correspondiente
                        const hashKey = vistaOrigen === 'kanban' ? 'ultimoHashKanban' :
                                        (vistaOrigen === 'lista' ? 'ultimoHashLista' : 'ultimoHashTabla');

                        const hashCambio = !this[hashKey] || this[hashKey] !== datos.hash;
                        
                        // Aplicar a la vista cuando: primera vez (sync inicial con el wire) o cuando el hash cambió
                        if (hashCambio) {
                            this[hashKey] = datos.hash;
                            
                            // Actualizar solo estado externo. Livewire repinta las columnas; no mutar su DOM manualmente.
                            const nuevosCount = datos.ticketsStatus.nuevos ? datos.ticketsStatus.nuevos.length : 0;
                            const procesoCount = datos.ticketsStatus.proceso ? datos.ticketsStatus.proceso.length : 0;
                            const resueltosCount = datos.ticketsStatus.resueltos ? datos.ticketsStatus.resueltos.length : 0;
                            
                            this.ticketsLista = {
                                nuevos: nuevosCount,
                                proceso: procesoCount,
                                resueltos: resueltosCount
                            };
                            
                            this.actualizarContadoresTickets(nuevosCount, procesoCount, resueltosCount);
                            
                        }
                    }
                };
                
                // Función para actualizar los contadores de tickets en los headers
                this.actualizarContadoresTickets = (nuevos, proceso, resueltos) => {
                    const contadores = {
                        'nuevos': nuevos,
                        'proceso': proceso,
                        'resueltos': resueltos
                    };
                    
                    Object.keys(contadores).forEach(categoria => {
                        // Buscar todos los contadores de esta categoría (puede haber múltiples en diferentes vistas)
                        const elementosContador = document.querySelectorAll(`[data-categoria-header="${categoria}"]`);
                        elementosContador.forEach(contador => {
                            const valorAnterior = parseInt(contador.textContent) || 0;
                            const valorNuevo = contadores[categoria];
                            
                            if (valorAnterior !== valorNuevo) {
                                // Agregar animación cuando cambia el valor
                                contador.style.transition = 'all 0.3s ease';
                                contador.style.transform = 'scale(1.2)';
                                contador.textContent = valorNuevo;
                                
                                setTimeout(() => {
                                    contador.style.transform = 'scale(1)';
                                }, 300);
                                
                            }
                        });
                    });
                };
                
                // Función para actualizar los tickets en el DOM usando los datos de Livewire.
                // vistaParaUpdate: 'kanban'|'lista'|'tabla' — qué DOM tocar (la que emitió el evento).
                this.actualizarTicketsEnDOM = (ticketsStatus, vistaParaUpdate) => {
                    if (!ticketsStatus) return;
                    const vista = (vistaParaUpdate !== undefined && vistaParaUpdate !== null) ? vistaParaUpdate : this.vista;
                    
                    let elementosActualizados = 0;
                    let cartasMovidas = 0;
                    
                    // Mapeo de estados a categorías
                    const estadoACategoria = {
                        'Pendiente': 'nuevos',
                        'En progreso': 'proceso',
                        'Cerrado': 'resueltos'
                    };
                    
                    // Primero, identificar qué tickets han cambiado de estado
                    const ticketsPorId = {};
                    ['nuevos', 'proceso', 'resueltos'].forEach(categoria => {
                        const tickets = ticketsStatus[categoria] || [];
                        tickets.forEach(ticket => {
                            ticketsPorId[ticket.id] = {
                                ...ticket,
                                categoriaActual: categoria,
                                estado: categoria === 'nuevos' ? 'Pendiente' : (categoria === 'proceso' ? 'En progreso' : 'Cerrado')
                            };
                        });
                    });
                    
                    // Raíz de la vista que debemos actualizar (kanban o lista; tabla no tiene columnas para mover)
                    const raizVista = vista === 'kanban'
                        ? document.querySelector('.kanban-root')
                        : (vista === 'lista' ? document.querySelector('[x-show*="lista"]') : null);
                    
                    // Procesar cada ticket existente y nuevos (id como string para el selector)
                    Object.keys(ticketsPorId).forEach(ticketId => {
                        const ticket = ticketsPorId[ticketId];
                        const idStr = String(ticket.id);
                        
                        // Si este ticket se está moviendo manualmente, saltarlo para evitar conflictos
                        if (this.ticketsMoviendose && this.ticketsMoviendose.has(idStr)) {
                            return;
                        }
                        
                        let elementos = document.querySelectorAll(`[data-ticket-id="${idStr}"]`);
                        // Siempre filtrar por la vista actual cuando hay raíz (evita mover el elemento de la otra vista)
                        if (raizVista && elementos.length > 0) {
                            elementos = Array.from(elementos).filter(el => raizVista.contains(el));
                        }
                        
                        if (elementos.length === 0) {
                            // Ticket nuevo que no existe en el DOM: no recargar sección para no romper el poll.
                            // Los contadores ya se actualizan más arriba; las cards nuevas aparecerán al refrescar.
                            return;
                        }
                        
                        elementos.forEach(elemento => {
                            // Validar que el elemento existe y está en el DOM
                            if (!elemento || !elemento.parentElement) {
                                console.warn(`Elemento del ticket #${ticket.id} no está en el DOM`);
                                return;
                            }
                            
                            const categoriaAnterior = elemento.getAttribute('data-categoria');
                            const categoriaNueva = ticket.categoriaActual;
                            
                            // Si el ticket cambió de categoría, moverlo visualmente
                            if (categoriaAnterior && categoriaAnterior !== categoriaNueva) {
                                
                                // Encontrar el contenedor de la nueva categoría según la vista que estamos actualizando
                                const contenedorNuevo = this.encontrarContenedorCategoria(categoriaNueva, vista);
                                const contenedorAnterior = elemento.closest('.flex-1.overflow-y-auto') || 
                                                          elemento.closest('.divide-y') ||
                                                          (elemento.parentElement ? elemento.parentElement : null);
                                
                                // Validar que ambos contenedores existen
                                if (!contenedorAnterior) {
                                    // Solo actualizar los datos sin mover
                                    elemento.setAttribute('data-categoria', categoriaNueva);
                                    this.actualizarAtributosTicket(elemento, ticket);
                                    this.actualizarContenidoTicket(elemento, ticket);
                                    elementosActualizados++;
                                    return;
                                }
                                
                                if (contenedorNuevo && contenedorAnterior && contenedorAnterior !== contenedorNuevo) {
                                    // Marcar que este ticket se está moviendo manualmente
                                    this.ticketsMoviendose.add(String(ticket.id));
                                    
                                    // Actualizar atributos y contenido antes de mover
                                    elemento.setAttribute('data-categoria', categoriaNueva);
                                    
                                    // Actualizar wire:key con el nuevo estatus para que Livewire lo rastree correctamente
                                    const nuevoEstatus = categoriaNueva === 'nuevos' ? 'Pendiente' : 
                                                         (categoriaNueva === 'proceso' ? 'En progreso' : 'Cerrado');
                                    const isLista = elemento.closest('[x-show*="lista"]') || elemento.closest('.divide-y');
                                    const prefix = isLista ? 'ticket-lista-' : 'ticket-';
                                    elemento.setAttribute('wire:key', `${prefix}${ticket.id}-${nuevoEstatus}`);
                                    
                                    this.actualizarAtributosTicket(elemento, ticket);
                                    this.actualizarContenidoTicket(elemento, ticket);
                                    
                                    // Si el ticket está en "proceso", asegurarse de que tenga responsable y tiempo
                                    // PERO solo si realmente necesita actualizarse (no remover timer existente)
                                    if (categoriaNueva === 'proceso') {
                                        // Verificar si ya tiene responsable y tiempo antes de actualizar
                                        const footer = elemento.querySelector('.pt-3.border-t') || 
                                                      elemento.querySelector('[class*="border-t"]') ||
                                                      elemento.querySelector('.flex.flex-col.gap-2');
                                        
                                        if (footer) {
                                            const tieneResponsable = footer.querySelector('.bg-blue-50, .bg-blue-900\\/20, [class*="bg-blue"]');
                                            const tieneTiempo = footer.querySelector('div.mt-2.w-full');
                                            
                                            // Solo actualizar si falta el responsable o el tiempo
                                            if (!tieneResponsable || !tieneTiempo) {
                                                setTimeout(() => {
                                                    this.actualizarContenidoTicketProceso(elemento, ticket);
                                                }, 100);
                                            }
                                        } else {
                                            // Si no hay footer, intentar actualizar de todas formas
                                            setTimeout(() => {
                                                this.actualizarContenidoTicketProceso(elemento, ticket);
                                            }, 100);
                                        }
                                    }
                                    
                                    // Re-obtener contenedor por si el DOM cambió (p. ej. por otro tick)
                                    const dest = this.encontrarContenedorCategoria(categoriaNueva, vista) || contenedorNuevo;
                                    if (!dest || !dest.isConnected) {
                                        console.warn(`Contenedor destino no válido para ticket #${ticket.id}`);
                                        this.ticketsMoviendose.delete(String(ticket.id));
                                        return;
                                    }
                                    
                                    // Mover de forma inmediata para que se vea el cambio (evitar condiciones de carrera)
                                    if (!elemento.parentElement) {
                                        this.ticketsMoviendose.delete(String(ticket.id));
                                        return;
                                    }
                                    
                                    // Remover placeholder si existe en el destino
                                    const placeholder = dest.querySelector('[data-empty-placeholder]');
                                    if (placeholder) placeholder.remove();
                                    
                                    // Remover el elemento del contenedor anterior
                              elemento.remove();
                                    
                                    // Agregar al nuevo contenedor
                              dest.appendChild(elemento);
                              
                                    // Pequeña animación de entrada
                                    elemento.style.transition = 'all 0.25s ease';
                                    elemento.style.opacity = '0.85';
                                    requestAnimationFrame(() => {
                                        if (elemento && elemento.parentElement) {
                                            elemento.style.opacity = '1';
                                        }
                                        // Remover de la lista de tickets moviéndose después de la animación
                                        setTimeout(() => {
                                            this.ticketsMoviendose.delete(String(ticket.id));
                                        }, 500);
                                    });
                                    
                                    cartasMovidas++;
                                } else {
                                    // Si no se encontró el contenedor, solo actualizar los datos
                                    elemento.setAttribute('data-categoria', categoriaNueva);
                                    this.actualizarAtributosTicket(elemento, ticket);
                                    this.actualizarContenidoTicket(elemento, ticket);
                                    
                                    // Si el ticket está en "proceso", asegurarse de que tenga responsable y tiempo
                                    // PERO solo si realmente necesita actualizarse (no remover timer existente)
                                    if (categoriaNueva === 'proceso') {
                                        // Verificar si ya tiene responsable y tiempo antes de actualizar
                                        const footer = elemento.querySelector('.pt-3.border-t') || 
                                                      elemento.querySelector('[class*="border-t"]') ||
                                                      elemento.querySelector('.flex.flex-col.gap-2');
                                        
                                        if (footer) {
                                            const tieneResponsable = footer.querySelector('.bg-blue-50, .bg-blue-900\\/20, [class*="bg-blue"]');
                                            const tieneTiempo = footer.querySelector('div.mt-2.w-full');
                                            
                                            // Solo actualizar si falta el responsable o el tiempo
                                            if (!tieneResponsable || !tieneTiempo) {
                                                setTimeout(() => {
                                                    this.actualizarContenidoTicketProceso(elemento, ticket);
                                                }, 100);
                                            }
                                        } else {
                                            // Si no hay footer, intentar actualizar de todas formas
                                            setTimeout(() => {
                                                this.actualizarContenidoTicketProceso(elemento, ticket);
                                            }, 100);
                                        }
                                    }
                                    
                                    elementosActualizados++;
                                }
                            } else {
                                // Si no cambió de categoría, solo actualizar los datos
                                // Validar que el elemento todavía existe antes de actualizar
                                if (elemento && elemento.parentElement) {
                                    this.actualizarAtributosTicket(elemento, ticket);
                                    this.actualizarContenidoTicket(elemento, ticket);
                                    
                                    // Si el ticket está en "proceso", asegurarse de que tenga responsable y tiempo
                                    // PERO solo si realmente necesita actualizarse (no remover timer existente)
                                    const categoriaActual = elemento.getAttribute('data-categoria');
                                    if (categoriaActual === 'proceso') {
                                        // Verificar si ya tiene responsable y tiempo antes de actualizar
                                        const footer = elemento.querySelector('.pt-3.border-t') || 
                                                      elemento.querySelector('[class*="border-t"]') ||
                                                      elemento.querySelector('.flex.flex-col.gap-2');
                                        
                                        if (footer) {
                                            const tieneResponsable = footer.querySelector('.bg-blue-50, .bg-blue-900\\/20, [class*="bg-blue"]');
                                            const tieneTiempo = footer.querySelector('div.mt-2.w-full');
                                            
                                            // Solo actualizar si falta el responsable o el tiempo
                                            if (!tieneResponsable || !tieneTiempo) {
                                                setTimeout(() => {
                                                    this.actualizarContenidoTicketProceso(elemento, ticket);
                                                }, 50);
                                            }
                                        } else {
                                            // Si no hay footer, intentar actualizar de todas formas
                                            setTimeout(() => {
                                                this.actualizarContenidoTicketProceso(elemento, ticket);
                                            }, 50);
                                        }
                                    }
                                    
                                    elementosActualizados++;
                                }
                            }
                            
                            // Si el ticket está seleccionado actualmente, actualizar también el modal
                            if (this.selected && this.selected.id == ticket.id) {
                                if (ticket.code_anydesk !== undefined) {
                                    this.selected.anydesk = ticket.code_anydesk || '';
                                }
                                if (ticket.descripcion !== undefined) {
                                    this.selected.descripcion = ticket.descripcion || '';
                                }
                            }
                        });
                    });
                    
                    // Eliminar tickets que ya no existen (solo en la vista actual para no tocar kanban/lista)
                    const todosEnVista = raizVista
                        ? Array.from(raizVista.querySelectorAll('[data-ticket-id]'))
                        : Array.from(document.querySelectorAll('[data-ticket-id]'));
                    todosEnVista.forEach(elemento => {
                        const ticketId = elemento.getAttribute('data-ticket-id');
                        if (!ticketsPorId[ticketId]) {
                            elemento.style.transition = 'all 0.3s ease';
                            elemento.style.opacity = '0';
                            elemento.style.transform = 'scale(0.8)';
                            setTimeout(() => {
                                if (elemento.parentElement) elemento.remove();
                            }, 300);
                        }
                    });
                    
                };
                
                // Función para recargar solo una sección de tickets cuando hay tickets nuevos
                this.recargarSeccionTickets = async (categoria) => {
                    // Hacer una petición AJAX para obtener solo el HTML de los tickets
                    try {
                        const response = await fetch(window.location.href.split('?')[0] + '?partial=1&categoria=' + categoria + '&t=' + Date.now(), {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html'
                            }
                        });
                        
                        if (response.ok) {
                            const html = await response.text();
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            
                            // Buscar la sección correspondiente en el nuevo HTML
                            let seccionNueva = null;
                            let seccionActual = null;
                            
                            if (this.vista === 'kanban') {
                                const indices = { 'nuevos': 0, 'proceso': 1, 'resueltos': 2 };
                                const columnasNuevas = doc.querySelectorAll('.kanban-root .grid > div');
                                const columnasActuales = document.querySelectorAll('.kanban-root .grid > div');
                                
                                if (columnasNuevas[indices[categoria]] && columnasActuales[indices[categoria]]) {
                                    seccionNueva = columnasNuevas[indices[categoria]].querySelector('.flex-1.overflow-y-auto');
                                    seccionActual = columnasActuales[indices[categoria]].querySelector('.flex-1.overflow-y-auto');
                                }
                            }
                            
                            if (seccionNueva && seccionActual) {
                                // Actualizar solo el contenido de la sección
                                seccionActual.innerHTML = seccionNueva.innerHTML;
                            } else {
                                // Si no se puede actualizar parcialmente, recargar solo la página
                                window.location.reload();
                            }
                        } else {
                            // Si falla, recargar la página completa
                            window.location.reload();
                        }
                    } catch (error) {
                        // Si falla, recargar la página completa
                        window.location.reload();
                    }
                };


                
                // Función auxiliar para encontrar el contenedor de una categoría.
                // vistaOpcional: 'kanban'|'lista' — si se pasa, se usa en lugar de this.vista (para el evento diferido).
                this.encontrarContenedorCategoria = (categoria, vistaOpcional) => {
                    const v = (vistaOpcional !== undefined && vistaOpcional !== null) ? vistaOpcional : this.vista;
                    if (v === 'kanban') {
                        const contenedor = document.querySelector('.kanban-root [data-categoria-contenedor="' + categoria + '"]');
                        if (contenedor) return contenedor;
                        const grid = document.querySelector('.kanban-root .grid');
                        if (grid) {
                            const columnas = Array.from(grid.children);
                            const indices = { 'nuevos': 0, 'proceso': 1, 'resueltos': 2 };
                            const columna = columnas[indices[categoria]];
                            if (columna) return columna.querySelector('.flex-1.overflow-y-auto');
                        }
                    } else if (v === 'lista') {
                        // Buscar el contenedor de la vista lista
                        const contenedorLista = document.querySelector('[x-show="vista === \'lista\'"]') || 
                                                document.querySelector('[x-show*="lista"]');
                        if (contenedorLista) {
                            // Buscar todas las secciones que tienen la clase divide-y (son los contenedores de tickets)
                            const todasLasSecciones = Array.from(contenedorLista.querySelectorAll('.rounded-lg.overflow-hidden.shadow-sm'));
                            
                            // Si no encuentra con esa clase, intentar con otra estructura
                            if (todasLasSecciones.length === 0) {
                                const seccionesAlternativas = Array.from(contenedorLista.children).filter(el => 
                                    el.querySelector && el.querySelector('.divide-y')
                                );
                                if (seccionesAlternativas.length > 0) {
                                    const indices = { 'nuevos': 0, 'proceso': 1, 'resueltos': 2 };
                                    const seccion = seccionesAlternativas[indices[categoria]];
                                    return seccion ? seccion.querySelector('.divide-y') : null;
                                }
                            }
                            
                            // Mapeo de categorías a índices de sección (orden: nuevos, proceso, resueltos)
                            const indices = { 'nuevos': 0, 'proceso': 1, 'resueltos': 2 };
                            const indiceNueva = indices[categoria];
                            const seccion = todasLasSecciones[indiceNueva];
                            
                            if (seccion) {
                                // Buscar el contenedor divide-y dentro de la sección
                                return seccion.querySelector('.divide-y.divide-gray-200') || 
                                       seccion.querySelector('.divide-y') ||
                                       null;
                            }
                        }
                    }
                    return null;
                };
                
                // Función auxiliar para actualizar atributos data-* de un ticket
                this.actualizarAtributosTicket = (elemento, ticket) => {
                    if (ticket.code_anydesk !== undefined) {
                        elemento.setAttribute('data-ticket-anydesk', ticket.code_anydesk || '');
                    }
                    if (ticket.descripcion !== undefined) {
                        elemento.setAttribute('data-ticket-descripcion', ticket.descripcion || '');
                    }
                    if (ticket.prioridad !== undefined) {
                        elemento.setAttribute('data-ticket-prioridad', ticket.prioridad || '');
                    }
                    if (ticket.numero !== undefined) {
                        elemento.setAttribute('data-ticket-numero', ticket.numero || '');
                    }
                    if (ticket.empleado && ticket.empleado.nombre) {
                        elemento.setAttribute('data-ticket-empleado', ticket.empleado.nombre);
                    }
                    if (ticket.empleado && ticket.empleado.correo) {
                        elemento.setAttribute('data-ticket-correo', ticket.empleado.correo || '');
                    }
                    if (ticket.puesto) {
                        elemento.setAttribute('data-ticket-puesto', ticket.puesto);
                    }
                    if (ticket.gerencia) {
                        elemento.setAttribute('data-ticket-gerencia', ticket.gerencia);
                    }
                    if (ticket.departamento) {
                        elemento.setAttribute('data-ticket-departamento', ticket.departamento);
                    }
                    if (ticket.responsable && ticket.responsable.nombre) {
                        elemento.setAttribute('data-ticket-responsable', ticket.responsable.nombre);
                    } else if (ticket.responsable === null || !ticket.responsable) {
                        // Si no hay responsable, remover el atributo
                        elemento.removeAttribute('data-ticket-responsable');
                    }
                    // Sincronizar wire:key con el estatus real (evita que quede "En progreso" cuando ya es "Cerrado")
                    if (ticket.id !== undefined && (ticket.estatus !== undefined || ticket.estado !== undefined)) {
                        const estatusKey = (ticket.estatus || ticket.estado || '').toString();
                        const isLista = elemento.closest('[x-show*="lista"]') || elemento.closest('.divide-y');
                        const prefix = isLista ? 'ticket-lista-' : 'ticket-';
                        elemento.setAttribute('wire:key', prefix + ticket.id + '-' + estatusKey);
                    }
                };
                
                // Función auxiliar para actualizar el contenido visible de un ticket
                this.actualizarContenidoTicket = (elemento, ticket) => {
                    // Validar que el elemento existe
                    if (!elemento) {
                        console.warn('Intento de actualizar contenido de elemento null');
                        return;
                    }
                    
                    try {
                        // Actualizar descripción
                        const descripcionElement = elemento.querySelector('p.text-sm.font-medium.line-clamp-3');
                        if (descripcionElement && ticket.descripcion) {
                            const descripcionLimitada = ticket.descripcion.length > 100 
                                ? ticket.descripcion.substring(0, 100) + '...' 
                                : ticket.descripcion;
                            descripcionElement.textContent = descripcionLimitada;
                        }
                        
                        // Actualizar prioridad (badge) - buscar de múltiples formas
                        let prioridadElement = elemento.querySelector('.text-\\[10px\\].uppercase') ||
                                             elemento.querySelector('[class*="text-[10px]"]') ||
                                             elemento.querySelector('.uppercase.font-bold');
                        
                        if (prioridadElement && ticket.prioridad) {
                            prioridadElement.textContent = ticket.prioridad;
                            // Actualizar clases según prioridad
                            prioridadElement.className = `text-[10px] uppercase font-bold px-2 py-0.5 rounded ${
                                ticket.prioridad === 'Baja' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                ticket.prioridad === 'Media' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                            }`;
                        }
                        
                        // Actualizar nombre del empleado - con validación segura
                        const iconoUsuario = elemento.querySelector('.fa-user');
                        if (iconoUsuario && iconoUsuario.parentElement) {
                            const empleadoElement = iconoUsuario.parentElement.nextElementSibling;
                            if (empleadoElement && ticket.empleado && ticket.empleado.nombre) {
                                empleadoElement.textContent = ticket.empleado.nombre.length > 15 
                                    ? ticket.empleado.nombre.substring(0, 15) + '...' 
                                    : ticket.empleado.nombre;
                            }
                        }
                    } catch (error) {
                        console.error('Error actualizando contenido del ticket:', error);
                        // No lanzar el error, solo registrarlo para no interrumpir otras actualizaciones
                    }
                };
                
                // Función para actualizar el contenido del ticket cuando está en "proceso"
                // ticketData puede ser un objeto con datos del ticket o un objeto con estructura { responsable: { nombre: ... }, estatus: ... }
                this.actualizarContenidoTicketProceso = async (elemento, ticketData) => {
                    try {
                        // Determinar si es vista kanban o lista
                        const esVistaKanban = elemento.closest('.kanban-root') || elemento.closest('[x-show*="kanban"]');
                        const esVistaLista = elemento.closest('[x-show*="lista"]') || elemento.closest('.divide-y');
                        
                        if (esVistaLista) {
                            // Para vista lista, actualizar el responsable en el footer de info
                            this.actualizarResponsableEnLista(elemento, ticketData);
                            return;
                        }
                        
                        // Para vista kanban, buscar el footer de la tarjeta donde van el responsable y tiempo
                        const footer = elemento.querySelector('.pt-3.border-t') || 
                                      elemento.querySelector('[class*="border-t"]') ||
                                      elemento.querySelector('.flex.flex-col.gap-2');
                        
                        if (!footer) return;
                        
                        // Verificar si ya existe la sección del responsable
                        let responsableSection = footer.querySelector('.bg-blue-50, .bg-blue-900\\/20, [class*="bg-blue"]');
                        let tiempoSection = footer.querySelector('div.mt-2.w-full');
                        
                        // Obtener el nombre del responsable desde múltiples fuentes
                        let responsableNombre = elemento.getAttribute('data-ticket-responsable') || '';
                        
                        // Si no está en el atributo, intentar obtenerlo desde múltiples fuentes
                        if (!responsableNombre || responsableNombre.trim() === '') {
                            // 1. Intentar desde ticketData (puede venir del servidor con relación responsable)
                            // ticketData puede tener estructura { responsable: { nombre: ... } } o ser un objeto plano
                            if (ticketData && ticketData.responsable) {
                                if (typeof ticketData.responsable === 'object' && ticketData.responsable.nombre) {
                                    responsableNombre = ticketData.responsable.nombre;
                                    // Guardar en el atributo para futuras referencias
                                    elemento.setAttribute('data-ticket-responsable', responsableNombre);
                                } else if (typeof ticketData.responsable === 'string') {
                                    responsableNombre = ticketData.responsable;
                                    elemento.setAttribute('data-ticket-responsable', responsableNombre);
                                }
                            } 
                            // 2. Intentar desde this.selected
                            if ((!responsableNombre || responsableNombre.trim() === '') && this.selected && this.selected.responsable) {
                                responsableNombre = this.selected.responsable;
                                elemento.setAttribute('data-ticket-responsable', responsableNombre);
                            } 
                            // 3. Intentar desde ResponsableTI usando el select
                            if ((!responsableNombre || responsableNombre.trim() === '') && ticketData && (ticketData.ResponsableTI || this.ticketResponsableTI)) {
                                const responsableId = ticketData.ResponsableTI || this.ticketResponsableTI;
                                const responsableSelect = document.getElementById('responsable-select');
                                if (responsableSelect) {
                                    const option = responsableSelect.querySelector(`option[value="${responsableId}"]`);
                                    if (option && option.textContent) {
                                        responsableNombre = option.textContent.trim();
                                        // Guardar en el atributo para futuras referencias
                                        elemento.setAttribute('data-ticket-responsable', responsableNombre);
                                    }
                                }
                            }
                        }
                        
                        // Si aún no tenemos el responsable pero el elemento tiene el atributo actualizado, usarlo
                        if ((!responsableNombre || responsableNombre.trim() === '') && elemento.getAttribute('data-ticket-responsable')) {
                            responsableNombre = elemento.getAttribute('data-ticket-responsable');
                        }
                        
                        // Si el ticket tiene responsable, agregar o actualizar la sección del responsable
                        if (responsableNombre && responsableNombre.trim() !== '') {
                            // Formatear el nombre (similar a como se hace en PHP)
                            const partes = responsableNombre.trim().split(/\s+/);
                            let nombreFormateado = responsableNombre;
                            if (partes.length >= 3) {
                                const sinSegundo = [...partes];
                                sinSegundo.splice(1, 1);
                                nombreFormateado = sinSegundo.join(' ');
                            }
                            
                            if (!responsableSection) {
                                // Crear la sección del responsable
                                responsableSection = document.createElement('div');
                                responsableSection.className = 'mt-1 flex items-center gap-2 text-xs px-2 py-1 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800';
                                responsableSection.innerHTML = `
                                    <i class="fas fa-user-tie"></i>
                                    <span class="font-semibold truncate">${nombreFormateado}</span>
                                `;
                                
                                // Insertar después del usuario y fecha (buscar el contenedor correcto)
                                const usuarioFechaContainer = footer.querySelector('.flex.items-center.gap-2')?.parentElement || 
                                                             footer.querySelector('.flex.justify-between.items-center');
                                
                                if (usuarioFechaContainer) {
                                    // Insertar después del contenedor de usuario/fecha
                                    usuarioFechaContainer.insertAdjacentElement('afterend', responsableSection);
                                } else {
                                    // Si no se encuentra, insertar al principio del footer
                                    footer.insertBefore(responsableSection, footer.firstChild);
                                }
                            } else {
                                // Actualizar el nombre del responsable existente
                                const nombreSpan = responsableSection.querySelector('span.font-semibold');
                                if (nombreSpan) {
                                    nombreSpan.textContent = nombreFormateado;
                                }
                            }
                        } else {
                            // Si no hay responsable, remover la sección si existe
                            if (responsableSection) {
                                responsableSection.remove();
                            }
                        }
                        
                        // Obtener información de tiempo si el ticket está en progreso
                        const ticketId = ticketData.TicketID || ticketData.id || this.selected.id;
                        
                        // Solo crear/actualizar tiempo si no existe la sección
                        // Si ya existe tiempoSection con datos válidos, NO hacer nada (se actualiza automáticamente por actualizarIndicadoresTiempoEnDOM)
                        if (!tiempoSection) {
                            const tiempoInfo = await this.obtenerTiempoTicket(ticketId);
                            
                            if (tiempoInfo) {
                                // Crear la sección de tiempo solo si no existe
                                tiempoSection = document.createElement('div');
                                tiempoSection.className = 'mt-2 w-full';
                                const estadoColor = tiempoInfo.estado === 'agotado' ? 'text-red-500 font-bold' : '';
                                const barraColor = tiempoInfo.estado === 'agotado' ? 'bg-red-500' : 
                                                  (tiempoInfo.estado === 'por_vencer' ? 'bg-yellow-500' : 'bg-green-500');
                                
                                tiempoSection.innerHTML = `
                                    <div class="flex justify-between text-[10px] mb-1 text-gray-500 dark:text-gray-400">
                                        <span>Tiempo:</span>
                                        <span class="${estadoColor}">${tiempoInfo.texto_transcurrido} / ${tiempoInfo.texto_estimado}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full ${barraColor}" style="width: ${Math.min(tiempoInfo.porcentaje, 100)}%"></div>
                                    </div>
                                `;
                                
                                footer.appendChild(tiempoSection);
                            }
                        }
                        // Si tiempoSection ya existe, NO hacer nada - se actualiza automáticamente por actualizarIndicadoresTiempoEnDOM
                    } catch (error) {
                        console.error('Error actualizando contenido del ticket en proceso:', error);
                    }
                };
                
                // Función para actualizar el responsable en la vista lista
                this.actualizarResponsableEnLista = (elemento, ticketData) => {
                    try {
                        // Buscar el contenedor de info footer donde va el responsable
                        const infoFooter = elemento.querySelector('.flex.flex-wrap.items-center.gap-4') ||
                                          elemento.querySelector('[class*="flex-wrap"]');
                        
                        if (!infoFooter) return;
                        
                        // Verificar si ya existe la sección del responsable
                        let responsableSection = infoFooter.querySelector('.bg-blue-50, .bg-blue-900\\/20, [class*="bg-blue"]');
                        
                        // Obtener el nombre del responsable desde múltiples fuentes
                        let responsableNombre = elemento.getAttribute('data-ticket-responsable') || '';
                        
                        // Si no está en el atributo, intentar obtenerlo desde múltiples fuentes
                        if (!responsableNombre || responsableNombre.trim() === '') {
                            // 1. Intentar desde ticketData
                            if (ticketData && ticketData.responsable) {
                                if (typeof ticketData.responsable === 'object' && ticketData.responsable.nombre) {
                                    responsableNombre = ticketData.responsable.nombre;
                                    elemento.setAttribute('data-ticket-responsable', responsableNombre);
                                } else if (typeof ticketData.responsable === 'string') {
                                    responsableNombre = ticketData.responsable;
                                    elemento.setAttribute('data-ticket-responsable', responsableNombre);
                                }
                            } 
                            // 2. Intentar desde this.selected
                            if ((!responsableNombre || responsableNombre.trim() === '') && this.selected && this.selected.responsable) {
                                responsableNombre = this.selected.responsable;
                                elemento.setAttribute('data-ticket-responsable', responsableNombre);
                            } 
                            // 3. Intentar desde ResponsableTI usando el select
                            if ((!responsableNombre || responsableNombre.trim() === '') && ticketData && (ticketData.ResponsableTI || this.ticketResponsableTI)) {
                                const responsableId = ticketData.ResponsableTI || this.ticketResponsableTI;
                                const responsableSelect = document.getElementById('responsable-select');
                                if (responsableSelect) {
                                    const option = responsableSelect.querySelector(`option[value="${responsableId}"]`);
                                    if (option && option.textContent) {
                                        responsableNombre = option.textContent.trim();
                                        elemento.setAttribute('data-ticket-responsable', responsableNombre);
                                    }
                                }
                            }
                        }
                        
                        // Si aún no tenemos el responsable pero el elemento tiene el atributo actualizado, usarlo
                        if ((!responsableNombre || responsableNombre.trim() === '') && elemento.getAttribute('data-ticket-responsable')) {
                            responsableNombre = elemento.getAttribute('data-ticket-responsable');
                        }
                        
                        // Si el ticket tiene responsable, agregar o actualizar la sección del responsable
                        if (responsableNombre && responsableNombre.trim() !== '') {
                            // Formatear el nombre (similar a como se hace en PHP)
                            const partes = responsableNombre.trim().split(/\s+/);
                            let nombreFormateado = responsableNombre;
                            if (partes.length >= 3) {
                                const sinSegundo = [...partes];
                                sinSegundo.splice(1, 1);
                                nombreFormateado = sinSegundo.join(' ');
                            }
                            
                            if (!responsableSection) {
                                // Crear la sección del responsable para lista
                                responsableSection = document.createElement('span');
                                responsableSection.className = 'flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800';
                                responsableSection.innerHTML = `
                                    <i class="fas fa-user-tie"></i>
                                    <span>${nombreFormateado}</span>
                                `;
                                
                                // Insertar después del elemento de fecha (buscar el contenedor de fecha)
                                const fechaElement = infoFooter.querySelector('.fa-calendar-alt')?.closest('span');
                                if (fechaElement && fechaElement.nextSibling) {
                                    infoFooter.insertBefore(responsableSection, fechaElement.nextSibling);
                                } else if (fechaElement) {
                                    fechaElement.insertAdjacentElement('afterend', responsableSection);
                                } else {
                                    // Si no se encuentra la fecha, agregar al final
                                    infoFooter.appendChild(responsableSection);
                                }
                            } else {
                                // Actualizar el nombre del responsable existente
                                const nombreSpan = responsableSection.querySelector('span:last-child');
                                if (nombreSpan) {
                                    nombreSpan.textContent = nombreFormateado;
                                }
                            }
                        } else {
                            // Si no hay responsable, remover la sección si existe
                            if (responsableSection) {
                                responsableSection.remove();
                            }
                        }
                    } catch (error) {
                        console.error('Error actualizando responsable en lista:', error);
                    }
                };
                
                // Función para remover las secciones de responsable y tiempo cuando el ticket sale de "proceso"
                this.removerContenidoTicketProceso = (elemento) => {
                    // Determinar si es vista kanban o lista
                    const esVistaKanban = elemento.closest('.kanban-root') || elemento.closest('[x-show*="kanban"]');
                    const esVistaLista = elemento.closest('[x-show*="lista"]') || elemento.closest('.divide-y');
                    
                    if (esVistaLista) {
                        // Para vista lista, remover el responsable del footer de info
                        const infoFooter = elemento.querySelector('.flex.flex-wrap.items-center.gap-4') ||
                                          elemento.querySelector('[class*="flex-wrap"]');
                        if (infoFooter) {
                            const responsableSection = infoFooter.querySelector('.bg-blue-50, .bg-blue-900\\/20, [class*="bg-blue"]');
                            if (responsableSection) {
                                responsableSection.remove();
                            }
                        }
                        return;
                    }
                    
                    // Para vista kanban, remover del footer
                    const footer = elemento.querySelector('.pt-3.border-t') || 
                                  elemento.querySelector('[class*="border-t"]') ||
                                  elemento.querySelector('.flex.flex-col.gap-2');
                    
                    if (!footer) return;
                    
                    // Remover sección del responsable
                    const responsableSection = footer.querySelector('.bg-blue-50, .bg-blue-900\\/20, [class*="bg-blue"]');
                    if (responsableSection) {
                        responsableSection.remove();
                    }
                    
                    // Remover sección de tiempo solo si el ticket ya no está en proceso
                    const tiempoSection = footer.querySelector('div.mt-2.w-full');
                    if (tiempoSection) {
                        // Verificar que realmente es la sección de tiempo (debe tener "Tiempo:" en el contenido)
                        const tiempoLabel = tiempoSection.querySelector('span:first-child');
                        if (tiempoLabel && tiempoLabel.textContent.includes('Tiempo:')) {
                            tiempoSection.remove();
                        }
                    }
                };
                
                // Función auxiliar para obtener información de tiempo del ticket
                this.obtenerTiempoTicket = async (ticketId) => {
                    try {
                        const response = await fetch(`/tickets/tiempo-progreso`, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success && data.tiempos && data.tiempos[ticketId]) {
                            const tiempo = data.tiempos[ticketId];
                            // Formatear horas a texto
                            const formatearHoras = (horas) => {
                                if (!horas || horas === 0 || horas === '') return '0m';
                                const h = parseFloat(horas);
                                if (isNaN(h)) return '0m';
                                const horasEnteras = Math.floor(h);
                                const minutos = Math.round((h - horasEnteras) * 60);
                                if (horasEnteras > 0 && minutos > 0) {
                                    return `${horasEnteras}h ${minutos}m`;
                                } else if (horasEnteras > 0) {
                                    return `${horasEnteras}h`;
                                } else if (minutos > 0) {
                                    return `${minutos}m`;
                                } else {
                                    return '0m';
                                }
                            };
                            
                            return {
                                texto_transcurrido: formatearHoras(tiempo.transcurrido),
                                texto_estimado: formatearHoras(tiempo.estimado),
                                porcentaje: tiempo.porcentaje,
                                estado: tiempo.estado
                            };
                        }
                        return null;
                    } catch (error) {
                        return null;
                    }
                };
                
                
                // Variable para rastrear el último hash de tickets
                this.ultimoHashTickets = {};
                
                // Lógica del tablero: solo en /tickets, no en la instancia global del panel
                if (!this.soloPanel) {
                // Watcher para ejecutar prepararDatosTabla cuando se cambie a vista tabla
                this.$watch('vista', (newValue) => {
                    if (newValue === 'tabla') {
                        // Esperar un momento para que el DOM se actualice y los elementos estén disponibles
                        setTimeout(() => {
                            this.prepararDatosTabla();
                        }, 200);
                        // Iniciar actualización en tiempo real también en vista tabla
                        this.iniciarActualizacionTiempoReal();
                    } else if (newValue === 'lista') {
                        // Ejecutar prepararDatosLista cuando se cambia a vista lista
                        this.$nextTick(() => {
                            this.prepararDatosLista();
                        });
                        // Iniciar actualización en tiempo real cuando se cambia a lista
                        this.iniciarActualizacionTiempoReal();
                    } else if (newValue === 'kanban') {
                        // Iniciar actualización en tiempo real cuando se cambia a kanban
                        this.iniciarActualizacionTiempoReal();
                    }
                });

                // Iniciar actualización en tiempo real de indicadores de tiempo para todas las vistas
                this.iniciarActualizacionTiempoReal();
                }

                this.mostrarCc = false;
                this.mostrarBcc = false;
                this.prioridadCorreo = 'normal';
                this.correoCc = '';
                this.correoBcc = '';
                
                // Tickets excedidos: solo en /tickets, no en la instancia global del panel
                if (!this.soloPanel) {
                // Verificar tickets excedidos al cargar
                this.verificarTicketsExcedidos();

                // Verificación automática cada 5 minutos (abre popup si hay tickets excedidos)
                this.intervaloExcedidos5min = setInterval(() => {
                    if (!document.hidden) {
                        this.verificarTicketsExcedidos();
                    }
                }, 5 * 60 * 1000); // 5 minutos

                // Reiniciar verificación si la página vuelve a estar visible (cuando el usuario regresa a la pestaña)
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        this.verificarTicketsExcedidos();
                    }
                });
                }

                // La actualización de mensajes se manejará mediante cron job
                // No se configura recarga automática

                // Exponer apertura global del panel de ticket (usado por notificaciones desde cualquier vista)
                window.__abrirModalTicket = (datos) => this.abrirModal(datos);
            },

            iniciarActualizacionTiempoReal() {
                // El refresco de tarjetas lo maneja Livewire con wire:poll.
                // Aquí solo hacemos una sincronización puntual de indicadores.
                this.actualizarIndicadoresTiempo();
            },

            actualizarIndicadoresTiempoEnDOM(tiempos) {
                // Función auxiliar para actualizar el DOM con los datos de tiempo
                // Puede ser llamada desde actualizarIndicadoresTiempo o desde eventos de Livewire
                if (!tiempos) return;
                
                Object.keys(tiempos).forEach(ticketId => {
                    const tiempoInfo = tiempos[ticketId];
                    if (!tiempoInfo) return;
                    
                    // Actualizar cada ticket en el DOM
                    this.actualizarTicketEnDOM(ticketId, tiempoInfo);
                });
            },
            
            actualizarTicketEnDOM(ticketId, tiempoInfo) {
                // Actualizar vista Kanban
                const ticketElementKanban = document.querySelector(`[data-ticket-id="${ticketId}"][data-categoria="proceso"]`);
                if (ticketElementKanban) {
                    const tiempoContainer = ticketElementKanban.querySelector('.tiempo-indicador-container');
                    if (tiempoContainer) {
                        // Actualizar el badge de estado
                        const badgeEstado = tiempoContainer.querySelector('.badge-estado');
                        if (badgeEstado) {
                            const estado = tiempoInfo.estado;
                            badgeEstado.className = `text-xs px-2 py-0.5 rounded-full font-semibold ${
                                estado === 'agotado' ? 'bg-red-100 text-red-700' : 
                                (estado === 'por_vencer' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700')
                            }`;
                            badgeEstado.innerHTML = estado === 'agotado' 
                                ? '<i class="fas fa-exclamation-triangle"></i> Tiempo Agotado'
                                : (estado === 'por_vencer' 
                                    ? '<i class="fas fa-clock"></i> Por Vencer'
                                    : '<i class="fas fa-check-circle"></i> En Tiempo');
                        }
                        
                        // Actualizar el texto de tiempo
                        const tiempoTexto = tiempoContainer.querySelector('.tiempo-texto');
                        if (tiempoTexto) {
                            // Convertir horas decimales a horas y minutos
                            const formatearHoras = (horas) => {
                                if (!horas || horas === 0 || horas === '') return '-';
                                const h = parseFloat(horas);
                                if (isNaN(h)) return '-';
                                const horasEnteras = Math.floor(h);
                                const minutos = Math.round((h - horasEnteras) * 60);
                                if (horasEnteras > 0 && minutos > 0) {
                                    return `${horasEnteras}h ${minutos}m`;
                                } else if (horasEnteras > 0) {
                                    return `${horasEnteras}h`;
                                } else if (minutos > 0) {
                                    return `${minutos}m`;
                                } else {
                                    return '0m';
                                }
                            };
                            tiempoTexto.textContent = `${formatearHoras(tiempoInfo.transcurrido)} / ${formatearHoras(tiempoInfo.estimado)}`;
                        }
                        
                        // Actualizar la barra de progreso
                        const barraProgreso = tiempoContainer.querySelector('.barra-progreso');
                        if (barraProgreso) {
                            barraProgreso.style.width = `${Math.min(tiempoInfo.porcentaje, 100)}%`;
                            barraProgreso.className = `h-1.5 rounded-full transition-all duration-300 ${
                                tiempoInfo.estado === 'agotado' ? 'bg-red-500' : 
                                (tiempoInfo.estado === 'por_vencer' ? 'bg-yellow-500' : 'bg-green-500')
                            }`;
                        }
                    }
                }
                
                // Actualizar vista Lista (usando Alpine.js)
                if (this.ticketsTabla && Array.isArray(this.ticketsTabla)) {
                    const ticketEnLista = this.ticketsTabla.find(t => t.id == ticketId);
                    if (ticketEnLista && ticketEnLista.tiempoTranscurrido !== undefined) {
                        ticketEnLista.tiempoTranscurrido = tiempoInfo.transcurrido.toString();
                        ticketEnLista.tiempoEstimado = tiempoInfo.estimado.toString();
                        ticketEnLista.tiempoEstado = tiempoInfo.estado;
                        
                        // Actualizar también los atributos data-* en el elemento DOM si existe
                        if (ticketEnLista.elemento) {
                            ticketEnLista.elemento.setAttribute('data-ticket-tiempo-transcurrido', tiempoInfo.transcurrido);
                            ticketEnLista.elemento.setAttribute('data-ticket-tiempo-estimado', tiempoInfo.estimado);
                            ticketEnLista.elemento.setAttribute('data-ticket-tiempo-estado', tiempoInfo.estado);
                        }
                    }
                }
                
                // Actualizar atributos data-* en todos los elementos del ticket para mantener consistencia
                const todosLosElementosTicket = document.querySelectorAll(`[data-ticket-id="${ticketId}"]`);
                todosLosElementosTicket.forEach(elemento => {
                    elemento.setAttribute('data-ticket-tiempo-transcurrido', tiempoInfo.transcurrido);
                    elemento.setAttribute('data-ticket-tiempo-estimado', tiempoInfo.estimado);
                    elemento.setAttribute('data-ticket-tiempo-estado', tiempoInfo.estado);
                });
            },

            async actualizarIndicadoresTiempo() {
                // Esta función ahora se llama manualmente cuando es necesario
                // Las actualizaciones automáticas se manejan con wire:poll a través de Livewire
                try {
                    const response = await fetch('/tickets/tiempo-progreso', {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();
                    
                    if (data.success && data.tiempos) {
                        // Usar la función auxiliar para actualizar el DOM
                        this.actualizarIndicadoresTiempoEnDOM(data.tiempos);
                    }
                } catch (error) {
                    console.error('Error actualizando indicadores de tiempo:', error);
                }
            },

            cargarTinyMCE() {
                if (window.tinymce) {
                    return Promise.resolve();
                }

                if (window.__tinyMCELoadPromise) {
                    return window.__tinyMCELoadPromise;
                }

                window.__tinyMCELoadPromise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js';
                    script.defer = true;
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });

                return window.__tinyMCELoadPromise;
            },

            async inicializarTinyMCE() {
                const editorElement = document.getElementById('editor-mensaje');
                
                if (!editorElement || this.tinyMCEInstance) return;

                if (typeof tinymce === 'undefined') {
                    try {
                        await this.cargarTinyMCE();
                    } catch (error) {
                        return;
                    }
                }

                if (typeof tinymce === 'undefined') {
                    return;
                }

                // Destruir instancia anterior si existe
                if (tinymce.get('editor-mensaje')) {
                    tinymce.remove('editor-mensaje');
                }

                // Detectar modo oscuro
                const isDarkMode = document.documentElement.classList.contains('dark');
                
                // Inicializar TinyMCE
                tinymce.init({
                    selector: '#editor-mensaje',
                    height: 300,
                    menubar: false,
                    plugins: [
                        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                        'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
                    ],
                    toolbar: 'undo redo | formatselect | ' +
                        'bold italic underline strikethrough | forecolor backcolor | ' +
                        'alignleft aligncenter alignright alignjustify | ' +
                        'bullist numlist | outdent indent | ' +
                        'removeformat | link image | code | help',
                    content_style: isDarkMode 
                        ? `body { font-family: Arial, sans-serif; font-size: 14px; background-color: #1f2937 !important; color: #ffffff !important; }
                        body * { color: #ffffff !important; }
                        img { max-width: 150px !important; max-height: 110px !important; object-fit: cover !important; border-radius: 6px !important; cursor: pointer !important; border: 2px solid #3B82F6 !important; margin: 3px !important; vertical-align: bottom !important; }`
                        : `body { font-family: Arial, sans-serif; font-size: 14px; }
                        img { max-width: 150px !important; max-height: 110px !important; object-fit: cover !important; border-radius: 6px !important; cursor: pointer !important; border: 2px solid #e5e7eb !important; margin: 3px !important; vertical-align: bottom !important; }`,
                    language: 'es',
                    placeholder: 'Escribe tu mensaje aquí...',
                    
                    automatic_uploads: true,
                    paste_data_images: true,
                    images_reuse_filename: false,

                    images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        console.error('TinyMCE: No se encontró el meta CSRF token');
                        // Sin CSRF no podemos subir, pero tampoco borramos la imagen
                        resolve('data:' + blobInfo.blob().type + ';base64,' + blobInfo.base64());
                        return;
                    }

                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename() || 'imagen.png');

                    fetch('/tickets/subir-imagen-tinymce', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                            'Accept': 'application/json',
                            // NO agregar Content-Type aquí — el browser lo pone solo con el boundary correcto
                        },
                        body: formData
                    })
                    .then(response => response.json().then(data => ({ status: response.status, data })))
                    .then(({ status, data }) => {
                        if (status === 200 && data.location) {
                            resolve(data.location);
                        } else {
                            // Error del servidor: guardar como base64 para que la imagen NO desaparezca
                            console.warn('TinyMCE upload error servidor:', status, data);
                            resolve('data:' + blobInfo.blob().type + ';base64,' + blobInfo.base64());
                        }
                    })
                    .catch(error => {
                        // Error de red: mismo fallback base64
                        console.warn('TinyMCE upload error red:', error.message);
                        resolve('data:' + blobInfo.blob().type + ';base64,' + blobInfo.base64());
                    });
                }),
                    setup: (editor) => {
                        this.tinyMCEInstance = editor;
                        
                        // Sincronizar contenido con Alpine.js en tiempo real
                        editor.on('input', () => {
                            const contenido = editor.getContent();
                            this.nuevoMensaje = contenido;
                            // Forzar actualización de Alpine.js
                            this.$nextTick(() => {
                                // Trigger para que Alpine detecte el cambio
                            });
                        });
                        
                        editor.on('change', () => {
                            const contenido = editor.getContent();
                            this.nuevoMensaje = contenido;
                            // Forzar actualización de Alpine.js
                            this.$nextTick(() => {
                                // Trigger para que Alpine detecte el cambio
                            });
                        });
                        
                        editor.on('keyup', () => {
                            const contenido = editor.getContent();
                            this.nuevoMensaje = contenido;
                        });
                        
                        editor.on('NodeChange', () => {
                            const contenido = editor.getContent();
                            this.nuevoMensaje = contenido;
                        });
                    },
                    init_instance_callback: (editor) => {
                        // Verificar estado al inicializar y deshabilitar si está cerrado
                        this.$nextTick(() => {
                            this.actualizarEstadoEditor();
                        });
                    },
                    file_picker_types: 'file',
                    file_picker_callback: (callback, value, meta) => {
                        // Abrir el input de archivos existente
                        const fileInput = document.getElementById('adjuntos');
                        if (fileInput) {
                            fileInput.click();
                            fileInput.onchange = (e) => {
                                const file = e.target.files[0];
                                if (file) {
                                    // Mostrar el archivo como enlace en el editor
                                    const reader = new FileReader();
                                    reader.onload = () => {
                                        const fileUrl = reader.result;
                                        callback(fileUrl, { text: file.name });
                                    };
                                    reader.readAsDataURL(file);
                                }
                            };
                        }
                    }
                });
            },

            prepararDatosLista() {
                // Los contadores ya están inicializados desde el servidor
                // Esta función solo se usa para recalcular si es necesario
                // No necesita hacer nada ya que los datos vienen del servidor
            },

            prepararDatosTabla() {
                // Preparar todos los tickets para la tabla desde los elementos del DOM
                // Usar getAttribute en lugar de dataset para asegurar que funcione incluso con elementos ocultos
                const preparar = () => {
                    const todosTickets = [];
                    const elementos = document.querySelectorAll('[data-categoria]');

                    elementos.forEach(el => {
                        const categoria = el.getAttribute('data-categoria');
                        if (!['nuevos', 'proceso', 'resueltos'].includes(categoria)) return;

                        // Usar getAttribute para obtener los valores incluso si el elemento está oculto
                        const ticketId = el.getAttribute('data-ticket-id') || el.dataset.ticketId;
                        const ticketAsunto = el.getAttribute('data-ticket-asunto') || el.dataset.ticketAsunto;
                        const ticketDescripcion = el.getAttribute('data-ticket-descripcion') || el.dataset.ticketDescripcion;
                        const ticketPrioridad = el.getAttribute('data-ticket-prioridad') || el.dataset.ticketPrioridad;
                        const ticketEmpleado = el.getAttribute('data-ticket-empleado') || el.dataset.ticketEmpleado;
                        const ticketAnydesk = el.getAttribute('data-ticket-anydesk') || el.dataset.ticketAnydesk || '';
                        const ticketNumero = el.getAttribute('data-ticket-numero') || el.dataset.ticketNumero || '';
                        const ticketCorreo = el.getAttribute('data-ticket-correo') || el.dataset.ticketCorreo;
                        const ticketFecha = el.getAttribute('data-ticket-fecha') || el.dataset.ticketFecha;
                        const ticketPuesto = el.getAttribute('data-ticket-puesto') || el.dataset.ticketPuesto;
                        const ticketGerencia = el.getAttribute('data-ticket-gerencia') || el.dataset.ticketGerencia;
                        const ticketDepartamento = el.getAttribute('data-ticket-departamento') || el.dataset.ticketDepartamento;
                        const ticketResponsable = el.getAttribute('data-ticket-responsable') || '';
                        const ticketTiempoTranscurrido = el.getAttribute('data-ticket-tiempo-transcurrido') || '';
                        const ticketTiempoEstimado = el.getAttribute('data-ticket-tiempo-estimado') || '';
                        const ticketTiempoEstado = el.getAttribute('data-ticket-tiempo-estado') || '';
                        
                        if (ticketId) {
                            todosTickets.push({
                                id: ticketId,
                                asunto: ticketAsunto || `Ticket #${ticketId}`,
                                descripcion: ticketDescripcion || '',
                                prioridad: ticketPrioridad || 'Media',
                                empleado: ticketEmpleado || '',
                                anydesk: ticketAnydesk,
                                numero: ticketNumero,
                                correo: ticketCorreo || '',
                                puesto: ticketPuesto || '',
                                gerencia: ticketGerencia || '',
                                departamento: ticketDepartamento || '',
                                fecha: ticketFecha || '',
                                estatus: categoria === 'nuevos' ? 'Pendiente' : (categoria === 'proceso' ? 'En progreso' : 'Cerrado'),
                                responsable: ticketResponsable ? ticketResponsable.trim() : '',
                                tiempoTranscurrido: ticketTiempoTranscurrido ? ticketTiempoTranscurrido.trim() : '',
                                tiempoEstimado: ticketTiempoEstimado ? ticketTiempoEstimado.trim() : '',
                                tiempoEstado: ticketTiempoEstado ? ticketTiempoEstado.trim() : '',
                                elemento: el
                            });
                        }
                    });
                    
                    // Si no se encontraron elementos, intentar nuevamente después de un breve delay
                    if (elementos.length === 0 && this.vista === 'tabla') {
                        setTimeout(() => {
                            this.prepararDatosTabla();
                        }, 500);
                        return;
                    }
                    
                    // Eliminar duplicados basados en el ID del ticket
                    const ticketsUnicos = [];
                    const idsVistos = new Set();
                    
                    todosTickets.forEach(ticket => {
                        if (!idsVistos.has(ticket.id)) {
                            idsVistos.add(ticket.id);
                            ticketsUnicos.push(ticket);
                        }
                    });
                    
                    // Asignar los tickets únicos y ordenar
                    // Crear un nuevo array para asegurar que Alpine.js detecte el cambio
                    this.ticketsTabla = [...ticketsUnicos];
                    this.ordenarTabla();
                };
                
                // Ejecutar después de que Alpine.js haya procesado el DOM
                this.$nextTick(() => {
                    preparar();
                });
            },

            obtenerTicketsListaPagina(categoria) {
                const tickets = this.ticketsLista[categoria] || [];
                const inicio = (this.paginaLista[categoria] - 1) * this.elementosPorPagina;
                const fin = inicio + this.elementosPorPagina;
                return tickets.slice(inicio, fin);
            },

            estaEnPaginaLista(categoria, indice) {
                const inicio = (this.paginaLista[categoria] - 1) * this.elementosPorPagina;
                const fin = inicio + this.elementosPorPagina;
                return indice >= inicio && indice < fin;
            },

            estaEnPaginaListaPorElemento(categoria, elemento) {
                // Calcular el índice del elemento dentro de su contenedor padre
                const contenedor = elemento?.parentElement;
                if (!contenedor) return false;
                
                const elementosEnSeccion = Array.from(contenedor.children);
                const indice = elementosEnSeccion.indexOf(elemento);
                
                return this.estaEnPaginaLista(categoria, indice);
            },

            obtenerTotalPaginasLista(categoria) {
                const totalTickets = this.ticketsLista[categoria] || 0;
                return Math.ceil(totalTickets / this.elementosPorPagina);
            },

            cambiarPaginaLista(categoria, pagina) {
                const totalPaginas = this.obtenerTotalPaginasLista(categoria);
                if (pagina >= 1 && pagina <= totalPaginas) {
                    this.paginaLista[categoria] = pagina;
                }
            },

            obtenerTicketsTablaPagina() {
                const inicio = (this.paginaTabla - 1) * this.elementosPorPagina;
                const fin = inicio + this.elementosPorPagina;
                return this.ticketsTabla.slice(inicio, fin);
            },

            obtenerTotalPaginasTabla() {
                return Math.ceil(this.ticketsTabla.length / this.elementosPorPagina);
            },

            cambiarPaginaTabla(pagina) {
                const totalPaginas = this.obtenerTotalPaginasTabla();
                if (pagina >= 1 && pagina <= totalPaginas) {
                    this.paginaTabla = pagina;
                }
            },

            ordenarTabla() {
                this.ticketsTabla.sort((a, b) => {
                    let valorA, valorB;
                    
                    switch(this.ordenColumna) {
                        case 'id':
                            valorA = parseInt(a.id);
                            valorB = parseInt(b.id);
                            break;
                        case 'descripcion':
                            valorA = a.descripcion.toLowerCase();
                            valorB = b.descripcion.toLowerCase();
                            break;
                        case 'empleado':
                            valorA = a.empleado.toLowerCase();
                            valorB = b.empleado.toLowerCase();
                            break;
                        case 'prioridad':
                            const prioridades = { 'Alta': 3, 'Media': 2, 'Baja': 1 };
                            valorA = prioridades[a.prioridad] || 0;
                            valorB = prioridades[b.prioridad] || 0;
                            break;
                        case 'estado':
                            valorA = a.estatus.toLowerCase();
                            valorB = b.estatus.toLowerCase();
                            break;
                        case 'fecha':
                        default:
                            valorA = new Date(a.fecha.split(' ')[0].split('/').reverse().join('-'));
                            valorB = new Date(b.fecha.split(' ')[0].split('/').reverse().join('-'));
                            break;
                    }
                    
                    if (valorA < valorB) return this.ordenDireccion === 'asc' ? -1 : 1;
                    if (valorA > valorB) return this.ordenDireccion === 'asc' ? 1 : -1;
                    return 0;
                });
                
                // Resetear a página 1 después de ordenar
                this.paginaTabla = 1;
            },

            cambiarOrden(columna) {
                if (this.ordenColumna === columna) {
                    this.ordenDireccion = this.ordenDireccion === 'asc' ? 'desc' : 'asc';
                } else {
                    this.ordenColumna = columna;
                    this.ordenDireccion = 'asc';
                }
                this.ordenarTabla();
            },

            // Función eliminada: La actualización de mensajes se manejará mediante cron job
            // configurarActualizacionAutomatica() {
            //     setInterval(() => {
            //         if (this.mostrar && this.selected.id) {
            //             this.cargarMensajes();
            //         }
            //     }, 30000); // 30 segundos
            // },

            abrirModal(datos) {
                this.selected = datos;
                this.mostrar = true;
                document.querySelectorAll(`[data-ticket-id="${datos.id}"]`).forEach(card => {
                    card.querySelectorAll('.bg-red-500.rounded-full.w-4.h-4').forEach(badge => badge.remove());
                });

                fetch(`/tickets/${datos.id}/mark-notifications-read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                }).catch(() => {});
                this.asuntoCorreo = `Re: Ticket #${datos.id}`;
                this.mostrarCc = false;
                this.mostrarBcc = false;
                this.prioridadCorreo = 'normal';
                this.correoCc = '';
                this.correoBcc = '';
                // Limpiar archivos adjuntos al abrir un nuevo modal
                this.archivosAdjuntos = [];
                const adjuntosInput = document.getElementById('adjuntos');
                if (adjuntosInput) {
                    adjuntosInput.value = '';
                }
                // Cargar datos del ticket para el formulario
                this.cargarDatosTicket(datos.id);
                this.cargarMensajes();
                // Iniciar verificación automática de mensajes nuevos
                this.iniciarVerificacionMensajes();
                // Inicializar TinyMCE si no está inicializado
                this.$nextTick(() => {
                    if (!this.tinyMCEInstance) {
                        this.inicializarTinyMCE();
                    } else {
                        // Si ya está inicializado, actualizar su estado
                        this.actualizarEstadoEditor();
                    }
                });
            },

            async cargarDatosTicket(ticketId) {
                try {
                    const response = await fetch(`/tickets/${ticketId}`, {
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        
                        if (data.success && data.ticket) {
                            this.ticketPrioridad = data.ticket.Prioridad || 'Baja';
                            this.ticketEstatus = data.ticket.Estatus || '';
                            this.ticketClasificacion = data.ticket.Clasificacion || '';
                            this.ticketResponsableTI = data.ticket.ResponsableTI ? String(data.ticket.ResponsableTI) : '';
                            this.ticketTipoID = data.ticket.TipoID ? String(data.ticket.TipoID) : '';
                            this.ticketSubtipoID = data.ticket.SubtipoID ? String(data.ticket.SubtipoID) : '';
                            this.ticketTertipoID = data.ticket.TertipoID ? String(data.ticket.TertipoID) : '';
                            
                            // Actualizar selected
                            if (this.selected) {
                                this.selected.numero = data.ticket.numero || ''; 
                                this.selected.anydesk = data.ticket.anydesk || '';
                                this.selected.estatus = data.ticket.Estatus || '';
                                this.selected.imagen = data.ticket.imagen || '';
                                this.selected.resolucion = data.ticket.Resolucion || data.ticket.resolucion || '';
                                this.selected.puesto = data.ticket.puesto || '';
                                this.selected.gerencia = data.ticket.gerencia || '';
                                this.selected.departamento = data.ticket.departamento || '';
                            }
                            
                            this.$nextTick(() => { this.actualizarEstadoEditor(); });
                            
                            // Lógica completa y limpia de los selects anidados
                            this.$nextTick(() => {
                                setTimeout(() => {
                                    const tipoSelect = document.getElementById('tipo-select');
                                    if (tipoSelect && this.ticketTipoID) {
                                        tipoSelect.value = this.ticketTipoID;
                                        tipoSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                        
                                        setTimeout(() => {
                                            const subtipoSelect = document.getElementById('subtipo-select');
                                            if (subtipoSelect && this.ticketSubtipoID) {
                                                if (subtipoSelect.options.length <= 1 && typeof loadSubtipos === 'function') {
                                                    loadSubtipos(this.ticketTipoID);
                                                }
                                                
                                                setTimeout(() => {
                                                    if (subtipoSelect.options.length > 1) {
                                                        subtipoSelect.value = this.ticketSubtipoID;
                                                        subtipoSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                                        
                                                        setTimeout(() => {
                                                            const tertipoSelect = document.getElementById('tertipo-select');
                                                            if (tertipoSelect && this.ticketTertipoID) {
                                                                if (tertipoSelect.options.length <= 1 && typeof loadTertipos === 'function') {
                                                                    loadTertipos(this.ticketSubtipoID);
                                                                }
                                                                setTimeout(() => { 
                                                                    if (tertipoSelect.options.length > 1) {
                                                                        tertipoSelect.value = this.ticketTertipoID; 
                                                                    }
                                                                }, 300);
                                                            }
                                                        }, 500);
                                                    }
                                                }, 500);
                                            }
                                        }, 300);
                                    }
                                }, 200);
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error cargando datos:', error);
                }
            },

            async guardarCambiosTicket() {
                if (!this.selected.id) {
                    this.mostrarNotificacion('No hay ticket seleccionado', 'error');
                    return;
                }

                // Validación extra: Si está marcado como cerrado pero no hay resolución
                if (this.ticketEstatus === 'Cerrado' && !this.selected.resolucion_temporal && !this.selected.resolucion) {
                     this.mostrarNotificacion('Error: Falta la resolución para cerrar el ticket', 'error');
                     return;
                }

                this.guardandoTicket = true;

                try {
                    const formData = {
                        ticketId: this.selected.id,
                        prioridad: this.ticketPrioridad,
                        estatus: this.ticketEstatus,
                        clasificacion: this.ticketClasificacion || null,
                        responsableTI: this.ticketResponsableTI || null,
                        tipoID: this.ticketTipoID || null,
                        subtipoID: this.ticketSubtipoID || null,
                        tertipoID: this.ticketTertipoID || null,
                        resolucion: this.selected.resolucion_temporal || null 
                    };

                    const response = await fetch('/tickets/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(formData)
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion('Cambios guardados correctamente', 'success');
                        
                        // Actualizar los datos seleccionados
                        if (data.ticket) {
                            const estatusAnterior = this.selected.estatus;
                            
                            this.selected.prioridad = data.ticket.Prioridad;
                            this.selected.estatus = data.ticket.Estatus;
                            this.ticketEstatus = data.ticket.Estatus;

                            // Re-habilitar/bloquear el editor según el nuevo estatus (no depende del tablero)
                            this.$nextTick(() => this.actualizarEstadoEditor());

                            // Determinar la nueva categoría basada en el estatus
                            let nuevaCategoria = '';
                            let nuevoEstatusTexto = '';
                            if (data.ticket.Estatus === 'Pendiente' || data.ticket.Estatus === 'Nuevo') {
                                nuevaCategoria = 'nuevos';
                                nuevoEstatusTexto = 'Pendiente';
                            } else if (data.ticket.Estatus === 'En progreso' || data.ticket.Estatus === 'Proceso') {
                                nuevaCategoria = 'proceso';
                                nuevoEstatusTexto = 'En progreso';
                            } else if (data.ticket.Estatus === 'Cerrado' || data.ticket.Estatus === 'Resuelto') {
                                nuevaCategoria = 'resueltos';
                                nuevoEstatusTexto = 'Cerrado';
                            }
                            
                            // Mover la tarjeta inmediatamente antes de que Livewire re-renderice
                            this.actualizarVistasDespuesDeGuardar(data.ticket, estatusAnterior, nuevaCategoria, nuevoEstatusTexto);
                            
                            // Forzar actualización del componente Livewire después de mover
                            setTimeout(() => {
                                if (typeof Livewire !== 'undefined') {
                                    Livewire.emit('ticket-estatus-actualizado');
                                }
                            }, 1000);
                        }
                    } else {
                        this.mostrarNotificacion(data.message || 'Error al guardar los cambios', 'error');
                    }
                } catch (error) {
                    console.error('Error guardando cambios:', error);
                    this.mostrarNotificacion('Error al guardar los cambios', 'error');
                } finally {
                    this.guardandoTicket = false;
                }
            },

            actualizarVistasDespuesDeGuardar(ticketData, estatusAnterior, nuevaCategoria, nuevoEstatusTexto) {
                // Esta función actualiza todas las vistas sin recargar la página
                // Determinar la categoría anterior
                let categoriaAnterior = '';
                if (estatusAnterior === 'Pendiente' || estatusAnterior === 'Nuevo') {
                    categoriaAnterior = 'nuevos';
                } else if (estatusAnterior === 'En progreso' || estatusAnterior === 'Proceso') {
                    categoriaAnterior = 'proceso';
                } else if (estatusAnterior === 'Cerrado' || estatusAnterior === 'Resuelto') {
                    categoriaAnterior = 'resueltos';
                }
                
                const estatusCambio = estatusAnterior !== ticketData.Estatus;
                
                if (!estatusCambio || !nuevaCategoria || !categoriaAnterior) {
                    return;
                }
                
                // Ejecutar inmediatamente sin esperar $nextTick para mover más rápido
                this.$nextTick(() => {
                    // Buscar todos los elementos con el mismo ticket-id (puede haber múltiples en diferentes vistas)
                    const ticketElements = document.querySelectorAll(`[data-ticket-id="${this.selected.id}"]`);
                    
                    if (ticketElements.length === 0) {
                        return;
                    }
                    
                    // Actualizar contadores antes de mover las tarjetas
                    if (estatusCambio && categoriaAnterior && nuevaCategoria) {
                        // Actualizar contadores en el objeto ticketsLista
                        if (this.ticketsLista[categoriaAnterior] > 0) {
                            this.ticketsLista[categoriaAnterior]--;
                        }
                        if (!this.ticketsLista[nuevaCategoria]) {
                            this.ticketsLista[nuevaCategoria] = 0;
                        }
                        this.ticketsLista[nuevaCategoria]++;
                        
                        // Actualizar contadores en los headers del Kanban
                        const headerAnterior = document.querySelector(`[data-categoria-header="${categoriaAnterior}"]`);
                        const headerNuevo = document.querySelector(`[data-categoria-header="${nuevaCategoria}"]`);
                        if (headerAnterior) {
                            const nuevoValor = Math.max(0, parseInt(headerAnterior.textContent.trim()) - 1);
                            headerAnterior.textContent = nuevoValor;
                        }
                        if (headerNuevo) {
                            const nuevoValor = parseInt(headerNuevo.textContent.trim()) + 1;
                            headerNuevo.textContent = nuevoValor;
                        }
                    }
                                
                                ticketElements.forEach(ticketElement => {
                                    // Actualizar atributos data-* del elemento
                        ticketElement.setAttribute('data-ticket-prioridad', ticketData.Prioridad);
                        
                        // Actualizar el atributo del responsable si está disponible
                        if (ticketData.ResponsableTI && this.ticketResponsableTI) {
                            // Buscar el nombre del responsable en el select
                            const responsableSelect = document.getElementById('responsable-select');
                            if (responsableSelect) {
                                const option = responsableSelect.querySelector(`option[value="${this.ticketResponsableTI}"]`);
                                if (option) {
                                    ticketElement.setAttribute('data-ticket-responsable', option.textContent.trim());
                                }
                            }
                        }
                                    
                        // Si el estatus cambió, mover el ticket entre secciones (kanban y lista)
                        if (estatusCambio && nuevaCategoria && categoriaAnterior) {
                            // Solo mover si el elemento tiene data-categoria
                            if (ticketElement.hasAttribute('data-categoria')) {
                                const categoriaActual = ticketElement.getAttribute('data-categoria');
                                
                                // Si está en una categoría diferente, moverlo físicamente
                                if (categoriaActual !== nuevaCategoria) {
                                    // Determinar si es vista kanban o lista
                                    const esVistaKanban = ticketElement.closest('[x-show*="kanban"]') || ticketElement.closest('.kanban-root');
                                    const esVistaLista = ticketElement.closest('[x-show*="lista"]');
                                    
                                    let contenedorNuevaSeccion = null;
                                    
                                    if (esVistaKanban) {
                                        // Mover en vista kanban - usar la misma función que actualizarTicketsEnDOM
                                        contenedorNuevaSeccion = this.encontrarContenedorCategoria(nuevaCategoria, 'kanban');
                                    } else if (esVistaLista) {
                                        // Mover en vista lista - usar la misma función que actualizarTicketsEnDOM
                                        contenedorNuevaSeccion = this.encontrarContenedorCategoria(nuevaCategoria, 'lista');
                                    }
                                    
                                    if (contenedorNuevaSeccion && contenedorNuevaSeccion.isConnected) {
                                        // Marcar que este ticket se está moviendo manualmente
                                        this.ticketsMoviendose.add(String(this.selected.id));
                                        
                                        // Encontrar el contenedor anterior para remover el elemento de ahí
                                        // Para kanban: .flex-1.overflow-y-auto, para lista: .divide-y
                                        const contenedorAnterior = ticketElement.closest('.flex-1.overflow-y-auto') || 
                                                                  ticketElement.closest('.divide-y.divide-gray-200') ||
                                                                  ticketElement.closest('.divide-y') ||
                                                                  (ticketElement.parentElement ? ticketElement.parentElement : null);
                                        
                                        // Remover el elemento del contenedor anterior si existe y es diferente al nuevo
                                        if (contenedorAnterior && contenedorAnterior !== contenedorNuevaSeccion && ticketElement.parentElement) {
                                            ticketElement.remove();
                                        }
                                        
                                        // Remover placeholder si existe
                                        const placeholder = contenedorNuevaSeccion.querySelector('[data-empty-placeholder]');
                                        if (placeholder) {
                                            placeholder.remove();
                                        }
                                        
                                        // Actualizar el atributo antes de mover
                                        ticketElement.setAttribute('data-categoria', nuevaCategoria);
                                        
                                        // Actualizar wire:key con el nuevo estatus para que Livewire lo rastree correctamente
                                        const isLista = ticketElement.closest('[x-show*="lista"]') || ticketElement.closest('.divide-y');
                                        const prefix = isLista ? 'ticket-lista-' : 'ticket-';
                                        ticketElement.setAttribute('wire:key', `${prefix}${this.selected.id}-${nuevoEstatusTexto}`);
                                        
                                        // Agregar el elemento al nuevo contenedor
                                        contenedorNuevaSeccion.appendChild(ticketElement);
                                        
                                        // Si el ticket se mueve a "proceso", actualizar el contenido para mostrar responsable y tiempo
                                        if (nuevaCategoria === 'proceso') {
                                            // Primero asegurarnos de que el atributo del responsable esté actualizado
                                            if (this.ticketResponsableTI) {
                                                const responsableSelect = document.getElementById('responsable-select');
                                                if (responsableSelect) {
                                                    const option = responsableSelect.querySelector(`option[value="${this.ticketResponsableTI}"]`);
                                                    if (option) {
                                                        ticketElement.setAttribute('data-ticket-responsable', option.textContent.trim());
                                                    }
                                                }
                                            }
                                            
                                            // Usar setTimeout para asegurar que el elemento ya esté en el DOM
                                            setTimeout(() => {
                                                this.actualizarContenidoTicketProceso(ticketElement, ticketData);
                                            }, 200);
                                        } else {
                                            // Si se mueve fuera de proceso, remover las secciones de responsable y tiempo
                                            this.removerContenidoTicketProceso(ticketElement);
                                        }
                                        
                                        // Pequeña animación de entrada
                                        ticketElement.style.transition = 'all 0.25s ease';
                                        ticketElement.style.opacity = '0.85';
                                        requestAnimationFrame(() => {
                                            if (ticketElement && ticketElement.parentElement) {
                                                ticketElement.style.opacity = '1';
                                            }
                                            // Remover de la lista de tickets moviéndose después de la animación
                                            setTimeout(() => {
                                                this.ticketsMoviendose.delete(String(this.selected.id));
                                            }, 500);
                                        });
                                    } else {
                                        // Si no se encuentra el contenedor, solo actualizar el atributo
                                        ticketElement.setAttribute('data-categoria', nuevaCategoria);
                                    }
                                }
                                        }
                                    }
                                    
                                    // Actualizar el badge de prioridad visualmente (todas las vistas)
                                    const badgesPrioridad = ticketElement.querySelectorAll('.text-xs.font-semibold.px-2, .text-xs.font-semibold.px-2.py-0\\.5, .text-xs.font-semibold.px-2.py-1');
                                    badgesPrioridad.forEach(badge => {
                                        // Verificar si es un badge de prioridad (no de estatus)
                                        const texto = badge.textContent.trim();
                                        if (texto === 'Baja' || texto === 'Media' || texto === 'Alta' || 
                                            texto === this.selected.prioridad || 
                                (badge.classList.contains('rounded-full') && !badge.textContent.includes('Ticket'))) {
                                badge.textContent = ticketData.Prioridad;
                                            // Actualizar clases de color según prioridad
                                            const clasesBase = badge.className.split(' ').filter(c => 
                                                !c.startsWith('bg-') && !c.startsWith('text-')
                                            ).join(' ');
                                const clasesColor = ticketData.Prioridad === 'Baja' 
                                                ? 'bg-green-200 text-green-600' 
                                    : ticketData.Prioridad === 'Media' 
                                                ? 'bg-yellow-200 text-yellow-600' 
                                                : 'bg-red-200 text-red-600';
                                            badge.className = clasesBase + ' ' + clasesColor;
                                        }
                                    });
                                });
                                
                                // Actualizar los datos de la tabla (siempre, para que se refleje en todas las vistas)
                                this.prepararDatosTabla();
                                
                    // Actualizar manualmente el estatus en ticketsTabla
                        setTimeout(() => {
                                    if (this.ticketsTabla && this.ticketsTabla.length > 0) {
                                        const ticketEnTabla = this.ticketsTabla.find(t => t.id == this.selected.id);
                            if (ticketEnTabla) {
                                ticketEnTabla.prioridad = ticketData.Prioridad;
                                if (nuevoEstatusTexto) {
                                            ticketEnTabla.estatus = nuevoEstatusTexto;
                                }
                                        }
                                    }
                                }, 100);
                                
                                // Actualizar estado del editor
                                this.actualizarEstadoEditor();
                            });
            },

            abrirModalDesdeElemento(elemento) {
                // Buscar el elemento padre que tenga los atributos data-ticket-*
                // Esto es necesario porque el clic puede ser en un elemento hijo
                let elementoConDatos = elemento;
                if (!elemento.dataset || !elemento.dataset.ticketId) {
                    elementoConDatos = elemento.closest('[data-ticket-id]');
                }
                
                if (!elementoConDatos) {
                    console.error('No se encontró el elemento con datos del ticket');
                    return;
                }
                
                // Usar getAttribute para asegurar que obtenemos los valores correctos
                const datos = {
                    id: elementoConDatos.getAttribute('data-ticket-id') || elementoConDatos.dataset.ticketId,
                    asunto: elementoConDatos.getAttribute('data-ticket-asunto') || elementoConDatos.dataset.ticketAsunto || `Ticket #${elementoConDatos.getAttribute('data-ticket-id')}`,
                    descripcion: elementoConDatos.getAttribute('data-ticket-descripcion') || elementoConDatos.dataset.ticketDescripcion || '',
                    prioridad: elementoConDatos.getAttribute('data-ticket-prioridad') || elementoConDatos.dataset.ticketPrioridad || 'Media',
                    empleado: elementoConDatos.getAttribute('data-ticket-empleado') || elementoConDatos.dataset.ticketEmpleado || '',
                    anydesk: elementoConDatos.getAttribute('data-ticket-anydesk') || elementoConDatos.dataset.ticketAnydesk || '',
                    numero: elementoConDatos.getAttribute('data-ticket-numero') || elementoConDatos.dataset.ticketNumero || '',
                    correo: elementoConDatos.getAttribute('data-ticket-correo') || elementoConDatos.dataset.ticketCorreo || '',
                    puesto: elementoConDatos.getAttribute('data-ticket-puesto') || elementoConDatos.dataset.ticketPuesto || '',
                    gerencia: elementoConDatos.getAttribute('data-ticket-gerencia') || elementoConDatos.dataset.ticketGerencia || '',
                    departamento: elementoConDatos.getAttribute('data-ticket-departamento') || elementoConDatos.dataset.ticketDepartamento || '',
                    fecha: elementoConDatos.getAttribute('data-ticket-fecha') || elementoConDatos.dataset.ticketFecha || new Date().toLocaleString('es-ES'),
                    imagen: elementoConDatos.getAttribute('data-ticket-imagen') || elementoConDatos.dataset.ticketImagen || ''
                };
                
                // Decodificar HTML entities en la descripción
                if (datos.descripcion) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = datos.descripcion;
                    datos.descripcion = tempDiv.textContent || tempDiv.innerText || datos.descripcion;
                }
                
                this.abrirModal(datos);
            },

            cerrarModal() {
                this.mostrar = false;
                this.mensajes = [];
                this.nuevoMensaje = '';
                this.asuntoCorreo = '';
                this.mostrarCc = false;
                this.mostrarBcc = false;
                this.prioridadCorreo = 'normal';
                this.correoCc = '';
                this.correoBcc = '';
                // Detener verificación automática de mensajes nuevos
                this.detenerVerificacionMensajes();
                // Limpiar el editor TinyMCE
                if (this.tinyMCEInstance) {
                    this.tinyMCEInstance.setContent('');
                }
                // Limpiar archivos adjuntos
                this.archivosAdjuntos = [];
                const adjuntosInput = document.getElementById('adjuntos');
                if (adjuntosInput) {
                    adjuntosInput.value = '';
                }
                setTimeout(() => this.selected = {}, 200);
            },

            obtenerContenidoEditor() {
                // Intentar obtener contenido de TinyMCE primero
                if (this.tinyMCEInstance) {
                    try {
                        const contenido = this.tinyMCEInstance.getContent();
                        // Remover etiquetas vacías y espacios HTML
                        const textoLimpio = contenido
                            .replace(/<p><\/p>/g, '')
                            .replace(/<p>\s*<\/p>/g, '')
                            .replace(/<br\s*\/?>/gi, '')
                            .replace(/&nbsp;/g, ' ')
                            .trim();
                        // Si después de limpiar hay contenido, retornar el contenido original
                        if (textoLimpio.length > 0) {
                            return contenido;
                        }
                    } catch (e) {
                        console.warn('Error obteniendo contenido de TinyMCE:', e);
                    }
                }
                // Fallback: usar nuevoMensaje si está disponible
                if (this.nuevoMensaje && this.nuevoMensaje.trim().length > 0) {
                    return this.nuevoMensaje;
                }
                return '';
            },
            
            tieneContenido() {
                // Verificar primero si TinyMCE está inicializado y tiene contenido
                if (this.tinyMCEInstance) {
                    try {
                        const contenido = this.tinyMCEInstance.getContent();
                        if (contenido) {
                            // Remover etiquetas HTML y espacios
                            const textoLimpio = contenido
                                .replace(/<[^>]*>/g, '') // Remover todas las etiquetas HTML
                                .replace(/&nbsp;/g, ' ')
                                .replace(/&amp;/g, '&')
                                .replace(/&lt;/g, '<')
                                .replace(/&gt;/g, '>')
                                .replace(/\s+/g, ' ')
                                .trim();
                            if (textoLimpio.length > 0) {
                                return true;
                            }
                        }
                    } catch (e) {
                        console.warn('Error verificando contenido de TinyMCE:', e);
                    }
                }
                
                // Fallback: verificar nuevoMensaje
                if (this.nuevoMensaje) {
                    const textoLimpio = this.nuevoMensaje
                        .replace(/<[^>]*>/g, '')
                        .replace(/&nbsp;/g, ' ')
                        .replace(/\s+/g, ' ')
                        .trim();
                    return textoLimpio.length > 0;
                }
                
                return false;
            },

            limpiarEditor() {
                this.nuevoMensaje = '';
                if (this.tinyMCEInstance) {
                    this.tinyMCEInstance.setContent('');
                }
                this.archivosAdjuntos = [];
                const adjuntosInput = document.getElementById('adjuntos');
                if (adjuntosInput) {
                    adjuntosInput.value = '';
                }
            },

                            verificarCambioEstatus(nuevoEstatus) {
                if (nuevoEstatus === 'Cerrado') {
                    Swal.fire({
                        title: 'Cerrar Ticket',
                        text: 'Por favor, describe la resolución del problema:',
                        input: 'textarea',
                        inputPlaceholder: 'Escribe aquí cómo se solucionó...',
                        showCancelButton: true,
                        confirmButtonText: 'Cerrar Ticket',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#10b981',
                        cancelButtonColor: '#ef4444',
                        inputValidator: (value) => {
                            if (!value) return 'Debes escribir una resolución';
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // 1. Guardar en temporal para enviar al backend
                            this.selected.resolucion_temporal = result.value;
                            
                            // 2. CORRECCIÓN IMPORTANTE: Actualizar la vista inmediatamente
                            this.selected.resolucion = result.value; 
                            
                            this.ticketEstatus = 'Cerrado';
                            // Forzamos actualización de Alpine para que detecte el cambio de estatus
                            this.selected.estatus = 'Cerrado'; 
                        } else {
                            // Si cancela, regresamos el select al estado anterior
                            this.ticketEstatus = this.selected.estatus; 
                        }
                    });
                }
            },

            manejarArchivosSeleccionados(event) {
                const input = event.target;
                const files = Array.from(input.files || []);
                
                if (files.length === 0) {
                    this.archivosAdjuntos = [];
                    return;
                }
                
                // Validar y agregar archivos
                this.procesarArchivos(files);
            },
            
            procesarArchivos(files) {
                const archivosValidos = [];
                const tiposPermitidos = ['.pdf', '.doc', '.docx', '.txt', '.jpg', '.jpeg', '.png', '.gif', '.xlsx', '.xls'];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                files.forEach(file => {
                    const extension = '.' + file.name.split('.').pop().toLowerCase();
                    if (tiposPermitidos.includes(extension)) {
                        if (file.size <= maxSize) {
                            archivosValidos.push(file);
                        } else {
                            alert(`El archivo "${file.name}" excede el tamaño máximo de 10MB`);
                        }
                    } else {
                        alert(`El archivo "${file.name}" no es un tipo permitido`);
                    }
                });
                
                // Agregar archivos válidos a la lista
                archivosValidos.forEach(file => {
                    this.archivosAdjuntos.push(file);
                });
                
                // Actualizar el input file
                const adjuntosInput = document.getElementById('adjuntos');
                if (adjuntosInput) {
                    const dataTransfer = new DataTransfer();
                    this.archivosAdjuntos.forEach(archivo => {
                        dataTransfer.items.add(archivo);
                    });
                    adjuntosInput.files = dataTransfer.files;
                }
                
                // Forzar actualización de Alpine.js
            },
            
            handleDragOver(event) {
                if (this.selected.estatus === 'Cerrado' || this.ticketEstatus === 'Cerrado' || 
                    (this.selected.estatus === 'Pendiente' || this.ticketEstatus === 'Pendiente')) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                const dragArea = document.getElementById('drag-drop-area');
                if (dragArea) {
                    dragArea.style.backgroundColor = 'rgba(59, 130, 246, 0.15)';
                    dragArea.style.borderColor = '#3B82F6';
                    dragArea.style.borderStyle = 'solid';
                }
            },
            
            handleDragLeave(event) {
                if (this.selected.estatus === 'Cerrado' || this.ticketEstatus === 'Cerrado' || 
                    (this.selected.estatus === 'Pendiente' || this.ticketEstatus === 'Pendiente')) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                const dragArea = document.getElementById('drag-drop-area');
                if (dragArea && !dragArea.contains(event.relatedTarget)) {
                    dragArea.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
                    dragArea.style.borderColor = 'rgba(59, 130, 246, 0.3)';
                    dragArea.style.borderStyle = 'dashed';
                }
            },
            
            handleDrop(event) {
                if (this.selected.estatus === 'Cerrado' || this.ticketEstatus === 'Cerrado' || 
                    (this.selected.estatus === 'Pendiente' || this.ticketEstatus === 'Pendiente')) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                
                const dragArea = document.getElementById('drag-drop-area');
                if (dragArea) {
                    dragArea.style.backgroundColor = 'rgba(59, 130, 246, 0.05)';
                    dragArea.style.borderColor = 'rgba(59, 130, 246, 0.3)';
                    dragArea.style.borderStyle = 'dashed';
                }
                
                const files = Array.from(event.dataTransfer.files || []);
                if (files.length > 0) {
                    this.procesarArchivos(files);
                }
            },

            eliminarArchivo(index) {
                if (index >= 0 && index < this.archivosAdjuntos.length) {
                    this.archivosAdjuntos.splice(index, 1);
                    
                    // Actualizar el input file para reflejar los cambios
                    const adjuntosInput = document.getElementById('adjuntos');
                    if (adjuntosInput) {
                        // Crear un nuevo DataTransfer para actualizar el input
                        const dataTransfer = new DataTransfer();
                        this.archivosAdjuntos.forEach(archivo => {
                            dataTransfer.items.add(archivo);
                        });
                        adjuntosInput.files = dataTransfer.files;
                    }
                }
            },

            formatearTamañoArchivo(bytes) {
                if (!bytes || bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            },

            actualizarEstadoEditor() {
                const estaCerrado = this.selected.estatus === 'Cerrado' || this.ticketEstatus === 'Cerrado';
                const estaPendiente = this.selected.estatus === 'Pendiente' || this.ticketEstatus === 'Pendiente';
                
                if (this.tinyMCEInstance) {
                    try {
                        // Cambiar el modo del editor a readonly si está cerrado o pendiente
                        if (estaCerrado || estaPendiente) {
                            this.tinyMCEInstance.mode.set('readonly');
                        } else {
                            this.tinyMCEInstance.mode.set('design');
                        }
                    } catch (e) {
                        console.warn('Error actualizando estado del editor:', e);
                    }
                }
                
                // También deshabilitar el textarea si TinyMCE no está inicializado
                const textarea = document.getElementById('editor-mensaje');
                if (textarea) {
                    const isDarkMode = document.documentElement.classList.contains('dark');
                    textarea.disabled = estaCerrado || estaPendiente;
                    if (estaCerrado || estaPendiente) {
                        textarea.style.cursor = 'not-allowed';
                        textarea.style.backgroundColor = isDarkMode ? '#374151' : '#f3f4f6';
                    } else {
                        textarea.style.cursor = 'text';
                        textarea.style.backgroundColor = isDarkMode ? '#374151' : 'white';
                        textarea.style.color = isDarkMode ? '#f9fafb' : '#000000';
                    }
                }
            },

            async cargarMensajes() {
                if (!this.selected.id) return;

                try {
                    const response = await fetch(`/tickets/chat-messages?ticket_id=${this.selected.id}`);
                    const data = await response.json();
                    
                    
                    if (data.success) {
                        this.mensajes = data.messages;
                        // Actualizar el último mensaje ID para la verificación automática
                        if (this.mensajes && this.mensajes.length > 0) {
                            this.ultimoMensajeId = Math.max(...this.mensajes.map(m => m.id));
                        } else {
                            this.ultimoMensajeId = 0;
                        }
                        this.marcarMensajesComoLeidos();
                        this.scrollToBottom();
                    
                        // Actualizar estadísticas después de cargar mensajes
                    this.estadisticas = await this.obtenerEstadisticasCorreos();
                    } else {
                        console.error('Error en la API:', data.message);
                    }
                } catch (error) {
                    console.error('Error cargando mensajes:', error);
                }
            },

            iniciarVerificacionMensajes() {
                // La verificación de mensajes ahora se maneja con wire:poll
                // Se elimina el setInterval ya que wire:poll actualiza automáticamente
                
                // Verificar inmediatamente al iniciar
                this.verificarMensajesNuevos();
            },

            detenerVerificacionMensajes() {
                // Ya no hay intervalo que limpiar, wire:poll maneja las actualizaciones
                this.ultimoMensajeId = 0;
            },

            async verificarMensajesNuevos() {
                if (!this.selected.id || !this.mostrar) return;

                try {
                    const response = await fetch(
                        `/tickets/verificar-mensajes-nuevos?ticket_id=${this.selected.id}&ultimo_mensaje_id=${this.ultimoMensajeId}`,
                        {
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json'
                            }
                        }
                    );

                    const data = await response.json();

                    if (data.success && data.tiene_nuevos) {
                        // Hay mensajes nuevos, recargar la lista de mensajes
                        await this.cargarMensajes();
                    }
                } catch (error) {
                    // Silenciar errores de verificación para no molestar al usuario
                    // Solo loguear en consola para debugging
                    console.debug('Error verificando mensajes nuevos:', error);
                }
            },

            normalizarAsunto(asunto) {
                // Asegurar que el asunto siempre tenga la nomenclatura con el ID del ticket
                const ticketId = this.selected.id;
                const patronTicket = new RegExp(`Ticket\\s*#?\\s*${ticketId}`, 'i');
                
                if (!patronTicket.test(asunto)) {
                    // Si no tiene el ID del ticket, agregarlo
                    if (asunto.trim().startsWith('Re:')) {
                        return `Re: Ticket #${ticketId} ${asunto.replace(/^Re:\s*/i, '').trim()}`;
                    } else {
                        return `Re: Ticket #${ticketId} ${asunto.trim()}`;
                    }
                }
                // Si ya tiene el ID, mantenerlo pero asegurar formato consistente
                return asunto.replace(/Ticket\s*#?\s*(\d+)/i, `Ticket #${ticketId}`);
            },

            async enviarRespuesta() {
                // Evitar envíos simultáneos
                if (this.cargando) return;
                this.cargando = true;
                // 1. Validar que el ticket no esté en Pendiente
                if (this.selected.estatus === 'Pendiente' || this.ticketEstatus === 'Pendiente') {
                    this.mostrarNotificacion('No se pueden enviar mensajes cuando el ticket está en estado "Pendiente". Cambia el estado a "En progreso".', 'error');
                    this.cargando = false;
                    return;
                }
                
                // 2. Obtener el contenido HTML de TinyMCE de forma segura
                let contenidoMensaje = '';
                if (this.tinyMCEInstance) {
                    contenidoMensaje = this.tinyMCEInstance.getContent();
                    if (contenidoMensaje === '<p><br></p>' || contenidoMensaje === '<p></p>' || contenidoMensaje.trim() === '') {
                        contenidoMensaje = '';
                    }
                } else {
                    contenidoMensaje = this.nuevoMensaje;
                }

                // 3. Validaciones antes de enviar
                if (!contenidoMensaje.trim()) {
                    this.mostrarNotificacion('El mensaje no puede estar vacío', 'error');
                    this.cargando = false;
                    return;
                }
                
                if (!this.asuntoCorreo.trim()) {
                    this.mostrarNotificacion('El asunto es requerido', 'error');
                    this.cargando = false;
                    return;
                }

                try {
                    const asuntoNormalizado = this.normalizarAsunto(this.asuntoCorreo);
                    this.asuntoCorreo = asuntoNormalizado;
                    
                    const formData = new FormData();
                    formData.append('ticket_id', this.selected.id);
                    formData.append('mensaje', contenidoMensaje);
                    formData.append('asunto', asuntoNormalizado);

                    if (this.archivosAdjuntos && this.archivosAdjuntos.length > 0) {
                        this.archivosAdjuntos.forEach((archivoProxy) => {
                            // Alpine.raw() saca el archivo real de la variable reactiva
                            const archivoReal = (typeof Alpine !== 'undefined' && Alpine.raw) 
                                ? Alpine.raw(archivoProxy) 
                                : archivoProxy;
                            formData.append('adjuntos[]', archivoReal);
                        });
                    }

                    // 5. Enviar la petición
                    const response = await fetch('/tickets/enviar-respuesta', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json' // OBLIGA al backend a responder el error real
                        }
                    });

                    const data = await response.json();

                    // 6. Procesar la respuesta
                    if (response.ok && data.success) {
                        // Limpiar campos y editor
                        this.nuevoMensaje = '';
                        if (this.tinyMCEInstance) {
                            this.tinyMCEInstance.setContent('');
                        }
                        
                        // Limpiar estado de adjuntos
                        this.archivosAdjuntos = [];
                        const adjuntosInput = document.getElementById('adjuntos');
                        if (adjuntosInput) adjuntosInput.value = '';
                        
                        this.mostrarNotificacion(data.message || 'Respuesta enviada exitosamente', 'success');
                        
                        // Refrescar el chat
                        await this.cargarMensajes();
                    } else {
                        // Muestra el mensaje de error EXACTO del servidor
                        throw new Error(data.message || 'Error del servidor al procesar la solicitud');
                    }
                } catch (error) {
                    console.error('Error enviando respuesta:', error);
                    this.mostrarNotificacion(error.message || 'Error enviando respuesta. Verifica tu conexión.', 'error');
                } finally {
                    this.cargando = false;
                }
            },


            async marcarMensajesComoLeidos() {
                if (!this.selected.id) return;

                try {
                    await fetch('/tickets/marcar-leidos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id
                        })
                    });
                } catch (error) {
                    console.error('Error marcando mensajes como leídos:', error);
                }
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const container = document.getElementById('chat-container');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                });
            },

            mostrarNotificacion(mensaje, tipo) {
                // Remover notificaciones anteriores si existen
                const notificacionesAnteriores = document.querySelectorAll('.ticket-notification');
                notificacionesAnteriores.forEach(n => n.remove());
                
                let bgColor = 'bg-red-500';
                if (tipo === 'success') bgColor = 'bg-green-500';
                else if (tipo === 'info') bgColor = 'bg-blue-500';
                
                const notification = document.createElement('div');
                notification.className = `ticket-notification p-4 rounded-lg shadow-2xl flex items-center gap-3 min-w-[300px] max-w-md ${bgColor} text-white`;
                
                // Establecer estilos inline para asegurar que aparezca por encima del modal (z-50)
                // Usar un z-index muy alto y position fixed
                notification.style.position = 'fixed';
                notification.style.top = '1rem';
                notification.style.right = '1rem';
                notification.style.zIndex = '999999'; // Mucho más alto que el modal (z-50)
                notification.style.pointerEvents = 'auto';
                
                // Estilos iniciales para animación
                notification.style.transform = 'translateX(400px)';
                notification.style.opacity = '0';
                notification.style.transition = 'all 0.3s ease-in-out';
                
                // Icono según el tipo
                let icono = '';
                if (tipo === 'success') {
                    icono = '<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                } else if (tipo === 'info') {
                    icono = '<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                } else {
                    icono = '<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                }
                
                notification.innerHTML = `
                    ${icono}
                    <span class="flex-1 font-medium">${mensaje}</span>
                `;
                
                // Agregar directamente al body para evitar problemas de contexto de apilamiento
                // Asegurarse de que esté fuera de cualquier contenedor del modal
                document.body.appendChild(notification);
                
                // Forzar el z-index después de agregar al DOM para asegurar que se aplique
                requestAnimationFrame(() => {
                    notification.style.zIndex = '999999';
                });
                
                // Animación de entrada
                setTimeout(() => {
                    notification.style.transform = 'translateX(0)';
                    notification.style.opacity = '1';
                }, 10);
                
                // Remover después de 4 segundos con animación
                setTimeout(() => {
                    notification.style.transform = 'translateX(400px)';
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }, 4000);
            },

            formatearFecha(fecha) {
                return new Date(fecha).toLocaleString('es-ES');
            },

            async verificarTicketsExcedidos(mostrarPopup = true) {
                try {
                    this.cargandoExcedidos = true;
                    const response = await fetch('{{ route("tickets.excedidos") }}', {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success && data.tickets && data.tickets.length > 0) {
                        this.ticketsExcedidos = data.tickets;
                        
                        // Mostrar popup si hay tickets excedidos
                        // Solo iniciar timer cuando se abre por primera vez, no al actualizar
                        if (mostrarPopup && this.ticketsExcedidos.length > 0) {
                            const yaEstabaAbierto = this.mostrarPopupExcedidos;
                            this.mostrarPopupExcedidos = true;
                            if (!yaEstabaAbierto) {
                                this.iniciarTimerPopup();
                            }
                        }
                    } else {
                        // Si no hay tickets excedidos, ocultar el popup
                        if (this.ticketsExcedidos.length > 0) {
                        }
                        this.ticketsExcedidos = [];
                        this.mostrarPopupExcedidos = false;
                    }
                } catch (error) {
                } finally {
                    this.cargandoExcedidos = false;
                }
            },

            cerrarPopupExcedidos() {
                // Limpiar el timer si existe
                if (this.timerPopupExcedidos) {
                    clearTimeout(this.timerPopupExcedidos);
                    this.timerPopupExcedidos = null;
                }
                // Limpiar el intervalo del contador si existe
                if (this.intervaloContadorPopup) {
                    clearInterval(this.intervaloContadorPopup);
                    this.intervaloContadorPopup = null;
                }
                this.mostrarPopupExcedidos = false;
            },
            
            iniciarTimerPopup() {
                // Limpiar timer anterior si existe
                if (this.timerPopupExcedidos) {
                    clearTimeout(this.timerPopupExcedidos);
                    this.timerPopupExcedidos = null;
                }
                // Limpiar intervalo del contador anterior si existe
                if (this.intervaloContadorPopup) {
                    clearInterval(this.intervaloContadorPopup);
                    this.intervaloContadorPopup = null;
                }
                
                // Reiniciar contador
                this.tiempoRestantePopup = 10;
                
                // Actualizar contador cada segundo
                this.intervaloContadorPopup = setInterval(() => {
                    this.tiempoRestantePopup--;
                    if (this.tiempoRestantePopup <= 0) {
                        clearInterval(this.intervaloContadorPopup);
                        this.intervaloContadorPopup = null;
                        if (this.timerPopupExcedidos) {
                            clearTimeout(this.timerPopupExcedidos);
                            this.timerPopupExcedidos = null;
                        }
                        this.cerrarPopupExcedidos();
                    }
                }, 1000);
                
                // Cerrar automáticamente después de 10 segundos
                this.timerPopupExcedidos = setTimeout(() => {
                    if (this.intervaloContadorPopup) {
                        clearInterval(this.intervaloContadorPopup);
                        this.intervaloContadorPopup = null;
                    }
                    this.cerrarPopupExcedidos();
                }, 10000); // 10 segundos
            },

            abrirTicketDesdePopup(ticketId) {
                // Buscar el elemento del ticket y abrirlo
                const ticketElement = document.querySelector(`[data-ticket-id="${ticketId}"]`);
                if (ticketElement) {
                    this.abrirModalDesdeElemento(ticketElement);
                    // Cerrar el popup (esto también limpiará el timer)
                    this.cerrarPopupExcedidos();
                }
            },

            obtenerIniciales(nombre) {
                if (!nombre) return '??';
                return nombre.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
            },

            formatearMensaje(mensaje) {
            if (!mensaje) return '';

            // Texto plano → formatear saltos y URLs
            if (!/<[a-z][\s\S]*>/i.test(mensaje)) {
                let formateado = mensaje.replace(/\n/g, '<br>');
                formateado = formateado.replace(
                    /(https?:\/\/[^\s]+)/g,
                    '<a href="$1" target="_blank" class="text-blue-600 hover:underline">$1</a>'
                );
                return formateado;
            }

            // Es HTML → separar texto e imágenes
            const parser = new DOMParser();
            const doc    = parser.parseFromString(mensaje, 'text/html');
            const imgs   = Array.from(doc.querySelectorAll('img'));

            if (imgs.length === 0) {
                return mensaje;
            }

            // Recopilar srcs para el lightbox
            const srcs     = imgs.map(img => img.getAttribute('src'));
            const srcsJson = JSON.stringify(srcs).replace(/"/g, '&quot;');

            // Eliminar las imágenes del HTML para que solo quede el texto
            imgs.forEach(img => {
                // Si el <p> padre solo tenía la imagen, eliminar el párrafo entero para no dejar líneas vacías
                const padre = img.parentElement;
                img.remove();
                if (padre && padre.tagName === 'P' && padre.innerHTML.trim() === '' || 
                    padre && padre.tagName === 'P' && padre.textContent.trim() === '') {
                    padre.remove();
                }
            });

            const textoHTML = doc.body.innerHTML.trim();

            // Construir el grid de miniaturas
            let gridHTML = '<div class="chat-img-grid" style="margin-top:12px;">';
            srcs.forEach((src, i) => {
                gridHTML += `
                    <span class="chat-img-thumb" title="Clic para ampliar" onclick="abrirLightbox(JSON.parse(this.dataset.srcs), ${i})" data-srcs="${srcsJson}">
                        <img src="${src}" alt="Imagen ${i + 1}">
                    </span>`;
            });
            gridHTML += '</div>';

            return (textoHTML ? textoHTML : '') + gridHTML;
        },

            obtenerAdjuntos() {
                if (!this.selected || !this.selected.imagen) return [];
                
                try {
                    // Intentar parsear el JSON
                    const adjuntos = typeof this.selected.imagen === 'string' 
                        ? JSON.parse(this.selected.imagen) 
                        : this.selected.imagen;
                    
                    // Asegurarse de que sea un array
                    return Array.isArray(adjuntos) ? adjuntos : [];
                } catch (e) {
                    // Si no es JSON válido, intentar como string simple
                    if (typeof this.selected.imagen === 'string' && this.selected.imagen.trim() !== '') {
                        return [this.selected.imagen];
                    }
                    return [];
                }
            },

            obtenerNombreArchivo(ruta) {
                if (!ruta) return 'Archivo sin nombre';
                // Extraer el nombre del archivo de la ruta
                const partes = ruta.split('/');
                let nombre = partes[partes.length - 1];
                // Remover el prefijo uniqid_ si existe
                if (nombre.includes('_')) {
                    nombre = nombre.substring(nombre.indexOf('_') + 1);
                }
                return nombre;
            },

            obtenerExtensionArchivo(ruta) {
                if (!ruta) return '';
                const nombre = this.obtenerNombreArchivo(ruta);
                const punto = nombre.lastIndexOf('.');
                if (punto === -1) return 'Sin extensión';
                return nombre.substring(punto + 1).toUpperCase();
            },

            obtenerUrlArchivo(ruta) {
                if (!ruta) return '#';
                
                // 1. Si ya es una URL web válida, retornarla directamente
                if (typeof ruta === 'string' && (ruta.startsWith('http://') || ruta.startsWith('https://'))) {
                    return ruta;
                }
                
                let pathToParse = '';

                // 2. Si la ruta es un objeto (como viene de la BD/tabla tickets)
                if (typeof ruta === 'object' && ruta !== null) {
                    // Si el backend nos mandó la URL pública ya armada
                    if (ruta.url) {
                        return ruta.url;
                    }
                    // Ruta física desde la tabla de tickets (path, storage_path, o name)
                    pathToParse = ruta.storage_path || ruta.path || ruta.name || '';
                } 
                // 3. Si es un string (ruta guardada en imagen/tabla tickets)
                else if (typeof ruta === 'string') {
                    pathToParse = ruta;
                }

                if (pathToParse) {
                    const pathNormalized = pathToParse.toString().replace(/^[\\/]+/, '').replace(/\\/g, '/').trim();
                    if (pathNormalized) {
                        // Usar asset (storageBaseUrl) para evitar problemas con public/storage
                        const base = (this.storageBaseUrl || '').replace(/\/$/, '');
                        return base ? base + '/' + pathNormalized : '/storage/' + pathNormalized;
                    }
                }
                
                return '#';
            },

            aplicarFormato(tipo) {
                if (!this.tinyMCEInstance) {
                    this.inicializarTinyMCE();
                    return;
                }
                
                switch(tipo) {
                    case 'bold':
                        this.tinyMCEInstance.execCommand('mceToggleFormat', false, 'bold');
                        break;
                    case 'italic':
                        this.tinyMCEInstance.execCommand('mceToggleFormat', false, 'italic');
                        break;
                    case 'underline':
                        this.tinyMCEInstance.execCommand('mceToggleFormat', false, 'underline');
                        break;
                }
            },

            getTipoMensaje(remitente) {
                return remitente === 'soporte' ? 'soporte' : 'usuario';
            },

            async sincronizarCorreos() {
                if (!this.selected.id) return;

                this.sincronizando = true;

                try {
                    const response = await fetch('/tickets/sincronizar-correos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(data.message, 'success');
                        
                        // Si hay mensajes en la respuesta, actualizarlos directamente
                        if (data.mensajes) {
                            this.mensajes = data.mensajes;
                            this.scrollToBottom();
                        } else {
                        // Recargar mensajes después de la sincronización
                        await this.cargarMensajes();
                        }
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error sincronizando correos:', error);
                    this.mostrarNotificacion('Error sincronizando correos', 'error');
                } finally {
                    this.sincronizando = false;
                }
            },

            async obtenerEstadisticasCorreos() {
                if (!this.selected.id) return;

                try {
                    const response = await fetch(`/tickets/estadisticas-correos?ticket_id=${this.selected.id}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        return data.estadisticas;
                    }
                } catch (error) {
                    console.error('Error obteniendo estadísticas:', error);
                }
                
                return null;
            },

            async diagnosticarCorreos() {
                if (!this.selected.id) return;

                try {
                    const response = await fetch(`/tickets/diagnosticar-correos?ticket_id=${this.selected.id}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        
                        // Mostrar diagnóstico en una ventana emergente
                        let mensaje = 'Diagnóstico de Correos:\n\n';
                        mensaje += `SMTP Host: ${data.diagnostico.smtp.host}\n`;
                        mensaje += `SMTP Port: ${data.diagnostico.smtp.port}\n`;
                        mensaje += `IMAP Host: ${data.diagnostico.imap.host}\n`;
                        mensaje += `IMAP Port: ${data.diagnostico.imap.port}\n`;
                        mensaje += `Conexión IMAP: ${data.diagnostico.imap_connection}\n\n`;
                        
                        if (data.diagnostico.mensajes_bd) {
                            mensaje += `Mensajes en BD:\n`;
                            mensaje += `- Total: ${data.diagnostico.mensajes_bd.total}\n`;
                            mensaje += `- Enviados: ${data.diagnostico.mensajes_bd.enviados}\n`;
                            mensaje += `- Recibidos: ${data.diagnostico.mensajes_bd.recibidos}\n`;
                            mensaje += `- Correos: ${data.diagnostico.mensajes_bd.correos}\n`;
                        }
                        
                        alert(mensaje);
                    } else {
                        this.mostrarNotificacion('Error en diagnóstico: ' + data.message, 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error ejecutando diagnóstico', 'error');
                }
            },

            async enviarInstrucciones() {
                if (!this.selected.id) return;

                try {
                    const response = await fetch('/tickets/enviar-instrucciones', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(data.message, 'success');
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error enviando instrucciones', 'error');
                }
            },

            async agregarRespuestaManual() {
                if (!this.selected.id || !this.respuestaManual.mensaje.trim()) return;

                try {
                    const response = await fetch('/tickets/agregar-respuesta-manual', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id,
                            mensaje: this.respuestaManual.mensaje,
                            nombre_emisor: this.respuestaManual.nombre || this.selected.empleado,
                            correo_emisor: this.respuestaManual.correo || this.selected.correo
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(data.message, 'success');
                        
                        // Actualizar mensajes
                        if (data.mensajes) {
                            this.mensajes = data.mensajes;
                            this.scrollToBottom();
                        }
                        
                        // Limpiar formulario
                        this.respuestaManual = {
                            nombre: '',
                            correo: '',
                            mensaje: ''
                        };
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error agregando respuesta manual', 'error');
                }
            },

            async probarConexionWebklex() {
                try {
                    
                    const response = await fetch('/api/test-webklex-connection', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(data.message, 'success');
                    } else {
                        this.mostrarNotificacion(data.message, 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error probando conexión Webklex', 'error');
                }
            },

            async buscarCorreosUsuarios() {
                if (!this.selected.id) {
                    this.mostrarNotificacion('Selecciona un ticket primero', 'error');
                    return;
                }

                this.buscandoCorreos = true;

                try {
                    
                    // Procesar correos entrantes desde IMAP
                    const response = await fetch('/api/process-webklex-responses', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        const mensaje = data.procesados > 0 
                            ? `✅ Se encontraron y procesaron ${data.procesados} correo(s)` + (data.descartados > 0 ? `. Se descartaron ${data.descartados} correo(s).` : '')
                            : data.message || 'Búsqueda completada';
                        
                        this.mostrarNotificacion(mensaje, data.procesados > 0 ? 'success' : 'error');
                        
                        // Recargar mensajes para mostrar los correos encontrados
                        await this.cargarMensajes();
                        
                        // Actualizar estadísticas
                        this.estadisticas = await this.obtenerEstadisticasCorreos();
                        
                    } else {
                        this.mostrarNotificacion(data.message || 'No se encontraron correos nuevos', 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error buscando correos de usuarios', 'error');
                } finally {
                    this.buscandoCorreos = false;
                }
            },

            async guardarCorreosEncontrados() {
                if (!this.selected.id) {
                    this.mostrarNotificacion('Selecciona un ticket primero', 'error');
                    return;
                }

                this.guardandoCorreos = true;

                try {
                    
                    // Sincronizar correos y guardarlos en el historial
                    const response = await fetch('/tickets/sincronizar-correos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            ticket_id: this.selected.id
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.mostrarNotificacion(
                            data.message || 'Correos guardados en historial exitosamente',
                            'success'
                        );
                        
                        // Recargar mensajes para mostrar el historial completo
                        await this.cargarMensajes();
                        
                        // Actualizar estadísticas
                        this.estadisticas = await this.obtenerEstadisticasCorreos();
                        
                    } else {
                        this.mostrarNotificacion(data.message || 'Error guardando correos', 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error guardando correos en historial', 'error');
                } finally {
                    this.guardandoCorreos = false;
                }
            },

            // Funciones para métricas
            async cargarMetricas() {
                this.cargandoMetricas = true;
                this.metricasTipos = [];
                try {
                    const response = await fetch('/tickets/tipos-con-metricas');
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success && data.tipos && Array.isArray(data.tipos)) {
                        this.metricasTipos = data.tipos.map(tipo => ({
                            TipoID: tipo.TipoID,
                            NombreTipo: tipo.NombreTipo,
                            TiempoEstimadoMinutos: tipo.TiempoEstimadoMinutos || null,
                            cambiado: false
                        }));
                    } else {
                        this.mostrarNotificacion(data.message || 'Error cargando métricas', 'error');
                        this.metricasTipos = [];
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error cargando métricas: ' + error.message, 'error');
                    this.metricasTipos = [];
                } finally {
                    this.cargandoMetricas = false;
                }
            },

            async guardarMetricas() {
                const cambios = this.metricasTipos.filter(t => t.cambiado);
                
                if (cambios.length === 0) {
                    this.mostrarNotificacion('No hay cambios para guardar', 'info');
                    return;
                }

                this.guardandoMetricas = true;
                
                try {
                    const metricas = cambios.map(tipo => ({
                        tipo_id: tipo.TipoID,
                        tiempo_estimado_minutos: tipo.TiempoEstimadoMinutos ? parseInt(tipo.TiempoEstimadoMinutos) : null
                    }));

                    const response = await fetch('/tickets/actualizar-metricas-masivo', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            metricas: metricas
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Marcar todos los cambios como guardados
                        cambios.forEach(tipo => {
                            tipo.cambiado = false;
                        });
                        
                        this.mostrarNotificacion(
                            data.message || `Se actualizaron ${data.actualizados || cambios.length} tipos de tickets`,
                            'success'
                        );
                        
                        // Recargar métricas para asegurar sincronización
                        await this.cargarMetricas();
                    } else {
                        this.mostrarNotificacion(data.message || 'Error guardando métricas', 'error');
                    }
                } catch (error) {
                    this.mostrarNotificacion('Error guardando métricas', 'error');
                } finally {
                    this.guardandoMetricas = false;
                }
            },

            formatearTiempo(minutos) {
                if (!minutos || minutos === 0) return '-';
                
                const horas = Math.floor(minutos / 60);
                const mins = minutos % 60;
                
                if (horas > 0 && mins > 0) {
                    return `${horas}h ${mins}m`;
                } else if (horas > 0) {
                    return `${horas}h`;
                } else {
                    return `${mins}m`;
                }
            },
            
            formatearHorasDecimales(horasDecimales) {
                if (!horasDecimales || horasDecimales === 0 || horasDecimales === '') return '-';
                
                const horas = parseFloat(horasDecimales);
                if (isNaN(horas)) return '-';
                
                const horasEnteras = Math.floor(horas);
                const minutos = Math.round((horas - horasEnteras) * 60);
                
                if (horasEnteras > 0 && minutos > 0) {
                    return `${horasEnteras}h ${minutos}m`;
                } else if (horasEnteras > 0) {
                    return `${horasEnteras}h`;
                } else if (minutos > 0) {
                    return `${minutos}m`;
                } else {
                    return '0m';
                }
            }
        }
    }

   
    // Hacer las funciones accesibles globalmente para que puedan ser llamadas desde Alpine.js
    window.loadSubtipos = null;
    
    // Función global para formatear horas decimales a horas y minutos
    window.formatearHorasDecimales = function(horasDecimales) {
        if (!horasDecimales || horasDecimales === 0 || horasDecimales === '') return '-';
        
        const horas = parseFloat(horasDecimales);
        if (isNaN(horas)) return '-';
        
        const horasEnteras = Math.floor(horas);
        const minutos = Math.round((horas - horasEnteras) * 60);
        
        if (horasEnteras > 0 && minutos > 0) {
            return `${horasEnteras}h ${minutos}m`;
        } else if (horasEnteras > 0) {
            return `${horasEnteras}h`;
        } else if (minutos > 0) {
            return `${minutos}m`;
        } else {
            return '0m';
        }
    };
    window.loadTertipos = null;
   
    document.addEventListener('DOMContentLoaded', function() {
        const tipoSelect = document.getElementById('tipo-select');
        const subtipoSelect = document.getElementById('subtipo-select');
        const tertipoSelect = document.getElementById('tertipo-select');

        loadTipos();

        tipoSelect.addEventListener('change', function() {
            const tipoId = this.value;
            
            clearSelect(subtipoSelect);
            clearSelect(tertipoSelect);
            subtipoSelect.disabled = true;
            tertipoSelect.disabled = true;

            if (tipoId) {
                loadSubtipos(tipoId);
            }
        });

        subtipoSelect.addEventListener('change', function() {
            const subtipoId = this.value;
            
            clearSelect(tertipoSelect);
            tertipoSelect.disabled = true;

            if (subtipoId) {
                loadTertipos(subtipoId);
            }
        });

        async function loadTipos() {
            try {
                const response = await fetch('/tickets/tipos');
                const data = await response.json();
                
                if (data.success) {
                    data.tipos.forEach(tipo => {
                        const option = document.createElement('option');
                        option.value = tipo.TipoID;
                        option.textContent = tipo.NombreTipo;
                        tipoSelect.appendChild(option);
                    });
                } else {
                }
            } catch (error) {
            }
        }

        window.loadSubtipos = async function loadSubtipos(tipoId) {
            try {
                // Verificar si el ticket está cerrado consultando Alpine.js
                const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                const estaCerrado = alpineData && (alpineData.selected?.estatus === 'Cerrado' || alpineData.ticketEstatus === 'Cerrado');
                
                subtipoSelect.innerHTML = '<option value="">Seleccione un subtipo</option>';
                subtipoSelect.disabled = true;
                
                tertipoSelect.innerHTML = '<option value="">Seleccione un tertipo</option>';
                tertipoSelect.disabled = true;
                
                if (!tipoId) {
                    return;
                }
                
                const response = await fetch(`/tickets/subtipos?tipo_id=${tipoId}`);
                const data = await response.json();
                
                if (data.success && data.subtipos.length > 0) {
                    data.subtipos.forEach(subtipo => {
                        const option = document.createElement('option');
                        option.value = subtipo.SubtipoID;
                        option.textContent = subtipo.NombreSubtipo;
                        subtipoSelect.appendChild(option);
                    });
                    // Solo habilitar si el ticket NO está cerrado
                    // Alpine.js manejará el disabled basado en su lógica (:disabled="!ticketTipoID || selected.estatus === 'Cerrado'")
                    if (!estaCerrado) {
                    subtipoSelect.disabled = false;
                    }
                } 
            } catch (error) {
            }
        }

        window.loadTertipos = async function loadTertipos(subtipoId) {
            try {
                tertipoSelect.innerHTML = '<option value="">Seleccione un tertipo</option>';
                tertipoSelect.disabled = true;
                
                if (!subtipoId) {
                    return;
                }
                
                const response = await fetch(`/tickets/tertipos?subtipo_id=${subtipoId}`);
                const data = await response.json();
                
                if (data.success && data.tertipos.length > 0) {
                    data.tertipos.forEach(tertipo => {
                        const option = document.createElement('option');
                        option.value = tertipo.TertipoID;
                        option.textContent = tertipo.NombreTertipo;
                        tertipoSelect.appendChild(option);
                    });
                    // Habilitar el campo - Alpine.js lo deshabilitará automáticamente si el ticket está cerrado
                    // mediante su directiva :disabled="!ticketSubtipoID || selected.estatus === 'Cerrado'"
                    tertipoSelect.disabled = false;
                } else {
                }
            } catch (error) {
            }
        }

        function clearSelect(selectElement) {
            while (selectElement.children.length > 1) {
                selectElement.removeChild(selectElement.lastChild);
            }
        }
    });

    // =====================================================================
    // POLLING EN TIEMPO REAL DE NOTIFICACIONES PENDIENTES
    // Intervalo ligero para mantener badges sin recargar la página.
    // Actualiza los badges rojos en kanban, lista y tabla sin recargar la página.
    // =====================================================================
    (function iniciarPollingNotificaciones() {
        // Estado local: mapa de ticket_id => cantidad (para detectar cambios)
        let estadoAnterior = {};

        /**
         * Crea o devuelve el badge rojo ya existente dentro de un wrapper de icono.
         * Busca por las clases que usan los 3 updaters (.bg-red-500.rounded-full.w-4.h-4).
         */
        function obtenerBadgeExistente(wrapperIcono) {
            return wrapperIcono.querySelector('.bg-red-500.rounded-full.w-4.h-4');
        }

        function crearBadge(cantidad) {
            const span = document.createElement('span');
            span.className = 'absolute top-0 right-0 translate-x-1/2 -translate-y-1/2 bg-red-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center';
            span.textContent = cantidad;
            return span;
        }

        /**
         * Aplica el estado de notificaciones al DOM en las 3 vistas.
         * @param {Object} pendientes — mapa { ticket_id: total }
         */
        function aplicarBadgesEnDOM(pendientes) {
            // Recorre TODOS los elementos con data-ticket-id del DOM
            document.querySelectorAll('[data-ticket-id]').forEach(function(card) {
                const ticketId = String(card.getAttribute('data-ticket-id'));
                const cantidad = pendientes[ticketId] ? parseInt(pendientes[ticketId]) : 0;

                // Busca el wrapper del ícono de notificación dentro de la tarjeta/fila
                const wrappers = card.querySelectorAll('.relative.flex-shrink-0.w-6.h-6');

                wrappers.forEach(function(wrapper) {
                    const badgeExistente = obtenerBadgeExistente(wrapper);

                    if (cantidad > 0) {
                        if (badgeExistente) {
                            // Badge ya existe — no hace falta actualizarlo, siempre muestra 1
                        } else {
                            // Insertar badge nuevo con animación de entrada (siempre muestra "1")
                            const nuevoBadge = crearBadge(1);
                            nuevoBadge.style.opacity = '0';
                            nuevoBadge.style.transform = 'translate(50%, -50%) scale(0.5)';
                            nuevoBadge.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                            wrapper.appendChild(nuevoBadge);
                            // Forzar reflow para que la animación de entrada se dispare
                            requestAnimationFrame(function() {
                                requestAnimationFrame(function() {
                                    nuevoBadge.style.opacity = '1';
                                    nuevoBadge.style.transform = 'translate(50%, -50%) scale(1)';
                                });
                            });
                        }
                    } else {
                        // Sin notificaciones: remover badge si existe
                        if (badgeExistente) {
                            badgeExistente.style.transition = 'opacity 0.15s ease, transform 0.15s ease';
                            badgeExistente.style.opacity = '0';
                            badgeExistente.style.transform = 'translate(50%, -50%) scale(0.5)';
                            setTimeout(function() {
                                if (badgeExistente.parentNode) {
                                    badgeExistente.parentNode.removeChild(badgeExistente);
                                }
                            }, 150);
                        }
                    }
                });
            });
        }

        function hayCambiosPendientes(actual, anterior) {
            const llavesActuales = Object.keys(actual);
            const llavesAnteriores = Object.keys(anterior);

            if (llavesActuales.length !== llavesAnteriores.length) {
                return true;
            }

            return llavesActuales.some(ticketId => actual[ticketId] !== anterior[ticketId]);
        }

        async function pollNotificaciones() {
            if (document.hidden) return;

            try {
                const response = await fetch('/tickets/notificaciones-pendientes', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute?.('content') || ''
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) return;

                const data = await response.json();
                const pendientes = data.pendientes || {};

                if (hayCambiosPendientes(pendientes, estadoAnterior)) {
                    estadoAnterior = pendientes;
                    aplicarBadgesEnDOM(pendientes);
                }
            } catch (e) {
                // Silencioso — no romper la UI si el endpoint falla
            }
        }

        // Arrancar inmediatamente y luego cada 5 segundos
        pollNotificaciones();
        setInterval(pollNotificaciones, 5000);
    })();
</script>
