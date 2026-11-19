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
    function verArchivoEnPantalla(url, nombre) {
        if (!url || url === '#') return;
        var modal = document.getElementById('modalVistaArchivo');
        var frame = document.getElementById('vistaArchivoFrame');
        var img = document.getElementById('vistaArchivoImg');
        var titulo = document.getElementById('vistaArchivoTitulo');
        if (!modal || !frame || !img) return;
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
        var modal = document.getElementById('modalVistaArchivo');
        var frame = document.getElementById('vistaArchivoFrame');
        var img = document.getElementById('vistaArchivoImg');
        if (frame) frame.src = 'about:blank';
        if (img) img.removeAttribute('src');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var modal = document.getElementById('modalVistaArchivo');
        if (modal && modal.style.display === 'flex') cerrarVistaArchivo();
    });
    window.verArchivoEnPantalla = verArchivoEnPantalla;
    window.cerrarVistaArchivo = cerrarVistaArchivo;
</script>
