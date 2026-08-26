<style>
    /* Paleta Analytics Dashboard: azul de datos + ámbar de atención.
       Tokens semánticos, nunca hex suelto en los componentes. */
    .aud {
        --aud-primary: #4F46E5;
        --aud-primary-2: #6366F1;
        --aud-primary-soft: #EEF0FF;
        --aud-accent: #B45309;
        --aud-accent-soft: #FEF3C7;
        --aud-danger: #DC2626;
        --aud-danger-soft: #FEE2E2;
        --aud-ok: #059669;
        --aud-ok-soft: #D1FAE5;

        --aud-surface: #FFFFFF;
        --aud-surface-2: #F8FAFC;
        --aud-text: #0F172A;
        --aud-text-muted: #64748B;
        --aud-border: #E5E9F0;

        --aud-radius-sm: 0.55rem;
        --aud-radius: 0.9rem;
        --aud-radius-lg: 1.15rem;

        --aud-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.05), 0 1px 1px rgba(15, 23, 42, 0.03);
        --aud-shadow: 0 4px 14px rgba(30, 41, 59, 0.07), 0 1px 3px rgba(30, 41, 59, 0.05);
        --aud-shadow-lg: 0 18px 38px rgba(30, 41, 59, 0.14), 0 4px 10px rgba(30, 41, 59, 0.06);

        color: var(--aud-text);
    }

    .dark .aud {
        --aud-primary: #818CF8;
        --aud-primary-2: #A5B4FC;
        --aud-primary-soft: rgba(99, 102, 241, 0.16);
        --aud-accent: #FBBF24;
        --aud-accent-soft: rgba(217, 119, 6, 0.18);
        --aud-danger: #FCA5A5;
        --aud-danger-soft: rgba(220, 38, 38, 0.18);
        --aud-ok: #6EE7B7;
        --aud-ok-soft: rgba(5, 150, 105, 0.18);

        --aud-surface: #131B2E;
        --aud-surface-2: #1B2540;
        --aud-text: #E7ECF6;
        --aud-text-muted: #98A6C2;
        --aud-border: #2B3757;

        --aud-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.25);
        --aud-shadow: 0 4px 16px rgba(0, 0, 0, 0.32);
        --aud-shadow-lg: 0 20px 44px rgba(0, 0, 0, 0.45);
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
        border-radius: var(--aud-radius);
        background: var(--aud-surface);
        box-shadow: var(--aud-shadow-sm);
        position: relative;
        overflow: hidden;
    }

    .aud-bar::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: linear-gradient(180deg, var(--aud-primary), var(--aud-primary-2));
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
        border-radius: var(--aud-radius);
        background: var(--aud-surface);
        box-shadow: var(--aud-shadow-sm);
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
        border-radius: var(--aud-radius-sm);
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background .18s ease, border-color .18s ease, color .18s ease,
                    transform .12s ease, box-shadow .18s ease;
    }

    .aud-btn--primary {
        background: #101D49;
        color: #fff;
        box-shadow: 0 1px 2px rgba(16, 29, 73, 0.15);
    }

    .aud-btn--primary:hover { background: #0C1638; }

    .dark .aud-btn--primary,
    .dark .aud-btn--primary:hover {
        background: #101D49;
        color: #fff;
    }

    .dark .aud-btn--primary:hover { background: #0C1638; }

    .aud-btn--ghost {
        background: transparent;
        border-color: var(--aud-border);
        color: var(--aud-text);
    }

    .aud-btn--ghost:hover { background: var(--aud-surface-2); border-color: var(--aud-primary); }

    .aud-btn--danger {
        background: transparent;
        border-color: var(--aud-danger);
        color: var(--aud-danger);
    }

    .aud-btn--danger:hover { background: var(--aud-danger-soft); }

    @media (prefers-reduced-motion: reduce) {
        .aud-btn--primary:hover { transform: none; }
    }

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

    /* ── Ancho y alto útiles ──
       El header y el sidebar son fijos, pero el área de adentro no tiene por qué
       respirar padding de sobra: en una tabla de auditoría cada centímetro es una
       fila más a la vista. Se recorta sólo mientras esta vista está en pantalla,
       con :has(), para no dejar clases pegadas cuando AppNav cambia de página. */
    #app-main:has(.aud) {
        padding: 0.6rem 0.75rem;
    }

    .aud .index-page { padding: 0 0 0.5rem; }

    .aud .index-page__header { padding-bottom: 0.35rem; }

    .aud .index-page__card {
        padding: 1.1rem 1.25rem;
        border-radius: var(--aud-radius-lg);
    }

    /* Aire entre tarjetas (antes/ahora, equipos/licencias): pegadas se leían como
       una sola tabla partida a la mitad. */
    .aud .index-page__card + .index-page__card { margin-top: 1.1rem; }


    @media (min-width: 1280px) {
        #app-main:has(.aud) { padding: 0.75rem 1rem; }
    }

    .aud-flash:empty { display: none; }

    /* ── Tarjetas de comparación (equipos/licencias) ──────────────────────────
       Reemplazan la tabla: cada renglón es su propia tarjeta, así el estado
       (se agregó / se quitó / cambió) se lee de un vistazo por color + icono +
       texto, nunca sólo por color. */
    .aud-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr));
        gap: 0.9rem;
    }

    .aud-card {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        padding: 0.9rem 1rem;
        border: 1px solid var(--aud-border);
        border-left: 4px solid var(--aud-border);
        border-radius: var(--aud-radius);
        background: var(--aud-surface);
        box-shadow: var(--aud-shadow-sm);
        animation: aud-card-entra 260ms ease-out both;
        transition: transform 160ms ease-out, box-shadow 160ms ease-out, border-color 160ms ease-out;
    }

    @keyframes aud-card-entra {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: none; }
    }

    .aud-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--aud-shadow);
    }

    .aud-card:has(:focus-visible) {
        border-color: var(--aud-primary);
    }

    .aud-card--nueva  { border-left-color: var(--aud-ok); }
    .aud-card--cambio { border-left-color: var(--aud-accent); }
    .aud-card--baja   { border-left-color: var(--aud-danger); }
    .aud-card--igual  { border-left-color: var(--aud-border); }

    /* Baja: ya no forma parte de esta corrida, se enseña como referencia. */
    .aud-card--fantasma {
        background: var(--aud-surface-2);
        opacity: 0.72;
    }

    .aud-card--fantasma .aud-card__titulo {
        text-decoration: line-through;
        text-decoration-color: var(--aud-danger);
    }

    .aud-card__badge {
        align-self: flex-start;
    }

    /* Aviso distinto del semáforo de comparación: no dice si cambió contra la
       otra columna, dice que el registro ya no existe en `inventarioequipo` —
       es del inventario en general, no de esta auditoría. */
    .aud-card__badge--baja-inv {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        align-self: flex-start;
        padding: 0.15rem 0.55rem;
        border: 1px dashed var(--aud-danger);
        border-radius: 999px;
        background: var(--aud-danger-soft);
        color: var(--aud-danger);
        font-size: 0.68rem;
        font-weight: 700;
    }

    .aud-card__cab {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
    }

    .aud-card__ico {
        margin-top: 0.15rem;
        color: var(--aud-text-muted);
    }

    .aud-card__titulo {
        font-size: 0.92rem;
        font-weight: 700;
        line-height: 1.3;
        word-break: break-word;
    }

    .aud-card__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem 1rem;
    }

    .aud-card__dato {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
        font-size: 0.72rem;
        color: var(--aud-text);
    }

    .aud-card__campo {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .aud-card__nota-vieja {
        margin: 0;
        padding: 0.4rem 0.6rem;
        border-left: 2px solid var(--aud-border);
        border-radius: 0 0.35rem 0.35rem 0;
        background: var(--aud-surface);
        color: var(--aud-text-muted);
        font-size: 0.78rem;
        font-style: italic;
        line-height: 1.4;
    }

    @media (prefers-reduced-motion: reduce) {
        .aud-card { animation: none; }
        .aud-card:hover { transform: none; }
    }

    /* ── Comparación en dos columnas ───────────────────────────────────────
       Izquierda = referencia elegible y de sólo lectura. Derecha = la corrida
       abierta, fija y editable. Una franja de color en la cabecera basta para
       distinguirlas sin depender del color como único indicador (llevan icono
       + texto: "Comparando con" vs "Esta auditoría"). */
    .aud-compare {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 1.1rem;
        align-items: start;
    }

    @media (max-width: 64rem) {
        .aud-compare { grid-template-columns: minmax(0, 1fr); }
    }

    .aud-compare__col {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        min-width: 0;
        padding: 1rem 1.1rem 1.2rem;
        border: 1px solid var(--aud-border);
        border-radius: var(--aud-radius-lg);
        background: var(--aud-surface);
        box-shadow: var(--aud-shadow-sm);
    }

    .aud-compare__col--izq {
        border-style: dashed;
    }

    .aud-compare__col--der {
        border-color: var(--aud-primary);
        box-shadow: 0 0 0 1px var(--aud-primary-soft);
    }

    .aud-compare__cabecera {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 0.75rem;
        padding-bottom: 0.75rem;
        margin-bottom: 0.15rem;
        border-bottom: 1px solid var(--aud-border);
    }

    .aud-compare__rotulo {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--aud-text-muted);
    }

    .aud-compare__rotulo--actual { color: var(--aud-primary); }

    .aud-compare__select {
        margin-left: auto;
        min-height: 38px;
        max-width: 16rem;
        font-size: 0.8rem;
    }

    /* Quién generó la corrida que muestra esta columna: una vez en la cabecera,
       no repetido tarjeta por tarjeta. Ocupa el ancho completo para que el
       select de arriba no lo empuje a competir por espacio en la misma línea. */
    .aud-compare__autor {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 1 1 100%;
        font-size: 0.72rem;
        color: var(--aud-text-muted);
    }

    .aud-compare__autor i { color: var(--aud-text-muted); }

    .aud-compare__seccion {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin: 0.85rem 0 0.6rem;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--aud-text);
    }

    .aud-compare__seccion i { color: var(--aud-text-muted); }

    /* Tarjetas de referencia: mismo componente, atenuado y sin hover de acción
       para que se lea como consulta, no como algo que se pueda tocar. */
    .aud-card--ref {
        background: var(--aud-surface-2);
        cursor: default;
    }

    .aud-card--ref:hover {
        transform: none;
        box-shadow: none;
        border-left-color: inherit;
    }

    /* "Se mantiene": el mismo lenguaje visual del badge de cambio, pero en gris
       neutro — hay algo que reportar (existe en la corrida de referencia) sin
       que compita con nueva/cambió/se quitó. */
    .aud-card--igual { border-left-color: var(--aud-border); }

    /* La licencia en la tarjeta de referencia no tiene <select>: el mismo chip
       de estado que pinta el editable a la derecha, aquí estático. */
    .aud-card--ref .aud-estado { align-self: flex-start; }

    /* ── Tabla ── */
    .aud .table-responsive {
        overflow-x: auto;
        border: 1px solid var(--aud-border);
        border-radius: var(--aud-radius);
        box-shadow: var(--aud-shadow-sm);
    }

    .aud .index-table.aud-grupos { border-collapse: separate; border-spacing: 0; }

    /* Encabezado fijo contra el scroll de la página, con un poco más de peso
       visual que antes: separaba mal de las filas y todo se leía plano. */
    .aud .index-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--aud-surface-2);
        border-bottom: 2px solid var(--aud-border);
    }

    .aud .index-table th,
    .aud .index-table td {
        padding: 0.7rem 0.9rem;
        font-size: 0.85rem;
        line-height: 1.4;
        vertical-align: middle;
    }

    .aud .index-table th {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        color: var(--aud-text-muted);
    }

    /* Cebra + hover propios, con nuestros tokens: sin esto el listado principal
       dependía del gris genérico de Bootstrap, que no respondía al tema oscuro
       del módulo igual que el resto de la pantalla. */
    .aud .index-table.aud-grupos tbody tr.aud-grupo:nth-child(4n+1) > td {
        background: var(--aud-surface-2);
    }

    .aud .index-table.aud-grupos tbody tr.aud-grupo:hover > td {
        background: var(--aud-surface-2) !important;
    }

    .aud .index-table.aud-grupos tbody tr.aud-grupo > td {
        border-bottom: 1px solid var(--aud-border);
        transition: background 120ms ease-out;
    }

    .aud-col-acciones { text-align: right; }
    .aud-col-acciones .aud-historial__acciones { justify-content: flex-end; }

    /* ── Empleado: iniciales + nombre ──
       Un círculo de color rompe la monotonía de puro texto y da un punto de
       referencia visual rápido al escanear la columna hacia abajo. */
    .aud-empleado {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
    }

    .aud-avatar {
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        width: 2.15rem;
        height: 2.15rem;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--aud-primary), var(--aud-primary-2));
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
    }

    .aud-empleado__datos { min-width: 0; }

    /* ── Folio de la última auditoría: chip con icono en vez de texto plano,
       igual que el resto del módulo lo hace con estados y alcances. ── */
    .aud-folio {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-bottom: 0.1rem;
        font-weight: 700;
        color: var(--aud-text);
    }

    .aud-folio i { color: var(--aud-primary); }

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
        background: var(--aud-primary-soft);
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
        border-radius: var(--aud-radius-sm);
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

    /* Velo sólido. Sin blur: sobre una tabla densa el desenfoque convierte el fondo
       en una mancha sucia en vez de apagarlo. */
    .aud-modal__fondo {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
    }

    .dark .aud-modal__fondo { background: rgba(2, 6, 23, 0.72); }

    /* Columna: cabecera y pie quietos, el cuerpo es lo único que se desplaza.
       Antes el cuadro entero hacía scroll y el título se perdía al bajar. */
    .aud-modal__caja {
        position: relative;
        display: flex;
        flex-direction: column;
        width: min(64rem, 100%);
        max-height: min(88vh, 52rem);
        overflow: hidden;
        border: 1px solid var(--aud-border);
        border-radius: var(--aud-radius-lg);
        background: var(--aud-surface);
        color: var(--aud-text);
        box-shadow: var(--aud-shadow-lg);
        animation: aud-modal-entra 200ms ease-out;
    }

    /* Entra desde su punto de origen, no aparece de golpe: da continuidad
       espacial entre el control que se tocó y lo que se abrió. */
    @keyframes aud-modal-entra {
        from { opacity: 0; transform: translateY(8px) scale(0.985); }
        to   { opacity: 1; transform: none; }
    }

    /* El modal de equipos lista fichas en dos columnas: a 64rem quedan estiradas. */
    .aud-modal__caja--sm { width: min(46rem, 100%); }

    /* Cabecera: el icono va en su propia pastilla para que el título arranque en
       una línea limpia y no compita con el glifo. */
    .aud-modal__cabecera {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex: 0 0 auto;
        padding: 0.9rem 1.15rem;
        border-bottom: 1px solid var(--aud-border);
        background: var(--aud-surface-2);
    }

    .aud-modal__titulo {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        margin: 0 0 0.15rem;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .aud-modal__titulo i {
        display: grid;
        place-items: center;
        width: 1.85rem;
        height: 1.85rem;
        flex: 0 0 auto;
        border-radius: 0.5rem;
        background: var(--aud-primary-soft);
        color: var(--aud-primary);
        font-size: 0.8rem;
    }

    .aud-modal__ayuda {
        margin: 0;
        padding-left: 2.4rem;      /* alineado bajo el título, no bajo el icono */
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--aud-text-muted);
    }

    .aud-modal__ayuda:empty { display: none; }

    /* Lo único desplazable del modal: un solo eje de scroll, sin regiones
       anidadas que se peleen con la rueda. */
    .aud-modal__cuerpo {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 1rem 1.15rem;
    }

    @media (prefers-reduced-motion: reduce) {
        .aud-modal__caja { animation: none; }
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

    /* El formulario es quien manda dentro del cuadro: la cabecera y el pie viven
       adentro de él, así que tiene que ser la columna. */
    .aud-modal__caja > form {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
    }

    .aud-modal__pie {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex: 0 0 auto;
        padding: 0.8rem 1.15rem;
        border-top: 1px solid var(--aud-border);
        background: var(--aud-surface-2);
    }

    /* ── Pasos del modal ──
       Lado a lado: cada paso tiene su propia lista desplazable, y apilados quedaban
       dos scrolls encimados. Cada columna es su propia caja. */
    .aud-pasos {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        align-items: stretch;
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 1rem 1.15rem;
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
        border-radius: var(--aud-radius);
        background: var(--aud-surface);
        box-shadow: var(--aud-shadow-lg);
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

    /* Semáforo del estado vigente: se empuja a la derecha para que la columna de
       nombres siga leyéndose en vertical. Vacío no ocupa espacio. */
    .aud-lic__estado {
        margin-left: auto;
        flex: 0 0 auto;
        padding: 0.1rem 0.45rem;
        border-radius: 999px;
        font-weight: 600;
        white-space: nowrap;
    }

    .aud-lic__estado:empty { display: none; }

    /* Licencia que el empleado no resguarda: se atenúa pero sigue elegible, por si
       el auditor encuentra una instalada que el inventario no tiene registrada. */
    .aud-lic.is-ajena { opacity: 0.55; }

    .aud-lic.is-ajena:hover { opacity: 1; }

    /* Equipo en el modal: se informa, no se elige. Sin cursor de clic ni hover,
       para que nadie intente marcarlo. */
    .aud-lic--info {
        cursor: default;
        background: var(--aud-surface-2);
    }

    .aud-lic--info:hover { border-color: var(--aud-border); }

    /* Encabezado de sección en el detalle. */
    .aud-seccion {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0 0 0.75rem;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--aud-text);
    }

    .aud-seccion i { color: var(--aud-text-muted); }

    .aud-paso__nota {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin: 0 0 0.5rem;
        color: var(--aud-text-muted);
        font-size: 0.78rem;
        font-weight: 600;
    }

    .aud-lic__estado--aldia {
        background: var(--aud-ok-soft);
        color: var(--aud-ok);
    }

    .aud-lic__estado--caducada {
        background: var(--aud-accent-soft);
        color: var(--aud-accent);
    }

    .aud-lic__estado--nunca {
        background: var(--aud-primary-soft);
        color: var(--aud-primary);
    }

    .aud-lic__estado--notiene {
        background: var(--aud-danger-soft);
        color: var(--aud-danger);
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

    /* ── Chips de cambio ──────────────────────────────────────────────────────
       Se usan como botón (filtros del detalle) y como etiqueta (semáforo del
       listado). El icono acompaña siempre al color: el estado no se comunica
       sólo con color. */
    .aud-marca {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.6rem;
        border: 1px solid transparent;
        border-radius: 999px;
        background: var(--aud-surface-2);
        color: var(--aud-text-muted);
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.5;
        white-space: nowrap;
    }

    button.aud-marca {
        cursor: pointer;
        transition: border-color 160ms ease-out, transform 160ms ease-out;
    }

    button.aud-marca:hover { border-color: currentColor; }
    button.aud-marca:active { transform: scale(0.97); }

    button.aud-marca.is-activo {
        border-color: currentColor;
        box-shadow: inset 0 0 0 1px currentColor;
    }

    .aud-marca .aud-num {
        padding-left: 0.2rem;
        font-weight: 700;
    }

    .aud-marca--todas { background: var(--aud-surface-2); color: var(--aud-text-muted); }
    .aud-marca--nueva { background: var(--aud-primary-soft); color: var(--aud-primary); }
    .aud-marca--cambio { background: var(--aud-accent-soft); color: var(--aud-accent); }
    .aud-marca--baja { background: var(--aud-danger-soft); color: var(--aud-danger); }
    .aud-marca--igual { background: var(--aud-ok-soft); color: var(--aud-ok); }

    /* Varios chips juntos: envuelven en vez de desbordar la celda. */
    .aud-semaforo,
    .aud-diff__marcas {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    /* ── Buscador del listado ─────────────────────────────────────────────────── */
    .aud-buscador {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.85rem;
    }

    .aud-buscador__campo {
        position: relative;
        flex: 1 1 22rem;
        min-width: 0;
    }

    .aud-buscador__campo i {
        position: absolute;
        top: 50%;
        left: 0.7rem;
        transform: translateY(-50%);
        color: var(--aud-text-muted);
        pointer-events: none;
    }

    .aud-buscador__campo .aud-buscar {
        width: 100%;
        padding-left: 2.1rem;
    }

    /* ── Equipos en la fila del listado ──────────────────────────────────────── */
    /* Badge con el conteo: la lista completa no cabe en la celda y con 16 equipos
       rompe el alto de la fila. El detalle se abre en modal. */
    .aud-chip-eq {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        border: 1px solid transparent;
        border-radius: 999px;
        background: var(--aud-primary-soft);
        color: var(--aud-primary);
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
        cursor: pointer;
        transition: border-color 160ms ease-out, filter 160ms ease-out;
    }

    .aud-chip-eq:hover {
        border-color: currentColor;
        filter: brightness(0.96);
    }

    .aud-chip-eq:active { transform: scale(0.97); }

    .aud-chip-eq:focus-visible {
        outline: 2px solid var(--aud-primary);
        outline-offset: 2px;
    }

    .aud-chip-eq i { color: var(--aud-primary); }

    /* Igual que el chip de equipos, pero informativo: no abre nada, así que sin
       cursor de clic ni hover que prometan una interacción que no existe. */
    .aud-chip-eq--info {
        cursor: default;
        transition: none;
    }

    .aud-chip-eq--info:hover {
        border-color: transparent;
        filter: none;
    }

    /* Origen del modal: nunca se ve, sólo se clona. */
    .aud-eqdatos { display: none; }

    /* Dos columnas: las fichas son angostas y en una sola columna dejan la mitad
       del modal en blanco. El scroll lo pone .aud-modal__cuerpo, no ellas. */
    #modalEquiposLista {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr));
        gap: 0.6rem;
        align-content: start;
    }

    #modalEquiposLista .aud-eqficha {
        padding: 0.6rem 0.75rem;
        gap: 0.35rem;
    }

    #modalEquiposLista .aud-eqficha__modelo { font-size: 0.9rem; }

    @media (prefers-reduced-motion: reduce) {
        .aud-chip-eq { transition: none; }
        .aud-chip-eq:active { transform: none; }
    }

    /* ── Listado por empleado ────────────────────────────────────────────────── */
    .aud-col-toggle {
        width: 3rem;
        text-align: center;
    }

    /* 44×44 de área táctil aunque el icono sea chico. */
    .aud-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        padding: 0;
        border: 1px solid transparent;
        border-radius: 0.5rem;
        background: transparent;
        color: var(--aud-text-muted);
        cursor: pointer;
        transition: background-color 160ms ease-out, color 160ms ease-out;
    }

    .aud-toggle:hover {
        background: var(--aud-surface-2);
        color: var(--aud-primary);
    }

    .aud-toggle:focus-visible {
        outline: 2px solid var(--aud-primary);
        outline-offset: 2px;
    }

    /* Sólo transform: no dispara reflow ni mueve la fila. */
    .aud-toggle i {
        transition: transform 180ms ease-out;
    }

    .aud-toggle.is-abierto i { transform: rotate(90deg); }

    .aud-grupo.is-abierto > td { background: var(--aud-surface-2); }

    .aud-conteo {
        display: inline-block;
        min-width: 1.9rem;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        background: var(--aud-primary-soft);
        color: var(--aud-primary);
        font-size: 0.8rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        text-align: center;
    }

    /* ── Historial desplegado ────────────────────────────────────────────────── */
    .aud-historial > td {
        padding: 0 !important;
        background: var(--aud-surface-2);
        border-top: 0;
    }

    /* app.css pinta `.index-table tbody tr:hover td` con !important. Como esta
       fila es un único <td colspan> que envuelve todo el panel, "hover la fila"
       significa "el mouse en cualquier parte del panel" y esa regla global
       repinta hasta las celdas de la sub-tabla anidada (se veía peor en la
       celda "Alcance", que ya trae su propio fondo). Se anula aquí. */
    .aud .index-table tbody tr.aud-historial:hover > td,
    .aud .index-table tbody tr.aud-historial:hover td {
        background: var(--aud-surface-2) !important;
    }

    /* La franja de color continúa el acento de la fila padre (chevron abierto):
       da continuidad visual entre el disparador y lo que se desplegó. El
       sangrado izquierdo alinea bajo la columna del empleado, no bajo el
       chevron, para que se lea como dependiente de la fila de arriba. */
    .aud-historial__inner {
        padding: 0.15rem 0.9rem 0.9rem 2.6rem;
        opacity: 0;
        transform: translateY(-4px);
        transition: opacity 200ms ease-out, transform 200ms ease-out;
    }

    .aud-historial__inner.is-abierto {
        opacity: 1;
        transform: none;
    }

    /* Tarjeta propia, separada de la fila padre por aire real (no sólo un borde):
       así el historial se lee como un panel que se despliega, no como una fila
       de tabla más apretada entre las demás. */
    .aud-historial__panel {
        border: 1px solid var(--aud-border);
        border-left: 3px solid var(--aud-primary);
        border-radius: var(--aud-radius);
        background: var(--aud-surface);
        box-shadow: var(--aud-shadow);
        overflow: hidden;
    }

    .aud-historial__tabla {
        width: 100%;
        border-collapse: collapse;
    }

    .aud-historial__tabla thead th {
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid var(--aud-border);
        background: var(--aud-surface-2);
        color: var(--aud-text-muted);
        font-size: 0.65rem;
        font-weight: 700;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }

    .aud-historial__tabla td {
        padding: 0.55rem 0.75rem;
        border-bottom: 1px solid var(--aud-border);
        color: var(--aud-text);
        font-size: 0.78rem;
        line-height: 1.4;
        vertical-align: middle;
    }

    /* Cebra sutil: con varias corridas, la vista sin líneas de fondo se volvía un
       bloque de texto continuo y era fácil perder la fila a mitad de lectura. */
    .aud-historial__tabla tbody tr:nth-child(even) td {
        background: var(--aud-surface-2);
    }

    .aud-historial__tabla tbody tr:hover td {
        background: var(--aud-surface-2) !important;
    }

    .aud-historial__tabla tr:last-child td { border-bottom: 0; }

    .aud-historial__tabla tbody tr[hidden] { display: none; }

    /* ── Paginado del historial expandido ── */
    .aud-historial__pag {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        padding: 0.55rem 0.75rem;
        border-top: 1px solid var(--aud-border);
        background: var(--aud-surface-2);
    }

    .aud-historial__pag[hidden] { display: none; }

    .aud-historial__pag button {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        min-height: 30px;
        padding: 0 0.6rem;
        border: 1px solid var(--aud-border);
        border-radius: var(--aud-radius-sm);
        background: var(--aud-surface);
        color: var(--aud-text);
        font-size: 0.72rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease;
    }

    .aud-historial__pag button:hover:not(:disabled) { background: var(--aud-surface-2); border-color: var(--aud-primary); }

    .aud-historial__pag button:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    /* Alcance de la corrida: dos chips con icono en vez de texto suelto "3 eq ·
       2 lic" — así se lee de un vistazo y no queda como celda vacía sin estilo. */
    .aud-alcance {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
    }

    .aud-alcance__item {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.15rem 0.5rem;
        border: 1px solid var(--aud-border);
        border-radius: 999px;
        background: var(--aud-surface-2);
        color: var(--aud-text-muted);
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .aud-alcance__item i { font-size: 0.68rem; }

    .aud-alcance__item .aud-num {
        color: var(--aud-text);
        font-weight: 700;
    }

    /* Chips y botones bajan un escalón dentro del historial: compiten con la fila
       principal si conservan su tamaño. */
    .aud-historial .aud-marca {
        padding: 0.1rem 0.45rem;
        font-size: 0.68rem;
        gap: 0.25rem;
    }

    .aud-historial .aud-btn--sm {
        min-height: 28px;
        padding: 0 0.55rem;
        font-size: 0.72rem;
    }

    .aud-historial .aud-semaforo { gap: 0.3rem; }

    .aud-historial__acciones {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
    }

    /* El despliegue es información, no decoración: sin motion sigue funcionando,
       sólo aparece de golpe. */
    @media (prefers-reduced-motion: reduce) {
        .aud-toggle i,
        .aud-historial__inner,
        button.aud-marca {
            transition: none;
        }

        .aud-historial__inner {
            opacity: 1;
            transform: none;
        }
    }

    @media (max-width: 640px) {
        .aud-historial__inner { padding-left: 1rem; }

        /* La sub-tabla no cabe angosta: se deja hacer scroll horizontal propio
           en vez de aplastar columnas hasta volverlas ilegibles. */
        .aud-historial__panel {
            overflow-x: auto;
        }
    }
</style>


