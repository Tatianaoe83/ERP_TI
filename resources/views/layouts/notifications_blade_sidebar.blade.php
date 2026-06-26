
{{-- Panel de notificaciones --}}
<style>
    #tooltipNotif {
        font-family: inherit;
        background: #ffffff;
        border: 1px solid #e8ecf1;
        border-radius: 12px;
        box-shadow: 0 12px 40px rgba(15, 23, 42, 0.12), 0 2px 8px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .dark #tooltipNotif {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
    }

    .notif-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px 12px;
        border-bottom: 1px solid #eef2f6;
        background: #ffffff;
    }

    .dark .notif-header {
        background: #1e293b;
        border-bottom-color: #334155;
    }

    .notif-header-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        color: #94a3b8;
        text-transform: uppercase;
    }

    .notif-header-badge {
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .notif-list {
        max-height: 420px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .notif-list::-webkit-scrollbar {
        width: 5px;
    }

    .notif-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .notif-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
    }

    .dark .notif-item {
        border-bottom-color: #334155;
    }

    .notif-item:last-child {
        border-bottom: none;
    }

    .notif-item:hover {
        background: #f8fafc;
    }

    .dark .notif-item:hover {
        background: #273449;
    }

    .notif-item--unread {
        background: #eff6ff;
    }

    .dark .notif-item--unread {
        background: rgba(59, 130, 246, 0.12);
    }

    .notif-item--unread:hover {
        background: #dbeafe;
    }

    .dark .notif-item--unread:hover {
        background: rgba(59, 130, 246, 0.18);
    }

    .notif-icon {
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    .notif-icon--blue   { background: #dbeafe; color: #2563eb; }
    .notif-icon--violet { background: #ede9fe; color: #7c3aed; }
    .notif-icon--indigo { background: #e0e7ff; color: #4f46e5; }
    .notif-icon--rose   { background: #ffe4e6; color: #e11d48; }
    .notif-icon--amber  { background: #fef3c7; color: #d97706; }
    .notif-icon--teal   { background: #ccfbf1; color: #0d9488; }

    .notif-content {
        flex: 1;
        min-width: 0;
        padding-top: 1px;
    }

    .notif-title {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.35;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dark .notif-title {
        color: #f1f5f9;
    }

    .notif-meta {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
        font-size: 12px;
        line-height: 1.3;
    }

    .notif-cat {
        font-weight: 600;
    }

    .notif-cat--blue   { color: #2563eb; }
    .notif-cat--violet { color: #7c3aed; }
    .notif-cat--indigo { color: #4f46e5; }
    .notif-cat--rose   { color: #e11d48; }
    .notif-cat--amber  { color: #d97706; }
    .notif-cat--teal   { color: #0d9488; }

    .notif-dot,
    .notif-time {
        color: #94a3b8;
        font-weight: 400;
    }

    .notif-empty {
        padding: 32px 20px;
        text-align: center;
        color: #94a3b8;
        font-size: 13px;
    }

    .notif-empty i {
        display: block;
        font-size: 28px;
        margin-bottom: 10px;
        opacity: 0.45;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const boton = document.getElementById('btnNotif');

        const tooltip = document.createElement('div');
        tooltip.id = 'tooltipNotif';
        tooltip.style.cssText = 'display:none; position:fixed; z-index:99999; width:22rem; max-height:80vh;';
        tooltip.innerHTML = `
            <div class="notif-header">
                <span class="notif-header-title">Notificaciones</span>
                <span id="notifHeaderBadge" class="notif-header-badge"></span>
            </div>
            <div id="notifList" class="notif-list">
                <div class="notif-empty"><i class="fas fa-bell-slash"></i>Cargando notificaciones...</div>
            </div>
        `;
        document.body.appendChild(tooltip);

        const REMINDER_MS = 24 * 60 * 60 * 1000;
        window._lastNotifData = null;

        function escapeHtml(str) {
            return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function normalizarDismissals(raw) {
            if (!Array.isArray(raw)) return [];
            return raw.filter(item => item && typeof item === 'object' && item.id);
        }

        function obtenerDismissals(storageKey) {
            try {
                return normalizarDismissals(JSON.parse(localStorage.getItem(storageKey) || '[]'));
            } catch (e) {
                return [];
            }
        }

        function marcarVistoParaBadge(storageKey, id, estado) {
            if (!id) return;
            const idStr = id.toString();
            const dismissals = obtenerDismissals(storageKey).filter(d => d.id !== idStr);
            dismissals.push({
                id: idStr,
                estado: String(estado ?? ''),
                leidoAt: Date.now()
            });
            localStorage.setItem(storageKey, JSON.stringify(dismissals));
            actualizarNotificaciones();
        }

        function suprimeConteoBadge(storageKey, id, estadoActual) {
            const dismissals = obtenerDismissals(storageKey);
            const registro = dismissals.find(d => d.id === id.toString());
            if (!registro) return false;
            if (registro.estado !== String(estadoActual ?? '')) return false;
            return (Date.now() - registro.leidoAt) < REMINDER_MS;
        }

        function crearItemNotificacion({ unread, theme, icon, titulo, categoria, tiempo, onclick }) {
            const unreadClass = unread ? ' notif-item--unread' : '';
            const onclickAttr = String(onclick).replace(/"/g, '&quot;');
            return `<div class="notif-item${unreadClass}" onclick="${onclickAttr}">
                <div class="notif-icon notif-icon--${theme}">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">${escapeHtml(titulo)}</div>
                    <div class="notif-meta">
                        <span class="notif-cat notif-cat--${theme}">${escapeHtml(categoria)}</span>
                        <span class="notif-dot">·</span>
                        <span class="notif-time">${escapeHtml(tiempo)}</span>
                    </div>
                </div>
            </div>`;
        }

        window.marcarTicketComoLeido = function(ticketId) {
            marcarVistoParaBadge('dismissTickets', ticketId, 'Pendiente');
        };

        window.marcarSolicitudComoLeida = function(solicitudId) {
            marcarVistoParaBadge('dismissSolicitudes', solicitudId, 'nueva');
        };

        window.marcarChatComoLeido = function(ticketId, estado) {
            marcarVistoParaBadge('dismissChats', ticketId, estado);
        };

        window.marcarSeguimientoTIComoLeido = function(solicitudId, estatus) {
            marcarVistoParaBadge('dismissSeguimientoTI', solicitudId, estatus);
        };

        window.marcarFacturaPendienteComoLeida = function(solicitudId, estadoFacturas) {
            marcarVistoParaBadge('dismissFacturas', solicitudId, estadoFacturas);
        };

        window.marcarCotizacionTIComoLeida = function(solicitudId, estado) {
            marcarVistoParaBadge('dismissCotizacionTI', solicitudId, estado);
        };

        function marcarSolicitudVistaDesdeApp(solicitudId) {
            const id = solicitudId.toString();
            const data = window._lastNotifData;
            if (!data) return;

            if (data.solicitudes_pendientes?.some(s => String(s.SolicitudID) === id)) {
                marcarVistoParaBadge('dismissSolicitudes', id, 'nueva');
            }

            const seguimiento = data.solicitudes_seguimiento_ti?.find(s => String(s.SolicitudID) === id);
            if (seguimiento) {
                marcarVistoParaBadge('dismissSeguimientoTI', id, seguimiento.Estatus);
            }

            const factura = data.solicitudes_factura_pendiente?.find(s => String(s.SolicitudID) === id);
            if (factura) {
                marcarVistoParaBadge('dismissFacturas', id, `${factura.facturas_subidas}/${factura.facturas_necesarias}`);
            }

            const cotizacion = data.solicitudes_cotizacion_ti?.find(s => String(s.SolicitudID) === id);
            if (cotizacion) {
                marcarVistoParaBadge('dismissCotizacionTI', id, `cotizar-${cotizacion.cotizaciones_count || 0}`);
            }
        }

        window.abrirNotificacionCotizacion = function(solicitudId) {
            window.location.href = `/solicitudes/${solicitudId}/cotizar`;
        };

        function abrirTicketPorId(ticketId) {
            if (typeof window.__abrirModalTicket !== 'function') return false;
            fetch(`/tickets/${ticketId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => {
                    const contentType = res.headers.get('content-type') || '';
                    if (!res.ok || !contentType.includes('application/json')) return null;
                    return res.json();
                })
                .then(data => {
                    if (!data || !data.success || !data.ticket) return;
                    const t = data.ticket;
                    window.__abrirModalTicket({
                        id: t.TicketID,
                        asunto: t.asunto || `Ticket #${t.TicketID}`,
                        descripcion: t.descripcion || '',
                        prioridad: t.Prioridad || 'Media',
                        empleado: t.empleado || '',
                        anydesk: t.anydesk || '',
                        numero: t.numero || '',
                        correo: t.correo || '',
                        puesto: t.puesto || '',
                        gerencia: t.gerencia || '',
                        departamento: t.departamento || '',
                        fecha: t.fecha || '',
                        imagen: t.imagen || ''
                    });
                })
                .catch(() => {});
            return true;
        }

        function marcarTicketLeidoEnDB(ticketId) {
            fetch('/tickets/marcar-leidos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ ticket_id: ticketId })
            }).catch(err => console.error("Error al marcar leídos en DB:", err));
        }

        function abrirTicketDesdeNotif(ticketId) {
            tooltip.style.display = 'none';
            marcarTicketLeidoEnDB(ticketId);

            // En /tickets, si la tarjeta está visible, ábrela (mantiene el flujo del tablero)
            if (window.location.pathname.endsWith('/tickets')) {
                const card = document.querySelector(`[data-ticket-id="${ticketId}"]`);
                if (card) {
                    card.click();
                    return;
                }
            }
            // Modal global del panel de ticket (cualquier vista). Si no existe, redirigir a soporte.
            if (!abrirTicketPorId(ticketId)) {
                window.location.href = `/tickets?ticket_id=${ticketId}`;
            }
        }

        window.abrirNotificacionTicket = function(ticketId) { abrirTicketDesdeNotif(ticketId); };
        window.abrirNotificacionChat = function(ticketId) { abrirTicketDesdeNotif(ticketId); };

        window.abrirNotificacionSolicitud = function(solicitudId) {
            tooltip.style.display = 'none';
            // Modal global de detalles (montado en el layout) → funciona en cualquier vista, sin redirigir
            if (typeof window.__abrirModalSolicitud === 'function') {
                window.__abrirModalSolicitud(solicitudId);
            } else {
                window.location.href = `/tickets?solicitud_id=${solicitudId}`;
            }
        };

        window.abrirNotificacionFactura = function(solicitudId) {
            tooltip.style.display = 'none';
            // Modal de Asignación global (instancia Livewire en el layout) → abre en cualquier vista
            if (window.Livewire) {
                window.Livewire.emit('abrirAsignacionNotif', parseInt(solicitudId));
            } else {
                window.location.href = `/tickets?asignacion_id=${solicitudId}`;
            }
        };

        const navEntries = performance.getEntriesByType('navigation');
        const esRecarga = navEntries.length > 0 && navEntries[0].type === 'reload';

        if (window.location.pathname.endsWith('/tickets')) {
            const urlParams = new URLSearchParams(window.location.search);
            const ticketId = urlParams.get('ticket_id');
            const solicitudId = urlParams.get('solicitud_id');
            const asignacionId = urlParams.get('asignacion_id');

            // Solo auto-abrir si se llegó por navegación (notif/link), no en recargas (F5)
            if (!esRecarga) {
                if (ticketId) {
                    setTimeout(() => {
                        const card = document.querySelector(`[data-ticket-id="${ticketId}"]`);
                        if (card) card.click();
                    }, 1000);
                }
                if (solicitudId) {
                    setTimeout(() => {
                        const botonSolicitud = document.querySelector(`[data-ver-solicitud="${solicitudId}"]`);
                        if (botonSolicitud) {
                            botonSolicitud.click();
                            return;
                        }

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
                if (asignacionId) {
                    setTimeout(() => {
                        if (window.Livewire) {
                            window.Livewire.emit('abrirAsignacionNotif', parseInt(asignacionId));
                        }
                    }, 1500);
                }
            }

            // Limpiar los params de la URL para que al recargar no se reabra el modal
            if (ticketId || solicitudId || asignacionId) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        document.addEventListener('click', function(e) {
            const card = e.target.closest('[data-ticket-id]');
            if (card) {
                const ticketId = card.getAttribute('data-ticket-id');
                if (ticketId) {
                    marcarVistoParaBadge('dismissTickets', ticketId, 'Pendiente');
                    const chat = window._lastNotifData?.mensajes_nuevos?.find(m => String(m.ticket_id) === ticketId);
                    if (chat) {
                        marcarVistoParaBadge('dismissChats', ticketId, `${chat.total || 1}-${chat.timestamp || 0}`);
                    }
                }
            }

            const btn = e.target.closest('button');
            if (btn) {
                const clickAttr = btn.getAttribute('@click') || btn.getAttribute('x-on:click');
                if (clickAttr && clickAttr.includes('abrirModal')) {
                    const match = clickAttr.match(/abrirModal\(\s*(\d+)\s*\)/);
                    if (match) marcarSolicitudVistaDesdeApp(match[1]);
                }
            }

            const cotizarLink = e.target.closest('a[href*="/cotizar"]');
            if (cotizarLink) {
                const match = cotizarLink.getAttribute('href')?.match(/solicitudes\/(\d+)\/cotizar/);
                if (match) {
                    const cotizacion = window._lastNotifData?.solicitudes_cotizacion_ti?.find(
                        s => String(s.SolicitudID) === match[1]
                    );
                    marcarVistoParaBadge(
                        'dismissCotizacionTI',
                        match[1],
                        `cotizar-${cotizacion?.cotizaciones_count || 0}`
                    );
                }
            }
        });

        let actualizandoNotificaciones = false;

        function actualizarNotificaciones() {
            if (document.hidden || actualizandoNotificaciones) return;

            actualizandoNotificaciones = true;
            fetch('/notificaciones-panel', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(res => {
                    const contentType = res.headers.get('content-type') || '';
                    if (!res.ok || !contentType.includes('application/json')) {
                        return null;
                    }

                    return res.json();
                })
                .then(data => {
                    if (!data) return;

                    window._lastNotifData = data;

                    let conteoNoLeidos = 0;
                    let listaNotificaciones = [];

                    if (data.tickets_nuevos) {
                        data.tickets_nuevos.forEach(t => {
                            if (!t || !t.TicketID) return;
                            const estadoTicket = 'Pendiente';
                            const unread = !t.vencidos && !suprimeConteoBadge('dismissTickets', t.TicketID, estadoTicket);
                            if (unread) conteoNoLeidos++;
                            listaNotificaciones.push({
                                timestamp: t.timestamp || 0,
                                html: crearItemNotificacion({
                                    unread,
                                    theme: 'blue',
                                    icon: 'fa-ticket-alt',
                                    titulo: `Ticket #${t.TicketID} · ${t.empleado}`,
                                    categoria: 'Ticket nuevo',
                                    tiempo: t.created_at,
                                    onclick: `marcarTicketComoLeido(${t.TicketID}); abrirNotificacionTicket(${t.TicketID})`
                                })
                            });
                        });
                    }

                    if (data.solicitudes_pendientes) {
                        data.solicitudes_pendientes.forEach(s => {
                            if (!s || !s.SolicitudID) return;
                            const unread = !s.vencidos && !suprimeConteoBadge('dismissSolicitudes', s.SolicitudID, 'nueva');
                            if (unread) conteoNoLeidos++;
                            listaNotificaciones.push({
                                timestamp: s.timestamp || 0,
                                html: crearItemNotificacion({
                                    unread,
                                    theme: 'violet',
                                    icon: 'fa-file-alt',
                                    titulo: `Solicitud #${s.SolicitudID} · ${s.empleado}`,
                                    categoria: 'Solicitud nueva',
                                    tiempo: s.created_at,
                                    onclick: `marcarSolicitudComoLeida(${s.SolicitudID}); abrirNotificacionSolicitud(${s.SolicitudID})`
                                })
                            });
                        });
                    }

                    if (data.solicitudes_cotizacion_ti) {
                        data.solicitudes_cotizacion_ti.forEach(s => {
                            if (!s || !s.SolicitudID) return;
                            const cotCount = parseInt(s.cotizaciones_count || 0, 10);
                            const estadoCotizacion = `cotizar-${cotCount}`;
                            const unread = !suprimeConteoBadge('dismissCotizacionTI', s.SolicitudID, estadoCotizacion);
                            if (unread) conteoNoLeidos++;
                            const estadoCotizacionJs = JSON.stringify(estadoCotizacion);
                            const categoria = cotCount > 0
                                ? `Cotización TI · ${cotCount} borrador(es)`
                                : 'Cotización TI pendiente';
                            listaNotificaciones.push({
                                timestamp: s.timestamp || 0,
                                html: crearItemNotificacion({
                                    unread,
                                    theme: 'violet',
                                    icon: 'fa-file-invoice-dollar',
                                    titulo: `Solicitud #${s.SolicitudID} · ${s.empleado}`,
                                    categoria,
                                    tiempo: s.created_at,
                                    onclick: `marcarCotizacionTIComoLeida(${s.SolicitudID}, ${estadoCotizacionJs}); abrirNotificacionCotizacion(${s.SolicitudID})`
                                })
                            });
                        });
                    }

                    if (data.solicitudes_seguimiento_ti) {
                        data.solicitudes_seguimiento_ti.forEach(s => {
                            if (!s || !s.SolicitudID) return;
                            const unread = !suprimeConteoBadge('dismissSeguimientoTI', s.SolicitudID, s.Estatus);
                            if (unread) conteoNoLeidos++;
                            const esRecotizar = s.Estatus === 'Re-cotizar';
                            const estatusJs = JSON.stringify(s.Estatus || '');
                            listaNotificaciones.push({
                                timestamp: s.timestamp || 0,
                                html: crearItemNotificacion({
                                    unread,
                                    theme: esRecotizar ? 'amber' : 'indigo',
                                    icon: esRecotizar ? 'fa-redo' : 'fa-paper-plane',
                                    titulo: `Solicitud #${s.SolicitudID} · ${s.empleado}`,
                                    categoria: esRecotizar ? 'Re-cotización TI' : 'Cotizaciones enviadas',
                                    tiempo: s.created_at,
                                    onclick: `marcarSeguimientoTIComoLeido(${s.SolicitudID}, ${estatusJs}); abrirNotificacionSolicitud(${s.SolicitudID})`
                                })
                            });
                        });
                    }

                    if (data.solicitudes_factura_pendiente) {
                        data.solicitudes_factura_pendiente.forEach(s => {
                            if (!s || !s.SolicitudID) return;
                            const subidas = parseInt(s.facturas_subidas || 0, 10);
                            const necesarias = parseInt(s.facturas_necesarias || 0, 10);
                            const estadoFacturas = `${subidas}/${necesarias}`;
                            const unread = !suprimeConteoBadge('dismissFacturas', s.SolicitudID, estadoFacturas);
                            if (unread) conteoNoLeidos++;
                            const estadoFacturasJs = JSON.stringify(estadoFacturas);
                            const parcial = subidas > 0;
                            listaNotificaciones.push({
                                timestamp: s.timestamp || 0,
                                html: crearItemNotificacion({
                                    unread,
                                    theme: parcial ? 'amber' : 'rose',
                                    icon: 'fa-file-invoice',
                                    titulo: `Solicitud #${s.SolicitudID} · ${s.empleado}`,
                                    categoria: parcial ? `Factura parcial ${estadoFacturas}` : `Factura pendiente ${estadoFacturas}`,
                                    tiempo: s.created_at,
                                    onclick: `marcarFacturaPendienteComoLeida(${s.SolicitudID}, ${estadoFacturasJs}); abrirNotificacionFactura(${s.SolicitudID})`
                                })
                            });
                        });
                    }

                    if (data.mensajes_nuevos) {
                        data.mensajes_nuevos.forEach(m => {
                            if (!m || m.ticket_id === undefined || m.ticket_id === null) return;
                            const chatTicketIdStr = String(m.ticket_id);
                            const estadoChat = `${m.total || 1}-${m.timestamp || 0}`;
                            const unread = !suprimeConteoBadge('dismissChats', chatTicketIdStr, estadoChat);
                            if (unread) conteoNoLeidos += parseInt(m.total || 1, 10);
                            const estadoChatJs = JSON.stringify(estadoChat);
                            const total = parseInt(m.total || 1, 10);
                            listaNotificaciones.push({
                                timestamp: m.timestamp || 0,
                                html: crearItemNotificacion({
                                    unread,
                                    theme: 'teal',
                                    icon: 'fa-comment-dots',
                                    titulo: `Ticket #${chatTicketIdStr}`,
                                    categoria: total === 1 ? '1 mensaje nuevo' : `${total} mensajes nuevos`,
                                    tiempo: m.created_at,
                                    onclick: `marcarChatComoLeido(${chatTicketIdStr}, ${estadoChatJs}); abrirNotificacionChat(${chatTicketIdStr})`
                                })
                            });
                        });
                    }

                    const badge = document.getElementById('badgeNotif');
                    if (badge) {
                        if (conteoNoLeidos > 0) {
                            badge.textContent = conteoNoLeidos > 99 ? '99+' : conteoNoLeidos;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }

                    const headerBadge = document.getElementById('notifHeaderBadge');
                    if (headerBadge) {
                        if (conteoNoLeidos > 0) {
                            headerBadge.textContent = conteoNoLeidos > 99 ? '99+' : conteoNoLeidos;
                            headerBadge.style.display = 'flex';
                        } else {
                            headerBadge.style.display = 'none';
                        }
                    }

                    listaNotificaciones.sort((a, b) => b.timestamp - a.timestamp);

                    const notifList = document.getElementById('notifList');
                    if (notifList) {
                        if (listaNotificaciones.length === 0) {
                            notifList.innerHTML = `<div class="notif-empty"><i class="fas fa-bell-slash"></i>No hay notificaciones pendientes</div>`;
                        } else {
                            notifList.innerHTML = listaNotificaciones.map(item => item.html).join('');
                        }
                    }
                })
                .catch(() => {})
                .finally(() => {
                    actualizandoNotificaciones = false;
                });
        }

        actualizarNotificaciones();
        setInterval(actualizarNotificaciones, 15000);

        function posicionarTooltip() {
            if (tooltip.style.display === 'none') return;

            const rect = boton.getBoundingClientRect();
            const tooltipW = tooltip.offsetWidth;
            const tooltipH = tooltip.offsetHeight;
            const viewW = window.innerWidth;
            const viewH = window.innerHeight;

            let top, left;
            left = rect.right + 10;
            if (left + tooltipW > viewW - 8) {
                left = rect.left - tooltipW - 10;
            }
            if (left < 8) left = 8;

            top = rect.top + (rect.height / 2) - (tooltipH / 2);
            if (top + tooltipH > viewH - 8) top = viewH - tooltipH - 8;
            if (top < 8) top = 8;

            tooltip.style.top = top + 'px';
            tooltip.style.left = left + 'px';
        }

        boton.addEventListener('click', function(e) {
            e.stopPropagation();
            const isHidden = tooltip.style.display === 'none';
            tooltip.style.display = isHidden ? 'block' : 'none';
            if (isHidden) posicionarTooltip();
        });

        document.addEventListener('click', function(e) {
            if (!tooltip.contains(e.target) && !boton.contains(e.target)) {
                tooltip.style.display = 'none';
            }
        });

        window.addEventListener('resize', posicionarTooltip);
    });
</script>
