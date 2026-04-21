/**
 * Extracción de texto de facturas PDF en el navegador (pdf.js + tesseract.js).
 * Los modelos y workers se sirven desde /public/vendor/* (sin Tesseract ni Imagick en el servidor — compatible con HostGator).
 */
function baseOcr() {
    if (typeof window === 'undefined' || !window.__facturaOcrBase) {
        return '';
    }
    return String(window.__facturaOcrBase).replace(/\/$/, '');
}

/**
 * @param {File} file
 * @param {(n: number, msg: string) => void} [onProgress]
 * @returns {Promise<string>}
 */
async function extraerTextoFacturaPdfParaServidor(file, onProgress) {
    const base = baseOcr();
    const report = (n, msg) => {
        if (typeof onProgress === 'function') onProgress(n, msg);
    };

    report(0.05, 'Leyendo PDF…');
    const pdfjsLib = await import('pdfjs-dist/build/pdf.mjs');
    pdfjsLib.GlobalWorkerOptions.workerSrc = `${base}/vendor/pdfjs/pdf.worker.min.mjs`;

    const buf = await file.arrayBuffer();
    const pdf = await pdfjsLib.getDocument({ data: buf }).promise;
    const page = await pdf.getPage(1);

    const textContent = await page.getTextContent();
    const textoCapa = textContent.items
        .map((it) => (it && typeof it.str === 'string' ? it.str : ''))
        .join(' ');
    const textoCapaNorm = textoCapa.replace(/\s+/g, ' ').trim();
    if (textoCapaNorm.length >= 40) {
        report(1, 'Texto del PDF (capa de texto)');
        return textoCapaNorm;
    }

    report(0.15, 'Poca capa de texto; preparando imagen para OCR…');
    const scale = 2;
    const viewport = page.getViewport({ scale });
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await page.render({ canvasContext: ctx, viewport }).promise;

    report(0.25, 'OCR (Tesseract.js en el navegador)…');
    const { createWorker } = await import('tesseract.js');

    const worker = await createWorker(['spa', 'eng'], 1, {
        workerPath: `${base}/vendor/tesseract-js/worker.min.js`,
        corePath: `${base}/vendor/tesseract-js-core/`,
        langPath: `${base}/vendor/tesseract-lang/`,
        logger: (m) => {
            if (m.status === 'recognizing text' && typeof m.progress === 'number') {
                report(0.25 + m.progress * 0.7, 'OCR…');
            }
        },
    });

    try {
        const {
            data: { text },
        } = await worker.recognize(canvas);
        await worker.terminate();
        const t = (text || '').trim();
        report(1, t ? 'OCR completado' : 'OCR sin texto');
        return t || textoCapaNorm;
    } catch (e) {
        try {
            await worker.terminate();
        } catch (_) {}
        throw e;
    }
}

window.extraerTextoFacturaPdfParaServidor = extraerTextoFacturaPdfParaServidor;
