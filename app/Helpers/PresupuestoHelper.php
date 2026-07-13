<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Models\Empleados;

class PresupuestoHelper
{
    private static function esPagoUnico($insumo): bool
    {
        return strcasecmp($insumo->FrecuenciaDePago ?? '', 'Pago único') === 0;
    }

    /**
     * Costo anual de un insumo suelto.
     *
     * 'Pago único' = un solo cargo de CostoMensual hecho en su MesDePago. Así se capturan las
     * licencias contratadas sólo unos meses del año: una fila por mes (Office 365 a 302, de
     * Julio a Diciembre). La columna CostoAnual de esas filas trae 302 x 12 = 3,624, que no
     * corresponde a lo que se eroga, y por eso se ignora.
     */
    private static function costoAnualInsumo($insumo): float
    {
        return self::esPagoUnico($insumo)
            ? (float) ($insumo->CostoMensual ?? 0)
            : (float) ($insumo->CostoAnual ?? 0);
    }

    /**
     * El switch "Presupuestado" parte el inventario en dos reportes que no se solapan:
     * el de presupuesto lista las asignaciones marcadas y el de inventario las que no.
     */
    private static function soloPresupuestados($query, string $modo)
    {
        return $query->where('Presupuestado', $modo === 'presupuesto' ? 1 : 0);
    }

    /**
     * Tipos de persona que entran a cada reporte. null = todos.
     *
     * El presupuesto sólo contempla FISICA y EXTRAORDINARIO. El de inventario NO restringe:
     * es el complemento exacto del presupuesto (todo lo que tiene Presupuestado = 0), y
     * acotarlo por tipo de persona dejaría fuera de ambos reportes las asignaciones no
     * presupuestadas de los tipos que sí pueden presupuestarse.
     */
    private static function tiposPersona(string $modo): ?array
    {
        return $modo === 'presupuesto' ? ['FISICA', 'EXTRAORDINARIO'] : null;
    }

    /**
     * Cuántas frecuencias de pago distintas tiene cada insumo de un empleado, por nombre.
     *
     * Un 'MANTENIMIENTO' mensual y un 'MANTENIMIENTO' de pago único son insumos distintos
     * aunque se llamen igual, y no pueden compartir columna en el reporte: el que colisiona
     * lleva su frecuencia en el nombre. Los que no colisionan conservan el nombre tal cual.
     */
    private static function frecuenciasPorNombre($insumos, callable $nombreBase)
    {
        return $insumos
            ->groupBy(fn ($i) => $nombreBase($i))
            ->map(fn ($grupo) => $grupo
                ->map(fn ($i) => strtoupper(trim((string) ($i->FrecuenciaDePago ?? ''))))
                ->unique()
                ->count());
    }

    private static function nombreConFrecuencia(string $nombre, $insumo, $frecuenciasPorNombre): string
    {
        if (($frecuenciasPorNombre[$nombre] ?? 1) <= 1) {
            return $nombre;
        }

        $frecuencia = trim((string) ($insumo->FrecuenciaDePago ?? ''));

        return $frecuencia === '' ? $nombre : $nombre . ' (' . $frecuencia . ')';
    }

    /**
     * Costo de todos los registros de un mismo insumo (mismo NombreInsumo, misma
     * FrecuenciaDePago, mismo empleado).
     *
     * Los 'Pago único' son cargos sueltos, uno por MesDePago:
     *
     * - En el reporte ANUAL cuentan todos (302 x 6 = 1,812).
     * - En el MENSUAL, que retrata un mes cualquiera, cuenta lo que se eroga en el mes más caro.
     *   Los cargos de meses distintos NO se acumulan (Office 365, a 302 de julio a diciembre,
     *   cuesta 302 al mes), pero los que caen en el MISMO mes sí se suman entre ellos (cuatro
     *   mantenimientos de 25 en septiembre son 100, no 25).
     *
     * Por eso el mensual no cuadra con el calendario de pagos, y está bien que no cuadre: el
     * calendario reparte cada cargo en su mes, así que ahí sí aparecen todos.
     */
    private static function costoGrupoInsumo($grupo, string $tipo): float
    {
        [$unicos, $recurrentes] = $grupo->partition(fn ($i) => self::esPagoUnico($i));

        $costo = $tipo === 'mens'
            ? $recurrentes->sum(fn ($i) => (float) ($i->CostoMensual ?? 0))
            : $recurrentes->sum(fn ($i) => (float) ($i->CostoAnual ?? 0));

        if ($unicos->isEmpty()) {
            return $costo;
        }

        $porMes = $unicos
            ->groupBy(fn ($i) => strtoupper(trim((string) ($i->MesDePago ?? ''))))
            ->map(fn ($mes) => $mes->sum(fn ($i) => (float) ($i->CostoMensual ?? 0)));

        $costo += $tipo === 'mens'
            ? (float) $porMes->max()
            : (float) $porMes->sum();

        return $costo;
    }

    // Método para obtener el reporte de accesorios y mantenimientos por gerencia mensual o anual
    public static function reporteAccesoriosYMantenimientos(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $tiposPersona = self::tiposPersona($modo);

        return Empleados::query()
            ->when($tiposPersona, fn ($q) => $q->whereIn('tipo_persona', $tiposPersona))
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            // Solo empleados con insumos de estas categorías
            ->whereHas('inventarioinsumo', function($query) use ($modo) {
                $query->whereIn('CateogoriaInsumo', ['MANTENIMIENTO', 'REPARACIONES', 'SERVICIO', 'HOSTING']);
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventarioinsumo' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'NombreInsumo', 'CateogoriaInsumo', 'CostoMensual', 'CostoAnual', 'FrecuenciaDePago', 'MesDePago')
                          ->whereIn('CateogoriaInsumo', ['MANTENIMIENTO', 'REPARACIONES', 'SERVICIO', 'HOSTING']);
                    self::soloPresupuestados($query, $modo);
                }
            ])
            ->get()
            ->flatMap(function($empleado) use ($tipo) {
                $nombreBase = fn ($i) => strcasecmp($i->NombreInsumo ?? '', 'REPARACIONES') === 0
                    ? 'ACCESORIOS Y REFACCIONES'
                    : (string) $i->NombreInsumo;

                $frecuencias = self::frecuenciasPorNombre($empleado->inventarioinsumo, $nombreBase);

                return $empleado->inventarioinsumo
                    // Mismo nombre con distinta frecuencia = insumos distintos (uno se paga cada
                    // mes, el otro una sola vez), así que van en filas separadas. Dentro de cada
                    // grupo, los 'Pago único' siguen siendo cargos sueltos, uno por MesDePago.
                    ->groupBy(fn ($i) => $nombreBase($i) . '|' . strtoupper(trim((string) ($i->FrecuenciaDePago ?? ''))))
                    ->map(function($grupo) use ($empleado, $tipo, $nombreBase, $frecuencias) {
                        $insumo = $grupo->first();

                        return (object)[
                            'EmpleadoID' => $empleado->EmpleadoID,
                            'NombreEmpleado' => $empleado->NombreEmpleado,
                            'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                            'NombreInsumo' => self::nombreConFrecuencia($nombreBase($insumo), $insumo, $frecuencias),
                            'CateogoriaInsumo' => $insumo->CateogoriaInsumo,
                            'CostoTotal' => (int) round(self::costoGrupoInsumo($grupo, $tipo)),
                        ];
                    })
                    ->values();
            })
            ->sortBy([
                ['NombreEmpleado', 'asc'],
                ['NombrePuesto', 'asc'],
                ['NombreInsumo', 'asc'],
            ])
            ->values();
    }

    /**
     * Reporte de hardware por gerencia.
     *
     * Lee inventarioequipo (los activos físicos), no inventarioinsumo. El único costo que
     * existe ahí es Precio (precio de compra), así que $tipo no aplica: una laptop no se
     * "paga mensual". El mismo monto sale en el reporte mensual y en el anual.
     */
    public static function reporteHardwarePorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $tiposPersona = self::tiposPersona($modo);
        // Nombres tal como existen en inventarioequipo: ahí es TABLETA (no TABLET) y UPS
        // (no NO BREAK). STARLINK no vive aquí: es un servicio recurrente, va como insumo.
        $categorias = ['LAPTOP', 'MONITOR', 'IMPRESORA', 'TABLETA', 'UPS'];

        return Empleados::query()
            ->when($tiposPersona, fn ($q) => $q->whereIn('tipo_persona', $tiposPersona))
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventarioequipo', function($query) use ($categorias, $modo) {
                $query->whereIn('CategoriaEquipo', $categorias);
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventarioequipo' => function($query) use ($categorias, $modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'CategoriaEquipo', 'Precio')
                          ->whereIn('CategoriaEquipo', $categorias);
                    self::soloPresupuestados($query, $modo);
                }
            ])
            ->get()
            ->flatMap(function($empleado) {
                return $empleado->inventarioequipo->map(function($equipo) use ($empleado) {
                    return (object)[
                        'EmpleadoID' => $empleado->EmpleadoID,
                        'NombreEmpleado' => $empleado->NombreEmpleado,
                        'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                        'NombreInsumo' => $equipo->CategoriaEquipo,
                        'CateogoriaInsumo' => $equipo->CategoriaEquipo,
                        'CostoTotal' => (int) round($equipo->Precio ?? 0),
                    ];
                });
            })
            ->sortBy('NombreEmpleado')
            ->values();
    }


    // Método para obtener el reporte de licencias por gerencia mensual o anual
    public static function reporteLicenciasPorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        // Obtener costos de Windows PRO. Se redondean aquí, antes de multiplicar por el
        // periodo, porque el SP los guarda en variables declaradas INT.
        $costoWin10Pro = (int) round(DB::table('inventarioinsumo')
            ->where('NombreInsumo', 'WINDOWS 10 PRO')
            ->max('CostoMensual') ?? 0);

        $costoWin11Pro = (int) round(DB::table('inventarioinsumo')
            ->where('NombreInsumo', 'WINDOWS 11 PRO')
            ->max('CostoMensual') ?? 0);


        $multiplicador = $tipo === 'mens' ? 1 : 12;
        $tiposPersona = self::tiposPersona($modo);
        
        return Empleados::query()
            ->when($tiposPersona, fn ($q) => $q->whereIn('tipo_persona', $tiposPersona))
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventarioinsumo', function($query) use ($modo) {
                $query->where('CateogoriaInsumo', 'LICENCIA')
                      ->whereNotIn('NombreInsumo', ['WINDOWS 10 PRO', 'WINDOWS 11 PRO', 'ERP VSCONTROL TOTAL']);
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventarioinsumo' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'NombreInsumo', 'CostoMensual', 'CostoAnual', 'FrecuenciaDePago', 'MesDePago')
                          ->where('CateogoriaInsumo', 'LICENCIA')
                          ->whereNotIn('NombreInsumo', ['WINDOWS 10 PRO', 'WINDOWS 11 PRO', 'ERP VSCONTROL TOTAL']);
                    self::soloPresupuestados($query, $modo);
                }
            ])
            ->orderBy('NombreEmpleado')
            ->get()
            ->flatMap(function($empleado) use ($tipo, $costoWin10Pro, $costoWin11Pro, $multiplicador, $gerenciaId) {
                $licencias = $empleado->inventarioinsumo
                    ->reject(function($insumo) use ($empleado, $gerenciaId) {
                        // Excluir WINDOWS para gerencias 17 y 18
                        if (in_array($gerenciaId, [17, 18]) && stripos($insumo->NombreInsumo ?? '', 'WINDOWS') === 0) {
                            return true;
                        }
                        return false;
                    });

                $nombreBase = fn ($i) => (string) $i->NombreInsumo;
                $frecuencias = self::frecuenciasPorNombre($licencias, $nombreBase);

                return $licencias
                    // Misma licencia con distinta frecuencia = insumos distintos (una se paga cada
                    // mes, la otra una sola vez), así que van en filas separadas. Dentro de cada
                    // grupo, los 'Pago único' siguen siendo cargos sueltos, uno por MesDePago.
                    ->groupBy(fn ($i) => $nombreBase($i) . '|' . strtoupper(trim((string) ($i->FrecuenciaDePago ?? ''))))
                    ->map(function($grupo) use ($empleado, $tipo, $costoWin10Pro, $costoWin11Pro, $multiplicador, $nombreBase, $frecuencias) {
                        $insumo = $grupo->first();

                        // Calcular costo según el tipo de licencia
                        $costo = match(strtoupper($insumo->NombreInsumo ?? '')) {
                            'WINDOWS 10 HOME' => $costoWin10Pro * $multiplicador * $grupo->count(),
                            'WINDOWS 11 HOME' => $costoWin11Pro * $multiplicador * $grupo->count(),
                            default => self::costoGrupoInsumo($grupo, $tipo),
                        };

                        return (object)[
                            'EmpleadoID' => $empleado->EmpleadoID,
                            'NombreEmpleado' => $empleado->NombreEmpleado,
                            'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                            'NombreInsumo' => self::nombreConFrecuencia($nombreBase($insumo), $insumo, $frecuencias),
                            'CostoTotal' => (int) round($costo),
                        ];
                    })
                    ->values();
            })
            ->values();
    }
    
    // Método para obtener el reporte de líneas de datos por gerencia mensual o anual
    public static function reporteLineasDatosPorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $tiposPersona = self::tiposPersona($modo);
        
        return Empleados::query()
            ->when($tiposPersona, fn ($q) => $q->whereIn('tipo_persona', $tiposPersona))
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventariolineas', function($query) use ($modo) {
                $query->where('TipoLinea', 'Datos');
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventariolineas' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'TipoLinea', 'CostoRentaMensual', 'CostoFianza', 'MontoRenovacionFianza')
                          ->where('TipoLinea', 'Datos');
                    self::soloPresupuestados($query, $modo);
                }
            ])
            ->get()
            // El SP agrupa por NombreEmpleado + NombrePuesto, no por EmpleadoID: dos homónimos
            // en el mismo puesto se funden en una sola fila con los costos sumados.
            ->groupBy(fn($empleado) => $empleado->NombreEmpleado . '|' . ($empleado->puestos->NombrePuesto ?? ''))
            ->map(function($grupo) use ($tipo) {
                $empleado = $grupo->first();
                $lineasDatos = $grupo->flatMap(fn($e) => $e->inventariolineas);

                if ($tipo === 'mens') {
                    $costoRenta = $lineasDatos->sum('CostoRentaMensual');
                    $costoFianza = $lineasDatos->sum('CostoFianza') / 12;
                    $montoRenovacion = $lineasDatos->sum('MontoRenovacionFianza') / 12;

                    return (object)[
                        'EmpleadoID' => $empleado->EmpleadoID,
                        'NombreEmpleado' => $empleado->NombreEmpleado,
                        'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                        'Datos_Costo_Renta_Mensual' => (int) round($costoRenta),
                        'Datos_Costo_Fianza' => (int) round($costoFianza),
                        'Datos_Monto_Renovacion' => (int) round($montoRenovacion),
                        'Total' => (int) round($costoRenta + $costoFianza + $montoRenovacion),
                        'Orden' => 5,
                    ];
                }

                $costoRenta = $lineasDatos->sum('CostoRentaMensual') * 12;
                $costoFianza = $lineasDatos->sum('CostoFianza');
                $montoRenovacion = $lineasDatos->sum('MontoRenovacionFianza');

                return (object)[
                    'EmpleadoID' => $empleado->EmpleadoID,
                    'NombreEmpleado' => $empleado->NombreEmpleado,
                    'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                    'Datos_Costo_Renta_Anual' => (int) round($costoRenta),
                    'Datos_Costo_Fianza_Anual' => (int) round($costoFianza),
                    'Datos_Monto_Renovacion_Anual' => (int) round($montoRenovacion),
                    'Total' => (int) round($costoRenta + $costoFianza + $montoRenovacion),
                    'Orden' => 5,
                ];
            })
            // El HAVING del SP mira sólo la renta, no el total
            ->filter(fn($row) => ($tipo === 'mens' ? $row->Datos_Costo_Renta_Mensual : $row->Datos_Costo_Renta_Anual) > 0)
            ->sortBy('NombreEmpleado')
            ->values();
    }
    
    // Método para obtener el reporte de líneas GPS por gerencia mensual o anual
    public static function reporteLineasGPSPorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $tiposPersona = self::tiposPersona($modo);
        
        return Empleados::query()
            ->when($tiposPersona, fn ($q) => $q->whereIn('tipo_persona', $tiposPersona))
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventariolineas', function($query) use ($modo) {
                $query->where('TipoLinea', 'GPS');
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventariolineas' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'TipoLinea', 'CostoRentaMensual', 'CostoFianza', 'MontoRenovacionFianza')
                          ->where('TipoLinea', 'GPS');
                    self::soloPresupuestados($query, $modo);
                }
            ])
            ->get()
            // El SP agrupa por NombreEmpleado + NombrePuesto, no por EmpleadoID: dos homónimos
            // en el mismo puesto se funden en una sola fila con los costos sumados.
            ->groupBy(fn($empleado) => $empleado->NombreEmpleado . '|' . ($empleado->puestos->NombrePuesto ?? ''))
            ->map(function($grupo) use ($tipo) {
                $empleado = $grupo->first();
                $lineasGPS = $grupo->flatMap(fn($e) => $e->inventariolineas);

                if ($tipo === 'mens') {
                    $costoRenta = $lineasGPS->sum('CostoRentaMensual');
                    $costoFianza = $lineasGPS->sum('CostoFianza') / 12;
                    $montoRenovacion = $lineasGPS->sum('MontoRenovacionFianza') / 12;

                    return (object)[
                        'EmpleadoID' => $empleado->EmpleadoID,
                        'NombreEmpleado' => $empleado->NombreEmpleado,
                        'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                        'GPS_Costo_Renta_Mensual' => (int) round($costoRenta),
                        'GPS_Costo_Fianza' => (int) round($costoFianza),
                        'GPS_Monto_Renovacion' => (int) round($montoRenovacion),
                        'Total' => (int) round($costoRenta + $costoFianza + $montoRenovacion),
                        'Orden' => 5,
                    ];
                }

                $costoRenta = $lineasGPS->sum('CostoRentaMensual') * 12;
                $costoFianza = $lineasGPS->sum('CostoFianza');
                $montoRenovacion = $lineasGPS->sum('MontoRenovacionFianza');

                return (object)[
                    'EmpleadoID' => $empleado->EmpleadoID,
                    'NombreEmpleado' => $empleado->NombreEmpleado,
                    'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                    'GPS_Costo_Renta_Anual' => (int) round($costoRenta),
                    'GPS_Costo_Fianza_Anual' => (int) round($costoFianza),
                    'GPS_Monto_Renovacion_Anual' => (int) round($montoRenovacion),
                    'Total' => (int) round($costoRenta + $costoFianza + $montoRenovacion),
                    'Orden' => 5,
                ];
            })
            // El HAVING del SP mira sólo la renta, no el total
            ->filter(fn($row) => ($tipo === 'mens' ? $row->GPS_Costo_Renta_Mensual : $row->GPS_Costo_Renta_Anual) > 0)
            ->sortBy('NombreEmpleado')
            ->values();
    }
    
    // Método para obtener el reporte de líneas de voz por gerencia mensual o anual
    public static function reporteLineasVozPorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $tiposPersona = self::tiposPersona($modo);
        
        return Empleados::query()
            ->when($tiposPersona, fn ($q) => $q->whereIn('tipo_persona', $tiposPersona))
                ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventariolineas', function($query) use ($modo) {
                $query->where('TipoLinea', 'Voz');
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventariolineas' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'TipoLinea', 'CostoRentaMensual', 'CostoFianza', 'MontoRenovacionFianza')
                          ->where('TipoLinea', 'Voz');
                    self::soloPresupuestados($query, $modo);
                }
            ])
            ->get()
            // El SP agrupa por NombreEmpleado + NombrePuesto, no por EmpleadoID: dos homónimos
            // en el mismo puesto se funden en una sola fila con los costos sumados.
            ->groupBy(fn($empleado) => $empleado->NombreEmpleado . '|' . ($empleado->puestos->NombrePuesto ?? ''))
            ->map(function($grupo) use ($tipo) {
                $empleado = $grupo->first();
                $lineasVoz = $grupo->flatMap(fn($e) => $e->inventariolineas);

                if ($tipo === 'mens') {
                    $costoRenta = $lineasVoz->sum('CostoRentaMensual');
                    $costoFianza = $lineasVoz->sum('CostoFianza') / 12;
                    $montoRenovacion = $lineasVoz->sum('MontoRenovacionFianza') / 12;

                    return (object)[
                        'EmpleadoID' => $empleado->EmpleadoID,
                        'NombreEmpleado' => $empleado->NombreEmpleado,
                        'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                        'Voz_Costo_Renta_Mensual' => (int) round($costoRenta),
                        'Voz_Costo_Fianza' => (int) round($costoFianza),
                        'Voz_Monto_Renovacion' => (int) round($montoRenovacion),
                        'Total' => (int) round($costoRenta + $costoFianza + $montoRenovacion),
                        'Orden' => 5,
                    ];
                }

                $costoRenta = $lineasVoz->sum('CostoRentaMensual') * 12;
                $costoFianza = $lineasVoz->sum('CostoFianza');
                $montoRenovacion = $lineasVoz->sum('MontoRenovacionFianza');

                return (object)[
                    'EmpleadoID' => $empleado->EmpleadoID,
                    'NombreEmpleado' => $empleado->NombreEmpleado,
                    'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                    'Voz_Costo_Renta_Anual' => (int) round($costoRenta),
                    'Voz_Costo_Fianza_Anual' => (int) round($costoFianza),
                    'Voz_Monto_Renovacion_Anual' => (int) round($montoRenovacion),
                    'Total' => (int) round($costoRenta + $costoFianza + $montoRenovacion),
                    'Orden' => 5,
                ];
            })
            // El HAVING del SP mira sólo la renta, no el total
            ->filter(fn($row) => ($tipo === 'mens' ? $row->Voz_Costo_Renta_Mensual : $row->Voz_Costo_Renta_Anual) > 0)
            ->sortBy('NombreEmpleado')
            ->values();
    }

    // Método para obtener el reporte anual de insumos por gerencia (con orden específico)
    public static function obtenerInsumosAnualesPorGerencia(int $gerenciaId): \Illuminate\Support\Collection
    {
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $mesIndice = array_flip($meses);

        $esExentaWindows = in_array($gerenciaId, [17, 18]);

        // 1. Costos base de Windows (con 7% de incremento, redondeados)
        $costoWin10Pro = $esExentaWindows ? 0 : (int) round(
            (float) (DB::table('inventarioinsumo')->where('NombreInsumo', 'WINDOWS 10 PRO')->max('CostoMensual') ?? 0) * 1.07
        );
        $costoWin11Pro = $esExentaWindows ? 0 : (int) round(
            (float) (DB::table('inventarioinsumo')->where('NombreInsumo', 'WINDOWS 11 PRO')->max('CostoAnual') ?? 0) * 1.07
        );

        // 2. Cargar empleados de la gerencia con sus insumos y líneas
        $empleados = Empleados::query()
            ->whereIn('tipo_persona', ['FISICA', 'EXTRAORDINARIO'])
            ->whereHas('puestos.departamentos.gerencia', function ($q) use ($gerenciaId) {
                $q->where('gerencia.GerenciaID', $gerenciaId);
            })
            // Este método sólo lo consume Cortes, que NO filtra por Presupuestado.
            ->with([
                'inventarioinsumo' => function ($q) {
                    $q->select('InventarioID', 'EmpleadoID', 'NombreInsumo', 'CateogoriaInsumo',
                               'CostoMensual', 'CostoAnual', 'FrecuenciaDePago', 'MesDePago');
                },
                'inventariolineas' => function ($q) {
                    $q->select('InventarioID', 'EmpleadoID', 'Compania', 'TipoLinea',
                               'CostoRentaMensual', 'CostoFianza', 'FechaFianza', 'MontoRenovacionFianza');
                },
            ])
            ->get();

        // 3. Total renovación fianzas (se suma a Junio en INVERSIONES)
        $totalRenovacionFianzas = $empleados->sum(
            fn ($e) => $e->inventariolineas->whereNotNull('MontoRenovacionFianza')->sum('MontoRenovacionFianza')
        );

        $todosInsumos = $empleados->flatMap(fn ($e) => $e->inventarioinsumo);
        $todasLineas  = $empleados->flatMap(fn ($e) => $e->inventariolineas);

        $resultado = collect();

        // --- ORDEN 1: Insumos Mensuales Directos ---
        $todosInsumos
            ->filter(fn ($i) =>
                $i->FrecuenciaDePago === 'Mensual' &&
                in_array($i->CateogoriaInsumo, ['LICENCIA', 'HOSTING', 'STARLINK', 'INTERNET', 'TABLET'])
            )
            ->groupBy('NombreInsumo')
            ->each(function ($grupo, $nombre) use ($meses, $gerenciaId, &$resultado) {
                $costoMensual = $grupo->sum('CostoMensual');
                if ($costoMensual * 12 <= 0) return;
                foreach ($meses as $mes) {
                    $resultado->push((object)[
                        'NombreInsumo' => $nombre,
                        'Mes'          => $mes,
                        'Costo'        => (int) round($costoMensual),
                        'Orden'        => 1,
                        'GerenciaID'   => $gerenciaId,
                    ]);
                }
            });

        // --- ORDEN 2: Licencias Anuales ---
        $todosInsumos
            ->filter(fn ($i) =>
                in_array($i->FrecuenciaDePago, ['Anual', 'Pago único']) &&
                $i->CateogoriaInsumo === 'LICENCIA' &&
                !($esExentaWindows && str_contains(strtoupper($i->NombreInsumo), 'WINDOWS'))
            )
            ->groupBy('NombreInsumo')
            ->each(function ($grupo, $nombre) use ($meses, $gerenciaId, $costoWin10Pro, $costoWin11Pro, &$resultado) {
                $costosPorMes = [];
                foreach ($meses as $mes) {
                    $costosPorMes[$mes] = $grupo->filter(fn ($i) => strcasecmp($i->MesDePago ?? '', $mes) === 0)
                        ->sum(function ($i) use ($nombre, $costoWin10Pro, $costoWin11Pro) {
                            return match (strtoupper($nombre)) {
                                'WINDOWS 10 HOME'                   => $costoWin10Pro,
                                'WINDOWS 11 HOME'                   => $costoWin11Pro,
                                'WINDOWS 10 PRO', 'WINDOWS 11 PRO'  => 0,
                                default                             => self::costoAnualInsumo($i),
                            };
                        });
                }

                // HAVING del SP: descarta el insumo si su total anual es 0
                if (array_sum($costosPorMes) <= 0) return;

                foreach ($meses as $mes) {
                    $resultado->push((object)[
                        'NombreInsumo' => $nombre,
                        'Mes'          => $mes,
                        'Costo'        => (int) round($costosPorMes[$mes]),
                        'Orden'        => 2,
                        'GerenciaID'   => $gerenciaId,
                    ]);
                }
            });

        // --- ORDEN 3: Otros Insumos anuales (no hardware, no licencia) ---
        $todosInsumos
            ->filter(fn ($i) =>
                in_array($i->FrecuenciaDePago, ['Anual', 'Pago único']) &&
                !in_array($i->CateogoriaInsumo, ['LAPTOP', 'MONITOR', 'NO BREAK', 'LICENCIA', 'ACCESORIOS', 'BATERIA UPS', 'IMPRESORA'])
            )
            ->groupBy(fn ($i) => strcasecmp($i->CateogoriaInsumo ?? '', 'REPARACIONES') === 0 ? 'ACCESORIOS Y REFACCIONES' : $i->NombreInsumo)
            ->each(function ($grupo, $nombre) use ($meses, $gerenciaId, &$resultado) {
                $costosPorMes = [];
                foreach ($meses as $mes) {
                    $costosPorMes[$mes] = $grupo->filter(fn ($i) => strcasecmp($i->MesDePago ?? '', $mes) === 0)
                        ->sum(fn ($i) => self::costoAnualInsumo($i));
                }

                // HAVING del SP: descarta el insumo si su total anual es 0
                if (array_sum($costosPorMes) <= 0) return;

                foreach ($meses as $mes) {
                    $resultado->push((object)[
                        'NombreInsumo' => $nombre,
                        'Mes'          => $mes,
                        'Costo'        => (int) round($costosPorMes[$mes]),
                        'Orden'        => 3,
                        'GerenciaID'   => $gerenciaId,
                    ]);
                }
            });

        // --- ORDEN 4: Fianzas por compañía/tipo de línea ---
        $todasLineas
            ->filter(fn ($l) => in_array(strtoupper(trim($l->TipoLinea ?? '')), ['VOZ', 'DATOS', 'GPS']))
            ->groupBy(fn ($l) => strtoupper(trim($l->Compania ?? '')) . '|' . strtoupper(trim($l->TipoLinea ?? '')))
            ->each(function ($grupo, $key) use ($meses, $gerenciaId, &$resultado) {
                [$compania, $tipoLinea] = explode('|', $key, 2);
                $nombre = $compania . ' FIANZA - ' . $tipoLinea;

                $costosPorMes = [];
                foreach ($meses as $numMes => $mes) {
                    $mesNum = $numMes + 1;
                    $costosPorMes[$mes] = $grupo
                        ->filter(fn ($l) => $l->FechaFianza && $l->FechaFianza->month === $mesNum)
                        ->sum('CostoFianza');
                }

                // HAVING del SP: descarta la compañía/tipo si su total anual de fianzas es 0
                if (array_sum($costosPorMes) <= 0) return;

                foreach ($meses as $mes) {
                    $resultado->push((object)[
                        'NombreInsumo' => $nombre,
                        'Mes'          => $mes,
                        'Costo'        => (int) round($costosPorMes[$mes]),
                        'Orden'        => 4,
                        'GerenciaID'   => $gerenciaId,
                    ]);
                }
            });

        // --- ORDEN 5: Líneas Mensuales (renta por compañía/tipo) ---
        $todasLineas
            ->groupBy(fn ($l) => strtoupper(trim($l->Compania ?? '')) . '|' . strtoupper(trim($l->TipoLinea ?? '')))
            ->each(function ($grupo, $key) use ($meses, $gerenciaId, &$resultado) {
                [$compania, $tipoLinea] = explode('|', $key, 2);
                $nombre = $compania . ' ' . $tipoLinea;
                $costoMensual = $grupo->sum('CostoRentaMensual');
                if ($costoMensual * 12 <= 0) return;
                foreach ($meses as $mes) {
                    $resultado->push((object)[
                        'NombreInsumo' => $nombre,
                        'Mes'          => $mes,
                        'Costo'        => (int) round($costoMensual),
                        'Orden'        => 5,
                        'GerenciaID'   => $gerenciaId,
                    ]);
                }
            });

        // --- ORDEN 6: Inversiones (hardware anual + renovación fianzas en Junio) ---
        $insumosHardware = $todosInsumos->filter(fn ($i) =>
            in_array($i->FrecuenciaDePago, ['Anual', 'Pago único']) &&
            in_array($i->CateogoriaInsumo, ['LAPTOP', 'MONITOR', 'NO BREAK', 'TABLET', 'IMPRESORA'])
        );

        // Calcular costos por mes
        $costosTotalesPorMes = [];
        foreach ($meses as $mes) {
            $costo = $insumosHardware->filter(fn ($i) => strcasecmp($i->MesDePago ?? '', $mes) === 0)
                ->sum(fn ($i) => self::costoAnualInsumo($i));
            if ($mes === 'Junio') {
                $costo += $totalRenovacionFianzas;
            }
            $costosTotalesPorMes[$mes] = $costo;
        }

        // Solo generar INVERSIONES si hay al menos un mes con costo > 0
        $totalAnual = array_sum($costosTotalesPorMes);
        if ($totalAnual > 0) {
            foreach ($meses as $mes) {
                $resultado->push((object)[
                    'NombreInsumo' => 'INVERSIONES',
                    'Mes'          => $mes,
                    'Costo'        => (int) round($costosTotalesPorMes[$mes]),
                    'Orden'        => 6,
                    'GerenciaID'   => $gerenciaId,
                ]);
            }
        }

        // Ordenar: NombreInsumo (A-Z, sin distinguir mayúsculas) → número de mes
        return $resultado->sortBy([
            fn ($a, $b) => strcasecmp($a->NombreInsumo, $b->NombreInsumo),
            fn ($a, $b) => $mesIndice[$a->Mes] <=> $mesIndice[$b->Mes],
        ])->values();
    }

    /**
     * Calendario de pagos (12 meses) por gerencia.
     *
     * Lo consumen la vista de presupuesto y el Excel: ambos deben ver lo mismo.
     */
    public static function calendarioPagosPorGerencia($gerenciaId, string $modo = 'presupuesto')
    {
        $tiposPersona = self::tiposPersona($modo);
        $tipoPersonaFilter = $tiposPersona
            ? " AND e.tipo_persona IN ('" . implode("', '", $tiposPersona) . "') "
            : "";

        // El switch "Presupuestado" parte el inventario en dos reportes que no se solapan:
        // presupuesto lista lo marcado, inventario lo que no. El alias de la tabla cambia
        // entre consultas, por eso van tres variantes del mismo filtro.
        $valorPresup    = $modo === 'presupuesto' ? 1 : 0;
        $presupInsumo   = " AND i.Presupuestado = {$valorPresup} ";
        $presupLinea    = " AND il.Presupuestado = {$valorPresup} ";

        $bindings = ['gerenciaId' => $gerenciaId];

        // 1. Obtener costos de Windows 10 Pro
        $sqlWin10 = "SELECT 
            CASE 
                WHEN :gerenciaId IN (17, 18) THEN 0.00 
                 ELSE ROUND(IFNULL(MAX(CostoMensual * 1.07), 0)) 
            END AS CostoWindows10Pro
        FROM inventarioinsumo
        WHERE NombreInsumo = 'WINDOWS 10 PRO'";
        $costoWindows10ProObj = DB::selectOne($sqlWin10, ['gerenciaId' => $gerenciaId]);
        $costoWindows10Pro = $costoWindows10ProObj ? $costoWindows10ProObj->CostoWindows10Pro : 0;

        // 2. Obtener costos de Windows 11 Pro
        $sqlWin11 = "SELECT 
            CASE 
                WHEN :gerenciaId IN (17, 18) THEN 0.00 
                 ELSE ROUND(IFNULL(MAX(CostoAnual * 1.07), 0)) 
            END AS CostoWindows11Pro
        FROM inventarioinsumo
        WHERE NombreInsumo = 'WINDOWS 11 PRO'";
        $costoWindows11ProObj = DB::selectOne($sqlWin11, ['gerenciaId' => $gerenciaId]);
        $costoWindows11Pro = $costoWindows11ProObj ? $costoWindows11ProObj->CostoWindows11Pro : 0;

        // 3. Obtener la suma de la MontoRenovacionFianza
        $sqlFianzas = "SELECT SUM(il.MontoRenovacionFianza) as TotalRenovacionFianzas
        FROM inventariolineas il
        INNER JOIN empleados e ON il.EmpleadoID = e.EmpleadoID
        INNER JOIN puestos p ON e.PuestoID = p.PuestoID
        INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
        INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
        WHERE il.MontoRenovacionFianza IS NOT NULL
        AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . $presupLinea;
        $totalFianzasObj = DB::selectOne($sqlFianzas, $bindings);
        $totalRenovacionFianzas = $totalFianzasObj ? ($totalFianzasObj->TotalRenovacionFianzas ?? 0) : 0;

        $reporteTemp = [];

        // Query 1: Lineas Renta (Orden 5)
        $sqlLineasRenta = "SELECT 
                CONCAT(il.Compania, ' ', il.TipoLinea) AS NombreInsumo,
                SUM((il.CostoRentaMensual)) AS Enero,
                SUM((il.CostoRentaMensual)) AS Febrero,
                SUM((il.CostoRentaMensual)) AS Marzo,
                SUM((il.CostoRentaMensual)) AS Abril,
                SUM((il.CostoRentaMensual)) AS Mayo,
                SUM((il.CostoRentaMensual)) AS Junio,
                SUM((il.CostoRentaMensual)) AS Julio,
                SUM((il.CostoRentaMensual)) AS Agosto,
                SUM((il.CostoRentaMensual)) AS Septiembre,
                SUM((il.CostoRentaMensual)) AS Octubre,
                SUM((il.CostoRentaMensual)) AS Noviembre,
                SUM((il.CostoRentaMensual)) AS Diciembre,
                5 AS Orden  
            FROM 
                inventariolineas il
            INNER JOIN 
                empleados e ON il.EmpleadoID = e.EmpleadoID
            INNER JOIN 
                puestos p ON e.PuestoID = p.PuestoID
            INNER JOIN 
                departamentos d ON p.DepartamentoID = d.DepartamentoID
            INNER JOIN 
                gerencia g ON d.GerenciaID = g.GerenciaID
            WHERE 
                g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . $presupLinea . "
            GROUP BY il.Compania, il.TipoLinea
            HAVING (SUM((il.CostoRentaMensual)) * 12) > 0";
        $res1 = DB::select($sqlLineasRenta, $bindings);
        $reporteTemp = array_merge($reporteTemp, $res1);

        // Query 2: Lineas Fianza (Orden 4)
        $sqlLineasFianza = "SELECT 
                CONCAT(il.Compania, ' FIANZA - ', il.TipoLinea) AS NombreInsumo,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 1 THEN (il.CostoFianza) ELSE 0 END) AS Enero,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 2 THEN (il.CostoFianza) ELSE 0 END) AS Febrero,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 3 THEN (il.CostoFianza) ELSE 0 END) AS Marzo,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 4 THEN (il.CostoFianza) ELSE 0 END) AS Abril,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 5 THEN (il.CostoFianza) ELSE 0 END) AS Mayo,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 6 THEN (il.CostoFianza) ELSE 0 END) AS Junio,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 7 THEN (il.CostoFianza) ELSE 0 END) AS Julio,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 8 THEN (il.CostoFianza) ELSE 0 END) AS Agosto,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 9 THEN (il.CostoFianza) ELSE 0 END) AS Septiembre,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 10 THEN (il.CostoFianza) ELSE 0 END) AS Octubre,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 11 THEN (il.CostoFianza) ELSE 0 END) AS Noviembre,
                SUM(CASE WHEN MONTH(il.FechaFianza) = 12 THEN (il.CostoFianza) ELSE 0 END) AS Diciembre,
                 4 AS Orden  
            FROM 
                inventariolineas il
            INNER JOIN 
                empleados e ON il.EmpleadoID = e.EmpleadoID
            INNER JOIN 
                puestos p ON e.PuestoID = p.PuestoID
            INNER JOIN 
                departamentos d ON p.DepartamentoID = d.DepartamentoID
            INNER JOIN 
                gerencia g ON d.GerenciaID = g.GerenciaID
            WHERE 
                il.TipoLinea IN ('Voz', 'Datos', 'GPS')  
                AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . $presupLinea . "
            GROUP BY il.Compania, il.TipoLinea
            HAVING (
                SUM(CASE WHEN MONTH(il.FechaFianza) = 1 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 2 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 3 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 4 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 5 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 6 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 7 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 8 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 9 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 10 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 11 THEN (il.CostoFianza) ELSE 0 END) +
                SUM(CASE WHEN MONTH(il.FechaFianza) = 12 THEN (il.CostoFianza) ELSE 0 END)
            ) > 0";
        $res2 = DB::select($sqlLineasFianza, $bindings);
        $reporteTemp = array_merge($reporteTemp, $res2);

        // Query 3: Inversiones (Orden 6)
        // El hardware sale de inventarioequipo, igual que PresupuestoHelper::reporteHardwarePorGerencia.
        // El mes lo elige el usuario en MesDePago (la comparación es case-insensitive por la
        // collation de MySQL, así que 'AGOSTO' capturado en el modal casa con 'Agosto').
        $presupEquipo = " AND q.Presupuestado = {$valorPresup} ";

        $sqlInversiones = "SELECT
            'INVERSIONES' AS NombreInsumo,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Enero'      THEN q.Precio ELSE 0 END), 0) AS Enero,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Febrero'    THEN q.Precio ELSE 0 END), 0) AS Febrero,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Marzo'      THEN q.Precio ELSE 0 END), 0) AS Marzo,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Abril'      THEN q.Precio ELSE 0 END), 0) AS Abril,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Mayo'       THEN q.Precio ELSE 0 END), 0) AS Mayo,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Junio'      THEN q.Precio ELSE 0 END), 0) + :totalRenovacionFianzas AS Junio,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Julio'      THEN q.Precio ELSE 0 END), 0) AS Julio,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Agosto'     THEN q.Precio ELSE 0 END), 0) AS Agosto,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Septiembre' THEN q.Precio ELSE 0 END), 0) AS Septiembre,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Octubre'    THEN q.Precio ELSE 0 END), 0) AS Octubre,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Noviembre'  THEN q.Precio ELSE 0 END), 0) AS Noviembre,
            IFNULL(SUM(CASE WHEN q.MesDePago = 'Diciembre'  THEN q.Precio ELSE 0 END), 0) AS Diciembre,
            6 as Orden
        FROM
            inventarioequipo q
        INNER JOIN
            empleados e ON q.EmpleadoID = e.EmpleadoID
        INNER JOIN
            puestos p ON e.PuestoID = p.PuestoID
        INNER JOIN
            departamentos d ON p.DepartamentoID = d.DepartamentoID
        INNER JOIN
            gerencia g ON d.GerenciaID = g.GerenciaID
        WHERE
            q.CategoriaEquipo IN ('LAPTOP', 'MONITOR', 'IMPRESORA', 'TABLETA', 'UPS')
            AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . $presupEquipo . "
        HAVING (IFNULL(SUM(q.Precio), 0) + :totalRenovacionFianzas2) > 0";

        $invBindings = array_merge($bindings, [
            'totalRenovacionFianzas' => $totalRenovacionFianzas,
            'totalRenovacionFianzas2' => $totalRenovacionFianzas
        ]);

        $res3 = DB::select($sqlInversiones, $invBindings);
        $reporteTemp = array_merge($reporteTemp, $res3);

        // Query 4: Licencias (Orden 2)
        $sqlLicencias = "SELECT 
        i.NombreInsumo,
        SUM(CASE WHEN i.MesDePago = 'Enero' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro1 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro1 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Enero,
        SUM(CASE WHEN i.MesDePago = 'Febrero' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro2 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro2 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Febrero,
        SUM(CASE WHEN i.MesDePago = 'Marzo' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro3 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro3 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Marzo,
        SUM(CASE WHEN i.MesDePago = 'Abril' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro4 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro4 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Abril,
        SUM(CASE WHEN i.MesDePago = 'Mayo' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro5 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro5 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Mayo,
        SUM(CASE WHEN i.MesDePago = 'Junio' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro6 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro6 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Junio,
        SUM(CASE WHEN i.MesDePago = 'Julio' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro7 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro7 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Julio,
        SUM(CASE WHEN i.MesDePago = 'Agosto' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro8 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro8 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Agosto,
        SUM(CASE WHEN i.MesDePago = 'Septiembre' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro9 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro9 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Septiembre,
        SUM(CASE WHEN i.MesDePago = 'Octubre' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro10 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro10 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Octubre,
        SUM(CASE WHEN i.MesDePago = 'Noviembre' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro11 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro11 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Noviembre,
        SUM(CASE WHEN i.MesDePago = 'Diciembre' AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro12 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro12 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END) AS Diciembre,
        2 AS Orden
    FROM 
        inventarioinsumo i
    INNER JOIN empleados e ON i.EmpleadoID = e.EmpleadoID
    INNER JOIN puestos p ON e.PuestoID = p.PuestoID
    INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
    INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
    WHERE 
        (i.FrecuenciaDePago = 'Anual' OR i.FrecuenciaDePago = 'Pago único')
        AND i.CateogoriaInsumo = 'LICENCIA'
        AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%')
        AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . $presupInsumo . "
    GROUP BY 
        i.NombreInsumo
    HAVING (
        SUM(CASE WHEN i.MesDePago IN ('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre') AND NOT (g.GerenciaID IN (17, 18) AND i.NombreInsumo LIKE 'WINDOWS%') THEN CASE WHEN i.NombreInsumo = 'WINDOWS 10 HOME' THEN :costoWindows10Pro13 WHEN i.NombreInsumo = 'WINDOWS 11 HOME' THEN :costoWindows11Pro13 WHEN i.NombreInsumo IN ('WINDOWS 10 PRO', 'WINDOWS 11 PRO') THEN 0.00 ELSE (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) END ELSE 0 END)
    ) > 0";

        $licBindings = array_merge($bindings, [
            'costoWindows10Pro1' => $costoWindows10Pro,
            'costoWindows11Pro1' => $costoWindows11Pro,
            'costoWindows10Pro2' => $costoWindows10Pro,
            'costoWindows11Pro2' => $costoWindows11Pro,
            'costoWindows10Pro3' => $costoWindows10Pro,
            'costoWindows11Pro3' => $costoWindows11Pro,
            'costoWindows10Pro4' => $costoWindows10Pro,
            'costoWindows11Pro4' => $costoWindows11Pro,
            'costoWindows10Pro5' => $costoWindows10Pro,
            'costoWindows11Pro5' => $costoWindows11Pro,
            'costoWindows10Pro6' => $costoWindows10Pro,
            'costoWindows11Pro6' => $costoWindows11Pro,
            'costoWindows10Pro7' => $costoWindows10Pro,
            'costoWindows11Pro7' => $costoWindows11Pro,
            'costoWindows10Pro8' => $costoWindows10Pro,
            'costoWindows11Pro8' => $costoWindows11Pro,
            'costoWindows10Pro9' => $costoWindows10Pro,
            'costoWindows11Pro9' => $costoWindows11Pro,
            'costoWindows10Pro10' => $costoWindows10Pro,
            'costoWindows11Pro10' => $costoWindows11Pro,
            'costoWindows10Pro11' => $costoWindows10Pro,
            'costoWindows11Pro11' => $costoWindows11Pro,
            'costoWindows10Pro12' => $costoWindows10Pro,
            'costoWindows11Pro12' => $costoWindows11Pro,
            'costoWindows10Pro13' => $costoWindows10Pro,
            'costoWindows11Pro13' => $costoWindows11Pro,
        ]);
        $res4 = DB::select($sqlLicencias, $licBindings);
        $reporteTemp = array_merge($reporteTemp, $res4);

        // Query 5: Otros Insumos (Orden 3)
        $sqlOtrosInsumos = "SELECT 
            NombreInsumo,
            SUM(Enero) AS Enero,
            SUM(Febrero) AS Febrero,
            SUM(Marzo) AS Marzo,
            SUM(Abril) AS Abril,
            SUM(Mayo) AS Mayo,
            SUM(Junio) AS Junio,
            SUM(Julio) AS Julio,
            SUM(Agosto) AS Agosto,
            SUM(Septiembre) AS Septiembre,
            SUM(Octubre) AS Octubre,
            SUM(Noviembre) AS Noviembre,
            SUM(Diciembre) AS Diciembre,
            3 AS Orden
        FROM (
            SELECT 
                CASE 
                    WHEN i.CateogoriaInsumo = 'REPARACIONES' THEN 'ACCESORIOS Y REFACCIONES'
                    ELSE i.NombreInsumo 
                END AS NombreInsumo,
                CASE WHEN i.MesDePago = 'Enero' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Enero,
                CASE WHEN i.MesDePago = 'Febrero' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Febrero,
                CASE WHEN i.MesDePago = 'Marzo' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Marzo,
                CASE WHEN i.MesDePago = 'Abril' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Abril,
                CASE WHEN i.MesDePago = 'Mayo' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Mayo,
                CASE WHEN i.MesDePago = 'Junio' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Junio,
                CASE WHEN i.MesDePago = 'Julio' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Julio,
                CASE WHEN i.MesDePago = 'Agosto' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Agosto,
                CASE WHEN i.MesDePago = 'Septiembre' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Septiembre,
                CASE WHEN i.MesDePago = 'Octubre' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Octubre,
                CASE WHEN i.MesDePago = 'Noviembre' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Noviembre,
                CASE WHEN i.MesDePago = 'Diciembre' THEN (CASE WHEN i.FrecuenciaDePago = 'Pago único' THEN i.CostoMensual ELSE i.CostoAnual END) ELSE 0 END AS Diciembre
            FROM 
                inventarioinsumo i
            INNER JOIN empleados e ON i.EmpleadoID = e.EmpleadoID
            INNER JOIN puestos p ON e.PuestoID = p.PuestoID
            INNER JOIN departamentos d ON p.DepartamentoID = d.DepartamentoID
            INNER JOIN gerencia g ON d.GerenciaID = g.GerenciaID
            WHERE 
                (i.FrecuenciaDePago = 'Anual' OR i.FrecuenciaDePago = 'Pago único')
                AND i.CateogoriaInsumo NOT IN ('LAPTOP', 'MONITOR', 'NO BREAK', 'LICENCIA', 'ACCESORIOS', 'BATERIA UPS', 'IMPRESORA') 
                AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . $presupInsumo . "
        ) as sub
        GROUP BY NombreInsumo
        HAVING (
            SUM(Enero) + SUM(Febrero) + SUM(Marzo) + SUM(Abril) + SUM(Mayo) + SUM(Junio) +
            SUM(Julio) + SUM(Agosto) + SUM(Septiembre) + SUM(Octubre) + SUM(Noviembre) + SUM(Diciembre)
        ) > 0";
        $res5 = DB::select($sqlOtrosInsumos, $bindings);
        $reporteTemp = array_merge($reporteTemp, $res5);

        // Query 6: Mensuales (Orden 1)
        $sqlMensuales = "SELECT 
        i.NombreInsumo,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Enero,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Febrero,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Marzo,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Abril,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Mayo,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Junio,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Julio,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Agosto,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Septiembre,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Octubre,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Noviembre,
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) AS Diciembre,
        1 AS Orden
    FROM 
        inventarioinsumo i
    INNER JOIN 
        empleados e ON i.EmpleadoID = e.EmpleadoID
    INNER JOIN 
        puestos p ON e.PuestoID = p.PuestoID
    INNER JOIN 
        departamentos d ON p.DepartamentoID = d.DepartamentoID
    INNER JOIN 
        gerencia g ON d.GerenciaID = g.GerenciaID
    WHERE 
        i.FrecuenciaDePago = 'Mensual'
        AND i.CateogoriaInsumo IN ('LICENCIA', 'HOSTING', 'STARLINK', 'INTERNET', 'TABLET')  
        AND g.GerenciaID = :gerenciaId" . $tipoPersonaFilter . $presupInsumo . "
    GROUP BY 
        i.NombreInsumo
    HAVING 
        SUM(CASE WHEN i.CateogoriaInsumo IN ('INTERNET', 'STARLINK') THEN i.CostoMensual ELSE i.CostoMensual END) * 12 > 0";
        $res6 = DB::select($sqlMensuales, $bindings);
        $reporteTemp = array_merge($reporteTemp, $res6);

        // Now format Enero to Diciembre as integers/floats without decimals or rounded as MySQL stored procedure does
        foreach ($reporteTemp as $row) {
            $row->Enero = round((float) $row->Enero, 0);
            $row->Febrero = round((float) $row->Febrero, 0);
            $row->Marzo = round((float) $row->Marzo, 0);
            $row->Abril = round((float) $row->Abril, 0);
            $row->Mayo = round((float) $row->Mayo, 0);
            $row->Junio = round((float) $row->Junio, 0);
            $row->Julio = round((float) $row->Julio, 0);
            $row->Agosto = round((float) $row->Agosto, 0);
            $row->Septiembre = round((float) $row->Septiembre, 0);
            $row->Octubre = round((float) $row->Octubre, 0);
            $row->Noviembre = round((float) $row->Noviembre, 0);
            $row->Diciembre = round((float) $row->Diciembre, 0);
        }

        // Sort by Orden
        usort($reporteTemp, function ($a, $b) {
            return $a->Orden <=> $b->Orden;
        });

        return $reporteTemp;
    }
}
