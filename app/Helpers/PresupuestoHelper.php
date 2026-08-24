<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Models\Empleados;
use App\Models\PresupuestoConfiguracion;
use App\Helpers\PagoMeses;

class PresupuestoHelper
{
    private static function costoCeldaInsumo($insumo): float
    {
        return (float) ($insumo->CostoMensual ?? 0);
    }

    private static function sumaEnMes($grupo, string $mes, callable $importe): float
    {
        return (float) $grupo->sum(function ($i) use ($mes, $importe) {
            return PagoMeses::aplica($i->MesDePago ?? '', $mes, $i->FrecuenciaDePago ?? null)
                ? (float) $importe($i)
                : 0.0;
        });
    }

    private static function precioEquipoEnMes($equipo, string $mes, array $meses, callable $mesDeFecha): float
    {
        $precio = (float) ($equipo->Precio ?? 0);
        $raw = trim((string) ($equipo->MesDePago ?? ''));
        $lista = PagoMeses::parse($raw, null);

        if ($lista === []) {
            $numero = $mesDeFecha($equipo->FechaDeCompra);
            $fallback = $numero ? ($meses[$numero - 1] ?? '') : '';

            return strcasecmp($fallback, $mes) === 0 ? $precio : 0.0;
        }

        if (! PagoMeses::aplica($raw, $mes, null)) {
            return 0.0;
        }

        return $precio / count($lista);
    }

    // Diferenciador de reporte de presupuesto e inventario 
    private static function soloPresupuestados($query, string $modo)
    {
        return PresupuestoAsignacion::aplicarWhere($query, $modo);
    }

    // Presupuesto: FÍSICA y EXTRAORDINARIO. Inventario: FÍSICA y REFERENCIADO.
    public static function tiposPersona(string $modo): array
    {
        return $modo === 'presupuesto'
            ? ['FISICA', 'EXTRAORDINARIO']
            : ['FISICA', 'REFERENCIADO'];
    }

    public static function etiquetaSeccion(string $modo): string
    {
        return $modo === 'inventario' ? 'Inventario' : 'Presupuesto';
    }

    public static function leyendaInclusion(string $modo): string
    {
        return $modo === 'presupuesto'
            ? 'Incluye asignaciones Extra y Compartido de empleados tipo FÍSICA y EXTRAORDINARIO.'
            : 'Incluye asignaciones Stock y Compartido de empleados tipo FÍSICA y REFERENCIADO.';
    }

    // Costo de un grupo de insumos según los meses elegidos en la asignación.
    private static function costoGrupoInsumo($grupo, string $tipo): float
    {
        return (float) $grupo->sum(function ($i) use ($tipo) {
            $mensual = (float) ($i->CostoMensual ?? 0);
            if ($tipo === 'mens') {
                return $mensual;
            }

            $n = count(PagoMeses::parse($i->MesDePago ?? '', $i->FrecuenciaDePago ?? null));

            return $mensual * $n;
        });
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
                PresupuestoConfiguracion::aplicarWhereIn($query, 'CateogoriaInsumo', 'otros_insumos');
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventarioinsumo' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'NombreInsumo', 'CateogoriaInsumo', 'CostoMensual', 'CostoAnual', 'FrecuenciaDePago', 'MesDePago');
                    PresupuestoConfiguracion::aplicarWhereIn($query, 'CateogoriaInsumo', 'otros_insumos');
                    self::soloPresupuestados($query, $modo);
                }
            ])
            ->get()
            ->flatMap(function($empleado) use ($tipo) {
                $nombreBase = fn ($i) => strcasecmp($i->NombreInsumo ?? '', 'REPARACIONES') === 0
                    ? 'ACCESORIOS Y REFACCIONES'
                    : (string) $i->NombreInsumo;

                return $empleado->inventarioinsumo
                    ->groupBy(fn ($i) => $i->CateogoriaInsumo . '|' . $nombreBase($i))
                    ->map(function($grupo) use ($empleado, $tipo, $nombreBase) {
                        $insumo = $grupo->first();

                        return (object)[
                            'EmpleadoID' => $empleado->EmpleadoID,
                            'NombreEmpleado' => $empleado->NombreEmpleado,
                            'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                            'NombreInsumo' => $nombreBase($insumo),
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

        return Empleados::query()
            ->when($tiposPersona, fn ($q) => $q->whereIn('tipo_persona', $tiposPersona))
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventarioequipo', function($query) use ($modo) {
                PresupuestoConfiguracion::aplicarWhereIn($query, 'CategoriaEquipo', 'hardware');
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventarioequipo' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'CategoriaEquipo', 'Precio', 'MesDePago');
                    PresupuestoConfiguracion::aplicarWhereIn($query, 'CategoriaEquipo', 'hardware');
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
                PresupuestoConfiguracion::aplicarWhereIn($query, 'CateogoriaInsumo', 'licencias');
                PresupuestoConfiguracion::aplicarWhereNotIn($query, 'NombreInsumo', 'licencias_excluir_nombres');
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventarioinsumo' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'NombreInsumo', 'CostoMensual', 'CostoAnual', 'FrecuenciaDePago', 'MesDePago');
                    PresupuestoConfiguracion::aplicarWhereIn($query, 'CateogoriaInsumo', 'licencias');
                    PresupuestoConfiguracion::aplicarWhereNotIn($query, 'NombreInsumo', 'licencias_excluir_nombres');
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

                return $licencias
                    ->groupBy(fn ($i) => $nombreBase($i))
                    ->map(function($grupo) use ($empleado, $tipo, $nombreBase) {
                        $insumo = $grupo->first();

                        return (object)[
                            'EmpleadoID' => $empleado->EmpleadoID,
                            'NombreEmpleado' => $empleado->NombreEmpleado,
                            'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                            'NombreInsumo' => $nombreBase($insumo),
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
                PresupuestoConfiguracion::aplicarWhereIn($query, 'TipoLinea', 'lineas_datos');
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventariolineas' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'TipoLinea', 'CostoRentaMensual', 'CostoFianza', 'MontoRenovacionFianza');
                    PresupuestoConfiguracion::aplicarWhereIn($query, 'TipoLinea', 'lineas_datos');
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
                PresupuestoConfiguracion::aplicarWhereIn($query, 'TipoLinea', 'lineas_gps');
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventariolineas' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'TipoLinea', 'CostoRentaMensual', 'CostoFianza', 'MontoRenovacionFianza');
                    PresupuestoConfiguracion::aplicarWhereIn($query, 'TipoLinea', 'lineas_gps');
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
                PresupuestoConfiguracion::aplicarWhereIn($query, 'TipoLinea', 'lineas_voz');
                self::soloPresupuestados($query, $modo);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventariolineas' => function($query) use ($modo) {
                    $query->select('InventarioID', 'EmpleadoID', 'TipoLinea', 'CostoRentaMensual', 'CostoFianza', 'MontoRenovacionFianza');
                    PresupuestoConfiguracion::aplicarWhereIn($query, 'TipoLinea', 'lineas_voz');
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
                               'CostoRentaMensual', 'CostoFianza', 'FechaFianza', 'MontoRenovacionFianza', 'MesDePago');
                },
                'inventarioequipo' => function ($q) {
                    $q->select('InventarioID', 'EmpleadoID', 'CategoriaEquipo', 'Precio', 'MesDePago', 'FechaDeCompra');
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

        $enMensuales = fn ($i) => PresupuestoConfiguracion::contiene($i->CateogoriaInsumo, 'insumos_mensuales');
        $enLicencias = fn ($i) => PresupuestoConfiguracion::contiene($i->CateogoriaInsumo, 'licencias');

        // --- ORDEN 1: insumos con meses elegidos (antes "mensuales" por frecuencia) ---
        $todosInsumos
            ->filter($enMensuales)
            ->groupBy('NombreInsumo')
            ->each(function ($grupo, $nombre) use ($meses, $gerenciaId, &$resultado) {
                $costosPorMes = [];
                foreach ($meses as $mes) {
                    $costosPorMes[$mes] = self::sumaEnMes($grupo, $mes, fn ($i) => self::costoCeldaInsumo($i));
                }
                if (array_sum($costosPorMes) <= 0) return;
                foreach ($meses as $mes) {
                    $resultado->push((object)[
                        'NombreInsumo' => $nombre,
                        'Mes'          => $mes,
                        'Costo'        => (int) round($costosPorMes[$mes]),
                        'Orden'        => 1,
                        'GerenciaID'   => $gerenciaId,
                    ]);
                }
            });

        // --- ORDEN 2: licencias (meses que el usuario marcó) ---
        $todosInsumos
            ->filter(fn ($i) =>
                $enLicencias($i) &&
                ! $enMensuales($i) &&
                !($esExentaWindows && str_contains(strtoupper($i->NombreInsumo), 'WINDOWS'))
            )
            ->groupBy('NombreInsumo')
            ->each(function ($grupo, $nombre) use ($meses, $gerenciaId, $costoWin10Pro, $costoWin11Pro, &$resultado) {
                $importe = function ($i) use ($nombre, $costoWin10Pro, $costoWin11Pro) {
                    return match (strtoupper($nombre)) {
                        'WINDOWS 10 HOME'                  => $costoWin10Pro,
                        'WINDOWS 11 HOME'                  => $costoWin11Pro,
                        'WINDOWS 10 PRO', 'WINDOWS 11 PRO' => 0,
                        default                            => self::costoCeldaInsumo($i),
                    };
                };
                $costosPorMes = [];
                foreach ($meses as $mes) {
                    $costosPorMes[$mes] = self::sumaEnMes($grupo, $mes, $importe);
                }
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

        // --- ORDEN 3: resto de insumos ---
        $nombreOtroInsumo = fn ($i) => strcasecmp($i->CateogoriaInsumo ?? '', 'REPARACIONES') === 0
            ? 'ACCESORIOS Y REFACCIONES'
            : $i->NombreInsumo;

        $todosInsumos
            ->filter(fn ($i) =>
                ! $enMensuales($i) &&
                ! $enLicencias($i) &&
                ! PresupuestoConfiguracion::contiene($i->CateogoriaInsumo, 'excluir_otros_anuales')
            )
            ->groupBy(fn ($i) => $nombreOtroInsumo($i))
            ->each(function ($grupo, $nombre) use ($meses, $gerenciaId, &$resultado) {
                $costosPorMes = [];
                foreach ($meses as $mes) {
                    $costosPorMes[$mes] = self::sumaEnMes($grupo, $mes, fn ($i) => self::costoCeldaInsumo($i));
                }
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
            ->filter(fn ($l) =>
                PresupuestoConfiguracion::contiene($l->TipoLinea, 'lineas_voz')
                || PresupuestoConfiguracion::contiene($l->TipoLinea, 'lineas_datos')
                || PresupuestoConfiguracion::contiene($l->TipoLinea, 'lineas_gps')
            )
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

        // --- ORDEN 5: Líneas (renta sólo en los meses marcados) ---
        $todasLineas
            ->groupBy(fn ($l) => strtoupper(trim($l->Compania ?? '')) . '|' . strtoupper(trim($l->TipoLinea ?? '')))
            ->each(function ($grupo, $key) use ($meses, $gerenciaId, &$resultado) {
                [$compania, $tipoLinea] = explode('|', $key, 2);
                $nombre = $compania . ' ' . $tipoLinea;
                $costosPorMes = [];
                foreach ($meses as $mes) {
                    $costosPorMes[$mes] = self::sumaEnMes($grupo, $mes, fn ($l) => (float) ($l->CostoRentaMensual ?? 0));
                }
                if (array_sum($costosPorMes) <= 0) return;
                foreach ($meses as $mes) {
                    $resultado->push((object)[
                        'NombreInsumo' => $nombre,
                        'Mes'          => $mes,
                        'Costo'        => (int) round($costosPorMes[$mes]),
                        'Orden'        => 5,
                        'GerenciaID'   => $gerenciaId,
                    ]);
                }
            });

        // --- ORDEN 6: Inversiones (hardware en los meses marcados + renovación fianzas en Junio) ---
        $equiposHardware = $todosEquipos->filter(fn ($e) =>
            PresupuestoConfiguracion::contiene($e->CategoriaEquipo, 'hardware')
        );

        $mesDeFechaCortes = function ($fecha): ?int {
            if (empty($fecha)) {
                return null;
            }

            return (int) ($fecha instanceof \DateTimeInterface
                ? $fecha->format('n')
                : date('n', strtotime((string) $fecha)));
        };

        $costosTotalesPorMes = [];
        foreach ($meses as $mes) {
            $costo = $equiposHardware->sum(fn ($e) => self::precioEquipoEnMes($e, $mes, $meses, $mesDeFechaCortes));
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
                               'CostoRentaMensual', 'CostoFianza', 'FechaFianza', 'MontoRenovacionFianza', 'MesDePago');
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

        $enMensuales = fn ($i) => PresupuestoConfiguracion::contiene($i->CateogoriaInsumo, 'insumos_mensuales');
        $enLicencias = fn ($i) => PresupuestoConfiguracion::contiene($i->CateogoriaInsumo, 'licencias');

        // --- ORDEN 1: insumos de categorías mensuales, sólo en los meses marcados ---
        $todosInsumos
            ->filter($enMensuales)
            ->groupBy(fn ($i) => $norm($i->NombreInsumo))
            ->sortKeys()
            ->each(function ($grupo) use ($agregar) {
                $agregar((string) $grupo->first()->NombreInsumo, 1, fn ($mes) =>
                    self::sumaEnMes($grupo, $mes, fn ($i) => self::costoCeldaInsumo($i))
                );
            });

        // --- ORDEN 2: licencias. Las HOME se cotizan al precio de su PRO.
        $todosInsumos
            ->filter(fn ($i) =>
                $enLicencias($i) &&
                ! $enMensuales($i) &&
                !($esExentaWindows && str_starts_with($norm($i->NombreInsumo), 'WINDOWS'))
            )
            ->groupBy(fn ($i) => $norm($i->NombreInsumo))
            ->sortKeys()
            ->each(function ($grupo) use ($agregar, $norm, $costoWin10Pro, $costoWin11Pro) {
                $importe = fn ($i) => match ($norm($i->NombreInsumo)) {
                    'WINDOWS 10 HOME'                  => (float) $costoWin10Pro,
                    'WINDOWS 11 HOME'                  => (float) $costoWin11Pro,
                    'WINDOWS 10 PRO', 'WINDOWS 11 PRO' => 0.0,
                    default                            => self::costoCeldaInsumo($i),
                };

                $agregar((string) $grupo->first()->NombreInsumo, 2, fn ($mes) =>
                    self::sumaEnMes($grupo, $mes, $importe)
                );
            });

        // --- ORDEN 3: resto de insumos. Mantenimientos y refacciones.
        $nombreOtroInsumo = fn ($i) => $norm($i->CateogoriaInsumo) === 'REPARACIONES'
            ? 'ACCESORIOS Y REFACCIONES'
            : (string) $i->NombreInsumo;

        $todosInsumos
            ->filter(fn ($i) =>
                ! $enMensuales($i) &&
                ! $enLicencias($i) &&
                ! PresupuestoConfiguracion::contiene($i->CateogoriaInsumo, 'excluir_otros_anuales')
            )
            ->groupBy(fn ($i) => $norm($nombreOtroInsumo($i)))
            ->sortKeys()
            ->each(function ($grupo) use ($agregar, $nombreOtroInsumo) {
                $agregar($nombreOtroInsumo($grupo->first()), 3, fn ($mes) =>
                    self::sumaEnMes($grupo, $mes, fn ($i) => self::costoCeldaInsumo($i))
                );
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
            ->filter(fn ($l) =>
                PresupuestoConfiguracion::contiene($l->TipoLinea, 'lineas_voz')
                || PresupuestoConfiguracion::contiene($l->TipoLinea, 'lineas_datos')
                || PresupuestoConfiguracion::contiene($l->TipoLinea, 'lineas_gps')
            )
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

        // --- ORDEN 5: renta de líneas, sólo en los meses marcados ---
        $todasLineas
            ->groupBy(fn ($l) => $norm($l->Compania . ' ' . $l->TipoLinea))
            ->sortKeys()
            ->each(function ($grupo) use ($agregar) {
                $primera = $grupo->first();
                $agregar($primera->Compania . ' ' . $primera->TipoLinea, 5, fn ($mes) =>
                    self::sumaEnMes($grupo, $mes, fn ($l) => (float) ($l->CostoRentaMensual ?? 0))
                );
            });

        // --- ORDEN 6: hardware en los meses marcados. La renovación de fianzas cae en Junio.
        $equipos = $todosEquipos->filter(fn ($e) =>
            PresupuestoConfiguracion::contiene($e->CategoriaEquipo, 'hardware')
        );

        $totalInversiones = (float) $equipos->sum(fn ($e) => (float) ($e->Precio ?? 0)) + $totalRenovacionFianzas;

        $agregar('INVERSIONES', 6, function ($mes) use ($equipos, $meses, $mesDeFecha, $totalRenovacionFianzas) {
            $costo = $equipos->sum(fn ($e) => self::precioEquipoEnMes($e, $mes, $meses, $mesDeFecha));

            return $mes === 'Junio' ? $costo + $totalRenovacionFianzas : $costo;
        }, $totalInversiones);

        usort($filas, fn ($a, $b) => $a->Orden <=> $b->Orden);

        return $filas;
    }
}
