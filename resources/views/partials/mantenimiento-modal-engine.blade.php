@php
    // El tablero ya define $responsableId; en el montaje global del layout se calcula aquí.
    if (!isset($responsableId)) {
        $responsablesEngine = \App\Models\TicketMantenimiento::obtenerResponsables();
        $responsableId = array_key_first($responsablesEngine) ?? '';
    }
@endphp

<script>
/**
 * Motor del modal de mantenimiento.
 *
 * soloPanel = true  -> instancia global montada en el layout (sin tablero detrás). Sirve para
 *                      abrir un mantenimiento desde cualquier vista (notificaciones).
 * soloPanel = false -> instancia del tablero /tickets-mantenimiento (kanban/lista/tabla + modal).
 */
function mantenimientoModal(soloPanel = false) {
    return {
        soloPanel,
        vista: localStorage.getItem('mantenimientoVista') || 'kanban',
        mostrar: false,
        selected: {},
        mensajes: [],
        estadisticas: null,
        asuntoCorreo: '',
        nuevoMensaje: '',
        archivosAdjuntos: [],
        cargando: false,
        guardando: false,
        ultimoMensajeId: 0,
        verificacionInterval: null,
        tinyMCEInstance: null,
        ticketPrioridad: '',
        ticketEstatus: 'Pendiente',
        estatusOriginal: 'Pendiente',
        transicionesEstatus: @json(\App\Models\TicketMantenimiento::TRANSICIONES),
        selectedEl: null,
        responsableFijo: '{{ $responsableId }}',
        ticketResponsable: '{{ $responsableId }}',
        ticketCategoria: '',
        ticketSla: null,

        get esFinalizado() {
            return ['Atendido', 'Cancelado'].includes(this.estatusOriginal);
        },
        get estaPendiente() {
            return this.estatusOriginal === 'Pendiente';
        },
        get bloqueadoEnvio() {
            return this.estaPendiente || this.esFinalizado;
        },
        get estatusDisponibles() {
            if (['Atendido', 'Cancelado'].includes(this.estatusOriginal)) {
                return [this.estatusOriginal];
            }
            const transiciones = this.transicionesEstatus[this.estatusOriginal] || [];
            return [this.estatusOriginal, ...transiciones];
        },

        init() {
            // Apertura por ID desde cualquier vista (notificaciones). Solo hay una instancia
            // por página, así que no se pisan entre tablero y layout.
            window.__abrirModalMantenimiento = (id) => this.abrirModalDesdeId(id);

            if (this.soloPanel) return;

            Livewire.on('mantenimiento-actualizados-kanban', (d) => this.procesarActualizacion(d));
            Livewire.on('mantenimiento-actualizados-lista', (d) => this.procesarActualizacion(d));
            Livewire.on('mantenimiento-actualizados-tabla', (d) => this.procesarActualizacion(d));
        },

        procesarActualizacion(datos) {
            if (!datos?.ticketsStatus) return;
            const counts = {
                pendiente: datos.ticketsStatus.pendiente?.length || 0,
                en_proceso: datos.ticketsStatus.en_proceso?.length || 0,
                pausado: datos.ticketsStatus.pausado?.length || 0,
                atendido: datos.ticketsStatus.atendido?.length || 0,
                cancelado: datos.ticketsStatus.cancelado?.length || 0,
            };
            Object.keys(counts).forEach(cat => {
                document.querySelectorAll(`[data-categoria-header="${cat}"]`).forEach(el => { el.textContent = counts[cat]; });
            });
        },

        /** Alinea el <select> con el estado real: x-for reconstruye las <option>
         *  y el navegador deja seleccionada la primera sin disparar change. */
        sincronizarSelectEstatus() {
            this.$nextTick(() => {
                const select = document.getElementById('mant-select-estatus');
                if (select) select.value = this.ticketEstatus;
            });
        },

        /** Único punto donde se puebla el modal: tablero y notificaciones pasan por aquí. */
        abrirModalDesdeDatos(datos) {
            const id = datos.id;
            this.selectedEl = datos.el || null;
            this.selected = {
                id,
                asunto: `Mantenimiento #${id}`,
                descripcion: datos.descripcion || '',
                solicitante: datos.solicitante || '',
                correo: datos.correo || '',
                area: datos.area || '',
                fecha: datos.fecha || '',
                estatus: datos.estatus || 'Pendiente',
                imagen: datos.imagen || '',
            };
            this.ticketPrioridad = datos.prioridad || '';
            this.ticketEstatus = datos.estatus || 'Pendiente';
            this.estatusOriginal = datos.estatus || 'Pendiente';
            this.sincronizarSelectEstatus();
            this.ticketResponsable = this.responsableFijo;
            this.ticketCategoria = datos.categoria || '';
            this.ticketSla = datos.sla || null;
            this.asuntoCorreo = `Re: Mantenimiento #${id}`;
            this.nuevoMensaje = '';
            this.archivosAdjuntos = [];
            this.mensajes = [];
            this.estadisticas = null;
            this.mostrar = true;

            this.cargarMensajes();
            this.iniciarVerificacionMensajes();
            this.$nextTick(() => {
                if (!this.tinyMCEInstance) this.inicializarTinyMCE();
                else this.actualizarEstadoEditor();
            });
        },

        /** Tablero: los datos vienen del dataset de la tarjeta. */
        abrirModalDesdeElemento(el) {
            let sla = null;
            try {
                sla = el.dataset.ticketSla ? JSON.parse(el.dataset.ticketSla) : null;
            } catch (e) {
                sla = null;
            }

            this.abrirModalDesdeDatos({
                el,
                id: el.dataset.ticketId,
                descripcion: el.dataset.ticketDescripcion,
                solicitante: el.dataset.ticketSolicitante,
                correo: el.dataset.ticketCorreo,
                area: el.dataset.ticketArea,
                fecha: el.dataset.ticketFecha,
                estatus: el.dataset.ticketEstatus,
                imagen: el.dataset.ticketImagen,
                prioridad: el.dataset.ticketPrioridad,
                categoria: el.dataset.ticketCategoria,
                sla,
            });
        },

        /** Notificaciones: no hay tarjeta en el DOM, los datos se piden al servidor. */
        async abrirModalDesdeId(id) {
            try {
                const response = await fetch(`/tickets-mantenimiento/${id}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const contentType = response.headers.get('content-type') || '';
                if (!response.ok || !contentType.includes('application/json')) return false;

                const data = await response.json();
                if (!data.success || !data.ticket) return false;

                const t = data.ticket;
                this.abrirModalDesdeDatos({
                    el: null,
                    id: t.id,
                    descripcion: t.descripcion,
                    solicitante: t.solicitante,
                    correo: t.correo,
                    area: t.area,
                    fecha: this.formatearFechaIso(t.created_at),
                    estatus: t.estatus,
                    imagen: t.imagen,
                    prioridad: t.prioridad,
                    categoria: t.categoria,
                    sla: t.sla,
                });
                return true;
            } catch (e) {
                return false;
            }
        },

        /** El tablero pinta la fecha ya formateada; desde JSON llega ISO8601. */
        formatearFechaIso(iso) {
            if (!iso) return '';
            const f = new Date(iso);
            if (isNaN(f)) return '';
            const p = (n) => String(n).padStart(2, '0');
            return `${p(f.getDate())}/${p(f.getMonth() + 1)}/${f.getFullYear()} ${p(f.getHours())}:${p(f.getMinutes())}:${p(f.getSeconds())}`;
        },

        cerrarModal() {
            this.detenerVerificacionMensajes();
            if (this.tinyMCEInstance) {
                try { tinymce.remove('#mant-editor-mensaje'); } catch (e) {}
                this.tinyMCEInstance = null;
            }
            this.mostrar = false;
            this.selected = {};
            this.ticketSla = null;
            this.mensajes = [];
            this.asuntoCorreo = '';
            this.nuevoMensaje = '';
            this.archivosAdjuntos = [];
        },

        async cargarMensajes() {
            if (!this.selected.id) return;
            try {
                const response = await fetch(`/tickets-mantenimiento/chat-messages?mantenimiento_id=${this.selected.id}`);
                const data = await response.json();
                if (data.success) {
                    this.mensajes = data.messages;
                    this.ultimoMensajeId = this.mensajes.length ? Math.max(...this.mensajes.map(m => m.id)) : 0;
                    await this.marcarMensajesComoLeidos();
                    this.estadisticas = await this.obtenerEstadisticasCorreos();
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) { console.error('Error cargando mensajes:', e); }
        },

        iniciarVerificacionMensajes() {
            this.detenerVerificacionMensajes();
            this.verificacionInterval = setInterval(() => this.verificarMensajesNuevos(), 15000);
        },
        detenerVerificacionMensajes() {
            if (this.verificacionInterval) { clearInterval(this.verificacionInterval); this.verificacionInterval = null; }
            this.ultimoMensajeId = 0;
        },
        async verificarMensajesNuevos() {
            if (!this.selected.id || !this.mostrar) return;
            try {
                const r = await fetch(`/tickets-mantenimiento/verificar-mensajes-nuevos?mantenimiento_id=${this.selected.id}&ultimo_mensaje_id=${this.ultimoMensajeId}`);
                const data = await r.json();
                if (data.success && data.tiene_nuevos) await this.cargarMensajes();
            } catch (e) {}
        },
        async marcarMensajesComoLeidos() {
            if (!this.selected.id) return;
            try {
                await fetch('/tickets-mantenimiento/marcar-leidos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ mantenimiento_id: this.selected.id }),
                });
            } catch (e) {}
        },
        async obtenerEstadisticasCorreos() {
            if (!this.selected.id) return null;
            try {
                const r = await fetch(`/tickets-mantenimiento/estadisticas-correos?mantenimiento_id=${this.selected.id}`);
                const data = await r.json();
                return data.success ? data.estadisticas : null;
            } catch (e) { return null; }
        },

        cargarTinyMCE() {
            if (window.tinymce) return Promise.resolve();
            if (window.__tinyMCELoadPromise) return window.__tinyMCELoadPromise;
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
            if (!document.getElementById('mant-editor-mensaje') || this.tinyMCEInstance) return;
            if (typeof tinymce === 'undefined') { try { await this.cargarTinyMCE(); } catch (e) { return; } }
            if (typeof tinymce === 'undefined') return;
            if (tinymce.get('mant-editor-mensaje')) tinymce.remove('#mant-editor-mensaje');

            const isDarkMode = document.documentElement.classList.contains('dark');
            tinymce.init({
                selector: '#mant-editor-mensaje',
                height: 300,
                menubar: false,
                plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'insertdatetime', 'media', 'table', 'help', 'wordcount'],
                toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | removeformat | link image | code | help',
                content_style: isDarkMode
                    ? 'body { font-family: Arial, sans-serif; font-size: 14px; background-color: #1f2937 !important; color: #ffffff !important; } body * { color: #ffffff !important; }'
                    : 'body { font-family: Arial, sans-serif; font-size: 14px; }',
                language: 'es',
                placeholder: 'Escribe tu mensaje aquí...',
                automatic_uploads: true,
                paste_data_images: true,
                images_upload_handler: (blobInfo) => new Promise((resolve) => {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) { resolve('data:' + blobInfo.blob().type + ';base64,' + blobInfo.base64()); return; }
                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename() || 'imagen.png');
                    fetch('/tickets/subir-imagen-tinymce', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken.getAttribute('content'), 'Accept': 'application/json' },
                        body: formData,
                    }).then(r => r.json().then(d => ({ status: r.status, data: d })))
                      .then(({ status, data }) => resolve(status === 200 && data.location ? data.location : 'data:' + blobInfo.blob().type + ';base64,' + blobInfo.base64()))
                      .catch(() => resolve('data:' + blobInfo.blob().type + ';base64,' + blobInfo.base64()));
                }),
                setup: (editor) => {
                    this.tinyMCEInstance = editor;
                    ['input', 'change', 'keyup', 'NodeChange'].forEach(ev => editor.on(ev, () => { this.nuevoMensaje = editor.getContent(); }));
                    editor.on('init', () => this.actualizarEstadoEditor());
                },
            });
        },

        actualizarEstadoEditor() {
            const bloqueado = this.bloqueadoEnvio;
            if (this.tinyMCEInstance) {
                try { this.tinyMCEInstance.mode.set(bloqueado ? 'readonly' : 'design'); } catch (e) {}
            }
            const textarea = document.getElementById('mant-editor-mensaje');
            if (textarea) textarea.disabled = bloqueado;
        },

        tieneContenido() {
            if (this.tinyMCEInstance) {
                try {
                    const texto = this.tinyMCEInstance.getContent().replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
                    if (texto.length > 0) return true;
                } catch (e) {}
            }
            return (this.nuevoMensaje || '').replace(/<[^>]*>/g, '').trim().length > 0;
        },

        limpiarEditor() {
            this.nuevoMensaje = '';
            if (this.tinyMCEInstance) this.tinyMCEInstance.setContent('');
            this.archivosAdjuntos = [];
            const input = document.getElementById('mant-adjuntos');
            if (input) input.value = '';
        },

        manejarArchivosSeleccionados(event) {
            this.procesarArchivos(Array.from(event.target.files || []));
        },

        procesarArchivos(files) {
            const tipos = ['.pdf', '.doc', '.docx', '.txt', '.jpg', '.jpeg', '.png', '.gif', '.xlsx', '.xls'];
            const max = 10 * 1024 * 1024;
            files.forEach(file => {
                const ext = '.' + file.name.split('.').pop().toLowerCase();
                if (!tipos.includes(ext)) { alert(`El archivo "${file.name}" no es un tipo permitido`); return; }
                if (file.size > max) { alert(`El archivo "${file.name}" excede 10MB`); return; }
                this.archivosAdjuntos.push(file);
            });
            const input = document.getElementById('mant-adjuntos');
            if (input) {
                const dt = new DataTransfer();
                this.archivosAdjuntos.forEach(f => dt.items.add(f));
                input.files = dt.files;
            }
        },

        handleDragOver(e) {
            if (this.bloqueadoEnvio) return;
            e.preventDefault();
            const area = document.getElementById('mant-drag-drop-area');
            if (area) { area.style.backgroundColor = 'rgba(59,130,246,0.15)'; area.style.borderColor = '#3B82F6'; area.style.borderStyle = 'solid'; }
        },
        handleDragLeave(e) {
            if (this.bloqueadoEnvio) return;
            e.preventDefault();
            const area = document.getElementById('mant-drag-drop-area');
            if (area && !area.contains(e.relatedTarget)) {
                area.style.backgroundColor = '';
                area.style.borderColor = '';
                area.style.borderStyle = 'dashed';
            }
        },
        handleDrop(e) {
            if (this.bloqueadoEnvio) return;
            e.preventDefault();
            const area = document.getElementById('mant-drag-drop-area');
            if (area) { area.style.backgroundColor = ''; area.style.borderColor = ''; area.style.borderStyle = 'dashed'; }
            this.procesarArchivos(Array.from(e.dataTransfer.files || []));
        },
        eliminarArchivo(index) {
            this.archivosAdjuntos.splice(index, 1);
            const input = document.getElementById('mant-adjuntos');
            if (input) {
                const dt = new DataTransfer();
                this.archivosAdjuntos.forEach(f => dt.items.add(f));
                input.files = dt.files;
            }
        },
        formatearTamañoArchivo(bytes) {
            if (!bytes) return '0 Bytes';
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + ['Bytes', 'KB', 'MB', 'GB'][i];
        },

        async enviarRespuesta() {
            if (this.cargando || this.bloqueadoEnvio) return;
            let contenido = this.tinyMCEInstance ? this.tinyMCEInstance.getContent() : this.nuevoMensaje;
            if (!contenido || contenido === '<p><br></p>' || !contenido.replace(/<[^>]*>/g, '').trim()) {
                Swal.fire({ icon: 'warning', title: 'Mensaje vacío', text: 'Escribe un mensaje antes de enviar.' });
                return;
            }

            this.cargando = true;
            try {
                const formData = new FormData();
                formData.append('mantenimiento_id', this.selected.id);
                formData.append('mensaje', contenido);
                formData.append('asunto', this.asuntoCorreo);
                this.archivosAdjuntos.forEach(f => formData.append('adjuntos[]', f));

                const response = await fetch('/tickets-mantenimiento/enviar-respuesta', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: formData,
                });
                const data = await response.json();
                if (data.success) {
                    this.limpiarEditor();
                    await this.cargarMensajes();
                    Swal.fire({ icon: 'success', title: 'Enviado', text: data.message, timer: 2000, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo enviar' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión al enviar' });
            } finally {
                this.cargando = false;
            }
        },

        /** Evita leer datos viejos si el modal se reabre antes del repintado de Livewire. */
        refrescarDatasetCard() {
            const el = this.selectedEl;
            if (!el || !el.isConnected) return;
            el.dataset.ticketEstatus = this.ticketEstatus;
            el.dataset.ticketPrioridad = this.ticketPrioridad;
            el.dataset.ticketCategoria = this.ticketCategoria;
            el.dataset.ticketResponsable = this.ticketResponsable;
        },

        async guardarTicket() {
            if (!this.selected.id) return;
            this.guardando = true;
            try {
                const response = await fetch('/tickets-mantenimiento/update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ ticketId: this.selected.id, prioridad: this.ticketPrioridad, estatus: this.ticketEstatus, responsable: this.ticketResponsable, categoria: this.ticketCategoria }),
                });
                const data = await response.json();
                if (data.success) {
                    this.selected.estatus = this.ticketEstatus;
                    this.estatusOriginal = this.ticketEstatus;
                    this.sincronizarSelectEstatus();
                    this.refrescarDatasetCard();
                    this.actualizarEstadoEditor();
                    Swal.fire({ icon: 'success', title: 'Guardado', text: data.message, timer: 2000, showConfirmButton: false });
                    if (window.Livewire) Livewire.emit('mantenimiento-estatus-actualizado');
                } else {
                    this.ticketEstatus = this.estatusOriginal;
                    this.sincronizarSelectEstatus();
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo guardar' });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión al guardar' });
            } finally {
                this.guardando = false;
            }
        },

        scrollToBottom() {
            const c = document.getElementById('mant-chat-container');
            if (c) c.scrollTop = c.scrollHeight;
        },
        obtenerIniciales(n) { return !n ? '??' : n.split(' ').map(x => x[0]).join('').toUpperCase().slice(0, 2); },
        formatearMensaje(m) {
            if (!m) return '';
            if (!/<[a-z][\s\S]*>/i.test(m)) return m.replace(/\n/g, '<br>').replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="text-blue-600 hover:underline">$1</a>');
            return m;
        },
        obtenerAdjuntos() {
            if (!this.selected?.imagen) return [];
            try {
                const a = typeof this.selected.imagen === 'string' ? JSON.parse(this.selected.imagen) : this.selected.imagen;
                return Array.isArray(a) ? a : (this.selected.imagen ? [this.selected.imagen] : []);
            } catch (e) { return this.selected.imagen ? [this.selected.imagen] : []; }
        },
        obtenerNombreArchivo(r) {
            if (!r) return 'Archivo';
            const s = typeof r === 'object' ? (r.name || r.path || r.url || '') : r;
            const n = String(s).split('/').pop();
            return n.includes('_') ? n.substring(n.indexOf('_') + 1) : n;
        },
        obtenerExtensionArchivo(r) {
            const n = this.obtenerNombreArchivo(r);
            const p = n.lastIndexOf('.');
            return p === -1 ? 'Sin extensión' : n.substring(p + 1).toUpperCase();
        },
        obtenerUrlArchivo(r) {
            if (!r) return '#';
            if (typeof r === 'string' && (r.startsWith('http://') || r.startsWith('https://'))) return r;
            if (typeof r === 'object' && r !== null) {
                if (r.url) return r.url;
                if (r.storage_path) return '/storage/' + r.storage_path.replace(/^\/+/, '');
                if (r.path) return r.path.startsWith('/') ? r.path : '/storage/' + r.path.replace(/^\/+/, '');
            }
            return typeof r === 'string' ? (r.startsWith('/storage/') ? r : '/storage/' + r.replace(/^\/+/, '')) : '#';
        },
    };
}
</script>
