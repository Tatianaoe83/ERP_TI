<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Models\Empleados;

class PresupuestoHelper
{
    // Un insumo es de 'Pago único' si su FrecuenciaDePago es exactamente esa cadena
    private static function esPagoUnico($insumo): bool
    {
        return strcasecmp($insumo->FrecuenciaDePago ?? '', 'Pago único') === 0;
    }

    // Un 'Pago único' se cobra una sola vez: su importe está en CostoMensual, no en CostoAnual
    private static function costoAnualInsumo($insumo): float
    {
        return self::esPagoUnico($insumo)
            ? (float) ($insumo->CostoMensual ?? 0)
            : (float) ($insumo->CostoAnual ?? 0);
    }

    // Diferenciador de reporte de presupuesto e inventario 
    private static function soloPresupuestados($query, string $modo)
    {
        return $query->where('Presupuestado', $modo === 'presupuesto' ? 1 : 0);
    }

    // Filtro de tipos de persona para tipo de reporte
    private static function tiposPersona(string $modo): ?array
    {
        return $modo === 'presupuesto' ? ['FISICA', 'EXTRAORDINARIO'] : null;
    }

    // Cuenta cuántas frecuencias distintas tiene cada insumo, para decidir si se añade la frecuencia al nombre
    private static function frecuenciasPorNombre($insumos, callable $nombreBase)
    {
        return $insumos
            ->groupBy(fn ($i) => $nombreBase($i))
            ->map(fn ($grupo) => $grupo
                ->map(fn ($i) => strtoupper(trim((string) ($i->FrecuenciaDePago ?? ''))))
                ->unique()
                ->count());
    }

    // Añade la frecuencia al nombre sólo cuando el mismo insumo aparece con más de un registro
    private static function nombreConFrecuencia(string $nombre, $insumo, $frecuenciasPorNombre): string
    {
        if (($frecuenciasPorNombre[$nombre] ?? 1) <= 1) {
            return $nombre;
        }

        $frecuencia = trim((string) ($insumo->FrecuenciaDePago ?? ''));

        return $frecuencia === '' ? $nombre : $nombre . ' (' . $frecuencia . ')';
    }

    // Costo de un grupo de insumos. Los recurrentes se suman directo; los 'Pago único' se
    // agrupan por MesDePago y en el reporte mensual sólo cuenta el mes más caro.
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

    // Reporte de accesorios y otros insumos
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
                    // Distinta categoría o frecuencia = insumo distinto, va en fila aparte
                    ->groupBy(fn ($i) => $i->CateogoriaInsumo . '|' . $nombreBase($i) . '|' . strtoupper(trim((string) ($i->FrecuenciaDePago ?? ''))))
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

    // Reporte de hardware: sale de inventarioequipo.
    public static function reporteHardwarePorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $tiposPersona = self::tiposPersona($modo);
        $categorias = ['LAPTOP', 'MONITOR', 'IMPRESORA', 'MODEM', 'TABLET', 'ANTENA'];

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
                    $query->select('InventarioID', 'EmpleadoID', 'CategoriaEquipo', 'Precio', 'MesDePago')
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

    // Reporte de licenciamiento.
    public static function reporteLicenciasPorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        // Costos de Windows PRO, se rendondea porque el presupuesto no es exacto y se quiere evitar centavos
        $costoWin10Pro = (int) round(DB::table('inventarioinsumo')
            ->where('NombreInsumo', 'WINDOWS 10 PRO')
            ->max('CostoMensual') ?? 0);

        $costoWin11Pro = (int) round(DB::table('inventarioinsumo')
            ->where('NombreInsumo', 'WINDOWS 11 PRO')
            ->max('CostoMensual') ?? 0);


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
            ->flatMap(function($empleado) use ($tipo, $costoWin10Pro, $costoWin11Pro, $gerenciaId) {
                $licencias = $empleado->inventarioinsumo
                    // Excluir WINDOWS para gerencias 17 y 18
                    ->reject(fn ($insumo) => in_array($gerenciaId, [17, 18])
                        && stripos($insumo->NombreInsumo ?? '', 'WINDOWS') === 0)
                    // Una HOME se presupuesta al precio de su PRO.
                    ->each(function ($insumo) use ($costoWin10Pro, $costoWin11Pro) {
                        $precioPro = match (strtoupper($insumo->NombreInsumo ?? '')) {
                            'WINDOWS 10 HOME' => $costoWin10Pro,
                            'WINDOWS 11 HOME' => $costoWin11Pro,
                            default => null,
                        };

                        if ($precioPro !== null) {
                            $insumo->CostoMensual = $precioPro;
                            $insumo->CostoAnual   = $precioPro * 12;
                        }
                    });

                $nombreBase = fn ($i) => (string) $i->NombreInsumo;
                $frecuencias = self::frecuenciasPorNombre($licencias, $nombreBase);

                return $licencias
                    ->groupBy(fn ($i) => $nombreBase($i) . '|' . strtoupper(trim((string) ($i->FrecuenciaDePago ?? ''))))
                    ->map(function($grupo) use ($empleado, $tipo, $nombreBase, $frecuencias) {
                        $insumo = $grupo->first();

                        return (object)[
                            'EmpleadoID' => $empleado->EmpleadoID,
                            'NombreEmpleado' => $empleado->NombreEmpleado,
                            'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                            'NombreInsumo' => self::nombreConFrecuencia($nombreBase($insumo), $insumo, $frecuencias),
                            'CostoTotal' => (int) round(self::costoGrupoInsumo($grupo, $tipo)),
                        ];
                    })
                    ->values();
            })
            ->values();
    }
    
    // Reporte de líneas de datos.
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
            ->filter(fn($row) => ($tipo === 'mens' ? $row->Datos_Costo_Renta_Mensual : $row->Datos_Costo_Renta_Anual) > 0)
            ->sortBy('NombreEmpleado')
            ->values();
    }

    // Reporte de líneas GPS.
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
            ->filter(fn($row) => ($tipo === 'mens' ? $row->GPS_Costo_Renta_Mensual : $row->GPS_Costo_Renta_Anual) > 0)
            ->sortBy('NombreEmpleado')
            ->values();
    }

    // Reporte de líneas de voz.
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
            ->filter(fn($row) => ($tipo === 'mens' ? $row->Voz_Costo_Renta_Mensual : $row->Voz_Costo_Renta_Anual) > 0)
            ->sortBy('NombreEmpleado')
            ->values();
    }

    // Desglose anual de insumos de una gerencia, agrupado en seis bloques que se muestran en un orden fijo
    public static function obtenerInsumosAnualesPorGerencia(int $gerenciaId): \Illuminate\Support\Collection
    {
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $mesIndice = array_flip($meses);

        $esExentaWindows = in_array($gerenciaId, [17, 18]);

        // 1. Precio de referencia del PRO, con el que se cotizan las HOME. La inflación ya viene
        //    aplicada en el costo capturado, así que aquí no se ajusta nada.
        $costoWin10Pro = $esExentaWindows ? 0 : (int) round(
            (float) (DB::table('inventarioinsumo')->where('NombreInsumo', 'WINDOWS 10 PRO')->max('CostoMensual') ?? 0)
        );
        $costoWin11Pro = $esExentaWindows ? 0 : (int) round(
            (float) (DB::table('inventarioinsumo')->where('NombreInsumo', 'WINDOWS 11 PRO')->max('CostoAnual') ?? 0)
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
                'inventarioequipo' => function ($q) {
                    $q->select('InventarioID', 'EmpleadoID', 'CategoriaEquipo', 'Precio', 'MesDePago');
                },
            ])
            ->get();

        // 3. Total renovación fianzas (se suma a Junio en INVERSIONES)
        $totalRenovacionFianzas = $empleados->sum(
            fn ($e) => $e->inventariolineas->whereNotNull('MontoRenovacionFianza')->sum('MontoRenovacionFianza')
        );

        $todosInsumos = $empleados->flatMap(fn ($e) => $e->inventarioinsumo);
        $todasLineas  = $empleados->flatMap(fn ($e) => $e->inventariolineas);
        $todosEquipos = $empleados->flatMap(fn ($e) => $e->inventarioequipo);

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

        // --- ORDEN 6: Inversiones (hardware + renovación fianzas en Junio) ---
        $equiposHardware = $todosEquipos->filter(fn ($e) =>
            in_array($e->CategoriaEquipo, ['LAPTOP', 'MONITOR', 'IMPRESORA', 'TABLET', 'NO BREAK', 'MODEM', 'ANTENA'])
        );

        // Calcular costos por mes
        $costosTotalesPorMes = [];
        foreach ($meses as $mes) {
            $costo = $equiposHardware->filter(fn ($e) => strcasecmp($e->MesDePago ?? '', $mes) === 0)
                ->sum(fn ($e) => (float) ($e->Precio ?? 0));
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
    
    // Calendario de pagos (12 meses) por gerencia(SOLO PARA REPORTE DE PRESUPUESTOS).
    public static function calendarioPagosPorGerencia($gerenciaId, string $modo = 'presupuesto')
    {
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $tiposPersona    = self::tiposPersona($modo);
        $esExentaWindows = in_array((int) $gerenciaId, [17, 18], true);

        $norm = fn ($valor) => mb_strtoupper(trim((string) ($valor ?? '')), 'UTF-8');

        // Precio de referencia del PRO, con el que se cotizan las HOME.
        $costoWin10Pro = $esExentaWindows ? 0 : (int) round(
            (float) (DB::table('inventarioinsumo')->where('NombreInsumo', 'WINDOWS 10 PRO')->max('CostoMensual') ?? 0)
        );
        $costoWin11Pro = $esExentaWindows ? 0 : (int) round(
            (float) (DB::table('inventarioinsumo')->where('NombreInsumo', 'WINDOWS 11 PRO')->max('CostoMensual') ?? 0)
        );

        // Empleados de la gerencia con su inventario.
        $empleados = Empleados::query()
            ->when($tiposPersona, fn ($q) => $q->whereIn('tipo_persona', $tiposPersona))
            ->whereHas('puestos.departamentos.gerencia', fn ($q) => $q->where('gerencia.GerenciaID', $gerenciaId))
            ->with([
                'inventarioinsumo' => function ($q) use ($modo) {
                    $q->select('InventarioID', 'EmpleadoID', 'NombreInsumo', 'CateogoriaInsumo',
                               'CostoMensual', 'CostoAnual', 'FrecuenciaDePago', 'MesDePago');
                    self::soloPresupuestados($q, $modo);
                },
                'inventariolineas' => function ($q) use ($modo) {
                    $q->select('InventarioID', 'EmpleadoID', 'Compania', 'TipoLinea',
                               'CostoRentaMensual', 'CostoFianza', 'FechaFianza', 'MontoRenovacionFianza');
                    self::soloPresupuestados($q, $modo);
                },
                'inventarioequipo' => function ($q) use ($modo) {
                    $q->select('InventarioID', 'EmpleadoID', 'CategoriaEquipo', 'Precio', 'MesDePago', 'FechaDeCompra');
                    self::soloPresupuestados($q, $modo);
                },
            ])
            ->get();

        $todosInsumos = $empleados->flatMap(fn ($e) => $e->inventarioinsumo);
        $todasLineas  = $empleados->flatMap(fn ($e) => $e->inventariolineas);
        $todosEquipos = $empleados->flatMap(fn ($e) => $e->inventarioequipo);

        // Se suma a Junio dentro de INVERSIONES
        $totalRenovacionFianzas = (float) $todasLineas
            ->whereNotNull('MontoRenovacionFianza')
            ->sum('MontoRenovacionFianza');

        $filas = [];

        $agregar = function (string $nombre, int $orden, callable $porMes, ?float $totalParaFiltro = null) use ($meses, &$filas) {
            $valores = [];
            $total   = 0.0;

            foreach ($meses as $mes) {
                $valores[$mes] = (float) $porMes($mes);
                $total        += $valores[$mes];
            }

            if (($totalParaFiltro ?? $total) <= 0) {
                return;
            }

            $fila = (object) ['NombreInsumo' => $nombre];
            foreach ($meses as $mes) {
                $fila->{$mes} = round($valores[$mes], 0);
            }
            $fila->Orden = $orden;

            $filas[] = $fila;
        };

        $mismoMes = fn ($valor, string $mes) => strcasecmp(trim((string) ($valor ?? '')), $mes) === 0;

        // --- ORDEN 1: insumos mensuales, se pagan los 12 meses ---
        $todosInsumos
            ->filter(fn ($i) =>
                $norm($i->FrecuenciaDePago) === 'MENSUAL' &&
                in_array($norm($i->CateogoriaInsumo), ['LICENCIA', 'HOSTING', 'STARLINK', 'INTERNET'], true)
            )
            ->groupBy(fn ($i) => $norm($i->NombreInsumo))
            ->sortKeys()
            ->each(function ($grupo) use ($agregar) {
                $costo = (float) $grupo->sum('CostoMensual');
                $agregar((string) $grupo->first()->NombreInsumo, 1, fn () => $costo);
            });

        // --- ORDEN 2: licencias anuales. Las HOME se cotizan al precio de su PRO.
        $todosInsumos
            ->filter(fn ($i) =>
                in_array($norm($i->FrecuenciaDePago), ['ANUAL', 'PAGO ÚNICO'], true) &&
                $norm($i->CateogoriaInsumo) === 'LICENCIA' &&
                !($esExentaWindows && str_starts_with($norm($i->NombreInsumo), 'WINDOWS'))
            )
            ->groupBy(fn ($i) => $norm($i->NombreInsumo))
            ->sortKeys()
            ->each(function ($grupo) use ($agregar, $mismoMes, $norm, $costoWin10Pro, $costoWin11Pro) {
                $importe = fn ($i) => match ($norm($i->NombreInsumo)) {
                    'WINDOWS 10 HOME'                  => (float) $costoWin10Pro,
                    'WINDOWS 11 HOME'                  => (float) $costoWin11Pro,
                    'WINDOWS 10 PRO', 'WINDOWS 11 PRO' => 0.0,
                    default                            => self::costoAnualInsumo($i),
                };

                $agregar((string) $grupo->first()->NombreInsumo, 2, fn ($mes) => $grupo
                    ->filter(fn ($i) => $mismoMes($i->MesDePago, $mes))
                    ->sum($importe));
            });

        // --- ORDEN 3: resto de insumos anuales. Mantenimientos y refacciones.
        $nombreOtroInsumo = fn ($i) => $norm($i->CateogoriaInsumo) === 'REPARACIONES'
            ? 'ACCESORIOS Y REFACCIONES'
            : (string) $i->NombreInsumo;

        $todosInsumos
            ->filter(fn ($i) =>
                in_array($norm($i->FrecuenciaDePago), ['ANUAL', 'PAGO ÚNICO'], true) &&
                !in_array($norm($i->CateogoriaInsumo),
                    ['LAPTOP', 'MONITOR', 'NO BREAK', 'LICENCIA', 'ACCESORIOS', 'BATERIA UPS', 'IMPRESORA'], true)
            )
            ->groupBy(fn ($i) => $norm($nombreOtroInsumo($i)))
            ->sortKeys()
            ->each(function ($grupo) use ($agregar, $mismoMes, $nombreOtroInsumo) {
                $agregar($nombreOtroInsumo($grupo->first()), 3, fn ($mes) => $grupo
                    ->filter(fn ($i) => $mismoMes($i->MesDePago, $mes))
                    ->sum(fn ($i) => self::costoAnualInsumo($i)));
            });

        // --- ORDEN 4: fianzas de líneas, cada una en el mes de su FechaFianza ---
        $mesDeFecha = function ($fecha): ?int {
            if (empty($fecha)) {
                return null;
            }

            return (int) ($fecha instanceof \DateTimeInterface
                ? $fecha->format('n')
                : date('n', strtotime((string) $fecha)));
        };

        $todasLineas
            ->filter(fn ($l) => in_array($norm($l->TipoLinea), ['VOZ', 'DATOS', 'GPS'], true))
            ->groupBy(fn ($l) => $norm($l->Compania . ' FIANZA - ' . $l->TipoLinea))
            ->sortKeys()
            ->each(function ($grupo) use ($agregar, $meses, $mesDeFecha) {
                $primera = $grupo->first();
                $nombre  = $primera->Compania . ' FIANZA - ' . $primera->TipoLinea;

                $agregar($nombre, 4, function ($mes) use ($grupo, $meses, $mesDeFecha) {
                    $numeroMes = array_search($mes, $meses, true) + 1;

                    return $grupo
                        ->filter(fn ($l) => $mesDeFecha($l->FechaFianza) === $numeroMes)
                        ->sum(fn ($l) => (float) ($l->CostoFianza ?? 0));
                });
            });

        // --- ORDEN 5: renta de líneas, se paga los 12 meses ---
        $todasLineas
            ->groupBy(fn ($l) => $norm($l->Compania . ' ' . $l->TipoLinea))
            ->sortKeys()
            ->each(function ($grupo) use ($agregar) {
                $primera = $grupo->first();
                $costo   = (float) $grupo->sum('CostoRentaMensual');

                $agregar($primera->Compania . ' ' . $primera->TipoLinea, 5, fn () => $costo);
            });

        // --- ORDEN 6: hardware, cada compra en su MesDePago. La renovación de fianzas cae en Junio.
        $equipos = $todosEquipos->filter(fn ($e) =>
            in_array($norm($e->CategoriaEquipo), ['LAPTOP', 'MONITOR', 'IMPRESORA', 'TABLETA', 'NO BREAK', 'ANTENA', 'MODEM'], true)
        );

        // Casi ningún equipo trae MesDePago; sin mes no cabe en el calendario, así que se cae al
        // mes de su FechaDeCompra. Sólo el mes: el año de compra no se toma en cuenta.
        $mesDelEquipo = function ($e) use ($mesDeFecha, $meses) {
            $mes = trim((string) ($e->MesDePago ?? ''));
            if ($mes !== '') {
                return $mes;
            }

            $numero = $mesDeFecha($e->FechaDeCompra);

            return $numero ? $meses[$numero - 1] : '';
        };

        // El bloque se publica si hay hardware o fianzas, aunque el mes de compra falte
        $totalInversiones = (float) $equipos->sum(fn ($e) => (float) ($e->Precio ?? 0)) + $totalRenovacionFianzas;

        $agregar('INVERSIONES', 6, function ($mes) use ($equipos, $mismoMes, $mesDelEquipo, $totalRenovacionFianzas) {
            $costo = $equipos
                ->filter(fn ($e) => $mismoMes($mesDelEquipo($e), $mes))
                ->sum(fn ($e) => (float) ($e->Precio ?? 0));

            return $mes === 'Junio' ? $costo + $totalRenovacionFianzas : $costo;
        }, $totalInversiones);

        usort($filas, fn ($a, $b) => $a->Orden <=> $b->Orden);

        return $filas;
    }
}
