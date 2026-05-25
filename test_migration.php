<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CortesController;

// Enable reflection to call private method
$controller = app(CortesController::class);
$reflector = new ReflectionClass($controller);
$method = $reflector->getMethod('generarReporteInsumos');
$method->setAccessible(true);

$gerenciaIDs = [1, 2, 3, 4, 17, 18]; // Test standard and specific gerencias

echo "====================================================\n";
echo "COMPARANDO STORED PROCEDURE VS CONTROLADOR LARAVEL\n";
echo "====================================================\n\n";

$allClear = true;

foreach ($gerenciaIDs as $id) {
    echo "--- Probando Gerencia ID: $id ---\n";
    
    // 1. Stored Procedure Result
    try {
        $spRows = DB::select('CALL ObtenerInsumosAnualesPorGerencia6(?)', [$id]);
    } catch (\Exception $e) {
        echo "Error al ejecutar SP: " . $e->getMessage() . "\n";
        continue;
    }
    
    // 2. PHP Migrated Result
    try {
        $phpRows = $method->invoke($controller, $id);
    } catch (\Exception $e) {
        echo "Error al ejecutar PHP: " . $e->getMessage() . "\n";
        $allClear = false;
        continue;
    }

    echo "SP Count: " . count($spRows) . " | PHP Count: " . count($phpRows) . "\n";
    
    // Group and sort both for comparison
    $spMapped = [];
    foreach ($spRows as $row) {
        $key = strtolower(trim($row->NombreInsumo)) . '_' . strtolower(trim($row->Mes));
        $spMapped[$key] = round((float)$row->Costo);
    }
    
    $phpMapped = [];
    foreach ($phpRows as $row) {
        $key = strtolower(trim($row['NombreInsumo'])) . '_' . strtolower(trim($row['Mes']));
        $phpMapped[$key] = round((float)$row['Costo']);
    }

    // Check sizes
    if (count($spMapped) !== count($phpMapped)) {
        echo "⚠️  ADVERTENCIA: Diferencia en cantidad de claves únicas! (SP: " . count($spMapped) . " vs PHP: " . count($phpMapped) . ")\n";
    }

    // Compare differences
    $discrepancies = [];
    
    // 1. Check keys in SP that might be different or missing in PHP
    foreach ($spMapped as $key => $cost) {
        if (!isset($phpMapped[$key])) {
            $discrepancies[] = "Clave '$key' existe en SP con costo $cost pero no en PHP.";
        } elseif ($phpMapped[$key] !== $cost) {
            $discrepancies[] = "Diferencia de costo para '$key': SP = $cost, PHP = {$phpMapped[$key]}";
        }
    }
    
    // 2. Check keys in PHP that might not exist in SP
    foreach ($phpMapped as $key => $cost) {
        if (!isset($spMapped[$key])) {
            $discrepancies[] = "Clave '$key' existe en PHP con costo $cost pero no en SP.";
        }
    }

    if (empty($discrepancies)) {
        echo "✅ GERENCIA $id: COINCIDENCIA TOTAL! 100% IDÉNTICO.\n\n";
    } else {
        echo "❌ GERENCIA $id: SE ENCONTRARON DISCREPANCIAS:\n";
        foreach (array_slice($discrepancies, 0, 10) as $disc) {
            echo "   - $disc\n";
        }
        if (count($discrepancies) > 10) {
            echo "   ... y " . (count($discrepancies) - 10) . " discrepancias más.\n";
        }
        echo "\n";
        $allClear = false;
    }
}

if ($allClear) {
    echo "====================================================\n";
    echo "🎉 ¡ÉXITO! Todos los resultados coinciden al 100%.\n";
    echo "====================================================\n";
} else {
    echo "====================================================\n";
    echo "⚠️ Se encontraron discrepancias. Por favor revise la lógica.\n";
    echo "====================================================\n";
}
