<style>
    /* Paleta Analytics Dashboard: azul de datos + ámbar de atención.
       Tokens semánticos, nunca hex suelto en los componentes. */
    .aud {
        --aud-primary: #1E40AF;
        --aud-primary-soft: #DBEAFE;
        --aud-accent: #B45309;
        --aud-accent-soft: #FEF3C7;
        --aud-danger: #B91C1C;
        --aud-danger-soft: #FEE2E2;
        --aud-ok: #047857;
        --aud-ok-soft: #D1FAE5;

        --aud-surface: #FFFFFF;
        --aud-surface-2: #F8FAFC;
        --aud-text: #0F172A;
        --aud-text-muted: #526077;
        --aud-border: #E2E8F0;

        color: var(--aud-text);
    }

    .dark .aud {
        --aud-primary: #60A5FA;
        --aud-primary-soft: rgba(59, 130, 246, 0.16);
        --aud-accent: #FBBF24;
        --aud-accent-soft: rgba(217, 119, 6, 0.18);
        --aud-danger: #FCA5A5;
        --aud-danger-soft: rgba(220, 38, 38, 0.18);
        --aud-ok: #6EE7B7;
        --aud-ok-soft: rgba(5, 150, 105, 0.18);

        --aud-surface: #0F172A;
        --aud-surface-2: #131C31;
        --aud-text: #E2E8F0;
        --aud-text-muted: #9FB0C7;
        --aud-border: #334155;
    }

    /* Etiqueta sólo para lector de pantalla (no depende de Bootstrap). */
    .aud-sr {
        position: absolute;
        width: 1px;
        height: 1px;
        margin: -1px;
        padding: 0;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Foco visible en todo lo interactivo: nunca se quita el anillo. */
    .aud :is(button, a, textarea, input, select):focus-visible {
        outline: 2px solid var(--aud-primary);
        outline-offset: 2px;
        border-radius: 0.4rem;
    }

    /* ── Barra de acción ── */
    .aud-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid var(--aud-border);
        border-left: 4px solid var(--aud-primary);
        border-radius: 0.75rem;
        background: var(--aud-surface);
    }

    .aud-bar__label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--aud-text-muted);
    }

    .aud-bar__valor {
        font-size: 0.95rem;
        font-weight: 600;
    }

    /* ── Encabezado de la corrida ──
       Una sola tarjeta: quién la generó, a quién se auditó y contra qué. El alcance
       de licencias salió de aquí porque el detalle ya trae una fila por licencia. */
    .aud-meta {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .aud-meta__card {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 1.75rem;
        padding: 0.7rem 1rem;
        border: 1px solid var(--aud-border);
        border-radius: 0.75rem;
        background: var(--aud-surface);
    }

    .aud-meta__dato {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        min-width: 0;
    }

    .aud-meta__label {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--aud-text-muted);
    }

    .aud-meta__valor {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
        font-weight: 600;
        line-height: 1.35;
    }

    .aud-meta__tenue {
        font-weight: 500;
        color: var(--aud-text-muted);
    }

    .aud-meta__sep { color: var(--aud-border); }

    .aud-meta__link {
        color: var(--aud-primary);
        text-decoration: none;
        border-bottom: 1px solid transparent;
    }

    .aud-meta__link:hover {
        color: var(--aud-primary);
        border-bottom-color: currentColor;
    }

    /* Conteo pegado a la etiqueta: dice cuántos son antes de leer la lista. */
    .aud-meta__conteo {
        display: inline-grid;
        place-items: center;
        min-width: 1.35rem;
        padding: 0 0.35rem;
        border-radius: 999px;
        background: var(--aud-primary-soft);
        color: var(--aud-primary);
        font-size: 0.68rem;
        letter-spacing: 0;
    }

    /* El equipo ocupa la fila completa: es el bloque más ancho de la tarjeta. */
    .aud-meta__dato--equipo { flex: 1 1 100%; }

    @media (max-width: 40rem) {
        .aud-meta__card { gap: 0.6rem 1.25rem; }
        .aud-meta__dato { flex: 1 1 100%; }
    }

    /* ── Ficha de equipo ──
       Se reutiliza en la celda del listado y en el encabezado del detalle, para
       que el mismo dato se lea igual en los dos sitios. */
    .aud-equipos {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .aud-eqficha {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-width: 0;
        padding: 0.45rem 0.6rem;
        border: 1px solid var(--aud-border);
        border-left: 3px solid var(--aud-primary);
        border-radius: 0.5rem;
        background: var(--aud-surface-2);
    }

    .aud-eqficha__top {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .aud-eqficha__cat {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--aud-text-muted);
    }

    .aud-eqficha__modelo {
        font-size: 0.85rem;
        font-weight: 600;
        line-height: 1.3;
        color: var(--aud-text);
    }

    .aud-eqficha__ids {
        display: flex;
        flex-wrap: wrap;
        gap: 0.15rem 0.85rem;
    }

    /* Etiqueta arriba del valor: sin ella, serie y folio se confunden entre sí. */
    .aud-eqficha__id {
        display: flex;
        flex-direction: column;
        font-size: 0.72rem;
        color: var(--aud-text);
    }

    .aud-eqficha__etq {
        font-size: 0.62rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--aud-text-muted);
    }

    /* En la celda del listado el espacio es poco: la ficha se aprieta y las
       fichas se apilan en vez de competir a lo ancho. */
    .aud-equipos--compacto {
        flex-direction: column;
        gap: 0.35rem;
    }

    .aud-equipos--compacto .aud-eqficha {
        padding: 0.35rem 0.5rem;
    }

    .aud-equipos--compacto .aud-eqficha__modelo { font-size: 0.78rem; }

    /* ── Botones ── */
    .aud-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        min-height: 44px;          /* objetivo táctil mínimo */
        padding: 0 1.1rem;
        border: 1px solid transparent;
        border-radius: 0.6rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background .18s ease, border-color .18s ease, color .18s ease;
    }

    .aud-btn--primary {
        background: var(--aud-primary);
        color: #fff;
    }

    .dark .aud-btn--primary { color: #0B1220; }

    .aud-btn--primary:hover { filter: brightness(0.92); }

    .aud-btn--ghost {
        background: transparent;
        border-color: var(--aud-border);
        color: var(--aud-text);
    }

    .aud-btn--ghost:hover { background: var(--aud-surface-2); }

    .aud-btn--danger {
        background: transparent;
        border-color: var(--aud-danger);
        color: var(--aud-danger);
    }

    .aud-btn--danger:hover { background: var(--aud-danger-soft); }

    .aud-btn[disabled] {
        opacity: 0.45;
        cursor: not-allowed;
        filter: none;
    }

    .aud-btn--sm {
        min-height: 36px;
        padding: 0 0.75rem;
        font-size: 0.78rem;
    }

    /* ── Alto fijo ──
       #app-main ya tiene alto definido (el shell es h-screen). Se encadena height/flex
       hasta la tabla para que la página no crezca y el único scroll sea el de la tabla.
       Cualquier eslabón sin min-height:0 rompe la cadena. */
    /* El shell (#app) ya es h-screen overflow-hidden, pero body sigue desplazable y
       arrastra el header y el sidebar. Se bloquea sólo mientras esta vista está en
       pantalla: con :has() el candado se suelta solo cuando AppNav cambia de página,
       sin dejar clases pegadas en body. */
    html:has(.aud--fijo),
    body:has(.aud--fijo) {
        height: 100%;
        overflow: hidden;
        overscroll-behavior: none;
    }

    .aud--fijo {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
    }

    .aud--fijo .index-page,
    .aud--fijo .aud-tabla,
    .aud--fijo .index-page__card {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }

    .aud--fijo .index-page__header,
    .aud--fijo .aud-flash,
    .aud--fijo .aud-meta { flex: 0 0 auto; }

    .aud-flash:empty { display: none; }

    .aud--fijo .index-page__card { overflow: hidden; }

    /* El único scroll de la vista. `contain` evita que la rueda siga hacia el shell
       al llegar al final de la tabla. */
    .aud--fijo .table-responsive {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
        overscroll-behavior: contain;
    }

    /* DataTables mete controles, tabla y paginación en su wrapper: debe estirarse
       completo dentro del área desplazable. */
    .aud--fijo .dataTables_wrapper {
        display: flex;
        flex-direction: column;
        min-height: 100%;
    }

    /* ── Controles de DataTables ──
       Vienen con los estilos de Bootstrap: se reescriben con los tokens del módulo
       para que el selector de cantidad y el buscador no desentonen. */
    .aud-dt__barra {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem 0.75rem;
        padding: 0 0 0.6rem;
        margin-bottom: 0.6rem;
        border-bottom: 1px solid var(--aud-border);
    }

    .aud-dt__pie {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem 0.75rem;
        margin-top: auto;          /* empuja la paginación al fondo del área */
        padding-top: 0.6rem;
        border-top: 1px solid var(--aud-border);
    }

    .aud .dataTables_wrapper .dataTables_length label,
    .aud .dataTables_wrapper .dataTables_filter label {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin: 0;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--aud-text-muted);
    }

    .aud .dataTables_wrapper select,
    .aud .dataTables_wrapper input[type="search"] {
        height: 32px;
        border: 1px solid var(--aud-border);
        border-radius: 0.45rem;
        padding: 0 0.55rem;
        background: var(--aud-surface);
        color: var(--aud-text);
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: 0;
        text-transform: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .aud .dataTables_wrapper select {
        min-width: 4.25rem;
        cursor: pointer;
    }

    /* Lupa dentro del campo: el rótulo "Buscar:" de la librería no se lee como campo. */
    .aud .dataTables_wrapper input[type="search"] {
        width: 15rem;
        max-width: 100%;
        padding-left: 1.9rem;
        background-image: none;
    }

    .aud .dataTables_wrapper .dataTables_filter label {
        position: relative;
        font-size: 0;              /* oculta el texto "Buscar:" sin quitarlo del DOM */
        letter-spacing: 0;
    }

    .aud .dataTables_wrapper .dataTables_filter label::before {
        content: "\f002";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        font-size: 0.72rem;
        position: absolute;
        left: 0.65rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--aud-text-muted);
        pointer-events: none;
        z-index: 1;
    }

    .aud .dataTables_wrapper select:focus,
    .aud .dataTables_wrapper input[type="search"]:focus {
        border-color: var(--aud-primary);
        box-shadow: 0 0 0 2px var(--aud-primary-soft);
        outline: none;
    }

    .aud .dataTables_wrapper .dataTables_info {
        font-size: 0.72rem;
        color: var(--aud-text-muted);
        font-variant-numeric: tabular-nums;
    }

    /* Paginación de la librería, con el mismo lenguaje que la del listado. */
    .aud .dataTables_wrapper .pagination { gap: 0.25rem; margin: 0; }

    .aud .dataTables_wrapper .page-link {
        min-width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--aud-border);
        border-radius: 0.45rem;
        background: var(--aud-surface);
        color: var(--aud-text);
        font-size: 0.75rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .aud .dataTables_wrapper .page-link:hover {
        background: var(--aud-surface-2);
        color: var(--aud-text);
    }

    .aud .dataTables_wrapper .page-item.active .page-link {
        border-color: var(--aud-primary);
        background: var(--aud-primary-soft);
        color: var(--aud-primary);
    }

    .aud .dataTables_wrapper .page-item.disabled .page-link {
        opacity: 0.45;
        background: var(--aud-surface);
        color: var(--aud-text-muted);
    }

    @media (max-width: 40rem) {
        .aud .dataTables_wrapper .dataTables_filter { text-align: left; }
        .aud .dataTables_wrapper input[type="search"] { width: 100%; }
    }

    /* ── Tabla ── */
    .aud .table-responsive {
        overflow-x: auto;
    }

    /* Encabezado fijo contra el scroll de la página. */
    .aud .index-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--aud-surface);
    }

    /* Densidad: son celdas de una línea, no necesitan alto de formulario. */
    .aud .index-table th,
    .aud .index-table td {
        padding: 0.3rem 0.55rem;
        font-size: 0.8rem;
        line-height: 1.3;
        vertical-align: middle;
    }

    .aud .index-table th {
        font-size: 0.7rem;
        letter-spacing: 0.04em;
    }

    /* ── Chips: color + icono/texto, nunca color solo ── */
    .aud-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .aud-chip--stock  { background: var(--aud-ok-soft); color: var(--aud-ok); border-color: currentColor; }
    .aud-chip--extra  { background: var(--aud-accent-soft); color: var(--aud-accent); border-color: currentColor; }
    .aud-chip--share  { background: var(--aud-info-soft, #eff6ff); color: var(--aud-info, #1d4ed8); border-color: currentColor; }
    .aud-chip--propio { background: var(--aud-primary-soft); color: var(--aud-primary); border-color: currentColor; }

    .aud-mini {
        display: inline-block;
        margin: 0.1rem 0.2rem 0.1rem 0;
        padding: 0.15rem 0.45rem;
        border-radius: 0.4rem;
        background: var(--aud-surface-2);
        color: var(--aud-text);
        font-size: 0.72rem;
    }

    /* ── Estados de la fila ──
       Siempre icono + texto: el color acompaña, nunca es el único indicador.
       Los tokens ya traen variante clara y oscura, así que el contraste se
       mantiene en los dos temas sin duplicar reglas. */
    .aud-estado {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.18rem 0.5rem;
        border: 1px solid currentColor;
        border-radius: 0.4rem;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .aud-estado--si {
        background: var(--aud-ok-soft);
        color: var(--aud-ok);
    }

    .aud-estado--no {
        background: var(--aud-surface-2);
        color: var(--aud-text-muted);
    }

    .aud-estado--alerta {
        background: var(--aud-danger-soft);
        color: var(--aud-danger);
    }

    /* Sin revisar no es un fallo: se distingue del "no original" con ámbar
       y borde punteado, para que no se lea como incidencia. */
    .aud-estado--pendiente {
        background: var(--aud-accent-soft);
        color: var(--aud-accent);
        border-style: dashed;
        cursor: help;
    }

    /* ── Celdas capturables ──
       Es un <select> nativo: se ve como el estado que representa, pero conserva
       teclado, lector de pantalla y el desplegable del sistema. */
    /* Se conserva la flecha nativa del sistema: sin ella el control no se lee como
       desplegable. Sólo se reserva espacio a la derecha para que no pise el texto.

       Todo va anclado a .aud y con !important porque app.css trae reglas que le
       ganan por especificidad y por importancia:
         .dark select:disabled                 { background-color:#374151!important }
         .dark .index-table tbody tr:hover td  { background:#1f2937!important }
       Sin esto el color se pinta al cargar y lo pisa la hoja del proyecto. */
    .aud select.aud-editable {
        min-height: 36px;
        padding: 0.18rem 1.6rem 0.18rem 0.4rem;
        border: 1px solid currentColor !important;
        border-radius: 0.4rem;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.4;
        cursor: pointer;
        max-width: 100%;
    }

    .aud select.aud-editable:focus-visible {
        outline: 2px solid var(--aud-primary);
        outline-offset: 1px;
    }

    /* El disabled se distingue por opacidad y cursor, no sólo por el color. */
    .aud select.aud-editable:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .aud select.aud-editable--si,
    .aud select.aud-editable--si:disabled {
        background-color: var(--aud-ok-soft) !important;
        color: var(--aud-ok) !important;
    }

    .aud select.aud-editable--no,
    .aud select.aud-editable--no:disabled {
        background-color: var(--aud-surface-2) !important;
        color: var(--aud-text-muted) !important;
    }

    .aud select.aud-editable--alerta,
    .aud select.aud-editable--alerta:disabled {
        background-color: var(--aud-danger-soft) !important;
        color: var(--aud-danger) !important;
    }

    .aud select.aud-editable--pendiente,
    .aud select.aud-editable--pendiente:disabled {
        background-color: var(--aud-accent-soft) !important;
        color: var(--aud-accent) !important;
        border-style: dashed !important;
    }

    /* Las opciones las pinta el sistema: sin esto heredan el color del select y
       en tema oscuro salían texto claro sobre fondo claro. */
    .aud select.aud-editable option {
        background-color: var(--aud-surface);
        color: var(--aud-text);
        font-weight: 500;
    }

    /* El hover de la fila repinta el <td> con !important; el select tiene que
       conservar el suyo o el color parpadea al pasar el mouse. */
    .aud .index-table tbody tr:hover td .aud-editable {
        box-shadow: none;
    }

    .aud-num { font-variant-numeric: tabular-nums; }
    .aud-strong { font-weight: 600; }
    .aud-muted { color: var(--aud-text-muted); font-size: 0.78rem; }

    /* ── Estado vacío ── */
    .aud-vacio {
        display: grid;
        justify-items: center;
        gap: 0.6rem;
        padding: 3rem 1.5rem;
        text-align: center;
        color: var(--aud-text-muted);
    }

    .aud-vacio__ico {
        display: grid;
        place-items: center;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 999px;
        background: var(--aud-surface-2);
        font-size: 1.4rem;
        color: var(--aud-primary);
    }

    .aud-vacio__titulo {
        font-size: 1rem;
        font-weight: 600;
        color: var(--aud-text);
    }

    /* ── Paginación del listado ── */
    .aud-pag {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1rem;
        padding-top: 0.9rem;
        border-top: 1px solid var(--aud-border);
    }

    .aud-pag__botones {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .aud-pag__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-width: 44px;
        min-height: 44px;
        padding: 0 0.7rem;
        border: 1px solid var(--aud-border);
        border-radius: 0.55rem;
        background: var(--aud-surface);
        color: var(--aud-text);
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: background .18s ease, border-color .18s ease, color .18s ease;
    }

    .aud-pag__btn:hover { background: var(--aud-surface-2); color: var(--aud-text); }

    .aud-pag__btn.is-actual {
        border-color: var(--aud-primary);
        background: var(--aud-primary-soft);
        color: var(--aud-primary);
    }

    .aud-pag__btn.is-disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .aud-pag__gap {
        display: inline-flex;
        align-items: center;
        padding: 0 0.25rem;
        color: var(--aud-text-muted);
    }

    /* ── Aviso de validación ── */
    .aud-alerta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 1rem;
        margin-bottom: 1rem;
        border: 1px solid var(--aud-danger);
        border-radius: 0.6rem;
        background: var(--aud-danger-soft);
        color: var(--aud-danger);
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* ── Modal de generación ── */
    .aud-modal {
        position: fixed;
        inset: 0;
        z-index: 1080;
        display: grid;
        place-items: center;
        padding: 1rem;
    }

    .aud-modal[hidden] { display: none; }

    .aud-modal__fondo {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
    }

    .aud-modal__caja {
        position: relative;
        width: min(64rem, 100%);
        max-height: 88vh;
        overflow: auto;
        padding: 1.25rem;
        border: 1px solid var(--aud-border);
        border-radius: 0.9rem;
        background: var(--aud-surface);
        color: var(--aud-text);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.28);
    }

    .aud-modal__cabecera {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .aud-modal__titulo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0 0 0.3rem;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .aud-modal__ayuda {
        margin: 0;
        font-size: 0.8rem;
        color: var(--aud-text-muted);
    }

    .aud-modal__barra {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .aud-modal__acciones {
        display: flex;
        gap: 0.5rem;
        margin-left: auto;
    }

    .aud-buscar {
        flex: 1 1 14rem;
        min-height: 44px;
        padding: 0 0.75rem;
        border: 1px solid var(--aud-border);
        border-radius: 0.6rem;
        background: var(--aud-surface);
        color: var(--aud-text);
        font-size: 0.85rem;
    }

    .aud-buscar::placeholder { color: var(--aud-text-muted); }

    .aud-modal__vacio-busqueda {
        margin: 0.6rem 0 0;
        font-size: 0.8rem;
        color: var(--aud-text-muted);
    }

    .aud-modal__vacio-busqueda[hidden] { display: none; }

    .aud-modal__pie {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1rem;
        padding-top: 0.9rem;
        border-top: 1px solid var(--aud-border);
    }

    /* ── Pasos del modal ──
       Lado a lado: cada paso tiene su propia lista desplazable, y apilados quedaban
       dos scrolls encimados. Cada columna es su propia caja. */
    .aud-pasos {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        align-items: start;
    }

    .aud-paso {
        display: flex;
        flex-direction: column;
        min-width: 0;
        border: 1px solid var(--aud-border);
        border-radius: 0.7rem;
        padding: 0.75rem;
        margin: 0;
        background: var(--aud-surface-2);
    }

    @media (max-width: 60rem) {
        .aud-pasos { grid-template-columns: minmax(0, 1fr); }
    }

    .aud-paso__titulo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        width: auto;
        margin-bottom: 0.55rem;
        padding: 0;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .aud-paso__num {
        display: inline-grid;
        place-items: center;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 999px;
        background: var(--aud-primary-soft);
        color: var(--aud-primary);
        font-size: 0.72rem;
        font-variant-numeric: tabular-nums;
    }


    .aud-select {
        flex: 0 1 13rem;
        min-height: 44px;
        padding: 0 0.6rem;
        border: 1px solid var(--aud-border);
        border-radius: 0.6rem;
        background: var(--aud-surface);
        color: var(--aud-text);
        font-size: 0.85rem;
        cursor: pointer;
    }

    /* ── Filtros del alcance ──
       Rejilla propia: en fila estos cuatro selects se apretaban al punto de
       cortar los nombres de departamento. */
    .aud-filtros {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
        gap: 0.6rem;
        margin-bottom: 0.85rem;
    }

    .aud-campo {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        min-width: 0;
    }

    /* Etiqueta visible, no sólo placeholder: al elegir, el placeholder se va y
       con él la única pista de qué significa el campo. */
    .aud-campo__label {
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        color: var(--aud-text-muted);
    }

    .aud-campo__req { color: var(--aud-danger); }

    .aud-campo .aud-select { flex: 1 1 auto; width: 100%; }

    .aud-campo--empleado { margin-bottom: 0.85rem; }

    .aud-campo__ayuda {
        margin: 0;
        font-size: 0.75rem;
        line-height: 1.4;
        color: var(--aud-text-muted);
    }

    /* ── Combobox de empleado ── */
    .aud-combo {
        position: relative;
        display: flex;
        align-items: center;
    }

    .aud-combo__ico {
        position: absolute;
        left: 0.75rem;
        font-size: 0.8rem;
        color: var(--aud-text-muted);
        pointer-events: none;
    }

    .aud-combo__input {
        width: 100%;
        min-height: 46px;
        padding: 0 2.5rem 0 2.15rem;
        border: 1px solid var(--aud-border);
        border-radius: 0.6rem;
        background: var(--aud-surface);
        color: var(--aud-text);
        font-size: 0.9rem;
    }

    .aud-combo__input::placeholder { color: var(--aud-text-muted); }

    .aud-combo__input:focus-visible,
    .aud-select:focus-visible,
    .aud-buscar:focus-visible {
        outline: 2px solid var(--aud-primary);
        outline-offset: 1px;
    }

    /* Target de 44px aunque el icono sea chico. */
    .aud-combo__limpiar {
        position: absolute;
        right: 0.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border: none;
        border-radius: 0.5rem;
        background: transparent;
        color: var(--aud-text-muted);
        cursor: pointer;
    }

    .aud-combo__limpiar:hover { color: var(--aud-text); }
    .aud-combo__limpiar[hidden] { display: none; }

    .aud-combo__lista {
        position: absolute;
        top: calc(100% + 0.3rem);
        left: 0;
        right: 0;
        z-index: 20;
        max-height: 16rem;
        overflow-y: auto;
        margin: 0;
        padding: 0.25rem;
        list-style: none;
        border: 1px solid var(--aud-border);
        border-radius: 0.7rem;
        background: var(--aud-surface);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
    }

    .aud-combo__lista[hidden] { display: none; }

    .aud-combo__opcion {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        padding: 0.55rem 0.65rem;
        border-radius: 0.5rem;
        cursor: pointer;
    }

    .aud-combo__opcion[hidden] { display: none; }

    .aud-combo__opcion:hover,
    .aud-combo__opcion.is-activo {
        background: var(--aud-primary-soft);
    }

    /* Bloqueada: se atenúa y cambia el cursor, pero el motivo va escrito en la
       propia opción. La opacidad sola no explica por qué no responde. */
    .aud-combo__opcion.is-bloqueada {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .aud-combo__opcion.is-bloqueada:hover,
    .aud-combo__opcion.is-bloqueada.is-activo {
        background: var(--aud-surface-2);
    }

    .aud-mini--bloqueo {
        background: var(--aud-danger-soft);
        color: var(--aud-danger);
    }

    .aud-combo__nombre {
        font-size: 0.86rem;
        font-weight: 600;
        color: var(--aud-text);
    }

    .aud-combo__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
    }

    .aud-combo__vacio {
        padding: 0.7rem 0.65rem;
        font-size: 0.8rem;
        color: var(--aud-text-muted);
    }

    .aud-combo__vacio[hidden] { display: none; }

    /* ── Listas seleccionables ── */
    .aud-lic-lista {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr));
        gap: 0.5rem;
        max-height: 40vh;
        overflow: auto;
        padding: 0.25rem;
        border: 1px solid var(--aud-border);
        border-radius: 0.6rem;
        background: var(--aud-surface-2);
    }

    /* Dentro de una columna las listas van a un solo carril; ambas comparten alto
       para que las dos cajas queden parejas. */
    .aud-paso .aud-lic-lista {
        grid-template-columns: minmax(0, 1fr);
        max-height: 34vh;
        background: var(--aud-surface);
    }

    .aud-paso .aud-modal__barra { gap: 0.4rem; }

    .aud-paso .aud-buscar,
    .aud-paso .aud-select { flex: 1 1 100%; }

    .aud-paso .aud-modal__acciones { margin-left: 0; }

    .aud-lic {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.6rem 0.7rem;
        border: 1px solid var(--aud-border);
        border-radius: 0.55rem;
        background: var(--aud-surface);
        cursor: pointer;
    }

    .aud-lic[hidden] { display: none; }

    .aud-lic:hover { border-color: var(--aud-primary); }

    .aud-lic__check {
        width: 1.1rem;
        height: 1.1rem;
        accent-color: var(--aud-primary);
        flex: 0 0 auto;
    }

    .aud-lic__nombre {
        font-size: 0.85rem;
        font-weight: 600;
        word-break: break-word;
    }

    /* ── Fila de equipo ── */
    .aud-lic-lista--equipos { grid-template-columns: minmax(0, 1fr); }

    .aud-lic--equipo { align-items: flex-start; }

    .aud-equipo {
        display: grid;
        gap: 0.2rem;
        min-width: 0;
    }

    .aud-equipo__nombre {
        font-size: 0.82rem;
        font-weight: 600;
        word-break: break-word;
    }

    .aud-equipo__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.2rem;
    }

    .aud-equipo__gerencia {
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--aud-text-muted);
    }

    /* Respeta a quien pide menos movimiento */
    @media (prefers-reduced-motion: reduce) {
        .aud * { transition: none !important; animation: none !important; }
    }

    /* ── Textarea de observaciones ──────────────────────────────────────────── */
    .aud-obs {
        width: 100%;
        min-width: 14rem;
        padding: 0.35rem 0.55rem;
        font-size: 0.82rem;
        line-height: 1.4;
        color: var(--aud-text);
        background: var(--aud-surface-2);
        border: 1px solid var(--aud-border);
        border-radius: 0.45rem;
        resize: vertical;
        transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
    }
    .aud-obs:focus {
        outline: none;
        border-color: var(--aud-primary);
        box-shadow: 0 0 0 3px var(--aud-primary-soft);
    }
    /* Flash verde: guardado correctamente */
    .aud-obs--ok {
        border-color: var(--aud-ok);
        background: var(--aud-ok-soft);
        transition: border-color 0.15s, background 0.15s;
    }
    /* Flash rojo: falló el guardado */
    .aud-obs--error {
        border-color: var(--aud-danger);
        background: var(--aud-danger-soft);
        transition: border-color 0.15s, background 0.15s;
    }
    /* Cursor de espera mientras viaja */
    .aud-obs--guardando {
        opacity: 0.6;
        cursor: wait;
    }

    /* ── Columna "Auditoría anterior" ───────────────────────────────────────── */
    /* Agrupa el badge de marca + estado resumido + nota anterior */
    .aud-antes {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        min-width: 12rem;
    }
    .aud-antes__estado {
        font-size: 0.8rem;
        color: var(--aud-text-muted);
    }
    /* Nota de la corrida anterior: en gris claro, entre comillas, texto cortado */
    .aud-antes__nota {
        font-size: 0.75rem;
        font-style: italic;
        color: var(--aud-text-muted);
        opacity: 0.8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 22rem;
        cursor: help;          /* el title="" del elemento muestra el texto completo */
    }

    /* Texto atenuado genérico */
    .aud-muted {
        color: var(--aud-text-muted);
        font-size: 0.8rem;
    }

    /* Fila de baja: la licencia desapareció del inventario.
       Se atenúa para que destaque menos que las licencias activas,
       pero sigue visible para dejar constancia de la desaparición. */
    .aud-fila--baja td {
        opacity: 0.6;
    }
    .aud-fila--baja .aud-strong {
        text-decoration: line-through;
        text-decoration-color: var(--aud-danger);
    }
    /* Badge estático para la columna "¿Tiene licencia?" */
    .aud-badge {
        display: inline-block;
        padding: 0.15rem 0.55rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 999px;
        letter-spacing: 0.03em;
    }
    .aud-badge--si {
        background: var(--aud-ok-soft);
        color: var(--aud-ok);
    }
    .aud-badge--no {
        background: var(--aud-danger-soft);
        color: var(--aud-danger);
    }
</style>


