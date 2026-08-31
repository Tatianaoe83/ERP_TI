<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Dos tablas de conteo:
 *
 *   1. Equipos y licencias por gerencia — filtros propios e independientes:
 *      Obra y Tipo de equipo (pegan a las dos columnas), Categoría de equipo
 *      —PC / laptop, tal cual está en la BD— (sólo al conteo de equipos) y
 *      Licencia, con todo el catálogo (sólo al conteo de licencias).
 *   2. Catálogo de licencias del sistema — filtros en cascada: Gerencia
 *      acota Obra, Obra acota Empleado, Empleado acota Tipo de equipo. Cada
 *      uno sólo ofrece valores que de verdad existen bajo lo ya elegido. La
 *      licencia no se filtra con un desplegable más —juntar muchos filtros
 *      independientes hacía morir la hoja—: se filtra con el propio
 *      AutoFilter de Excel en la columna "Licencia" de la tabla.
 *
 * Los desplegables leen datos crudos escondidos a la derecha —un renglón por
 * equipo real y uno por licencia real, sin el cruce equipo×licencia que sí
 * usa la hoja de detalle—, así que cambiar un filtro sólo recalcula su
 * propia tabla.
 *
 * El detalle completo, renglón por renglón, vive aparte en
 * "Detalle equipos y licencias".
 */
class AuditoriaGerenciaSheet implements FromArray, WithTitle, WithEvents, WithStrictNullComparison
{
    private const TODAS = '(Todas)';

    private const MODALIDADES = ['Stock', 'Compartido', 'Propio'];

    private const FILA_TITULO    = 1;
    private const FILA_SUBTITULO = 2;

    /** Filas ocultas: fila 1 son claves de combo/depuración, los datos arrancan en la 2. */
    private const FILA_OCULTA_DATOS = 2;

    /** Ancho visible: lo marca la tabla 2, con 4 filtros. */
    private const ANCHO_VISIBLE = 4;

    /** Aire entre lo visible y los datos crudos escondidos. */
    private const AIRE = 1;

    private const TINTA        = '0F172A';
    private const TENUE        = '64748B';
    private const BORDE        = 'E2E8F0';
    private const CEBRA        = 'F8FAFC';
    private const PAPEL        = 'FFFFFF';
    private const ACENTO       = '4F46E5';
    private const ACENTO_HONDO = '312E81';
    private const ACENTO_TENUE = 'EEF2FF';

    // ── Geometría de la tabla 1 ────────────────────────────────────────────
    private const FILA_ROTULO_T1 = 4;
    private const FILA_FETQ_T1   = 5;
    private const FILA_FILTRO_T1 = 6;
    private const FILA_CAB_T1    = 8;
    private const FILA_DATOS_T1  = 9;

    private int $filaTotalT1;

    // ── Geometría de la tabla 2 (depende de cuántas gerencias hay) ─────────
    private int $filaRotuloT2;
    private int $filaFetqT2;
    private int $filaFiltroT2;
    private int $filaCabT2;
    private int $filaDatosT2;
    private int $filaTotalT2;

    /** Gerencias y licencias con renglón propio, ordenadas. */
    private array $gerencias;
    private array $licenciasCatalogo;

    /** Cuántos valores trae cada lista fija de desplegable, para su Data Validation. */
    private array $conteoListas = [];

    /**
     * @param array $rawEquipos   [[gerencia, obra, tipoEquipo, categoria], …]
     *                            un renglón por equipo real (laptop/PC) del
     *                            personal de planta; categoria es el
     *                            CategoriaEquipo crudo de la BD.
     * @param array $rawLicencias [[gerencia, obra, empleado, modalidadesCSV,
     *                            licencia], …] un renglón por licencia real;
     *                            modalidadesCSV es la lista ("Stock, Propio")
     *                            de modalidades que tiene ESE empleado en sus
     *                            equipos —la licencia no tiene modalidad
     *                            propia, la hereda de quien la trae encima—.
     */
    public function __construct(
        private string $titulo,
        private string $subtitulo,
        private array $rawEquipos,
        private array $rawLicencias,
    ) {
        $this->gerencias = collect($rawEquipos)->pluck(0)
            ->concat(collect($rawLicencias)->pluck(0))
            ->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        $this->licenciasCatalogo = collect($rawLicencias)->pluck(4)
            ->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        $this->filaTotalT1 = self::FILA_DATOS_T1 + count($this->gerencias);

        $this->filaRotuloT2 = $this->filaTotalT1 + 3;
        $this->filaFetqT2   = $this->filaRotuloT2 + 1;
        $this->filaFiltroT2 = $this->filaFetqT2 + 1;
        $this->filaCabT2    = $this->filaFiltroT2 + 2;
        $this->filaDatosT2  = $this->filaCabT2 + 1;
        $this->filaTotalT2  = $this->filaDatosT2 + count($this->licenciasCatalogo);
    }

    public function title(): string
    {
        return 'Auditoría equipos y licencias';
    }

    // ────────────────────────────── Geometría de datos ocultos ──────────────

    /** Cuántas columnas visibles + aire hay antes de los datos escondidos. */
    private function inicioOcultos(): int
    {
        return self::ANCHO_VISIBLE + self::AIRE;
    }

    private function letraOculta(int $offset): string
    {
        return Coordinate::stringFromColumnIndex($this->inicioOcultos() + $offset + 1);
    }

    // Bloque A: equipos crudos — Gerencia, Obra, TipoEquipo, Categoría, Coincide.
    private function letraGerenciaA(): string { return $this->letraOculta(0); }
    private function letraObraA(): string { return $this->letraOculta(1); }
    private function letraTipoA(): string { return $this->letraOculta(2); }
    private function letraCategoriaA(): string { return $this->letraOculta(3); }
    private function letraCoincideA(): string { return $this->letraOculta(4); }

    // Bloque B: licencias crudas — Gerencia, Obra, Empleado, ModalidadesCSV,
    // Licencia, CoincideT1 (filtros de la tabla 1), CoincideT2 (tabla 2).
    private function letraGerenciaB(): string { return $this->letraOculta(5); }
    private function letraObraB(): string { return $this->letraOculta(6); }
    private function letraEmpleadoB(): string { return $this->letraOculta(7); }
    private function letraModalidadesB(): string { return $this->letraOculta(8); }
    private function letraLicenciaB(): string { return $this->letraOculta(9); }
    private function letraCoincideT1(): string { return $this->letraOculta(10); }
    private function letraCoincideT2(): string { return $this->letraOculta(11); }

    // Listas fijas: tabla 1 completa (Obra, Tipo, Categoría de equipo y
    // Licencia), y la Gerencia de la tabla 2 (raíz de su cascada).
    // Obra/Empleado/Tipo de equipo de la tabla 2 no son listas fijas: son
    // combos en cascada, ver construirCascada().
    private function letraListaObraA(): string { return $this->letraOculta(12); }
    private function letraListaTipoA(): string { return $this->letraOculta(13); }
    private function letraListaCategoriaA(): string { return $this->letraOculta(14); }
    private function letraListaLicenciaT1(): string { return $this->letraOculta(15); }
    private function letraListaGerenciaT2(): string { return $this->letraOculta(16); }

    /** Primera columna absoluta (1-based) libre para escribir los combos en cascada. */
    private function colBaseCascada(): int
    {
        return $this->inicioOcultos() + 18;
    }

    private function ultimaFilaOculta(int $filas): int
    {
        return max(self::FILA_OCULTA_DATOS, self::FILA_OCULTA_DATOS + $filas - 1);
    }

    private function rango(string $letra, int $filas): string
    {
        $ultima = $this->ultimaFilaOculta($filas);

        return sprintf('$%s$%d:$%s$%d', $letra, self::FILA_OCULTA_DATOS, $letra, $ultima);
    }

    // ────────────────────────────── Contenido ────────────────────────────────

    public function array(): array
    {
        $listaObraA = array_merge([self::TODAS], $this->valoresUnicos($this->rawEquipos, 1));
        $listaTipoA = array_merge([self::TODAS], self::MODALIDADES);
        $listaCategoriaA = array_merge([self::TODAS], $this->valoresUnicos($this->rawEquipos, 3));
        $listaLicenciaT1 = array_merge([self::TODAS], $this->licenciasCatalogo);
        $listaGerenciaT2 = array_merge([self::TODAS], $this->gerencias);

        $anchoOculto = $this->inicioOcultos() + 18;
        $filasNecesarias = max(
            $this->filaTotalT2,
            $this->ultimaFilaOculta(count($this->rawEquipos)),
            $this->ultimaFilaOculta(count($this->rawLicencias)),
            $this->ultimaFilaOculta(max(
                count($listaObraA), count($listaTipoA), count($listaCategoriaA),
                count($listaLicenciaT1), count($listaGerenciaT2)
            ))
        );

        $hoja = array_fill(0, $filasNecesarias, array_fill(0, $anchoOculto, null));

        $hoja[self::FILA_TITULO - 1][0]    = $this->titulo;
        $hoja[self::FILA_SUBTITULO - 1][0] = $this->subtitulo;

        // ── Tabla 1: filtros + cabecera + renglones ────────────────────────
        $hoja[self::FILA_ROTULO_T1 - 1][0] = 'EQUIPOS Y LICENCIAS POR GERENCIA · filtros propios abajo '
            . '(Obra, Tipo y Categoría de equipo → Equipos; Obra, Tipo y Licencia → Licencias)';
        $hoja[self::FILA_FETQ_T1 - 1][0] = 'Obra';
        $hoja[self::FILA_FETQ_T1 - 1][1] = 'Tipo de equipo';
        $hoja[self::FILA_FETQ_T1 - 1][2] = 'Categoría de equipo';
        $hoja[self::FILA_FETQ_T1 - 1][3] = 'Licencia';
        $hoja[self::FILA_FILTRO_T1 - 1][0] = self::TODAS;
        $hoja[self::FILA_FILTRO_T1 - 1][1] = self::TODAS;
        $hoja[self::FILA_FILTRO_T1 - 1][2] = self::TODAS;
        $hoja[self::FILA_FILTRO_T1 - 1][3] = self::TODAS;

        $hoja[self::FILA_CAB_T1 - 1][0] = 'Gerencia';
        $hoja[self::FILA_CAB_T1 - 1][1] = 'Equipos';
        $hoja[self::FILA_CAB_T1 - 1][2] = 'Licencias';

        foreach ($this->gerencias as $i => $gerencia) {
            $fila = self::FILA_DATOS_T1 + $i;
            $hoja[$fila - 1][0] = $gerencia;
            $hoja[$fila - 1][1] = $this->formulaConteo($this->letraGerenciaA(), $gerencia, $this->letraCoincideA(), count($this->rawEquipos));
            $hoja[$fila - 1][2] = $this->formulaConteo($this->letraGerenciaB(), $gerencia, $this->letraCoincideT1(), count($this->rawLicencias));
        }
        if ($this->gerencias) {
            $hoja[$this->filaTotalT1 - 1][0] = 'TOTAL';
            $hoja[$this->filaTotalT1 - 1][1] = sprintf('=SUM(B%d:B%d)', self::FILA_DATOS_T1, $this->filaTotalT1 - 1);
            $hoja[$this->filaTotalT1 - 1][2] = sprintf('=SUM(C%d:C%d)', self::FILA_DATOS_T1, $this->filaTotalT1 - 1);
        }

        // ── Tabla 2: filtros en cascada + cabecera + renglones ─────────────
        $hoja[$this->filaRotuloT2 - 1][0] = 'CATÁLOGO DE LICENCIAS · Gerencia → Obra → Empleado → Tipo de equipo, en cascada. '
            . 'La licencia se busca con el filtro de su propia columna, abajo.';
        foreach (['Gerencia', 'Obra', 'Empleado', 'Tipo de equipo'] as $i => $etiqueta) {
            $hoja[$this->filaFetqT2 - 1][$i] = $etiqueta;
            $hoja[$this->filaFiltroT2 - 1][$i] = self::TODAS;
        }

        $hoja[$this->filaCabT2 - 1][0] = 'Licencia';
        $hoja[$this->filaCabT2 - 1][1] = 'Cantidad';

        foreach ($this->licenciasCatalogo as $i => $licencia) {
            $fila = $this->filaDatosT2 + $i;
            $hoja[$fila - 1][0] = $licencia;
            $hoja[$fila - 1][1] = $this->formulaConteo($this->letraLicenciaB(), $licencia, $this->letraCoincideT2(), count($this->rawLicencias));
        }
        if ($this->licenciasCatalogo) {
            $hoja[$this->filaTotalT2 - 1][0] = 'TOTAL';
            $hoja[$this->filaTotalT2 - 1][1] = sprintf('=SUM(B%d:B%d)', $this->filaDatosT2, $this->filaTotalT2 - 1);
        }

        // ── Datos crudos ocultos ────────────────────────────────────────────
        $this->escribirCrudos($hoja, $this->rawEquipos, [
            $this->letraGerenciaA(), $this->letraObraA(), $this->letraTipoA(), $this->letraCategoriaA(),
        ]);
        foreach ($this->rawEquipos as $i => $fila) {
            $filaHoja = self::FILA_OCULTA_DATOS + $i;
            $hoja[$filaHoja - 1][$this->indiceDeLetra($this->letraCoincideA())] = $this->formulaCoincideA($filaHoja);
        }

        $this->escribirCrudos($hoja, $this->rawLicencias, [
            $this->letraGerenciaB(), $this->letraObraB(), $this->letraEmpleadoB(),
            $this->letraModalidadesB(), $this->letraLicenciaB(),
        ]);
        foreach ($this->rawLicencias as $i => $fila) {
            $filaHoja = self::FILA_OCULTA_DATOS + $i;
            $hoja[$filaHoja - 1][$this->indiceDeLetra($this->letraCoincideT1())] = $this->formulaCoincideT1($filaHoja);
            $hoja[$filaHoja - 1][$this->indiceDeLetra($this->letraCoincideT2())] = $this->formulaCoincideT2($filaHoja);
        }

        $this->escribirLista($hoja, $this->letraListaObraA(), $listaObraA);
        $this->escribirLista($hoja, $this->letraListaTipoA(), $listaTipoA);
        $this->escribirLista($hoja, $this->letraListaCategoriaA(), $listaCategoriaA);
        $this->escribirLista($hoja, $this->letraListaLicenciaT1(), $listaLicenciaT1);
        $this->escribirLista($hoja, $this->letraListaGerenciaT2(), $listaGerenciaT2);

        return $hoja;
    }

    private function indiceDeLetra(string $letra): int
    {
        return Coordinate::columnIndexFromString($letra) - 1;
    }

    private function escribirCrudos(array &$hoja, array $filas, array $letras): void
    {
        foreach ($filas as $i => $fila) {
            $filaHoja = self::FILA_OCULTA_DATOS + $i;
            foreach ($letras as $col => $letra) {
                $hoja[$filaHoja - 1][$this->indiceDeLetra($letra)] = $fila[$col];
            }
        }
    }

    private function escribirLista(array &$hoja, string $letra, array $valores): void
    {
        $col = $this->indiceDeLetra($letra);
        foreach ($valores as $i => $valor) {
            $hoja[self::FILA_OCULTA_DATOS + $i - 1][$col] = $valor;
        }
        $this->conteoListas[$letra] = count($valores);
    }

    private function valoresUnicos(array $filas, int $indice): array
    {
        return collect($filas)->pluck($indice)
            ->map(fn($v) => trim((string) $v))->filter()->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }

    /** 1 si el renglón de equipo pasa los 3 filtros de la tabla 1 (Obra, Tipo, Categoría). */
    private function formulaCoincideA(int $fila): string
    {
        $celdaObra = '$A$' . self::FILA_FILTRO_T1;
        $celdaTipo = '$B$' . self::FILA_FILTRO_T1;
        $celdaCat  = '$C$' . self::FILA_FILTRO_T1;

        return sprintf(
            '=IF(AND(OR(%s="%s",%s=$%s%d),OR(%s="%s",%s=$%s%d),OR(%s="%s",%s=$%s%d)),1,0)',
            $celdaObra, self::TODAS, $celdaObra, $this->letraObraA(), $fila,
            $celdaTipo, self::TODAS, $celdaTipo, $this->letraTipoA(), $fila,
            $celdaCat, self::TODAS, $celdaCat, $this->letraCategoriaA(), $fila
        );
    }

    /**
     * 1 si el renglón de licencia pasa los filtros de la tabla 1 —Obra, Tipo
     * de equipo y Licencia—. La licencia no tiene modalidad propia: Tipo se
     * prueba contra la lista de modalidades del empleado con SEARCH, no con
     * "=". La Categoría de equipo (PC/laptop) sólo afecta al conteo de
     * equipos, no al de licencias.
     */
    private function formulaCoincideT1(int $fila): string
    {
        $celdaObra = '$A$' . self::FILA_FILTRO_T1;
        $celdaTipo = '$B$' . self::FILA_FILTRO_T1;
        $celdaLic  = '$D$' . self::FILA_FILTRO_T1;

        return sprintf(
            '=IF(AND(OR(%s="%s",%s=$%s%d),OR(%s="%s",ISNUMBER(SEARCH(%s,$%s%d))),OR(%s="%s",%s=$%s%d)),1,0)',
            $celdaObra, self::TODAS, $celdaObra, $this->letraObraB(), $fila,
            $celdaTipo, self::TODAS, $celdaTipo, $this->letraModalidadesB(), $fila,
            $celdaLic, self::TODAS, $celdaLic, $this->letraLicenciaB(), $fila
        );
    }

    /**
     * 1 si el renglón de licencia pasa los 4 filtros en cascada de la tabla
     * 2 (Gerencia, Obra, Empleado, Tipo de equipo). Licencia no entra aquí:
     * se filtra con el AutoFilter nativo de la columna de la tabla.
     */
    private function formulaCoincideT2(int $fila): string
    {
        $f = $this->filaFiltroT2;
        $celdaGer  = "\$A\${$f}";
        $celdaObra = "\$B\${$f}";
        $celdaEmp  = "\$C\${$f}";
        $celdaTipo = "\$D\${$f}";

        return sprintf(
            '=IF(AND(OR(%s="%s",%s=$%s%d),OR(%s="%s",%s=$%s%d),OR(%s="%s",%s=$%s%d),OR(%s="%s",ISNUMBER(SEARCH(%s,$%s%d)))),1,0)',
            $celdaGer, self::TODAS, $celdaGer, $this->letraGerenciaB(), $fila,
            $celdaObra, self::TODAS, $celdaObra, $this->letraObraB(), $fila,
            $celdaEmp, self::TODAS, $celdaEmp, $this->letraEmpleadoB(), $fila,
            $celdaTipo, self::TODAS, $celdaTipo, $this->letraModalidadesB(), $fila
        );
    }

    /** Cuenta, entre los renglones crudos que coinciden, los que pertenecen a $valor. */
    private function formulaConteo(string $letraGrupo, string $valor, string $letraCoincide, int $totalFilas): string
    {
        $valor = str_replace('"', '""', $valor);
        $rangoGrupo = $this->rango($letraGrupo, $totalFilas);
        $rangoCoincide = $this->rango($letraCoincide, $totalFilas);

        return sprintf('=SUMPRODUCT((%s="%s")*%s)', $rangoGrupo, $valor, $rangoCoincide);
    }

    // ────────────────────────────────── Formato ──────────────────────────────

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hoja = $event->sheet->getDelegate();
                $ultimaVisible = Coordinate::stringFromColumnIndex(self::ANCHO_VISIBLE);

                $hoja->mergeCells('A' . self::FILA_TITULO . ":{$ultimaVisible}" . self::FILA_TITULO);
                $hoja->mergeCells('A' . self::FILA_SUBTITULO . ":{$ultimaVisible}" . self::FILA_SUBTITULO);
                $hoja->getStyle('A' . self::FILA_TITULO . ":{$ultimaVisible}" . self::FILA_SUBTITULO)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO_TENUE]],
                ]);
                $hoja->getStyle('A' . self::FILA_TITULO)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 18, 'color' => ['rgb' => self::ACENTO_HONDO]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $hoja->getStyle('A' . self::FILA_SUBTITULO)->applyFromArray([
                    'font'      => ['size' => 10, 'italic' => true, 'color' => ['rgb' => self::TENUE]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $hoja->getRowDimension(self::FILA_TITULO)->setRowHeight(32);
                $hoja->getRowDimension(self::FILA_SUBTITULO)->setRowHeight(20);
                $hoja->getRowDimension(3)->setRowHeight(8);

                $this->pintarTabla1($hoja, $ultimaVisible);
                $this->pintarTabla2($hoja, $ultimaVisible);

                // Tabla 1: filtros propios, independientes. Obra y Tipo pegan
                // a las dos columnas; Categoría de equipo sólo a Equipos y
                // Licencia sólo a Licencias.
                $this->pintarFiltro($hoja, 'A', self::FILA_FILTRO_T1, 'Obra', $this->letraListaObraA());
                $this->pintarFiltro($hoja, 'B', self::FILA_FILTRO_T1, 'Tipo de equipo', $this->letraListaTipoA());
                $this->pintarFiltro($hoja, 'C', self::FILA_FILTRO_T1, 'Categoría de equipo', $this->letraListaCategoriaA());
                $this->pintarFiltro($hoja, 'D', self::FILA_FILTRO_T1, 'Licencia', $this->letraListaLicenciaT1());

                // Tabla 2: Gerencia es la raíz (lista fija); Obra, Empleado y
                // Tipo de equipo son cascada —cada uno sólo ofrece lo que
                // existe bajo lo ya elegido arriba—.
                $this->pintarFiltro($hoja, 'A', $this->filaFiltroT2, 'Gerencia', $this->letraListaGerenciaT2());
                $this->estiloFiltro($hoja, 'B', $this->filaFiltroT2);
                $this->estiloFiltro($hoja, 'C', $this->filaFiltroT2);
                $this->estiloFiltro($hoja, 'D', $this->filaFiltroT2);
                $finCascada = $this->construirCascada($hoja);

                // Todo lo que sostiene las fórmulas se esconde: no es un reporte.
                $primeraOculta = $this->inicioOcultos() + 1;
                for ($col = $primeraOculta; $col < $finCascada; $col++) {
                    $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setVisible(false);
                }

                $hoja->setSelectedCell('A' . self::FILA_FILTRO_T1);
            },
        ];
    }

    /**
     * Los 3 filtros en cascada de la tabla 2: Obra depende de Gerencia,
     * Empleado depende de Gerencia+Obra, Tipo de equipo depende de Empleado.
     * Cada nivel se implementa con rangos con nombre + INDIRECT/MATCH —nada
     * de funciones nuevas— porque el libro debe abrir igual en Excel viejo.
     *
     * @return int La primera columna (1-based) que queda libre después de
     *             todos los combos, para saber hasta dónde ocultar.
     */
    private function construirCascada($hoja): int
    {
        $universo = $this->rawLicencias;
        $tag = 'AUDGL' . spl_object_id($this);

        $celdaGerencia = 'A' . $this->filaFiltroT2;
        $celdaObra     = 'B' . $this->filaFiltroT2;
        $celdaEmpleado = 'C' . $this->filaFiltroT2;
        $celdaTipo     = 'D' . $this->filaFiltroT2;

        $colBase = $this->colBaseCascada();

        $colBase = $this->cascadaUnPadre($hoja, $universo, 0, 1, $tag . 'OBRA_', $celdaGerencia, $celdaObra, $colBase);
        $colBase = $this->cascadaDosPadres($hoja, $universo, 0, 1, 2, $tag . 'EMP_', $celdaGerencia, $celdaObra, $celdaEmpleado, $colBase);
        // Tipo de equipo depende de Empleado sólo para la mecánica del
        // desplegable dependiente; la lista que ofrece es siempre la misma
        // fija de 3 —Stock, Compartido, Propio—, igual que en la tabla 1, no
        // sólo lo que ese empleado ya tiene.
        $colBase = $this->cascadaUnPadre($hoja, $universo, 2, 3, $tag . 'TIPO_', $celdaEmpleado, $celdaTipo, $colBase, self::MODALIDADES);

        return $colBase;
    }

    /**
     * Combos de un filtro con un solo padre: uno por valor de padre, más
     * "(Todas)". Si $listaFija viene con valores, todos los combos usan esa
     * misma lista —el desplegable sigue dependiendo del padre para la
     * mecánica de INDIRECT/MATCH, pero no se acota por lo que haya en cada
     * subconjunto—.
     */
    private function cascadaUnPadre($hoja, array $universo, int $idxPadre, int $idxHijo, string $tag, string $celdaPadre, string $celdaHijo, int $colBase, ?array $listaFija = null): int
    {
        $listaHijoTotal = $listaFija ?? $this->valoresUnicos($universo, $idxHijo);
        $combos = [[self::TODAS, array_merge([self::TODAS], $listaHijoTotal)]];

        foreach ($this->valoresUnicos($universo, $idxPadre) as $valorPadre) {
            if ($listaFija !== null) {
                $combos[] = [$valorPadre, array_merge([self::TODAS], $listaFija)];
                continue;
            }

            $subset = array_values(array_filter($universo, fn($f) => trim((string) $f[$idxPadre]) === $valorPadre));
            $combos[] = [$valorPadre, array_merge([self::TODAS], $this->valoresUnicos($subset, $idxHijo))];
        }

        return $this->escribirCombos($hoja, $combos, $tag, $celdaHijo, $celdaPadre, $colBase);
    }

    /**
     * Combos de un filtro con dos padres anidados (Empleado bajo Gerencia y
     * Obra): sólo genera las combinaciones que el segundo padre puede
     * realmente tener elegidas, ya acotadas por el primero.
     */
    private function cascadaDosPadres($hoja, array $universo, int $idxP1, int $idxP2, int $idxHijo, string $tag, string $celdaP1, string $celdaP2, string $celdaHijo, int $colBase): int
    {
        $valoresP1 = array_merge([self::TODAS], $this->valoresUnicos($universo, $idxP1));
        $combos = [];

        foreach ($valoresP1 as $vp1) {
            $subset1 = $vp1 === self::TODAS
                ? $universo
                : array_values(array_filter($universo, fn($f) => trim((string) $f[$idxP1]) === $vp1));

            $valoresP2 = array_merge([self::TODAS], $this->valoresUnicos($subset1, $idxP2));

            foreach ($valoresP2 as $vp2) {
                $subset2 = $vp2 === self::TODAS
                    ? $subset1
                    : array_values(array_filter($subset1, fn($f) => trim((string) $f[$idxP2]) === $vp2));

                $combos[] = [$vp1 . '|' . $vp2, array_merge([self::TODAS], $this->valoresUnicos($subset2, $idxHijo))];
            }
        }

        $claveFormula = "{$celdaP1}&\"|\"&{$celdaP2}";

        return $this->escribirCombos($hoja, $combos, $tag, $celdaHijo, $claveFormula, $colBase);
    }

    /**
     * Escribe cada combo en su propia columna oculta (clave en el renglón 1,
     * lista debajo), define su rango con nombre y apunta el desplegable del
     * hijo a INDIRECT(nombre & posición de la clave actual). Regresa la
     * primera columna libre para el siguiente filtro en cascada.
     */
    private function escribirCombos($hoja, array $combos, string $tag, string $celdaHijo, string $claveFormula, int $colBase): int
    {
        foreach ($combos as $i => [$clave, $lista]) {
            $col = $colBase + $i;
            $letra = Coordinate::stringFromColumnIndex($col);

            $hoja->setCellValue($letra . '1', $clave);
            foreach ($lista as $j => $valor) {
                $hoja->setCellValue($letra . ($j + 2), $valor);
            }
            $hoja->getColumnDimension($letra)->setVisible(false);

            $hoja->getParent()->addNamedRange(new NamedRange(
                $tag . ($i + 1),
                $hoja,
                sprintf('$%s$2:$%s$%d', $letra, $letra, 1 + count($lista)),
                true
            ));
        }

        $letraInicio = Coordinate::stringFromColumnIndex($colBase);
        $letraFin    = Coordinate::stringFromColumnIndex($colBase + count($combos) - 1);
        $rangoClaves = sprintf('$%s$1:$%s$1', $letraInicio, $letraFin);

        $validacion = $hoja->getCell($celdaHijo)->getDataValidation();
        $validacion->setType(DataValidation::TYPE_LIST);
        $validacion->setErrorStyle(DataValidation::STYLE_STOP);
        $validacion->setAllowBlank(false);
        $validacion->setShowDropDown(true);
        $validacion->setShowInputMessage(true);
        $validacion->setShowErrorMessage(true);
        $validacion->setPrompt('Elige un valor o deja ' . self::TODAS . ' para no filtrar.');
        $validacion->setErrorTitle('Valor fuera de la lista');
        $validacion->setError('Elige uno de los valores del desplegable.');
        $validacion->setFormula1(sprintf('=INDIRECT("%s" & MATCH(%s,%s,0))', $tag, $claveFormula, $rangoClaves));

        return $colBase + count($combos);
    }

    private function pintarRotulo($hoja, int $fila, string $ultimaCol): void
    {
        $hoja->mergeCells("A{$fila}:{$ultimaCol}{$fila}");
        $hoja->getStyle("A{$fila}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => self::ACENTO_HONDO]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO_TENUE]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $hoja->getRowDimension($fila)->setRowHeight(20);
    }

    /** El aspecto visual de una celda de filtro: etiqueta arriba, celda marcada abajo. */
    private function estiloFiltro($hoja, string $columna, int $filaValor): void
    {
        $filaEtiqueta = $filaValor - 1;

        $hoja->getStyle("{$columna}{$filaEtiqueta}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 8, 'color' => ['rgb' => self::TENUE]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $hoja->getStyle("{$columna}{$filaValor}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::ACENTO_HONDO]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PAPEL]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::ACENTO]]],
        ]);
        $hoja->getRowDimension($filaEtiqueta)->setRowHeight(15);
        $hoja->getRowDimension($filaValor)->setRowHeight(24);
    }

    /** Un desplegable de filtro con lista fija: estilo + etiqueta + Data Validation de rango. */
    private function pintarFiltro($hoja, string $columna, int $filaValor, string $etiqueta, string $letraLista): void
    {
        $this->estiloFiltro($hoja, $columna, $filaValor);

        $validacion = $hoja->getCell("{$columna}{$filaValor}")->getDataValidation();
        $validacion->setType(DataValidation::TYPE_LIST);
        $validacion->setErrorStyle(DataValidation::STYLE_STOP);
        $validacion->setAllowBlank(false);
        $validacion->setShowDropDown(true);
        $validacion->setShowInputMessage(true);
        $validacion->setShowErrorMessage(true);
        $validacion->setPromptTitle($etiqueta);
        $validacion->setPrompt('Elige un valor o deja ' . self::TODAS . ' para no filtrar.');
        $validacion->setErrorTitle('Valor fuera de la lista');
        $validacion->setError('Elige uno de los valores del desplegable.');
        $ultimaFilaLista = self::FILA_OCULTA_DATOS + max($this->conteoListas[$letraLista] ?? 1, 1) - 1;
        $validacion->setFormula1(sprintf('$%s$%d:$%s$%d', $letraLista, self::FILA_OCULTA_DATOS, $letraLista, $ultimaFilaLista));
    }

    private function pintarTabla1($hoja, string $ultimaVisible): void
    {
        $this->pintarRotulo($hoja, self::FILA_ROTULO_T1, $ultimaVisible);

        if (! $this->gerencias) {
            return;
        }

        $hoja->getStyle('A' . self::FILA_CAB_T1 . ":C" . self::FILA_CAB_T1)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::PAPEL]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::PAPEL]]],
        ]);
        $hoja->getRowDimension(self::FILA_CAB_T1)->setRowHeight(24);

        $hoja->getStyle("A" . self::FILA_DATOS_T1 . ":C{$this->filaTotalT1}")->applyFromArray([
            'font'      => ['size' => 10, 'color' => ['rgb' => self::TINTA]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDE]]],
        ]);
        $hoja->getStyle("B" . self::FILA_DATOS_T1 . ":C{$this->filaTotalT1}")
            ->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]])
            ->getNumberFormat()->setFormatCode('#,##0');

        for ($fila = self::FILA_DATOS_T1; $fila < $this->filaTotalT1; $fila++) {
            if (($fila - self::FILA_DATOS_T1) % 2 === 1) {
                $hoja->getStyle("A{$fila}:C{$fila}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CEBRA]],
                ]);
            }
        }

        $hoja->getStyle("A{$this->filaTotalT1}:C{$this->filaTotalT1}")->applyFromArray([
            'font'    => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::ACENTO_HONDO]],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO_TENUE]],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ACENTO]]],
        ]);

        $hoja->getColumnDimension('A')->setWidth(30);
        foreach (['B', 'C'] as $col) {
            $hoja->getColumnDimension($col)->setWidth(14);
        }
        $hoja->getColumnDimension('D')->setWidth(16);
    }

    private function pintarTabla2($hoja, string $ultimaVisible): void
    {
        $this->pintarRotulo($hoja, $this->filaRotuloT2, $ultimaVisible);

        if (! $this->licenciasCatalogo) {
            return;
        }

        $hoja->getStyle('A' . $this->filaCabT2 . ":B" . $this->filaCabT2)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => self::PAPEL]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::PAPEL]]],
        ]);
        $hoja->getRowDimension($this->filaCabT2)->setRowHeight(24);

        $hoja->getStyle("A{$this->filaDatosT2}:B{$this->filaTotalT2}")->applyFromArray([
            'font'      => ['size' => 10, 'color' => ['rgb' => self::TINTA]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDE]]],
        ]);
        $hoja->getStyle("B{$this->filaDatosT2}:B{$this->filaTotalT2}")
            ->applyFromArray(['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]])
            ->getNumberFormat()->setFormatCode('#,##0');

        for ($fila = $this->filaDatosT2; $fila < $this->filaTotalT2; $fila++) {
            if (($fila - $this->filaDatosT2) % 2 === 1) {
                $hoja->getStyle("A{$fila}:B{$fila}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CEBRA]],
                ]);
            }
        }

        $hoja->getStyle("A{$this->filaTotalT2}:B{$this->filaTotalT2}")->applyFromArray([
            'font'    => ['bold' => true, 'size' => 11, 'color' => ['rgb' => self::ACENTO_HONDO]],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ACENTO_TENUE]],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ACENTO]]],
        ]);

        // La licencia se busca con el filtro nativo de Excel en su propia
        // columna, no con otro desplegable: es la única forma de no morir
        // con tantos filtros combinados. TOTAL queda fuera del rango para
        // que siga sumando todo, filtrado o no.
        $hoja->setAutoFilter("A{$this->filaCabT2}:B" . ($this->filaTotalT2 - 1));
    }
}
