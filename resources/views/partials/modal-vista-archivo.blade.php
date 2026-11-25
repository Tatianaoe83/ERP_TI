<div id="modalVistaArchivo" class="hidden" role="dialog" aria-modal="true" aria-labelledby="vistaArchivoTitulo" onclick="if (event.target === this) cerrarVistaArchivo()"
    style="position:fixed;inset:0;z-index:100000;width:100vw;height:100vh;margin:0;display:none;align-items:center;justify-content:center;background:rgba(2,6,23,.82);">
    <div class="erp-modal-panel flex flex-col rounded-xl shadow-2xl overflow-hidden"
        style="width:min(1100px,94vw);height:min(88vh,900px);">
        <div class="erp-modal-panel__bar flex items-center justify-between gap-3 px-4 py-3">
            <p id="vistaArchivoTitulo" class="text-sm font-semibold truncate"></p>
            <button type="button" onclick="cerrarVistaArchivo()" class="erp-modal-panel__cerrar p-2 rounded-lg cursor-pointer" aria-label="Cerrar">
                <span class="text-xl leading-none">×</span>
            </button>
        </div>
        <div class="erp-modal-panel__lienzo flex-1 min-h-0 flex items-center justify-center overflow-auto">
            <img id="vistaArchivoImg" alt="" class="hidden max-w-full max-h-full object-contain">
            <iframe id="vistaArchivoFrame" title="Vista del archivo" class="hidden w-full h-full" style="border:0;background:transparent;"></iframe>
        </div>
    </div>
</div>
<script>
    function esImagenArchivo(nombre) {
        return /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(nombre || '');
    }
    // El modal se mueve a <body> al abrirlo, fuera de #app-shell-extras: la navegación interna
    // trae uno nuevo pero el viejo se queda ahí. Con dos del mismo id, cerrar agarraba el que
    // no estaba abierto y la vista se quedaba trabada. Se deja solo el más reciente.
    function obtenerModalVistaArchivo() {
        var modales = document.querySelectorAll('#modalVistaArchivo');
        var actual = document.querySelector('#app-shell-extras #modalVistaArchivo') || modales[modales.length - 1];
        modales.forEach(function (m) { if (m !== actual) m.remove(); });
        return actual;
    }
    function verArchivoEnPantalla(url, nombre) {
        if (!url || url === '#') return;
        var modal = obtenerModalVistaArchivo();
        if (!modal) return;
        var frame = modal.querySelector('#vistaArchivoFrame');
        var img = modal.querySelector('#vistaArchivoImg');
        var titulo = modal.querySelector('#vistaArchivoTitulo');
        if (!frame || !img) return;
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        if (titulo) titulo.textContent = nombre || 'Archivo';
        if (esImagenArchivo(nombre) || esImagenArchivo(url)) {
            frame.classList.add('hidden');
            frame.src = 'about:blank';
            img.alt = nombre || 'Archivo';
            img.src = url;
            img.classList.remove('hidden');
        } else {
            img.classList.add('hidden');
            img.removeAttribute('src');
            frame.classList.remove('hidden');
            frame.src = url;
        }
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }
    function cerrarVistaArchivo() {
        // Cierra todas las copias, por si quedó alguna abierta de antes.
        document.querySelectorAll('#modalVistaArchivo').forEach(function (modal) {
            var frame = modal.querySelector('#vistaArchivoFrame');
            var img = modal.querySelector('#vistaArchivoImg');
            if (frame) frame.src = 'about:blank';
            if (img) img.removeAttribute('src');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var abierto = Array.prototype.some.call(document.querySelectorAll('#modalVistaArchivo'), function (m) { return m.style.display === 'flex'; });
        if (!abierto) return;
        e.preventDefault(); // que el modal de abajo no se cierre con el mismo Esc
        cerrarVistaArchivo();
    });
    window.verArchivoEnPantalla = verArchivoEnPantalla;
    window.cerrarVistaArchivo = cerrarVistaArchivo;
</script>
