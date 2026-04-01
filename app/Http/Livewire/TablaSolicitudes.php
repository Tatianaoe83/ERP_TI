<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

use App\Models\Solicitud;
use App\Models\Cotizacion;
use App\Models\Empleados;
use App\Models\SolicitudPasos;
use App\Models\DepartamentoRequerimientos;
use App\Models\SolicitudActivo;
use App\Models\SolicitudActivoCheckList;
use App\Models\Insumos;
use App\Models\Facturas;
use App\Models\Tickets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TablaSolicitudes extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $filtroEstatus = '';
    public string $search        = '';
    public int    $perPage       = 10;

    protected $paginationTheme = 'tailwind';

    public bool  $modalAsignacionAbierto = false;
    public bool  $modalEsSoloLectura     = false;
    public ?int  $asignacionSolicitudId  = null;

    public array $propuestasAsignacion = [];
    public array $facturas             = [];

    public array $facturaXml  = [];
    public array $facturaPdf  = [];
    public array $xmlParseado = [];

    public array $usuarioSearch     = [];
    public array $usuarioOptions    = [];
    public array $usuarioSearchLock = [];

    public bool   $modalCancelacionAbierto = false;
    public ?int   $solicitudCancelarId     = null;
    public string $motivoCancelacion       = '';

    public bool $modalInfoAbierto = false;
    public $infoSolicitud = null;

    public bool $confirmarCierreModalAbierto = false;

    public bool   $modalTicketInstalacionAbierto = false;
    public ?int   $ticketInstalacionPIndex       = null;
    public ?int   $ticketInstalacionUIndex       = null;
    public ?int   $ticketInstalacionEmpleadoId   = null;
    public string $ticketInstalacionResponsable  = '';
    public array  $ticketInstalacionOptions      = [];

    protected $listeners = [
        'aprobarSolicitudConfirmed'  => 'aprobar',
        'rechazarSolicitudConfirmed' => 'rechazar',
        'forzarCloseAsignacion'      => 'forzarCloseAsignacion',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Hooks de archivos
    // ─────────────────────────────────────────────────────────────────────────

    public function updatedFacturaXml($value, $name): void
    {
        $parts = explode('.', $name);
        if (count($parts) !== 2) return;

        $pIndex = (int) $parts[0];
        $uIndex = (int) $parts[1];

        $file = $this->facturaXml[$pIndex][$uIndex] ?? null;
        if (!$file) return;

        try {
            $this->xmlParseado[$pIndex][$uIndex] = $this->parsearCfdi($file->getRealPath());
        } catch (\Throwable $e) {
            $this->xmlParseado[$pIndex][$uIndex] = ['error' => $e->getMessage()];
        }

        // Compartir con todas las unidades del mismo proveedor
        $proveedorOrigen = $this->propuestasAsignacion[$pIndex]['proveedor'] ?? null;
        if (!$proveedorOrigen) return;

        foreach ($this->propuestasAsignacion as $pi => $propuesta) {
            if (($propuesta['proveedor'] ?? '') === $proveedorOrigen) {
                foreach (array_keys($propuesta['unidades'] ?? []) as $ui) {
                    if ($pi === $pIndex && $ui === $uIndex) continue;
                    $this->facturaXml[$pi][$ui]  = $file;
                    $this->xmlParseado[$pi][$ui] = $this->xmlParseado[$pIndex][$uIndex];
                }
            }
        }

        $this->dispatchBrowserEvent('swal:info', [
            'message' => "XML aplicado a todas las unidades del proveedor: {$proveedorOrigen}",
        ]);
    }

    public function updatedFacturaPdf($value, $name): void
    {
        $parts = explode('.', $name);
        if (count($parts) !== 2) return;

        $pIndex = (int) $parts[0];
        $uIndex = (int) $parts[1];

        $file = $this->facturaPdf[$pIndex][$uIndex] ?? null;
        if (!$file) return;

        $proveedorCotizado = $this->propuestasAsignacion[$pIndex]['proveedor'] ?? '';

       try {
            $datosPdf = $this->leerPdfExtranjero($file->getRealPath(), $proveedorCotizado);
        } catch (\Throwable $e) {
            $datosPdf = ['error' => true, 'emisor' => $proveedorCotizado, 'total' => 0, 'conceptos' => []];
        }

        $catalogo = Insumos::query()
            ->whereNull('deleted_at')
            ->get(['ID', 'NombreInsumo'])
            ->map(fn($i) => ['id' => (int)$i->ID, 'norm' => $this->normalizeText((string)$i->NombreInsumo)])
            ->toArray();

        $conceptosMapeados = [];
        $esStarlink        = str_contains($this->normalizeText($datosPdf['emisor'] ?? ''), 'starlink');
        $insumoStarlinkId  = null;

        if ($esStarlink) {
            $star = $this->matchPorKeyword('starlink', $catalogo) ?? $this->matchPorKeyword('internet satelital', $catalogo);
            if ($star) $insumoStarlinkId = $star['id'];
        }

        foreach (($datosPdf['conceptos'] ?? []) as $concepto) {
            $insumoId = null;
            if ($insumoStarlinkId && $esStarlink) {
                $insumoId = $insumoStarlinkId;
            } else {
                [$bestMatch, $score] = $this->matchInsumo($concepto['nombre'], $catalogo);
                if ($score >= 60) $insumoId = $bestMatch['id'];
            }
            $conceptosMapeados[] = [
                'nombre'   => $concepto['nombre'],
                'cantidad' => 1,
                'costo'    => $concepto['importe'],
                'importe'  => $concepto['importe'],
                'insumoId' => $insumoId,
            ];
        }

        if (empty($conceptosMapeados)) {
            $nombreEquipo    = (string) ($this->propuestasAsignacion[$pIndex]['nombreEquipo'] ?? 'Equipo');
            $precioFallback  = !empty($datosPdf['total'])
                ? (float) $datosPdf['total']
                : (float) ($this->propuestasAsignacion[$pIndex]['precioUnitario'] ?? 0);
            [$bestMatch, $score] = $this->matchInsumo($nombreEquipo, $catalogo);
            $insumoId = ($score >= 55) ? ($bestMatch['id'] ?? null) : null;
            $conceptosMapeados[] = [
                'nombre'   => $nombreEquipo,
                'cantidad' => 1,
                'costo'    => $precioFallback,
                'importe'  => $precioFallback,
                'insumoId' => $insumoId,
            ];
        }   

        $emisorFinal = !empty($datosPdf['emisor']) ? $datosPdf['emisor'] : $proveedorCotizado;
        $totalFinal  = !empty($datosPdf['total'])
            ? $datosPdf['total']
            : (float) ($this->propuestasAsignacion[$pIndex]['precioUnitario'] ?? 0);

        $this->xmlParseado[$pIndex][$uIndex] = [
            'uuid'      => 'EXT-COT-' . ($this->propuestasAsignacion[$pIndex]['cotizacionId'] ?? 'N/A'),
            'emisor'    => $emisorFinal,
            'mes'       => now()->format('n'),
            'anio'      => now()->format('Y'),
            'total'     => $totalFinal,
            'moneda'    => 'MXN',
            'conceptos' => $conceptosMapeados,
            'error'     => null,
            'es_pdf'    => true,
        ];

        $proveedorOrigen = $this->propuestasAsignacion[$pIndex]['proveedor'] ?? null;
        if (!$proveedorOrigen) return;

        foreach ($this->propuestasAsignacion as $pi => $propuesta) {
            if (($propuesta['proveedor'] ?? '') === $proveedorOrigen) {
                foreach (array_keys($propuesta['unidades'] ?? []) as $ui) {
                    if ($pi === $pIndex && $ui === $uIndex) continue;
                    $this->facturaPdf[$pi][$ui] = $file;
                    if (isset($this->xmlParseado[$pIndex][$uIndex])) {
                        $this->xmlParseado[$pi][$ui] = $this->xmlParseado[$pIndex][$uIndex];
                    }
                }
            }
        }

        $this->dispatchBrowserEvent('swal:info', [
            'message' => "PDF procesado y aplicado al proveedor: {$proveedorOrigen}",
        ]);
    }

    public function updatedFacturas($value, $name): void
    {
        $parts = explode('.', $name);
        if (count($parts) !== 2) return;

        $pIndexOrigen = (int) $parts[0];
        $uIndexOrigen = (int) $parts[1];

        $facturaSubida = $this->facturas[$pIndexOrigen][$uIndexOrigen] ?? null;
        if (!$facturaSubida) return;

        $proveedorOrigen = $this->propuestasAsignacion[$pIndexOrigen]['proveedor'] ?? null;
        if (!$proveedorOrigen) return;

        foreach ($this->propuestasAsignacion as $pIndex => $propuesta) {
            if (($propuesta['proveedor'] ?? '') === $proveedorOrigen) {
                foreach (array_keys($propuesta['unidades'] ?? []) as $uIndex) {
                    $this->facturas[$pIndex][$uIndex] = $facturaSubida;
                }
            }
        }

        $this->dispatchBrowserEvent('swal:info', [
            'message' => "Factura aplicada a todas las unidades de: {$proveedorOrigen}",
        ]);
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = str_replace(
            ['á','é','í','ó','ú','ä','ë','ï','ö','ü','ñ'],
            ['a','e','i','o','u','a','e','i','o','u','n'],
            $text
        );
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }



    private function parsearCfdi(string $rutaArchivo): array
    {
        $contenido = file_get_contents($rutaArchivo);
        if ($contenido === false) throw new \Exception('No se pudo leer el archivo XML.');

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contenido, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            $errores = array_map(fn($e) => $e->message, libxml_get_errors());
            libxml_clear_errors();
            throw new \Exception('XML inválido: ' . implode(', ', $errores));
        }

        $namespaces = $xml->getDocNamespaces(true);
        $cfdiUri    = $namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4';
        $xml->registerXPathNamespace('cfdi', $cfdiUri);
        $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

        $attrs   = $xml->attributes();
        $version = (string) ($attrs['Version'] ?? $attrs['version'] ?? '3.3');
        $fecha   = (string) ($attrs['Fecha']  ?? '');

        $total   = (string) ($attrs['SubTotal'] ?? $attrs['subTotal'] ?? '0'); 
        $moneda  = (string) ($attrs['Moneda'] ?? 'MXN');

        $emisorNode   = $xml->xpath('//cfdi:Comprobante/cfdi:Emisor') ?: $xml->xpath('//cfdi:Emisor');
        $emisorNombre = $emisorNode ? (string) $emisorNode[0]['Nombre'] : '';

        $uuid        = '';
        $timbreNodes = $xml->xpath('//tfd:TimbreFiscalDigital') ?: [];
        if (!empty($timbreNodes)) {
            $uuid = strtoupper(trim((string) ($timbreNodes[0]['UUID'] ?? '')));
        }

        $mes  = null;
        $anio = null;
        if ($fecha) {
            try {
                $cf   = Carbon::parse($fecha);
                $mes  = (int) $cf->format('n');
                $anio = (int) $cf->format('Y');
            } catch (\Throwable) {}
        }

        $conceptoNodes = $xml->xpath('//cfdi:Comprobante/cfdi:Conceptos/cfdi:Concepto') ?: $xml->xpath('//cfdi:Concepto') ?: [];

        $catalogo = Insumos::query()
            ->whereNull('deleted_at')
            ->get(['ID', 'NombreInsumo'])
            ->map(fn($i) => [
                'id'     => (int) $i->ID,
                'nombre' => $i->NombreInsumo,
                'norm'   => $this->normalizeText((string) $i->NombreInsumo),
            ])
            ->toArray();

        $UMBRAL    = 60;
        $conceptos = [];

        foreach ($conceptoNodes as $concepto) {
            $ca          = $concepto->attributes();
            $descripcion = (string) ($ca['Descripcion'] ?? '');
            $valorUnit   = (string) ($ca['ValorUnitario'] ?? '0');
            $importe     = (string) ($ca['Importe'] ?? '0');
            $cantidad    = (string) ($ca['Cantidad'] ?? '1');

            [$best, $score] = $this->matchInsumo($descripcion, $catalogo);

            if (($best === null || $score < $UMBRAL) && $emisorNombre) {
                $normEmisor = $this->normalizeText($emisorNombre);
                if (str_contains($normEmisor, 'starlink') || str_contains($normEmisor, 'space exploration')) {
                    $star = $this->matchPorKeyword('starlink', $catalogo) ?? $this->matchPorKeyword('internet satelital', $catalogo);
                    if ($star) {
                        $best  = $star;
                        $score = 95;
                    }
                }
            }

            $conceptos[] = [
                'nombre'   => $descripcion,
                'costo'    => $valorUnit,
                'importe'  => $importe,
                'cantidad' => $cantidad,
                'insumoId' => $best['id'] ?? null,
            ];
        }

        return [
            'version'   => $version,
            'uuid'      => $uuid,
            'emisor'    => $emisorNombre,
            'fecha'     => $fecha,
            'mes'       => $mes,
            'anio'      => $anio,
            'total'     => $total,
            'moneda'    => $moneda,
            'conceptos' => $conceptos,
        ];
    }

    private function leerPdfExtranjero(string $rutaPdfRutaAbsoluta, string $proveedorCotizado): array
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($rutaPdfRutaAbsoluta);
            $text   = $pdf->getText();
        } catch (\Throwable $e) {
            return ['error' => true, 'total' => 0, 'conceptos' => []];
        }

        $textLower      = strtolower($text);
        $proveedorLower = strtolower(trim($proveedorCotizado));

        $esStarlink  = str_contains($textLower, 'starlink')
                    || str_contains($textLower, 'space exploration technologies')
                    || str_contains($proveedorLower, 'starlink');

        $esHostgator = str_contains($textLower, 'hostgator')
                    || str_contains($textLower, 'newfold digital')
                    || str_contains($proveedorLower, 'hostgator');

        $emisorFinal = $proveedorCotizado;
        if ($esStarlink)      $emisorFinal = 'STARLINK';
        elseif ($esHostgator) $emisorFinal = 'HOSTGATOR';

        $tieneIvaExplicito = (bool) preg_match(
            '/\b(?:iva|i\.v\.a|vat|tax(?:es)?)\b[^\d\n]{0,30}([\d,]+\.\d{2})/i',
            $text
        );

        $subtotalDoc = null;
        $totalDoc    = null;

        if (preg_match('/(?:sub\s*total|subtotal|net\s*amount)[^\d\n]{0,20}([\d,]+\.\d{2})/i', $text, $m)) {
            $subtotalDoc = (float) str_replace(',', '', $m[1]);
        }
        if (preg_match('/(?:^|\n)[^\n]{0,30}(?:total|amount\s*due|balance\s*due|invoice\s*total)[^\d\n]{0,20}([\d,]+\.\d{2})/im', $text, $m)) {
            $totalDoc = (float) str_replace(',', '', $m[1]);
        }

        $tieneIvaPorRatio = false;
        if ($subtotalDoc && $totalDoc && $totalDoc > $subtotalDoc) {
            $ratio = $totalDoc / $subtotalDoc;
            if ($ratio >= 1.14 && $ratio <= 1.18) {
                $tieneIvaPorRatio = true;
            }
        }

        $tieneIva = ($tieneIvaExplicito || $tieneIvaPorRatio) && !$esHostgator;

        $total = 0.0;

        if ($subtotalDoc) {
            $total = $subtotalDoc;
        } elseif ($totalDoc) {
            $total = $tieneIva ? round($totalDoc / 1.16, 2) : $totalDoc;
        } else {
            preg_match_all('/\$\s*([\d,]+\.\d{2})/', $text, $allM);
            if (!empty($allM[1])) {
                $mayor = max(array_map(fn($n) => (float) str_replace(',', '', $n), $allM[1]));
                $total = $tieneIva ? round($mayor / 1.16, 2) : $mayor;
            }
        }

        $conceptos = $this->extraerConceptosUniversal($text, $tieneIva);

        if (empty($conceptos) && $total > 0) {
            $conceptos[] = [
                'nombre'  => ucwords(strtolower($emisorFinal)) ?: 'Servicio Extranjero',
                'importe' => $total,
            ];
        }

        return [
            'error'     => false,
            'emisor'    => $emisorFinal,
            'total'     => $total,
            'conceptos' => $conceptos,
        ];
    }

    private function extraerConceptosUniversal(string $text, bool $quitarIva): array
    {
        $conceptos = [];

        $excluir = [
            'subtotal', 'sub total', 'sub-total', 'net amount',
            'total', 'amount due', 'balance due', 'invoice total',
            'iva', 'i.v.a', 'vat', 'tax', 'taxes',
            'payment', 'due date', 'invoice date', 'invoice',
            'bill to', 'ship to', 'sold to',
            'page', 'thank you', 'please', 'note',
            'balance', 'credit', 'discount', 'descuento',
            'transaction', 'gateway', 'powered by',
            'description', 'descripcion', 'amount', 'qty', 'quantity',
            'pdf generated', 'invoiced to',
        ];

      
        $patronTabla = '/^[ \t]*'
            . '([A-Za-zÁÉÍÓÚáéíóúñÑ][A-Za-zÁÉÍÓÚáéíóúñÑ0-9 ,\.\-\/\(\)\#\@\_\:]{2,149}?)'
            . '[ \t]*(?:\.{2,}|_{2,}|-{2,})?[ \t]*'
            . '\$?\s*([\d]{1,3}(?:,[\d]{3})*\.[\d]{2})'
            . '(?!\d)/m';

        if (preg_match_all($patronTabla, $text, $matchesA, PREG_SET_ORDER)) {
            foreach ($matchesA as $m) {
                $nombre  = trim(preg_replace('/\s+/', ' ', $m[1]));
                $importe = (float) str_replace(',', '', $m[2]);

                if (!$this->esConceptoValido($nombre, $importe, $excluir)) continue;

                $nombreFinal = ucfirst($nombre);
                $yaExiste    = array_filter($conceptos, fn($c) => strtolower($c['nombre']) === strtolower($nombre));
                if (!empty($yaExiste)) continue;

                $conceptos[] = [
                    'nombre'  => $nombreFinal,
                    'importe' => $quitarIva ? round($importe / 1.16, 2) : $importe,
                ];
            }
        }

       
        if (empty($conceptos)) {
            $lineas = preg_split('/\r?\n/', $text);
            $total  = count($lineas);

            for ($i = 0; $i < $total - 1; $i++) {
                $lineaDesc   = trim($lineas[$i]);
                $lineaPrecio = trim($lineas[$i + 1]);

                if (!preg_match('/^\$?\s*([\d]{1,3}(?:,[\d]{3})*\.[\d]{2})$/', $lineaPrecio, $pm)) continue;

                $importe = (float) str_replace(',', '', $pm[1]);
                $nombre  = preg_replace('/\s+/', ' ', $lineaDesc);

                if (!$this->esConceptoValido($nombre, $importe, $excluir)) continue;

                $nombreFinal = ucfirst(trim($nombre));
                $yaExiste    = array_filter($conceptos, fn($c) => strtolower($c['nombre']) === strtolower($nombreFinal));
                if (!empty($yaExiste)) continue;

                $conceptos[] = [
                    'nombre'  => $nombreFinal,
                    'importe' => $quitarIva ? round($importe / 1.16, 2) : $importe,
                ];
            }
        }

        return $conceptos;
    }


    private function esConceptoValido(string $nombre, float $importe, array $excluir): bool
    {
        if ($importe <= 0)                                        return false;
        if (strlen($nombre) < 4)                                  return false;
        if (!preg_match('/[A-Za-záéíóúñÁÉÍÓÚÑ]{2}/', $nombre))  return false;
        if (preg_match('/^\d/', $nombre))                         return false;

        $lower = strtolower($nombre);
        foreach ($excluir as $kw) {
            if (str_contains($lower, $kw)) return false;
        }

        return true;
    }

private function extraerConceptosGenericos(string $text, bool $dividirIva): array
{
    $conceptos = [];

    $patron = '/^[ \t]*([A-Za-z][A-Za-zÁÉÍÓÚáéíóúñÑ0-9 ,\-\/\(\)]{3,79}?)'
            . '[ \t]*(?:\.{2,}|-{2,}|_{2,})?[ \t]*'
            . '\$?\s*([\d]{1,3}(?:,[\d]{3})*\.[\d]{2})'
            . '(?!\d)/m';

    if (!preg_match_all($patron, $text, $matches, PREG_SET_ORDER)) {
        return [];
    }

    $excluir = [
        'subtotal', 'sub total', 'sub-total', 'total', 'tax', 'iva', 'vat',
        'amount due', 'balance due', 'invoice total', 'net amount',
        'payment', 'due date', 'date', 'invoice', 'bill to', 'ship to',
        'page', 'thank you', 'please', 'note', 'balance', 'credit',
        'transaction', 'gateway', 'powered by',
    ];

    foreach ($matches as $m) {
        $nombre  = trim(preg_replace('/\s+/', ' ', $m[1]));
        $importe = (float) str_replace(',', '', $m[2]);

        if ($importe <= 0) continue;

        if (strlen($nombre) < 6) continue;
        if (!preg_match('/[A-Za-záéíóúñÁÉÍÓÚÑ]{2}/', $nombre)) continue;
        if (preg_match('/^\d/', $nombre)) continue;

        $nombreLower = strtolower($nombre);
        $esExcluido  = false;
        foreach ($excluir as $kw) {
            if (str_contains($nombreLower, $kw)) {
                $esExcluido = true;
                break;
            }
        }
        if ($esExcluido) continue;

        $yaExiste = array_filter($conceptos, fn($c) => strtolower($c['nombre']) === $nombreLower);
        if (!empty($yaExiste)) continue;

        $conceptos[] = [
            'nombre'  => ucfirst($nombre),
            'importe' => $dividirIva ? round($importe / 1.16, 2) : $importe,
        ];
    }

    return $conceptos;
}

    private function matchInsumo(string $descripcion, array $catalogo): array
    {
        $descNorm = $this->normalizeText($descripcion);
        if ($descNorm === '') return [null, 0];

        $bestMatch = null;
        $bestScore = 0;

        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            
            if ($cat['norm'] === $descNorm 
                || str_contains($descNorm, $cat['norm']) 
                || str_contains($cat['norm'], $descNorm)) {
                return [$cat, 100];
            }
        }

        foreach ($catalogo as $cat) {
            if ($cat['norm'] === '') continue;
            
            similar_text($descNorm, $cat['norm'], $score);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $cat;
            }
        }

        return [$bestMatch, $bestScore];
    }

    private function matchPorKeyword(string $keyword, array $catalogo): ?array
    {
        $keywordNorm = $this->normalizeText($keyword);
        foreach ($catalogo as $cat) {
            if (str_contains($cat['norm'], $keywordNorm)) {
                return $cat;
            }
        }
        return null;
    }
    private const VALID_STAGES = ['supervisor', 'gerencia', 'administracion'];

    private const STAGE_PERMISSIONS = [
        'gerencia'       => 'aprobar-solicitudes-gerencia',
        'administracion' => 'aprobar-solicitudes-administracion',
    ];

    private ?string $serialColumn            = null;
    private array   $checklistTemplatesCache = [];

    public function mount(): void
    {
        $this->serialColumn = $this->detectSerialColumn();
    }

    public function updatingFiltroEstatus(): void { $this->resetPage(); }
    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingPerPage(): void       { $this->resetPage(); }

    public function updated($name, $value): void
    {
        $name = (string) $name;

        if (str_starts_with($name, 'usuarioSearch.')) {
            $parts = explode('.', $name);
            if (count($parts) === 3) {
                $pIndex = (int) $parts[1];
                $uIndex = (int) $parts[2];
                if ($this->isUsuarioSearchLocked($pIndex, $uIndex)) {
                    $this->unlockUsuarioSearch($pIndex, $uIndex);
                    return;
                }
                $this->handleUsuarioSearchUpdated($pIndex, $uIndex, (string) $value);
            }
            return;
        }

        if (str_starts_with($name, 'ticketInstalacionResponsable')) {
            $this->buscarResponsableTicket((string) $value);
            return;
        }
    }

    // render()
    public function render()
    {
        $user             = auth()->user();
        $empleadoActual   = $user ? Empleados::query()->where('Correo', $user->email)->first() : null;
        $empleadoActualId = $empleadoActual ? (int) $empleadoActual->EmpleadoID : null;

        $query = Solicitud::with([
            'empleadoid',
            'cotizaciones',
            'pasoSupervisor',
            'pasoGerencia',
            'pasoAdministracion',
        ]);

        if ($this->search) {
            $term = trim((string) $this->search);
            $query->where(function ($q) use ($term) {
                $q->where('SolicitudID', 'like', "%{$term}%")
                    ->orWhere('Motivo', 'like', "%{$term}%")
                    ->orWhereHas('empleadoid', function ($subQ) use ($term) {
                        $subQ->where('NombreEmpleado', 'like', "%{$term}%");
                    });
            });
        }

        $query->orderBy('created_at', 'desc');
        $solicitudesRaw = $query->get();

        $solicitudesProcesadas = $solicitudesRaw->map(function ($solicitud) use ($user, $empleadoActualId) {
            return $this->hydrateSolicitudRow($solicitud, $user, $empleadoActualId);
        });

        if ($this->filtroEstatus) {
            $solicitudesProcesadas = $solicitudesProcesadas->filter(function ($item) {
                return $item->estatusDisplay === $this->filtroEstatus;
            })->values();
        }

        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $items       = $solicitudesProcesadas->slice(($currentPage - 1) * $this->perPage, $this->perPage)->values();
        $paginator   = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $solicitudesProcesadas->count(),
            $this->perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.tabla-solicitudes', ['todasSolicitudes' => $paginator]);
    }

    // Hydration de filas
    private function hydrateSolicitudRow($solicitud, $user, ?int $empleadoActualId)
    {
        $nombreEmpleado              = $solicitud->empleadoid->NombreEmpleado ?? '';
        $solicitud->nombreFormateado = $this->formatNombreEmpleado((string) $nombreEmpleado);

        [$estatusReal, $estaRechazada]    = $this->resolveEstatusReal($solicitud);
        [$estatusDisplay, $colorEstatus]  = $this->resolveEstatusDisplay($solicitud, $estatusReal);

        $solicitud->estatusReal    = $estatusReal;
        $solicitud->estatusDisplay = $estatusDisplay;
        $solicitud->colorEstatus   = $colorEstatus;
        $solicitud->recotizarPropuestasText = '';

        if ($estatusReal === 'Re-cotizar' && $solicitud->pasoGerencia && $solicitud->pasoGerencia->comment) {
            $comment = $solicitud->pasoGerencia->comment;
            if (str_starts_with($comment, 'RECOTIZAR|')) {
                $parts = explode('|', $comment, 3);
                $nums  = isset($parts[1]) ? array_filter(array_map('trim', explode(',', $parts[1]))) : [];
                $solicitud->recotizarPropuestasText = $nums ? ' (Prop. ' . implode(', ', $nums) . ')' : '';
            }
        }

        $todasFirmaron      = $this->allStepsApproved($solicitud);
        $supervisorAprobado = $solicitud->pasoSupervisor && $solicitud->pasoSupervisor->status === 'approved';
        $tieneSeleccionada  = $this->hasSelectedCotizacion($solicitud);
        $todosGanadores     = $solicitud->todosProductosTienenGanador();
        $estaCancelada      = ($estatusReal === 'Cancelada');

        $solicitud->puedeCotizar = (bool) (
            !$estaCancelada && $supervisorAprobado && $user && !$estaRechazada
            && $estatusDisplay !== 'Aprobada' && !$todosGanadores
        );
        $solicitud->puedeSubirFactura = (bool) (!$estaCancelada && $todasFirmaron && $tieneSeleccionada && $user);
        $solicitud->puedeAsignar      = (bool) (!$estaCancelada && $todasFirmaron && $tieneSeleccionada && $user);
        $solicitud->puedeAprobar      = false;
        $solicitud->nivelAprobacion   = '';

        [$facturasSubidas, $totalNecesarias]  = $this->contarFacturas($solicitud);
        $solicitud->facturasSubidas            = $facturasSubidas;
        $solicitud->totalFacturasNecesarias    = $totalNecesarias;

        $pasoSupervisor     = $solicitud->pasoSupervisor;
        $pasoGerencia       = $solicitud->pasoGerencia;
        $pasoAdministracion = $solicitud->pasoAdministracion;

        if ($user && !$estaRechazada && !$estaCancelada) {
            if (
                $estatusReal === 'Pendiente Aprobación Supervisor'
                && $pasoSupervisor
                && (int) $pasoSupervisor->approver_empleado_id === (int) $empleadoActualId
            ) {
                $solicitud->puedeAprobar    = true;
                $solicitud->nivelAprobacion = 'supervisor';
            } elseif (
                $estatusReal === 'Pendiente Aprobación Gerencia'
                && $solicitud->GerenciaID
                && $user->can(self::STAGE_PERMISSIONS['gerencia'])
            ) {
                $solicitud->puedeAprobar    = true;
                $solicitud->nivelAprobacion = 'gerencia';
            } elseif (
                $estatusReal === 'Pendiente Aprobación Administración'
                && $user->can(self::STAGE_PERMISSIONS['administracion'])
            ) {
                $solicitud->puedeAprobar    = true;
                $solicitud->nivelAprobacion = 'administracion';
            }
        }

        return $solicitud;
    }

    private function formatNombreEmpleado(string $nombreEmpleado): string
    {
        $partes = preg_split('/\s+/', trim($nombreEmpleado));
        if (is_array($partes) && count($partes) >= 3) {
            array_splice($partes, 1, 1);
        }
        return (string) Str::of(implode(' ', $partes ?? []))->title();
    }

    private function resolveEstatusReal($solicitud): array
    {
        $pasoSupervisor     = $solicitud->pasoSupervisor;
        $pasoGerencia       = $solicitud->pasoGerencia;
        $pasoAdministracion = $solicitud->pasoAdministracion;

        if (in_array($solicitud->Estatus, ['Cancelada', 'Cerrada'], true)) return ['Cancelada', false];

        if (
            ($pasoSupervisor     && $pasoSupervisor->status     === 'rejected') ||
            ($pasoGerencia       && $pasoGerencia->status       === 'rejected') ||
            ($pasoAdministracion && $pasoAdministracion->status === 'rejected')
        ) return ['Rechazada', true];

        if (in_array($solicitud->Estatus, ['Aprobado', 'Aprobada'], true)) return ['Aprobado', false];
        if ($solicitud->Estatus === 'Re-cotizar') return ['Re-cotizar', false];

        $estatusReal = $solicitud->Estatus ?? 'Pendiente';

        if (in_array($solicitud->Estatus, ['Pendiente', 'En revisión', null, ''], true) || empty($solicitud->Estatus)) {
            if ($pasoSupervisor && $pasoSupervisor->status === 'approved') {
                if ($pasoGerencia && $pasoGerencia->status === 'approved') {
                    if ($pasoAdministracion && $pasoAdministracion->status === 'approved') {
                        $tieneSeleccionada = $this->hasSelectedCotizacion($solicitud);
                        $cotizacionesCount = $solicitud->cotizaciones ? $solicitud->cotizaciones->count() : 0;

                        if ($tieneSeleccionada && $this->todasUnidadesFinalizadas($solicitud)) {
                            return ['Listo', false];
                        }

                        $estatusReal = $tieneSeleccionada
                            ? 'Aprobado'
                            : ($cotizacionesCount >= 1 ? 'Completada' : 'Pendiente Cotización TI');
                    } else {
                        $estatusReal = 'Pendiente Aprobación Administración';
                    }
                } else {
                    $estatusReal = 'Pendiente Aprobación Gerencia';
                }
            } else {
                $estatusReal = 'Pendiente Aprobación Supervisor';
            }
        }

        return [$estatusReal, false];
    }

    private function todasUnidadesFinalizadas($solicitud): bool
    {
        $seleccionadas = $solicitud->cotizaciones
            ? $solicitud->cotizaciones->where('Estatus', 'Seleccionada')
            : collect();

        if ($seleccionadas->isEmpty()) return false;

        foreach ($seleccionadas as $cot) {
            $qty = max(1, (int) ($cot->Cantidad ?? 1));
            for ($i = 1; $i <= $qty; $i++) {
                $activo = SolicitudActivo::query()
                    ->where('SolicitudID', $solicitud->SolicitudID)
                    ->where('CotizacionID', $cot->CotizacionID)
                    ->where('UnidadIndex', $i)
                    ->first();

                if (!$activo || empty($activo->fecha_fin_configuracion)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function resolveEstatusDisplay($solicitud, string $estatusReal): array
    {
        if ($estatusReal === 'Cancelada')    return ['Cancelada',   'bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-700'];
        if ($estatusReal === 'Rechazada')    return ['Rechazada',   'bg-red-50 text-red-800 border border-red-200'];
        if ($estatusReal === 'Aprobado')     return ['Aprobada',    'bg-emerald-50 text-emerald-800 border border-emerald-200'];
        if ($estatusReal === 'Cotizaciones Enviadas') return ['Cotizaciones Enviadas', 'bg-blue-50 text-blue-800 border border-blue-200'];
        if ($estatusReal === 'Re-cotizar')   return ['Re-cotizar',  'bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-700'];
        if ($estatusReal === 'Completada')   return ['En revisión', 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700'];
        if ($estatusReal === 'Pendiente Cotización TI') return ['Pendiente', 'bg-amber-50 text-amber-800 border border-amber-200'];
        if ($estatusReal === 'Listo') return ['Listo', 'bg-teal-50 text-teal-800 border border-teal-300 dark:bg-teal-900/30 dark:text-teal-200 dark:border-teal-700'];

        if (in_array($estatusReal, ['Pendiente Aprobación Supervisor', 'Pendiente Aprobación Gerencia', 'Pendiente Aprobación Administración'], true)) {
            return ['En revisión', 'bg-white text-purple-700 border border-purple-200 dark:text-purple-700 dark:border-purple-700'];
        }

        return ['Pendiente', 'bg-gray-50 text-gray-700 border border-gray-200'];
    }

    private function hasSelectedCotizacion($solicitud): bool
    {
        return (bool) ($solicitud->cotizaciones
            ? $solicitud->cotizaciones->where('Estatus', 'Seleccionada')->isNotEmpty()
            : false);
    }

    private function allStepsApproved($solicitud): bool
    {
        $ps = $solicitud->pasoSupervisor;
        $pg = $solicitud->pasoGerencia;
        $pa = $solicitud->pasoAdministracion;
        return ($ps && $ps->status === 'approved') && ($pg && $pg->status === 'approved') && ($pa && $pa->status === 'approved');
    }

    private function contarFacturas($solicitud): array
    {
        if (!$solicitud->cotizaciones || $solicitud->cotizaciones->isEmpty()) return [0, 0];
        $seleccionadas = $solicitud->cotizaciones->where('Estatus', 'Seleccionada');
        if ($seleccionadas->isEmpty()) return [0, 0];

        $proveedoresUnicos = $seleccionadas->pluck('Proveedor')->filter()->unique();
        $totalNecesarias   = $proveedoresUnicos->count();
        if ($totalNecesarias === 0) return [0, 0];

        $cotizacionIds = $seleccionadas->pluck('CotizacionID')->filter()->unique()->toArray();
        if (empty($cotizacionIds)) return [0, $totalNecesarias];

        $activos = SolicitudActivo::query()
            ->whereIn('CotizacionID', $cotizacionIds)
            ->whereNotNull('FacturaPath')
            ->where('FacturaPath', '!=', '')
            ->select('CotizacionID')
            ->distinct()
            ->get();

        if ($activos->isEmpty()) return [0, $totalNecesarias];

        $cotizacionesConFactura = $activos->pluck('CotizacionID')->toArray();
        $proveedoresConFactura  = $seleccionadas
            ->whereIn('CotizacionID', $cotizacionesConFactura)
            ->pluck('Proveedor')->filter()->unique()->count();

        return [$proveedoresConFactura, $totalNecesarias];
    }

    // Aprobación / Rechazo
    public function aprobar($id, $nivel, $comentario)
    {
        try {
            $this->decidirPaso((int) $id, (string) $nivel, (string) ($comentario ?? ''), 'approved');
            $this->dispatchBrowserEvent('swal:success', ['message' => 'Solicitud aprobada correctamente']);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => $e->getMessage()]);
        }
    }

    public function rechazar($id, $nivel, $comentario)
    {
        try {
            $this->decidirPaso((int) $id, (string) $nivel, (string) ($comentario ?? ''), 'rejected');
            $this->dispatchBrowserEvent('swal:success', ['message' => 'Solicitud rechazada correctamente']);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => $e->getMessage()]);
        }
    }

    private function decidirPaso(int $solicitudId, string $nivel, string $comentario, string $decision): void
    {
        $nivel = trim(strtolower($nivel));
        $this->assertValidStage($nivel);

        DB::transaction(function () use ($solicitudId, $nivel, $comentario, $decision) {
            $solicitud     = Solicitud::findOrFail($solicitudId);
            $usuarioActual = auth()->user();
            if (!$usuarioActual) throw new \Exception('Sesión inválida.');

            $usuarioEmpleado = Empleados::query()->where('Correo', $usuarioActual->email)->firstOrFail();

            $step = SolicitudPasos::query()
                ->where('solicitud_id', $solicitud->SolicitudID)
                ->where('stage', $nivel)
                ->lockForUpdate()
                ->firstOrFail();

            if ($step->status !== 'pending') throw new \Exception('Etapa ya resuelta.');

            $this->authorizeDecision($usuarioActual, $usuarioEmpleado, $solicitud, $step, $nivel);

            $step->update([
                'status'                 => $decision,
                'comment'                => $comentario,
                'decided_at'             => now(),
                'decided_by_empleado_id' => (int) $usuarioEmpleado->EmpleadoID,
            ]);

            if ($decision === 'rejected') $solicitud->update(['Estatus' => 'Rechazada']);
        });
    }

    private function assertValidStage(string $nivel): void
    {
        if (!in_array($nivel, self::VALID_STAGES, true)) throw new \Exception('Etapa inválida.');
    }

    private function authorizeDecision($user, Empleados $empleado, Solicitud $solicitud, SolicitudPasos $step, string $nivel): void
    {
        $approverId = (int) ($step->approver_empleado_id ?? 0);
        if ($approverId > 0 && $approverId !== (int) $empleado->getAttribute('EmpleadoID')) throw new \Exception('No tienes permiso para resolver esta etapa.');
        if ($approverId > 0) return;
        if ($nivel === 'supervisor') throw new \Exception('No tienes permiso para resolver esta etapa.');
        $perm = self::STAGE_PERMISSIONS[$nivel] ?? null;
        if ($perm && !$user->can($perm)) throw new \Exception('No tienes permiso para resolver esta etapa.');
        if ($nivel === 'gerencia' && empty($solicitud->GerenciaID)) throw new \Exception('Solicitud sin gerencia asignada.');
    }

    // Modal Cancelación
    public function abrirModalCancelacion(int $solicitudId): void
    {
        $this->resetErrorBag();
        $this->motivoCancelacion       = '';
        $this->solicitudCancelarId     = $solicitudId;
        $this->modalCancelacionAbierto = true;
    }

    public function cerrarModalCancelacion(): void { $this->resetCancelacionState(); }

    public function confirmarCancelacion(): void
    {
        $this->validate(
            ['motivoCancelacion' => 'required|string|min:10|max:1000'],
            [
                'motivoCancelacion.required' => 'El motivo de cancelación es obligatorio.',
                'motivoCancelacion.min'      => 'El motivo debe tener al menos 10 caracteres.',
                'motivoCancelacion.max'      => 'El motivo no puede exceder 1000 caracteres.',
            ]
        );

        if (!$this->solicitudCancelarId) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Solicitud inválida.']);
            return;
        }

        try {
            $usuarioActual = auth()->user();
            if (!$usuarioActual) throw new \Exception('Sesión inválida.');

            DB::transaction(function () use ($usuarioActual) {
                $solicitud = Solicitud::findOrFail($this->solicitudCancelarId);
                $solicitud->update([
                    'Estatus'            => 'Cancelada',
                    'motivo_cancelacion' => trim($this->motivoCancelacion),
                    'fecha_cancelacion'  => now(),
                    'cancelado_por'      => $usuarioActual->id,
                ]);
            });

            $this->dispatchBrowserEvent('swal:success', [
                'message' => "Solicitud #{$this->solicitudCancelarId} cancelada correctamente.",
            ]);
            $this->resetCancelacionState();
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error al cancelar: ' . $e->getMessage()]);
        }
    }

    private function resetCancelacionState(): void
    {
        $this->modalCancelacionAbierto = false;
        $this->solicitudCancelarId     = null;
        $this->motivoCancelacion       = '';
        $this->resetErrorBag('motivoCancelacion');
    }

    // Modal Asignación
    public function abrirModalAsignacion(int $solicitudId): void { $this->openAsignacion($solicitudId); }

    public function openAsignacion(int $solicitudId): void
    {
        try {
            $this->resetAsignacionState();
            $this->asignacionSolicitudId = $solicitudId;

            $seleccionadas = Cotizacion::query()
                ->where('SolicitudID', $solicitudId)
                ->where('Estatus', 'Seleccionada')
                ->orderBy('NumeroPropuesta')
                ->get();

            if ($seleccionadas->isEmpty()) {
                $this->dispatchBrowserEvent('swal:error', ['message' => 'No hay cotizaciones Seleccionadas para asignar.']);
                return;
            }

            $activos       = SolicitudActivo::query()->where('SolicitudID', $solicitudId)->get();
            $activosPorKey = $activos->keyBy(fn($a) => (int) $a->CotizacionID . ':' . (int) $a->UnidadIndex);
            $activoIds     = $activos->pluck('SolicitudActivoID')->filter()->values()->all();

            $checklists = empty($activoIds)
                ? collect()
                : SolicitudActivoCheckList::query()
                    ->whereIn('SolicitudActivoID', $activoIds)
                    ->get()
                    ->groupBy('SolicitudActivoID');

            $empleadosMap = $this->loadEmpleadosDeptMap(
                $activos->pluck('EmpleadoID')->filter()->unique()->values()->all()
            );

            $facturasDelSolicitud = Facturas::query()
                ->where('SolicitudID', $solicitudId)
                ->where(function ($q) {
                    $q->whereNotNull('ArchivoRuta')->where('ArchivoRuta', '!=', '')
                      ->orWhereNotNull('PdfRuta')->where('PdfRuta', '!=', '');
                })
                ->get()
                ->unique('UUID')
                ->keyBy('UUID');

            $rutasPorProveedor = [];
            foreach ($seleccionadas as $cot) {
                $proveedor = (string) ($cot->Proveedor ?? '');
                if (!$proveedor || isset($rutasPorProveedor[$proveedor])) continue;
                $rutasPorProveedor[$proveedor] = ['xml' => '', 'pdf' => ''];
            }

            foreach ($seleccionadas as $cot) {
                $proveedor = (string) ($cot->Proveedor ?? '');
                if (!$proveedor) continue;
                if (!empty($rutasPorProveedor[$proveedor]['xml']) && !empty($rutasPorProveedor[$proveedor]['pdf'])) continue;

                $qty = max(1, (int) ($cot->Cantidad ?? 1));
                for ($i = 1; $i <= $qty; $i++) {
                    $key    = (int) $cot->CotizacionID . ':' . $i;
                    $activo = $activosPorKey->get($key);
                    if (!$activo) continue;

                    $activoDir = "solicitudes/{$solicitudId}/activos/{$activo->SolicitudActivoID}";
                    $xmlFs = "{$activoDir}/factura.xml";
                    $pdfFs = "{$activoDir}/factura.pdf";

                    if (empty($rutasPorProveedor[$proveedor]['xml']) && Storage::disk('public')->exists($xmlFs)) {
                        $rutasPorProveedor[$proveedor]['xml'] = $xmlFs;
                    }
                    if (empty($rutasPorProveedor[$proveedor]['pdf']) && Storage::disk('public')->exists($pdfFs)) {
                        $rutasPorProveedor[$proveedor]['pdf'] = $pdfFs;
                    }

                    if (empty($rutasPorProveedor[$proveedor]['xml']) && empty($rutasPorProveedor[$proveedor]['pdf'])) {
                        $fp = (string) ($activo->FacturaPath ?? '');
                        if ($fp && Storage::disk('public')->exists($fp)) {
                            $ext = strtolower(pathinfo($fp, PATHINFO_EXTENSION));
                            if ($ext === 'xml') $rutasPorProveedor[$proveedor]['xml'] = $fp;
                            else                $rutasPorProveedor[$proveedor]['pdf'] = $fp;
                        }
                    }
                }

                if (empty($rutasPorProveedor[$proveedor]['xml']) && empty($rutasPorProveedor[$proveedor]['pdf'])) {
                    $facturaMatch = $facturasDelSolicitud->first();
                    if ($facturaMatch) {
                        $rutasPorProveedor[$proveedor]['xml'] = (string) ($facturaMatch->ArchivoRuta ?? '');
                        $rutasPorProveedor[$proveedor]['pdf'] = (string) ($facturaMatch->PdfRuta ?? '');
                    }
                }
            }

            $agrupadas = $seleccionadas->groupBy(fn($c) => (int) ($c->NumeroPropuesta ?? 0));
            $out       = [];

            foreach ($agrupadas as $numeroPropuesta => $items) {
                $cot = $items->first();
                if (!$cot) continue;

                $proveedor = (string) ($cot->Proveedor ?? '');
                $qty       = max(1, (int) ($cot->Cantidad ?? 1));
                $unidades  = [];

                for ($i = 1; $i <= $qty; $i++) {
                    $key    = (int) $cot->CotizacionID . ':' . $i;
                    $activo = $activosPorKey->get($key);

                    $empleadoId  = $activo ? (int) ($activo->EmpleadoID ?? 0) : 0;
                    $empleadoRow = $empleadoId && isset($empleadosMap[$empleadoId]) ? $empleadosMap[$empleadoId] : null;

                    $deptId = $activo && !empty($activo->DepartamentoID)
                        ? (int) $activo->DepartamentoID
                        : ($empleadoRow['departamento_id'] ?? null);

                    $template        = $this->checklistTemplateByDept($deptId);
                    $saved           = $activo ? ($checklists->get((int) $activo->SolicitudActivoID) ?? collect()) : collect();
                    $unidadChecklist = $this->applySavedChecklist($template, $saved);

                    $serialVal = '';
                    if ($activo && $this->serialColumn) {
                        $serialVal = (string) ($activo->{$this->serialColumn} ?? '');
                    }

                
                    $xmlSavedPath = $rutasPorProveedor[$proveedor]['xml'] ?? '';
                    $pdfSavedPath = $rutasPorProveedor[$proveedor]['pdf'] ?? '';

                    if (empty($xmlSavedPath) && empty($pdfSavedPath) && $activo) {
                        $activoDir = "solicitudes/{$solicitudId}/activos/{$activo->SolicitudActivoID}";
                        if (Storage::disk('public')->exists("{$activoDir}/factura.xml")) {
                            $xmlSavedPath = "{$activoDir}/factura.xml";
                        }
                        if (Storage::disk('public')->exists("{$activoDir}/factura.pdf")) {
                            $pdfSavedPath = "{$activoDir}/factura.pdf";
                        }
                    }

                    $unidades[] = [
                        'unidadIndex'             => $i,
                        'activoId'                => $activo ? (int) $activo->SolicitudActivoID : null,
                        'serial'                  => $serialVal,
                        'factura_xml_path'        => $xmlSavedPath,
                        'factura_pdf_path'        => $pdfSavedPath,
                        'factura_path'            => $activo ? (string) ($activo->FacturaPath ?? '') : '',
                        'fecha_entrega'           => $activo && $activo->FechaEntrega ? $activo->FechaEntrega->format('Y-m-d') : null,
                        'empleado_id'             => $empleadoId ?: null,
                        'empleado_nombre'         => $empleadoRow['nombre'] ?? null,
                        'departamento_id'         => $deptId,
                        'departamento_nombre'     => $empleadoRow['departamento_nombre'] ?? null,
                        'checklist_open'          => true,
                        'checklist'               => $unidadChecklist,
                        'requiere_config'         => $saved->isNotEmpty(),
                        'fecha_fin_configuracion' => $activo ? (string) ($activo->fecha_fin_configuracion ?? '') : '',
                    ];
                }

                $out[] = [
                    'numeroPropuesta' => (int) $numeroPropuesta,
                    'cotizacionId'    => (int) $cot->CotizacionID,
                    'nombreEquipo'    => (string) ($cot->NombreEquipo ?? 'Sin nombre'),
                    'proveedor'       => $proveedor,
                    'precioUnitario'  => (string) ($cot->Precio ?? '0.00'),
                    'itemsTotal'      => $qty,
                    'unidades'        => $unidades,
                ];
            }

            $this->propuestasAsignacion = $out;
            $this->usuarioSearch        = [];
            $this->usuarioOptions       = [];

            foreach ($this->propuestasAsignacion as $pIndex => $p) {
                foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                    $this->usuarioSearch[$pIndex][$uIndex]  = (string) ($u['empleado_nombre'] ?? '');
                    $this->usuarioOptions[$pIndex][$uIndex] = [];
                }
            }

            $cotizacionIdsSel = $seleccionadas->pluck('CotizacionID')->filter()->unique()->values()->all();
            $proveedoresTotal = $seleccionadas->pluck('Proveedor')->filter()->unique()->count();
            $this->modalEsSoloLectura = false;

            if ($proveedoresTotal > 0 && !empty($cotizacionIdsSel)) {
                $cotizacionesConFactura = SolicitudActivo::query()
                    ->whereIn('CotizacionID', $cotizacionIdsSel)
                    ->whereNotNull('FacturaPath')
                    ->where('FacturaPath', '!=', '')
                    ->pluck('CotizacionID')
                    ->unique()
                    ->values()
                    ->all();

                $proveedoresConFactura = $seleccionadas
                    ->whereIn('CotizacionID', $cotizacionesConFactura)
                    ->pluck('Proveedor')->filter()->unique()->count();

                $this->modalEsSoloLectura = $proveedoresConFactura >= $proveedoresTotal;
            }

            $this->modalAsignacionAbierto = true;
        } catch (\Throwable $e) {
            $this->resetAsignacionState();
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error abriendo asignación: ' . $e->getMessage()]);
        }
    }

    public function closeAsignacion(): void
    {
        if (!$this->modalEsSoloLectura && !empty($this->propuestasAsignacion)) {
            $hayFacturaPendiente = $this->hayFacturaPendienteDeCarga();
            if ($hayFacturaPendiente) {
                $this->confirmarCierreModalAbierto = true;
                return;
            }
        }
        $this->resetAsignacionState();
    }



    public function forzarCloseAsignacion(): void
    {
        $this->resetAsignacionState();
    }


    private function hayFacturaPendienteDeCarga(): bool
    {
        $proveedoresYaRevisados = [];

        foreach ($this->propuestasAsignacion as $pIndex => $p) {
            $proveedor = $p['proveedor'] ?? '';
            if (in_array($proveedor, $proveedoresYaRevisados, true)) continue;
            $proveedoresYaRevisados[] = $proveedor;

            $tieneGuardada  = false;
            $tieneNueva     = false;

            foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                if (!empty($u['factura_xml_path']) || !empty($u['factura_pdf_path'])) {
                    $tieneGuardada = true;
                    break;
                }
                if (!empty($this->facturaXml[$pIndex][$uIndex]) || !empty($this->facturaPdf[$pIndex][$uIndex])) {
                    $tieneNueva = true;
                    break;
                }
            }

            if (!$tieneGuardada && !$tieneNueva) {
                return true; 
            }
        }

        return false;
    }

    private function handleUsuarioSearchUpdated(int $pIndex, int $uIndex, string $term): void
    {
        $term = trim($term);
        $this->usuarioOptions[$pIndex]          = $this->usuarioOptions[$pIndex] ?? [];
        $this->usuarioOptions[$pIndex][$uIndex] = [];

        if ($term === '') return;

        $rows = Empleados::query()
            ->where('Estado', true)
            ->where(function ($q) use ($term) {
                $q->where('NombreEmpleado', 'like', "%{$term}%")
                    ->orWhere('Correo', 'like', "%{$term}%");
                if (ctype_digit($term)) $q->orWhere('EmpleadoID', (int) $term);
            })
            ->limit(8)
            ->get(['EmpleadoID', 'NombreEmpleado', 'Correo'])
            ->map(fn($e) => [
                'id'     => (int) $e->EmpleadoID,
                'name'   => (string) $e->NombreEmpleado,
                'correo' => (string) $e->Correo,
            ])
            ->toArray();

        $this->usuarioOptions[$pIndex][$uIndex] = $rows;
    }

    public function seleccionarEmpleado(int $pIndex, int $uIndex, int $empleadoId): void
    {
        if (!isset($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex])) throw new \Exception('Ítem inválido para asignación.');

        $row = $this->getEmpleadoConDept($empleadoId);

        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['empleado_id']         = (int) $row['EmpleadoID'];
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['empleado_nombre']     = (string) $row['NombreEmpleado'];
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['departamento_id']     = $row['DepartamentoID'] ? (int) $row['DepartamentoID'] : null;
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['departamento_nombre'] = $row['NombreDepartamento'] ?: null;

        $deptId = $row['DepartamentoID'] ? (int) $row['DepartamentoID'] : null;
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'] = $this->checklistTemplateByDept($deptId);

        $this->lockUsuarioSearch($pIndex, $uIndex);
        $this->usuarioSearch[$pIndex][$uIndex]  = (string) $row['NombreEmpleado'];
        $this->usuarioOptions[$pIndex][$uIndex] = [];
    }

    public function guardarAsignacion(): void { $this->persistAsignacion(false, false); }

public function persistAsignacion($strict = false, $closeAfter = false): void
    {
        $strict     = (bool) $strict;
        $closeAfter = (bool) $closeAfter;
        if (!$this->asignacionSolicitudId) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Solicitud inválida.']);
            return;
        }

        try {
            $errors = $this->validateAsignacionPayload($strict);
            if (!empty($errors)) {
                $primerError = array_values($errors)[0] ?? 'Error de validación';
                $this->dispatchBrowserEvent('swal:error', ['message' => $primerError]);
                throw ValidationException::withMessages($errors);
            }

            DB::transaction(function () {
                $xmlPorProveedor     = [];
                $pdfPorProveedor     = [];
                $facturaPorProveedor = [];

                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    $proveedor = $p['proveedor'] ?? null;
                    if (!$proveedor) continue;
                    foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                        if (!isset($xmlPorProveedor[$proveedor])     && !empty($this->facturaXml[$pIndex][$uIndex]))  $xmlPorProveedor[$proveedor]     = $this->facturaXml[$pIndex][$uIndex];
                        if (!isset($pdfPorProveedor[$proveedor])     && !empty($this->facturaPdf[$pIndex][$uIndex]))  $pdfPorProveedor[$proveedor]     = $this->facturaPdf[$pIndex][$uIndex];
                        if (!isset($facturaPorProveedor[$proveedor]) && !empty($this->facturas[$pIndex][$uIndex]))    $facturaPorProveedor[$proveedor] = $this->facturas[$pIndex][$uIndex];
                    }
                }

                $rutasGuardadasPorProveedor = [];
                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    $proveedor = $p['proveedor'] ?? null;
                    if (!$proveedor || isset($rutasGuardadasPorProveedor[$proveedor])) continue;
                    foreach (($p['unidades'] ?? []) as $u) {
                        if (!empty($u['factura_xml_path']) || !empty($u['factura_pdf_path'])) {
                            $rutasGuardadasPorProveedor[$proveedor] = [
                                'xml' => $u['factura_xml_path'] ?? '',
                                'pdf' => $u['factura_pdf_path'] ?? '',
                            ];
                            break;
                        }
                    }
                }

                foreach ($this->propuestasAsignacion as $pIndex => $p) {
                    foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                        if (!$this->shouldPersistUnit($pIndex, $uIndex, $u)) continue;

                        $dataUpdate = [
                            'NumeroPropuesta' => (int) ($p['numeroPropuesta'] ?? 0),
                            'FechaEntrega'    => !empty($u['fecha_entrega']) ? $u['fecha_entrega'] : null,
                            'EmpleadoID'      => !empty($u['empleado_id']) ? (int) $u['empleado_id'] : null,
                            'DepartamentoID'  => !empty($u['departamento_id']) ? (int) $u['departamento_id'] : null,
                        ];
                        if ($this->serialColumn) $dataUpdate[$this->serialColumn] = (string) ($u['serial'] ?? '');

                        $activo = SolicitudActivo::updateOrCreate(
                            [
                                'SolicitudID'  => (int) $this->asignacionSolicitudId,
                                'CotizacionID' => (int) ($p['cotizacionId'] ?? 0),
                                'UnidadIndex'  => (int) ($u['unidadIndex'] ?? ($uIndex + 1)),
                            ],
                            $dataUpdate
                        );

                        $proveedor = $p['proveedor'] ?? null;
                        $baseDir   = "solicitudes/{$this->asignacionSolicitudId}/activos/{$activo->SolicitudActivoID}";

                        $rutaXml = null;
                        $rutaPdf = null;

                        $xmlAGuardar = ($proveedor && isset($xmlPorProveedor[$proveedor]))
                            ? $xmlPorProveedor[$proveedor]
                            : ($this->facturaXml[$pIndex][$uIndex] ?? null);

                        if ($xmlAGuardar) {
                            $ext  = strtolower((string) $xmlAGuardar->getClientOriginalExtension());
                            $mime = strtolower((string) $xmlAGuardar->getMimeType());
                            if (!in_array($ext, ['xml'], true) || !in_array($mime, ['text/xml', 'application/xml', 'text/plain'], true)) {
                                throw new \Exception('El archivo XML de factura no es válido.');
                            }
                            $rutaXml = $xmlAGuardar->storeAs($baseDir, 'factura.xml', 'public');
                        }

                        $pdfAGuardar = ($proveedor && isset($pdfPorProveedor[$proveedor]))
                            ? $pdfPorProveedor[$proveedor]
                            : ($this->facturaPdf[$pIndex][$uIndex] ?? null);

                        if ($pdfAGuardar) {
                            $ext  = strtolower((string) $pdfAGuardar->getClientOriginalExtension());
                            $mime = strtolower((string) $pdfAGuardar->getMimeType());
                            if ($ext !== 'pdf' || !in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
                                throw new \Exception('El archivo PDF de factura no es válido.');
                            }
                            $rutaPdf = $pdfAGuardar->storeAs($baseDir, 'factura.pdf', 'public');
                        }

                        if (!$rutaXml && !empty($u['factura_xml_path'])) {
                            $rutaXml = $u['factura_xml_path'];
                        }
                        if (!$rutaPdf && !empty($u['factura_pdf_path'])) {
                            $rutaPdf = $u['factura_pdf_path'];
                        }

                        if (!$rutaXml && !$rutaPdf) {
                            $facturaLegacy = ($proveedor && isset($facturaPorProveedor[$proveedor]))
                                ? $facturaPorProveedor[$proveedor]
                                : ($this->facturas[$pIndex][$uIndex] ?? null);

                            if ($facturaLegacy) {
                                $ext  = strtolower((string) $facturaLegacy->getClientOriginalExtension());
                                $mime = strtolower((string) $facturaLegacy->getMimeType());
                                if (!in_array($ext, ['pdf', 'xml'], true) || !in_array($mime, ['application/pdf', 'application/x-pdf', 'text/xml', 'application/xml'], true)) {
                                    throw new \Exception('La factura debe ser PDF o XML.');
                                }
                                $path = $facturaLegacy->storeAs($baseDir, $ext === 'xml' ? 'factura.xml' : 'factura.pdf', 'public');
                                if ($ext === 'xml') $rutaXml = $path;
                                else                $rutaPdf = $path;
                            }
                        }

                        if ($rutaXml || $rutaPdf) {
                            $activo->update(['FacturaPath' => $rutaXml ?? $rutaPdf]);

                            if ($rutaXml) $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['factura_xml_path'] = $rutaXml;
                            if ($rutaPdf) $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['factura_pdf_path'] = $rutaPdf;

                            if ($proveedor) {
                                foreach ($this->propuestasAsignacion as $pi => $prop) {
                                    if (($prop['proveedor'] ?? '') !== $proveedor) continue;
                                    foreach (array_keys($prop['unidades'] ?? []) as $ui) {
                                        if ($pi === $pIndex && $ui === $uIndex) continue;
                                        if ($rutaXml) $this->propuestasAsignacion[$pi]['unidades'][$ui]['factura_xml_path'] = $rutaXml;
                                        if ($rutaPdf) $this->propuestasAsignacion[$pi]['unidades'][$ui]['factura_pdf_path'] = $rutaPdf;
                                    }
                                }
                            }
                        }

                        $localParsed    = $this->xmlParseado[$pIndex][$uIndex] ?? null;
                        $providerParsed = $proveedor ? $this->buscarXmlParseadoPorProveedor($pIndex, $proveedor) : null;

                        $parsed = null;
                        if ($localParsed && empty($localParsed['error']) && empty($localParsed['es_pdf'])) {
                            $parsed = $localParsed;                                          
                        } elseif ($providerParsed && empty($providerParsed['es_pdf'])) {
                            $parsed = $providerParsed;                                       
                        } elseif ($localParsed && empty($localParsed['error'])) {
                            $parsed = $localParsed;                                          
                        } else {
                            $parsed = $providerParsed;                                       
                        }

                        if ($parsed && empty($parsed['error']) && !empty($parsed['conceptos'])) {
                            $uuid = trim((string) ($parsed['uuid'] ?? ''));

                            foreach ($parsed['conceptos'] as $concepto) {
                                $nombreConcepto = trim((string) ($concepto['nombre'] ?? ''));
                                if ($nombreConcepto === '') continue;
                                $nombreConcepto = mb_substr($nombreConcepto, 0, 300);

                                try {         
                                    $facturaGlobal = null;
                                    if ($uuid) {
                                        $facturaGlobal = Facturas::query()
                                            ->where('UUID', $uuid)
                                            ->where('Nombre', $nombreConcepto)
                                            ->first();
                                    }

                                    if ($facturaGlobal) {
                                        $updateData = [];
                                        if ($rutaXml && empty($facturaGlobal->ArchivoRuta)) $updateData['ArchivoRuta'] = $rutaXml;
                                        if ($rutaPdf && empty($facturaGlobal->PdfRuta))     $updateData['PdfRuta']     = $rutaPdf;
                                        if (!empty($updateData)) $facturaGlobal->update($updateData);

                                        if ((int) $facturaGlobal->SolicitudID !== (int) $this->asignacionSolicitudId) {
                                            Facturas::firstOrCreate(
                                                [
                                                    'SolicitudID' => (int) $this->asignacionSolicitudId,
                                                    'UUID'        => $uuid,
                                                    'Nombre'      => $nombreConcepto,
                                                ],
                                                [
                                                    'Importe'     => is_numeric($concepto['importe'] ?? null) ? (float) $concepto['importe'] : 0,
                                                    'Costo'       => is_numeric($concepto['costo']   ?? null) ? (float) $concepto['costo']   : 0,
                                                    'Mes'         => !empty($parsed['mes'])  ? (int) $parsed['mes']  : null,
                                                    'Anio'        => !empty($parsed['anio']) ? (int) $parsed['anio'] : null,
                                                    'InsumoID'    => $concepto['insumoId'] ?? null,
                                                    'Emisor'      => $parsed['emisor'] ?? '',
                                                    'ArchivoRuta' => $rutaXml ?? $facturaGlobal->ArchivoRuta ?? '',
                                                    'PdfRuta'     => $rutaPdf ?? $facturaGlobal->PdfRuta ?? '',
                                                ]
                                            );
                                        }
                                    } else {
                                        Facturas::updateOrCreate(
                                            [
                                                'SolicitudID' => (int) $this->asignacionSolicitudId,
                                                'UUID'        => $uuid,
                                                'Nombre'      => $nombreConcepto,
                                            ],
                                            [
                                                'Importe'     => is_numeric($concepto['importe'] ?? null) ? (float) $concepto['importe'] : 0,
                                                'Costo'       => is_numeric($concepto['costo']   ?? null) ? (float) $concepto['costo']   : 0,
                                                'Mes'         => !empty($parsed['mes'])  ? (int) $parsed['mes']  : null,
                                                'Anio'        => !empty($parsed['anio']) ? (int) $parsed['anio'] : null,
                                                'InsumoID'    => $concepto['insumoId'] ?? null,
                                                'Emisor'      => $parsed['emisor'] ?? '',
                                                'ArchivoRuta' => $rutaXml ?? '',
                                                'PdfRuta'     => $rutaPdf ?? '',
                                            ]
                                        );
                                    }
                                } catch (\Throwable $fe) {
                                    \Log::error('[Asignacion] ERROR al guardar factura XML', [
                                        'concepto' => $nombreConcepto,
                                        'error'    => $fe->getMessage(),
                                    ]);
                                    throw $fe;
                                }
                            }
                        } 
                        elseif ($rutaPdf && empty($rutaXml)) {
                            
                            $rutaAbsoluta = Storage::disk('public')->path($rutaPdf);
                            $proveedorExt = trim((string) ($p['proveedor'] ?? 'Proveedor Extranjero'));
                            
                            $datosPdf = $this->leerPdfExtranjero($rutaAbsoluta, $proveedorExt);
                            
                            $emisorFinal = $datosPdf['emisor'] ?: $proveedorExt;
                            $conceptosGuardar = !empty($datosPdf['conceptos']) ? $datosPdf['conceptos'] : [
                                [
                                    'nombre'  => trim((string) ($p['nombreEquipo'] ?? 'Servicio Extranjero')), 
                                    'importe' => is_numeric($p['precioUnitario'] ?? null) ? (float) $p['precioUnitario'] : 0
                                ]
                            ];

                            $cotId = (int) ($p['cotizacionId'] ?? 0);
                            $uuidBase = "EXT-COT-" . $cotId;

                            try {
                                foreach ($conceptosGuardar as $index => $conceptoPdf) {
                                    $nombreConcepto = mb_substr($conceptoPdf['nombre'], 0, 300);
                                    $precioConcepto = $conceptoPdf['importe'];
                                    
                                    $uuidConcepto = $uuidBase . "-" . $index;

                                    $facturaGlobal = Facturas::query()
                                        ->where('SolicitudID', (int) $this->asignacionSolicitudId)
                                        ->where('UUID', $uuidConcepto)
                                        ->first();

                                    if ($facturaGlobal) {
                                        if (empty($facturaGlobal->PdfRuta)) {
                                            $facturaGlobal->update(['PdfRuta' => $rutaPdf]);
                                        }
                                    } else {
                                        $catalogo = Insumos::query()->whereNull('deleted_at')->get(['ID', 'NombreInsumo'])->map(fn($i) => ['id' => (int)$i->ID, 'norm' => $this->normalizeText((string)$i->NombreInsumo)])->toArray();
                                        [$bestMatch, $score] = $this->matchInsumo($nombreConcepto, $catalogo);
                                        $insumoId = ($score >= 60) ? $bestMatch['id'] : null;

                                        if (!$insumoId && str_contains($this->normalizeText($emisorFinal), 'starlink')) {
                                            $star = $this->matchPorKeyword('starlink', $catalogo) ?? $this->matchPorKeyword('internet satelital', $catalogo);
                                            if ($star) $insumoId = $star['id'];
                                        }

                                        Facturas::create([
                                            'SolicitudID' => (int) $this->asignacionSolicitudId,
                                            'UUID'        => $uuidConcepto,
                                            'Nombre'      => $nombreConcepto,
                                            'Importe'     => $precioConcepto,
                                            'Costo'       => $precioConcepto,
                                            'Mes'         => (int) now()->format('n'),
                                            'Anio'        => (int) now()->format('Y'),
                                            'InsumoID'    => $insumoId,
                                            'Emisor'      => $emisorFinal,
                                            'ArchivoRuta' => '',
                                            'PdfRuta'     => $rutaPdf,
                                        ]);
                                    }
                                }
                            } catch (\Throwable $fe) {
                                \Log::error('[Asignacion] ERROR al guardar factura extranjera PDF', [
                                    'error' => $fe->getMessage(),
                                ]);
                                throw $fe;
                            }
                        }

                        if (!empty($u['requiere_config'])) {
                            foreach (array_keys($u['checklist'] ?? []) as $catKey) {
                                foreach (($u['checklist'][$catKey] ?? []) as $item) {
                                    $reqId = (int) ($item['req_id'] ?? 0);
                                    if (!$reqId) continue;
                                    $realizado = !empty($item['realizado']);
                                    SolicitudActivoCheckList::updateOrCreate(
                                        [
                                            'SolicitudActivoID'           => (int) $activo->SolicitudActivoID,
                                            'DepartamentoRequerimientoID' => $reqId,
                                        ],
                                        [
                                            'completado'  => (bool) $realizado,
                                            'responsable' => $realizado ? (string) ($item['responsable'] ?? '') : null,
                                        ]
                                    );
                                }
                            }
                        }
                    }
                }
            });

            $this->facturaXml  = [];
            $this->facturaPdf  = [];
            $this->facturas    = [];
            $this->xmlParseado = [];

            $this->dispatchBrowserEvent('swal:success', [
                'message' => $strict ? 'Asignación finalizada' : 'Avance guardado correctamente',
            ]);

            if ($closeAfter) $this->closeAsignacion();
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', ['message' => 'Error guardando: ' . $e->getMessage()]);
        }
    }


    private function buscarXmlParseadoPorProveedor(int $pIndex, string $proveedor): ?array
    {
        $fallbackPdf = null;
        foreach ($this->propuestasAsignacion as $pi => $prop) {
            if (($prop['proveedor'] ?? '') !== $proveedor) continue;
            foreach (array_keys($prop['unidades'] ?? []) as $ui) {
                $data = $this->xmlParseado[$pi][$ui] ?? null;
                if (!$data || !empty($data['error'])) continue;
                if (empty($data['es_pdf'])) return $data;
                $fallbackPdf ??= $data;
            }
        }
        return $fallbackPdf;
    }

    private function validateAsignacionPayload(bool $strict): array
    {
        $errors = [];

        foreach ($this->propuestasAsignacion as $pIndex => $p) {
            foreach (($p['unidades'] ?? []) as $uIndex => $u) {
                $basePath = "propuestasAsignacion.$pIndex.unidades.$uIndex";

                if ($strict) {
                    if (empty($u['serial']))        $errors["$basePath.serial"]        = 'El Serial es obligatorio.';
                    if (empty($u['fecha_entrega'])) $errors["$basePath.fecha_entrega"] = 'La fecha de entrega es obligatoria.';
                    if (empty($u['empleado_id']))   $errors["$basePath.empleado_id"]   = 'El usuario final es obligatorio.';
                }

                if (!empty($u['requiere_config'])) {
                    foreach (array_keys($u['checklist'] ?? []) as $catKey) {
                        foreach (($u['checklist'][$catKey] ?? []) as $idx => $item) {
                            if (!empty($item['realizado']) && empty($item['responsable'])) {
                                $errors["$basePath.checklist.$catKey.$idx.responsable"] = 'Responsable obligatorio si la tarea está marcada.';
                            }
                        }
                    }
                }

                if (isset($this->facturaXml[$pIndex][$uIndex]) && $this->facturaXml[$pIndex][$uIndex]) {
                    try {
                        $ext = strtolower((string) $this->facturaXml[$pIndex][$uIndex]->getClientOriginalExtension());
                        if ($ext !== 'xml') $errors["facturaXml.$pIndex.$uIndex"] = 'El archivo debe ser XML.';
                    } catch (\Throwable) {}
                }

                if (isset($this->facturaPdf[$pIndex][$uIndex]) && $this->facturaPdf[$pIndex][$uIndex]) {
                    try {
                        $ext = strtolower((string) $this->facturaPdf[$pIndex][$uIndex]->getClientOriginalExtension());
                        if ($ext !== 'pdf') $errors["facturaPdf.$pIndex.$uIndex"] = 'El archivo debe ser PDF.';
                    } catch (\Throwable) {}
                }

                if (isset($this->facturas[$pIndex][$uIndex]) && $this->facturas[$pIndex][$uIndex]) {
                    $file   = $this->facturas[$pIndex][$uIndex];
                    $ext    = strtolower((string) $file->getClientOriginalExtension());
                    $mime   = strtolower((string) $file->getMimeType());
                    if (!in_array($ext, ['pdf', 'xml'], true) || !in_array($mime, ['application/pdf', 'application/x-pdf', 'text/xml', 'application/xml'], true)) {
                        $errors["facturas.$pIndex.$uIndex"] = 'La factura debe ser PDF o XML.';
                    }
                }
            }
        }

        return $errors;
    }

    private function checklistTemplateByDept(?int $departamentoId): array
    {
        if (!$departamentoId) return [];
        if (isset($this->checklistTemplatesCache[$departamentoId])) return $this->checklistTemplatesCache[$departamentoId];

        $reqs = DepartamentoRequerimientos::query()
            ->byDepartamentos($departamentoId)
            ->seleccionados()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get(['id', 'categoria', 'nombre']);

        $payload = [];
        foreach ($reqs as $r) {
            $catKey = (string) $r->categoria;
            if (!isset($payload[$catKey])) $payload[$catKey] = [];
            $payload[$catKey][] = ['req_id' => (int) $r->id, 'nombre' => (string) $r->nombre, 'realizado' => false, 'responsable' => ''];
        }

        $this->checklistTemplatesCache[$departamentoId] = $payload;
        return $payload;
    }

    private function applySavedChecklist(array $template, $checklistRows): array
    {
        $map = collect($checklistRows)->keyBy(fn($x) => (int) ($x->DepartamentoRequerimientoID ?? 0));
        foreach (array_keys($template) as $catKey) {
            foreach ($template[$catKey] as $idx => $item) {
                $reqId = (int) ($item['req_id'] ?? 0);
                if (!$reqId) continue;
                $row = $map->get($reqId);
                if ($row) {
                    $template[$catKey][$idx]['realizado']   = (bool) ($row->completado  ?? false);
                    $template[$catKey][$idx]['responsable'] = (string) ($row->responsable ?? '');
                }
            }
        }
        return $template;
    }

    private function currentUserPrefix(): string
    {
        $user = auth()->user();
        if (!$user || empty($user->name)) return '';
        $empleado = Empleados::query()->whereRaw('LOWER(NombreEmpleado) = ?', [mb_strtolower(trim((string) $user->name))])->first();
        if (!$empleado || empty($empleado->Correo)) return '';
        return (string) Str::before(strtolower($empleado->Correo), '@');
    }

    private function resetAsignacionState(): void
    {
        $this->modalAsignacionAbierto        = false;
        $this->modalEsSoloLectura            = false;
        $this->asignacionSolicitudId         = null;
        $this->propuestasAsignacion          = [];
        $this->facturas                      = [];
        $this->facturaXml                    = [];
        $this->facturaPdf                    = [];
        $this->xmlParseado                   = [];
        $this->usuarioSearch                 = [];
        $this->usuarioOptions                = [];
        $this->usuarioSearchLock             = [];
        $this->checklistTemplatesCache       = [];
        $this->confirmarCierreModalAbierto   = false;
    }

    private function detectSerialColumn(): ?string
    {
        if (!Schema::hasTable('solicitud_activos')) return null;
        if (Schema::hasColumn('solicitud_activos', 'serial'))      return 'serial';
        if (Schema::hasColumn('solicitud_activos', 'Serial'))      return 'Serial';
        if (Schema::hasColumn('solicitud_activos', 'NumeroSerie')) return 'NumeroSerie';
        if (Schema::hasColumn('solicitud_activos', 'NumSerie'))    return 'NumSerie';
        return null;
    }

    private function shouldPersistUnit(int $pIndex, int $uIndex, array $u): bool
    {
        if (!empty($u['requiere_config'])) return true;
        if (!empty($u['serial']))          return true;
        if (!empty($u['fecha_entrega']))   return true;
        if (!empty($u['empleado_id']))     return true;
        if (!empty($u['departamento_id'])) return true;
        if (isset($this->facturas[$pIndex][$uIndex])    && $this->facturas[$pIndex][$uIndex])    return true;
        if (isset($this->facturaXml[$pIndex][$uIndex])  && $this->facturaXml[$pIndex][$uIndex])  return true;
        if (isset($this->facturaPdf[$pIndex][$uIndex])  && $this->facturaPdf[$pIndex][$uIndex])  return true;
        if (isset($this->xmlParseado[$pIndex][$uIndex]) && empty($this->xmlParseado[$pIndex][$uIndex]['error'])) return true;
        return false;
    }

    private function loadEmpleadosDeptMap(array $empleadoIds): array
    {
        if (empty($empleadoIds)) return [];
        $rows = Empleados::query()
            ->withTrashed()
            ->from('empleados as e')
            ->leftJoin('puestos as p',       'p.PuestoID',       '=', 'e.PuestoID')
            ->leftJoin('departamentos as d',  'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->whereIn('e.EmpleadoID', $empleadoIds)
            ->get(['e.EmpleadoID', 'e.NombreEmpleado', 'e.Correo', 'd.DepartamentoID', 'd.NombreDepartamento']);

        $map = [];
        foreach ($rows as $r) {
            $id       = (int) $r->EmpleadoID;
            $map[$id] = [
                'nombre'              => (string) ($r->NombreEmpleado    ?? ''),
                'correo'              => (string) ($r->Correo            ?? ''),
                'departamento_id'     => $r->DepartamentoID     ? (int) $r->DepartamentoID     : null,
                'departamento_nombre' => $r->NombreDepartamento ? (string) $r->NombreDepartamento : null,
            ];
        }
        return $map;
    }

    private function getEmpleadoConDept(int $empleadoId): array
    {
        $row = Empleados::query()
            ->withTrashed()
            ->from('empleados as e')
            ->leftJoin('puestos as p',      'p.PuestoID',       '=', 'e.PuestoID')
            ->leftJoin('departamentos as d', 'd.DepartamentoID', '=', 'p.DepartamentoID')
            ->where('e.EmpleadoID', $empleadoId)
            ->first(['e.EmpleadoID', 'e.NombreEmpleado', 'e.Correo', 'd.DepartamentoID', 'd.NombreDepartamento']);

        if (!$row) throw new \Exception('Empleado no encontrado.');

        return [
            'EmpleadoID'         => (int) $row->EmpleadoID,
            'NombreEmpleado'     => (string) ($row->NombreEmpleado    ?? ''),
            'Correo'             => (string) ($row->Correo            ?? ''),
            'DepartamentoID'     => $row->DepartamentoID     ? (int) $row->DepartamentoID     : null,
            'NombreDepartamento' => $row->NombreDepartamento ? (string) $row->NombreDepartamento : null,
        ];
    }

    private function lockUsuarioSearch(int $pIndex, int $uIndex): void
    {
        $this->usuarioSearchLock[$pIndex]          = $this->usuarioSearchLock[$pIndex] ?? [];
        $this->usuarioSearchLock[$pIndex][$uIndex] = true;
    }

    private function isUsuarioSearchLocked(int $pIndex, int $uIndex): bool
    {
        return !empty($this->usuarioSearchLock[$pIndex][$uIndex]);
    }

    private function unlockUsuarioSearch(int $pIndex, int $uIndex): void
    {
        if (isset($this->usuarioSearchLock[$pIndex][$uIndex])) {
            $this->usuarioSearchLock[$pIndex][$uIndex] = false;
        }
    }

    public function marcarChecklist(int $pIndex, int $uIndex, string $catKey, int $idx): void
    {
        if (!isset($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx])) return;

        $estadoActual = (bool) ($this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['realizado'] ?? false);
        $realizado    = !$estadoActual;

        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['realizado']   = $realizado;
        $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['responsable'] = $realizado ? $this->currentUserPrefix() : '';

        $responsable = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['responsable'];
        $activoId    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] ?? null;
        $reqId       = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist'][$catKey][$idx]['req_id'] ?? null;

        if ($activoId && $reqId) {
            try {
                SolicitudActivoCheckList::updateOrCreate(
                    [
                        'SolicitudActivoID'           => (int) $activoId,
                        'DepartamentoRequerimientoID' => (int) $reqId,
                    ],
                    [
                        'completado'  => $realizado,
                        'responsable' => $realizado ? $responsable : null,
                    ]
                );
            } catch (\Throwable $e) {
                $this->dispatchBrowserEvent('swal:error', ['message' => 'Error al guardar el checklist: ' . $e->getMessage()]);
            }
        }
    }

    public function finalizarConfiguracionUnidad(int $pIndex, int $uIndex): void
    {
        try {
            $unidad    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex] ?? null;
            $propuesta = $this->propuestasAsignacion[$pIndex] ?? null;

            if (!$unidad || !$propuesta) {
                $this->dispatchBrowserEvent('swal:error', ['message' => 'No se encontró la unidad.']);
                return;
            }

            if (empty($unidad['empleado_id'])) {
                $this->dispatchBrowserEvent('swal:warning', ['message' => 'Debes asignar un Usuario final antes de finalizar la configuración.']);
                return;
            }

            $activoId = $unidad['activoId'] ?? null;

            DB::transaction(function () use ($pIndex, $uIndex, $propuesta, &$unidad, &$activoId) {
                $dataUpdate = [
                    'NumeroPropuesta'         => (int) ($propuesta['numeroPropuesta'] ?? 0),
                    'FechaEntrega'            => !empty($unidad['fecha_entrega']) ? $unidad['fecha_entrega'] : null,
                    'EmpleadoID'              => !empty($unidad['empleado_id']) ? (int) $unidad['empleado_id'] : null,
                    'DepartamentoID'          => !empty($unidad['departamento_id']) ? (int) $unidad['departamento_id'] : null,
                    'fecha_fin_configuracion' => now(),
                ];

                if ($this->serialColumn && !empty($unidad['serial'])) {
                    $dataUpdate[$this->serialColumn] = (string) $unidad['serial'];
                }

                $activo = SolicitudActivo::updateOrCreate(
                    [
                        'SolicitudID'  => (int) $this->asignacionSolicitudId,
                        'CotizacionID' => (int) ($propuesta['cotizacionId'] ?? 0),
                        'UnidadIndex'  => (int) ($unidad['unidadIndex'] ?? ($uIndex + 1)),
                    ],
                    $dataUpdate
                );

                $activoId = (int) $activo->SolicitudActivoID;
                $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['activoId'] = $activoId;
                $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['fecha_fin_configuracion'] = now()->toDateTimeString();

                foreach (array_keys($unidad['checklist'] ?? []) as $catKey) {
                foreach (($unidad['checklist'][$catKey] ?? []) as $item) {
                    $reqId = (int) ($item['req_id'] ?? 0);
                    if (!$reqId) continue;
                    $realizado = !empty($item['realizado']);
                    SolicitudActivoCheckList::updateOrCreate(
                        ['SolicitudActivoID' => $activoId, 'DepartamentoRequerimientoID' => $reqId],
                        ['completado' => $realizado, 'responsable' => $realizado ? (string) ($item['responsable'] ?? '') : null]
                    );
                }
            }
            });

            $this->crearTicketPorEquipo($pIndex, $uIndex, $activoId);

            $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['checklist_open'] = false; 
            $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex]['config_lista_ui'] = true; 

            $this->dispatchBrowserEvent('swal:info', [
                'message' => "Ticket de instalación creado exitosamente.\n\n⚠️ Siguiente paso: No olvides subir la factura XML/PDF en la parte inferior de la unidad.",
            ]);

        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal:error', [
                'message' => 'Error al finalizar configuración: ' . $e->getMessage(),
            ]);
        }
    }

    private function crearTicketPorEquipo(int $pIndex, int $uIndex, int $activoId): void
    {
        $unidad    = $this->propuestasAsignacion[$pIndex]['unidades'][$uIndex];
        $propuesta = $this->propuestasAsignacion[$pIndex];

        $solicitanteEmpleadoId = $unidad['empleado_id'] ?? null;
        if (!$solicitanteEmpleadoId && $this->asignacionSolicitudId) {
            $solicitud             = Solicitud::find($this->asignacionSolicitudId);
            $solicitanteEmpleadoId = $solicitud?->EmpleadoID;
        }

        $checklistLineas = [];
        foreach (($unidad['checklist'] ?? []) as $categoria => $items) {
            $realizados = array_filter($items, fn($i) => !empty($i['realizado']));
            if (empty($realizados)) continue;
            $checklistLineas[] = ">> {$categoria}:";
            foreach ($realizados as $item) {
                $resp              = !empty($item['responsable']) ? " [{$item['responsable']}]" : '';
                $checklistLineas[] = "   - {$item['nombre']}{$resp}";
            }
        }

        $lineas = array_filter([
            'Instalacion y configuracion de equipo.',
            '',
            'Equipo: '      . ($propuesta['nombreEquipo'] ?? 'Sin nombre'),
            'Proveedor: '   . ($propuesta['proveedor']    ?? 'Sin proveedor'),
            !empty($unidad['serial'])              ? 'Serial: '        . $unidad['serial']              : null,
            !empty($unidad['departamento_nombre']) ? 'Departamento: '  . $unidad['departamento_nombre'] : null,
            !empty($unidad['empleado_nombre'])     ? 'Usuario final: ' . $unidad['empleado_nombre']     : null,
            $this->asignacionSolicitudId           ? 'Solicitud #'     . $this->asignacionSolicitudId   : null,
        ]);

        if (!empty($checklistLineas)) {
            $lineas[] = '';
            $lineas[] = 'Tareas de configuracion realizadas:';
            foreach ($checklistLineas as $linea) {
                $lineas[] = $linea;
            }
        }

        $numeroUnidad  = $unidad['unidadIndex'] ?? ($uIndex + 1);
        $totalUnidades = (int) ($propuesta['itemsTotal'] ?? 1);
        $sufijo        = $totalUnidades > 1 ? " (Unidad {$numeroUnidad}/{$totalUnidades})" : '';
        $titulo        = 'Instalacion - ' . ($propuesta['nombreEquipo'] ?? 'Equipo') . $sufijo;

        $yaExiste = Tickets::query()
            ->where('Descripcion', 'like', $titulo . '%')
            ->where('Descripcion', 'like', '%Solicitud #' . $this->asignacionSolicitudId . '%')
            ->exists();

        if ($yaExiste) return;  

        Tickets::create([
            'EmpleadoID'    => $solicitanteEmpleadoId,
            'ResponsableTI' => null,
            'Descripcion'   => $titulo . "\n\n" . implode("\n", $lineas),
            'Estatus'       => 'Pendiente',
            'Prioridad'     => 'Media',
        ]);
    }

    private function buscarResponsableTicket(string $term): void
    {
        $this->ticketInstalacionOptions = [];
        $term = trim($term);
        if ($term === '') return;

        $this->ticketInstalacionOptions = Empleados::query()
            ->where('Estado', true)
            ->where(function ($q) use ($term) {
                $q->where('NombreEmpleado', 'like', "%{$term}%")
                  ->orWhere('Correo', 'like', "%{$term}%");
            })
            ->limit(8)
            ->get(['EmpleadoID', 'NombreEmpleado', 'Correo'])
            ->map(fn($e) => [
                'id'     => (int) $e->EmpleadoID,
                'name'   => (string) $e->NombreEmpleado,
                'correo' => (string) $e->Correo,
            ])
            ->toArray();
    }

    public function seleccionarResponsableTicket(int $empleadoId, string $nombre): void
    {
        $this->ticketInstalacionEmpleadoId  = $empleadoId > 0 ? $empleadoId : null;
        $this->ticketInstalacionResponsable = $empleadoId > 0 ? $nombre : '';
        $this->ticketInstalacionOptions     = [];
    }

    public function cerrarModalTicketInstalacion(): void
    {
        $this->modalTicketInstalacionAbierto = false;
        $this->ticketInstalacionPIndex       = null;
        $this->ticketInstalacionUIndex       = null;
        $this->ticketInstalacionEmpleadoId   = null;
        $this->ticketInstalacionResponsable  = '';
        $this->ticketInstalacionOptions      = [];
    }


    public function verDetallesSLA($solicitudId)
    {
        $this->infoSolicitud = Solicitud::with([
            'empleadoid',
            'pasoSupervisor',
            'pasoGerencia',
            'pasoAdministracion',
        ])->find($solicitudId);

        $this->modalInfoAbierto = true;
    }

    public function cerrarModalInfo()
    {
        $this->modalInfoAbierto = false;
        $this->infoSolicitud    = null;
    }

    public function calcularHorasLaborales($inicio, $fin)
    {
        if (!$inicio || !$fin) return 'Pendiente';

        $inicio = Carbon::parse($inicio);
        $fin    = Carbon::parse($fin);

        if ($inicio->gt($fin)) return '0 h';

        $totalMinutos = 0;
        $actual       = $inicio->copy();

        while ($actual->lt($fin)) {
            $diaSemana = $actual->dayOfWeek;

            if ($diaSemana == 0) {
                $actual->addDay()->setTime(9, 0, 0);
                continue;
            }

            $horaInicio = $actual->copy()->setTime(9, 0, 0);
            $horaFin    = ($diaSemana == 6)
                ? $actual->copy()->setTime(14, 0, 0)
                : $actual->copy()->setTime(18, 0, 0);

            if ($actual->lt($horaInicio)) {
                $actual = $horaInicio->copy();
            }

            if ($actual->gte($horaFin)) {
                $actual->addDay()->setTime(9, 0, 0);
                continue;
            }

            $limite        = $fin->lt($horaFin) ? $fin : $horaFin;
            $totalMinutos += $actual->diffInMinutes($limite);
            $actual        = $horaFin->copy();
        }

        if ($totalMinutos == 0) return '0 h';

        $horas   = floor($totalMinutos / 60);
        $minutos = $totalMinutos % 60;

        return $horas > 0 ? "{$horas} h " . ($minutos > 0 ? "{$minutos} m" : '') : "{$minutos} m";
    }
}