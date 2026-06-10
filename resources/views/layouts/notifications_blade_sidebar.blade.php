
{{-- Tooltip de notificaciones --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const boton = document.getElementById('btnNotif');

        // ── Crear el tooltip y montarlo en <body> ──
        const tooltip = document.createElement('div');
        tooltip.id = 'tooltipNotif';
        tooltip.style.cssText = 'display:none; position:fixed; z-index:99999; width:24rem; max-height:80vh; overflow-y:auto;';
        tooltip.className = 'bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg';

        tooltip.innerHTML = `
        <div class="font-semibold p-3 border-b bg-gray-100 dark:bg-gray-800 dark:border-gray-700" style="font-weight:600;">
            NOTIFICACIONES
        </div>
        <div class="p-3 space-y-3">
            <div class="text-sm text-gray-500 dark:text-gray-400 text-center py-2">Cargando notificaciones...</div>
        </div>
    `;

        document.body.appendChild(tooltip);

        // ── Helpers de LocalStorage para ocultación instantánea (Optimistic UI) ──
        window.marcarTicketComoLeido = function(ticketId) {
            if (!ticketId) return;
            let readTickets = JSON.parse(localStorage.getItem('readTickets') || '[]');
            if (!readTickets.includes(ticketId.toString())) {
                readTickets.push(ticketId.toString());
                localStorage.setItem('readTickets', JSON.stringify(readTickets));
            }
            actualizarNotificaciones();
        };

        window.marcarSolicitudComoLeida = function(solicitudId) {
            if (!solicitudId) return;
            let readSolicitudes = JSON.parse(localStorage.getItem('readSolicitudes') || '[]');
            if (!readSolicitudes.includes(solicitudId.toString())) {
                readSolicitudes.push(solicitudId.toString());
                localStorage.setItem('readSolicitudes', JSON.stringify(readSolicitudes));
            }
            actualizarNotificaciones();
        };

        window.marcarChatComoLeido = function(ticketId) {
            if (!ticketId) return;
            let readChats = JSON.parse(localStorage.getItem('readChats') || '[]');
            if (!readChats.includes(ticketId.toString())) {
                readChats.push(ticketId.toString());
                localStorage.setItem('readChats', JSON.stringify(readChats));
            }
            actualizarNotificaciones();
        };

        // ── Acción para abrir la notificación de un ticket ──
        window.abrirNotificacionTicket = function(ticketId) {
            window.marcarTicketComoLeido(ticketId);

            fetch('/tickets/marcar-leidos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    ticket_id: ticketId
                })
            }).catch(err => console.error("Error al marcar leídos en DB:", err));

            if (window.location.pathname.endsWith('/tickets')) {
                const card = document.querySelector(`[data-ticket-id="${ticketId}"]`);
                if (card) card.click();
            } else {
                window.location.href = `/tickets?ticket_id=${ticketId}`;
            }
        };

        // ── Acción para abrir la notificación de un chat ──
        window.abrirNotificacionChat = function(ticketId) {
            window.marcarChatComoLeido(ticketId);

            fetch('/tickets/marcar-leidos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    ticket_id: ticketId
                })
            }).catch(err => console.error("Error al marcar chat leído en DB:", err));

            if (window.location.pathname.endsWith('/tickets')) {
                const card = document.querySelector(`[data-ticket-id="${ticketId}"]`);
                if (card) card.click();
            } else {
                window.location.href = `/tickets?ticket_id=${ticketId}`;
            }
        };

        // ── Acción para abrir la notificación de una solicitud ──
        window.abrirNotificacionSolicitud = function(solicitudId) {
            window.marcarSolicitudComoLeida(solicitudId);

            if (window.location.pathname.endsWith('/tickets')) {
                const buttons = document.querySelectorAll('button');
                for (let btn of buttons) {
                    const clickAttr = btn.getAttribute('@click') || btn.getAttribute('x-on:click');
                    if (clickAttr && clickAttr.includes(`abrirModal(${solicitudId})`)) {
                        btn.click();
                        break;
                    }
                }
            } else {
                window.location.href = `/tickets?solicitud_id=${solicitudId}`;
            }
        };

        // ── Auto-abrir desde URL si se redirecciona ──
        if (window.location.pathname.endsWith('/tickets')) {
            const urlParams = new URLSearchParams(window.location.search);
            const ticketId = urlParams.get('ticket_id');
            if (ticketId) {
                setTimeout(() => {
                    const card = document.querySelector(`[data-ticket-id="${ticketId}"]`);
                    if (card) card.click();
                }, 1000);
            }
            const solicitudId = urlParams.get('solicitud_id');
            if (solicitudId) {
                setTimeout(() => {
                    const buttons = document.querySelectorAll('button');
                    for (let btn of buttons) {
                        const clickAttr = btn.getAttribute('@click') || btn.getAttribute('x-on:click');
                        if (clickAttr && clickAttr.includes(`abrirModal(${solicitudId})`)) {
                            btn.click();
                            break;
                        }
                    }
                }, 1000);
            }
        }

        // Interceptar clics globales en la app (Tarjetas del Kanban)
        document.addEventListener('click', function(e) {
            const card = e.target.closest('[data-ticket-id]');
            if (card) {
                const ticketId = card.getAttribute('data-ticket-id');
                if (ticketId) window.marcarTicketComoLeido(ticketId);
            }

            const btn = e.target.closest('button');
            if (btn) {
                const clickAttr = btn.getAttribute('@click') || btn.getAttribute('x-on:click');
                if (clickAttr && clickAttr.includes('abrirModal')) {
                    const match = clickAttr.match(/abrirModal\(\s*(\d+)\s*\)/);
                    if (match) window.marcarSolicitudComoLeida(match[1]);
                }
            }
        });

        // ── Función para actualizar las notificaciones mediante Polling AJAX ──
        function actualizarNotificaciones() {
            const tooltip = document.getElementById('tooltipNotif');

            fetch('/notificaciones-panel')
                .then(res => res.json())
                .then(data => {
                    const readTickets = JSON.parse(localStorage.getItem('readTickets') || '[]');
                    const readSolicitudes = JSON.parse(localStorage.getItem('readSolicitudes') || '[]');
                    const readChats = JSON.parse(localStorage.getItem('readChats') || '[]');

                    let conteoNoLeidos = 0;
                    let listaNotificaciones = [];

                    // 1. TICKETS NUEVOS
                    if (data.tickets_nuevos) {
                        data.tickets_nuevos.forEach(t => {
                            if (!t || !t.TicketID) return;
                            if (readTickets.includes(t.TicketID.toString())) return; // ← filtro
                            conteoNoLeidos++;
                            listaNotificaciones.push({
                                timestamp: t.timestamp || 0,
                                html: `<div class="text-sm font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2  p-1.5 rounded transition" 
                               // ">
                            Se ha creado el ticket <strong>#${t.TicketID}</strong> por <strong>${t.empleado}</strong> (${t.created_at}).
                        </div>`
                            });
                        });
                    }

                    // 2. SOLICITUDES PENDIENTES
                    if (data.solicitudes_pendientes) {
                        data.solicitudes_pendientes.forEach(s => {
                            if (!s || !s.SolicitudID) return;
                            if (readSolicitudes.includes(s.SolicitudID.toString())) return; // ← filtro
                            conteoNoLeidos++;
                            listaNotificaciones.push({
                                timestamp: s.timestamp || 0,
                                html: `<div class="text-sm font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 p-1.5 rounded transition"> 
                            Se ha creado la solicitud <strong>#${s.SolicitudID}</strong> por <strong>${s.empleado}</strong> (${s.created_at}).
                        </div>`
                            });
                        });
                    }

                    // 3. MENSAJES DE CHAT
                    if (data.mensajes_nuevos) {
                        data.mensajes_nuevos.forEach(m => {
                            if (!m || m.ticket_id === undefined || m.ticket_id === null) return;
                            const chatTicketIdStr = String(m.ticket_id);
                            // if (readChats.includes(chatTicketIdStr)) return; // ← filtro
                            conteoNoLeidos += parseInt(m.total || 1, 10);
                            listaNotificaciones.push({
                                timestamp: m.timestamp || 0,
                                html: `<div class="text-sm font-medium text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-700 pb-2 p-1.5 rounded transition" 
                            Tienes <strong>${m.total}</strong> mensaje(s) nuevo(s) en el chat del ticket <strong>#${chatTicketIdStr}</strong> (${m.created_at}).
                        </div>`
                            });
                        });
                    }

                    // --- RENDERIZADO DEL BADGE ---
                    const badge = document.getElementById('badgeNotif');
                    if (badge) {
                        if (conteoNoLeidos > 0) {
                            badge.textContent = conteoNoLeidos;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }

                    // Ordenar por fecha desc
                    listaNotificaciones.sort((a, b) => b.timestamp - a.timestamp);

                    // Inyectar en el Tooltip
                    if (tooltip) {
                        const tooltipContenedor = tooltip.querySelector('.space-y-3');
                        if (tooltipContenedor) {

                            let html = '';
                            listaNotificaciones.forEach(item => html += item.html);
                            if (html === '') {
                                html = `<div class="text-sm text-gray-500 dark:text-gray-400 text-center py-2">No hay notificaciones nuevas</div>`;
                            }
                            tooltipContenedor.innerHTML = html;
                        }
                    }
                })
                .catch(err => console.error("Error al procesar notificaciones:", err));
        }

        // Inicializar polling
        actualizarNotificaciones();
        setInterval(actualizarNotificaciones, 5000);

        // ── Posicionar el tooltip respecto al botón ──
        function posicionarTooltip() {
            if (tooltip.style.display === 'none') return;

            const rect = boton.getBoundingClientRect();
            const tooltipW = tooltip.offsetWidth;
            const tooltipH = tooltip.offsetHeight;
            const viewW = window.innerWidth;
            const viewH = window.innerHeight;

            let top, left;

            left = rect.right + 8;
            if (left + tooltipW > viewW - 8) {
                left = rect.left - tooltipW - 8;
            }
            if (left < 8) left = 8;

            top = rect.top + (rect.height / 2) - (tooltipH / 2);
            if (top + tooltipH > viewH - 8) top = viewH - tooltipH - 8;
            if (top < 8) top = 8;

            tooltip.style.top = top + 'px';
            tooltip.style.left = left + 'px';
        }

        // Toggle del tooltip (Sin alterar el badge)
        boton.addEventListener('click', function(e) {
            e.stopPropagation();
            const isHidden = tooltip.style.display === 'none';
            tooltip.style.display = isHidden ? 'block' : 'none';
            if (isHidden) {
                posicionarTooltip();
            }
        });

        // Cerrar al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!tooltip.contains(e.target) && !boton.contains(e.target)) {
                tooltip.style.display = 'none';
            }
        });

        window.addEventListener('resize', posicionarTooltip);
    });
</script>