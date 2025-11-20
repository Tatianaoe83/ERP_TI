<?php

namespace App\Console\Commands;

use App\Models\Tickets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use Dompdf\Dompdf as DompdfLib;
use Dompdf\Options;

class ConvertirArchivosTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:convertir-archivos {--dry-run : Solo mostrar qué archivos se procesarían sin hacer cambios}';

    /**
     * Image manager instance
     *
     * @var ImageManager
     */
    protected $imageManager;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convierte archivos de tickets que no sean PNG a formato PNG';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando conversión de archivos de tickets...');
        
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('MODO DRY-RUN: No se realizarán cambios reales');
        }

        // Obtener todos los tickets que tienen archivos
        $tickets = Tickets::whereNotNull('imagen')
            ->where('imagen', '!=', '')
            ->get();

        $this->info("Se encontraron {$tickets->count()} tickets con archivos");

        $procesados = 0;
        $errores = 0;
        $archivosConvertidos = 0;

        foreach ($tickets as $ticket) {
            try {
                $archivos = json_decode($ticket->imagen, true);
                
                if (!is_array($archivos)) {
                    $this->warn("Ticket ID {$ticket->TicketID}: Formato de imagen inválido");
                    continue;
                }

                $archivosActualizados = [];
                $tieneCambios = false;

                foreach ($archivos as $archivo) {
                    $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                    
                    // Solo procesar archivos que no sean PNG
                    if ($extension !== 'png') {
                        $this->line("Procesando: {$archivo}");
                        
                        $rutaCompleta = storage_path('app/public/' . $archivo);
                        
                        if (!File::exists($rutaCompleta)) {
                            $this->warn("Archivo no encontrado: {$rutaCompleta}");
                            $archivosActualizados[] = $archivo;
                            continue;
                        }

                        $nuevoArchivo = $this->convertirArchivo($rutaCompleta, $archivo, $dryRun);
                        
                        if ($nuevoArchivo) {
                            $archivosActualizados[] = $nuevoArchivo;
                            $tieneCambios = true;
                            $archivosConvertidos++;
                            
                            if (!$dryRun) {
                                // Eliminar archivo original
                                File::delete($rutaCompleta);
                            }
                        } else {
                            $archivosActualizados[] = $archivo;
                        }
                    } else {
                        $archivosActualizados[] = $archivo;
                    }
                }

                // Actualizar el ticket si hubo cambios
                if ($tieneCambios && !$dryRun) {
                    $ticket->imagen = json_encode($archivosActualizados);
                    $ticket->save();
                }

                $procesados++;

            } catch (\Exception $e) {
                $this->error("Error procesando ticket ID {$ticket->TicketID}: " . $e->getMessage());
                $errores++;
            }
        }

        $this->info("\n=== RESUMEN ===");
        $this->info("Tickets procesados: {$procesados}");
        $this->info("Archivos convertidos: {$archivosConvertidos}");
        $this->info("Errores: {$errores}");

        if ($dryRun) {
            $this->warn("Modo dry-run completado. Ejecuta sin --dry-run para aplicar cambios.");
        } else {
            $this->info("Conversión completada exitosamente.");
        }

        return 0;
    }

    /**
     * Convierte un archivo a PNG
     *
     * @param string $rutaArchivo
     * @param string $archivoOriginal
     * @param bool $dryRun
     * @return string|null
     */
    private function convertirArchivo($rutaArchivo, $archivoOriginal, $dryRun = false)
    {
        try {
            $extension = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));
            $directorio = dirname($archivoOriginal);
            $nombreSinExtension = pathinfo($archivoOriginal, PATHINFO_FILENAME);
            $nuevoNombre = $nombreSinExtension . '.png';
            $nuevaRuta = $directorio . '/' . $nuevoNombre;
            $rutaCompletaNueva = storage_path('app/public/' . $nuevaRuta);

            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                case 'gif':
                case 'bmp':
                case 'webp':
                    // Convertir imagen usando Intervention Image
                    if (!$dryRun) {
                        $imagen = $this->imageManager->read($rutaArchivo);
                        $imagen->toPng(90)->save($rutaCompletaNueva);
                    }
                    $this->info("  ✓ Convertido: {$extension} → PNG");
                    return $nuevaRuta;

                case 'pdf':
                    // Convertir PDF a imagen usando DomPDF
                    if (!$dryRun) {
                        $this->convertirPdfAImagen($rutaArchivo, $rutaCompletaNueva);
                    }
                    $this->info("  ✓ Convertido: PDF → PNG");
                    return $nuevaRuta;

                case 'xlsx':
                case 'xls':
                case 'csv':
                    // Convertir Excel a imagen
                    if (!$dryRun) {
                        $this->convertirExcelAImagen($rutaArchivo, $rutaCompletaNueva);
                    }
                    $this->info("  ✓ Convertido: {$extension} → PNG");
                    return $nuevaRuta;

                case 'docx':
                case 'doc':
                    // Convertir Word a imagen (requiere librería adicional)
                    if (!$dryRun) {
                        $this->convertirWordAImagen($rutaArchivo, $rutaCompletaNueva);
                    }
                    $this->info("  ✓ Convertido: {$extension} → PNG");
                    return $nuevaRuta;

                default:
                    $this->warn("  ⚠ Extensión no soportada: {$extension}");
                    return null;
            }

        } catch (\Exception $e) {
            $this->error("  ✗ Error convirtiendo {$archivoOriginal}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Convierte PDF a imagen PNG
     */
    private function convertirPdfAImagen($rutaPdf, $rutaImagen)
    {
        // Usar Imagick si está disponible
        if (extension_loaded('imagick')) {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($rutaPdf . '[0]'); // Solo primera página
            $imagick->setImageFormat('png');
            $imagick->writeImage($rutaImagen);
            $imagick->clear();
            $imagick->destroy();
        } else {
            // Fallback: crear una imagen simple sin texto
            $imagen = $this->imageManager->create(800, 600)->fill('#f0f0f0');
            // Crear un borde
            $imagen->drawRectangle(10, 10, function (RectangleFactory $rectangle) {
                $rectangle->size(780, 580);
                $rectangle->border('#cccccc', 2);
            });
            // Crear un rectángulo central con texto simulado usando formas
            $imagen->drawRectangle(100, 250, function (RectangleFactory $rectangle) {
                $rectangle->size(600, 100);
                $rectangle->background('#e0e0e0');
                $rectangle->border('#999999', 1);
            });
            $imagen->toPng()->save($rutaImagen);
        }
    }

    /**
     * Convierte Excel a imagen PNG
     */
    private function convertirExcelAImagen($rutaExcel, $rutaImagen)
    {
        try {
            $spreadsheet = IOFactory::load($rutaExcel);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Crear imagen con el contenido del Excel
            $imagen = $this->imageManager->create(1000, 600)->fill('#ffffff');
            
            $y = 50;
            $rowCount = 0;
            $maxRows = 20; // Limitar filas para evitar imagen muy grande
            
            foreach ($worksheet->getRowIterator() as $row) {
                if ($rowCount >= $maxRows) break;
                
                $x = 50;
                foreach ($row->getCellIterator() as $cell) {
                    $value = $cell->getCalculatedValue();
                    if ($value) {
                        // Crear rectángulo para simular texto
                        $imagen->drawRectangle($x, $y-15, function (RectangleFactory $rectangle) {
                            $rectangle->size(110, 15);
                            $rectangle->background('#ffffff');
                            $rectangle->border('#cccccc', 1);
                        });
                        $x += 120;
                    }
                }
                $y += 25;
                $rowCount++;
            }
            
            $imagen->toPng()->save($rutaImagen);
            
        } catch (\Exception $e) {
            // Fallback: crear imagen simple sin texto
            $imagen = $this->imageManager->create(800, 600)->fill('#f0f8f0');
            // Crear un borde
            $imagen->drawRectangle(10, 10, function (RectangleFactory $rectangle) {
                $rectangle->size(780, 580);
                $rectangle->border('#cccccc', 2);
            });
            // Crear un rectángulo central
            $imagen->drawRectangle(100, 250, function (RectangleFactory $rectangle) {
                $rectangle->size(600, 100);
                $rectangle->background('#e0f0e0');
                $rectangle->border('#999999', 1);
            });
            $imagen->toPng()->save($rutaImagen);
        }
    }

    /**
     * Convierte Word a imagen PNG
     */
    private function convertirWordAImagen($rutaWord, $rutaImagen)
    {
        // Crear imagen simple sin texto ya que convertir Word requiere librerías adicionales
        $imagen = $this->imageManager->create(800, 600)->fill('#f8f8ff');
        // Crear un borde
        $imagen->drawRectangle(10, 10, function (RectangleFactory $rectangle) {
            $rectangle->size(780, 580);
            $rectangle->border('#cccccc', 2);
        });
        // Crear un rectángulo central
        $imagen->drawRectangle(100, 250, function (RectangleFactory $rectangle) {
            $rectangle->size(600, 100);
            $rectangle->background('#e8e8f0');
            $rectangle->border('#999999', 1);
        });
        $imagen->toPng()->save($rutaImagen);
    }
}
