<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use App\Models\Empleados;

class PresupuestoHelper
{
    // Método para obtener el reporte de accesorios y mantenimientos por gerencia mensual o anual
    public static function reporteAccesoriosYMantenimientos(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $campoCosto = $tipo === 'mens' ? 'CostoMensual' : 'CostoAnual';
        $tiposPersona = $modo === 'presupuesto' ? ['FISICA', 'EXTRAORDINARIO'] : ['FISICA', 'REFERENCIADO'];
        
        return Empleados::query()
            ->whereIn('tipo_persona', $tiposPersona)
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            // Solo empleados con insumos de estas categorías
            ->whereHas('inventarioinsumo', function($query) {
                $query->whereIn('CateogoriaInsumo', ['MANTENIMIENTO', 'REPARACIONES', 'SERVICIO', 'HOSTING']);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventarioinsumo' => function($query) {
                    $query->select('InventarioID', 'EmpleadoID', 'NombreInsumo', 'CateogoriaInsumo', 'CostoMensual', 'CostoAnual')
                          ->whereIn('CateogoriaInsumo', ['MANTENIMIENTO', 'REPARACIONES', 'SERVICIO', 'HOSTING']);
                }
            ])
            ->orderBy('NombreEmpleado')
            ->get()
            ->flatMap(function($empleado) use ($campoCosto) {
                return $empleado->inventarioinsumo->map(function($insumo) use ($empleado, $campoCosto) {
                    return (object)[
                        'EmpleadoID' => $empleado->EmpleadoID,
                        'NombreEmpleado' => $empleado->NombreEmpleado,
                        'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                        'NombreInsumo' => $insumo->NombreInsumo === 'REPARACIONES' 
                            ? 'ACCESORIOS Y REFACCIONES' 
                            : $insumo->NombreInsumo,
                        'CateogoriaInsumo' => $insumo->CateogoriaInsumo,
                        'CostoTotal' => (int) round($insumo->$campoCosto ?? 0),
                    ];
                });
            })
            ->sortBy([
                ['NombreEmpleado', 'asc'],
                ['NombrePuesto', 'asc'],
                ['NombreInsumo', 'asc'],
            ])
            ->values();
    }
    
    // Método para obtener el reporte de hardware por gerencia mensual o anual
    public static function reporteHardwarePorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $tiposPersona = $modo === 'presupuesto' ? ['FISICA', 'EXTRAORDINARIO'] : ['FISICA', 'REFERENCIADO'];
        $categoriasInversiones = ['LAPTOP', 'MONITOR', 'NO BREAK', 'STARLINK', 'TABLET', 'IMPRESORA'];

        return Empleados::query()
            ->whereIn('tipo_persona', $tiposPersona)
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventarioequipo', function($query) use ($categoriasInversiones) {
                $query->whereIn(DB::raw('UPPER(TRIM(CategoriaEquipo))'), $categoriasInversiones);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventarioequipo' => function($query) use ($categoriasInversiones) {
                    $query->select('InventarioID', 'EmpleadoID', 'CategoriaEquipo', 'Precio')
                          ->whereIn(DB::raw('UPPER(TRIM(CategoriaEquipo))'), $categoriasInversiones);
                }
            ])
            ->orderByDesc('NombreEmpleado')
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
            ->values();
    }
    
    // Método para obtener el reporte de licencias por gerencia mensual o anual
    public static function reporteLicenciasPorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        // Obtener costos de Windows PRO
        $costoWin10Pro = DB::table('inventarioinsumo')
            ->where('NombreInsumo', 'WINDOWS 10 PRO')
            ->max('CostoMensual') ?? 0;
            
        $costoWin11Pro = DB::table('inventarioinsumo')
            ->where('NombreInsumo', 'WINDOWS 11 PRO')
            ->max('CostoMensual') ?? 0;
        
        $multiplicador = $tipo === 'mens' ? 1 : 12;
        $campoCosto = $tipo === 'mens' ? 'CostoMensual' : 'CostoAnual';
        $tiposPersona = $modo === 'presupuesto' ? ['FISICA', 'EXTRAORDINARIO'] : ['FISICA', 'REFERENCIADO'];
        
        return Empleados::query()
            ->whereIn('tipo_persona', $tiposPersona)
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventarioinsumo', function($query) {
                $query->where('CateogoriaInsumo', 'LICENCIA')
                      ->whereNotIn('NombreInsumo', ['WINDOWS 10 PRO', 'WINDOWS 11 PRO', 'ERP VSCONTROL TOTAL']);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventarioinsumo' => function($query) {
                    $query->select('InventarioID', 'EmpleadoID', 'NombreInsumo', 'CostoMensual', 'CostoAnual')
                          ->where('CateogoriaInsumo', 'LICENCIA')
                          ->whereNotIn('NombreInsumo', ['WINDOWS 10 PRO', 'WINDOWS 11 PRO', 'ERP VSCONTROL TOTAL']);
                }
            ])
            ->orderByDesc('NombreEmpleado')
            ->get()
            ->flatMap(function($empleado) use ($campoCosto, $costoWin10Pro, $costoWin11Pro, $multiplicador, $gerenciaId) {
                return $empleado->inventarioinsumo
                    ->reject(function($insumo) use ($empleado, $gerenciaId) {
                        // Excluir WINDOWS para gerencias 17 y 18
                        if (in_array($gerenciaId, [17, 18]) && str_starts_with($insumo->NombreInsumo, 'WINDOWS')) {
                            return true;
                        }
                        return false;
                    })
                    ->map(function($insumo) use ($empleado, $campoCosto, $costoWin10Pro, $costoWin11Pro, $multiplicador) {
                        // Calcular costo según el tipo de licencia
                        $costo = match($insumo->NombreInsumo) {
                            'WINDOWS 10 HOME' => $costoWin10Pro * $multiplicador,
                            'WINDOWS 11 HOME' => $costoWin11Pro * $multiplicador,
                            default => $insumo->$campoCosto ?? 0
                        };
                        
                        return (object)[
                            'EmpleadoID' => $empleado->EmpleadoID,
                            'NombreEmpleado' => $empleado->NombreEmpleado,
                            'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                            'NombreInsumo' => $insumo->NombreInsumo,
                            'CostoTotal' => (int) $costo,
                        ];
                    });
            })
            ->values();
    }
    
    // Método para obtener el reporte de líneas de datos por gerencia mensual o anual
    public static function reporteLineasDatosPorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $tiposPersona = $modo === 'presupuesto' ? ['FISICA', 'EXTRAORDINARIO'] : ['FISICA', 'REFERENCIADO'];
        
        return Empleados::query()
            ->whereIn('tipo_persona', $tiposPersona)
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventariolineas', function($query) {
                $query->where('TipoLinea', 'Datos');
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventariolineas' => function($query) {
                    $query->select('InventarioID', 'EmpleadoID', 'TipoLinea', 'CostoRentaMensual', 'CostoFianza', 'MontoRenovacionFianza')
                          ->where('TipoLinea', 'Datos');
                }
            ])
            ->orderBy('NombreEmpleado')
            ->get()
            ->map(function($empleado) use ($tipo) {
                $lineasDatos = $empleado->inventariolineas;
                
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
                } else {
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
                }
            })
            ->filter(fn($row) => $row->Total > 0)
            ->values();
    }
    
    // Método para obtener el reporte de líneas GPS por gerencia mensual o anual
    public static function reporteLineasGPSPorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $tiposPersona = $modo === 'presupuesto' ? ['FISICA', 'EXTRAORDINARIO'] : ['FISICA', 'REFERENCIADO'];
        
        return Empleados::query()
            ->whereIn('tipo_persona', $tiposPersona)
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventariolineas', function($query) {
                $query->where('TipoLinea', 'GPS');
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventariolineas' => function($query) {
                    $query->select('InventarioID', 'EmpleadoID', 'TipoLinea', 'CostoRentaMensual', 'CostoFianza', 'MontoRenovacionFianza')
                          ->where('TipoLinea', 'GPS');
                }
            ])
            ->orderBy('NombreEmpleado')
            ->get()
            ->map(function($empleado) use ($tipo) {
                $lineasGPS = $empleado->inventariolineas;
                
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
                } else {
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
                }
            })
            ->filter(fn($row) => $row->Total > 0)
            ->values();
    }
    
    // Método para obtener el reporte de líneas de voz por gerencia mensual o anual
    public static function reporteLineasVozPorGerencia(int $gerenciaId, string $tipo = 'mens', string $modo = 'presupuesto')
    {
        $tiposPersona = $modo === 'presupuesto' ? ['FISICA', 'EXTRAORDINARIO'] : ['FISICA', 'REFERENCIADO'];
        
        return Empleados::query()
            ->whereIn('tipo_persona', $tiposPersona)
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            ->whereHas('inventariolineas', function($query) {
                $query->where('TipoLinea', 'Voz');
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventariolineas' => function($query) {
                    $query->select('InventarioID', 'EmpleadoID', 'TipoLinea', 'CostoRentaMensual', 'CostoFianza', 'MontoRenovacionFianza')
                          ->where('TipoLinea', 'Voz');
                }
            ])
            ->orderBy('NombreEmpleado')
            ->get()
            ->map(function($empleado) use ($tipo) {
                $lineasVoz = $empleado->inventariolineas;
                
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
                } else {
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
                }
            })
            ->filter(fn($row) => $row->Total > 0)
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
                foreach ($meses as $mes) {
                    $costo = $grupo->filter(fn ($i) => strcasecmp($i->MesDePago ?? '', $mes) === 0)
                        ->sum(function ($i) use ($nombre, $costoWin10Pro, $costoWin11Pro) {
                            return match ($nombre) {
                                'WINDOWS 10 HOME'                   => $costoWin10Pro,
                                'WINDOWS 11 HOME'                   => $costoWin11Pro,
                                'WINDOWS 10 PRO', 'WINDOWS 11 PRO'  => 0,
                                default                             => $i->CostoAnual ?? 0,
                            };
                        });
                    $resultado->push((object)[
                        'NombreInsumo' => $nombre,
                        'Mes'          => $mes,
                        'Costo'        => (int) round($costo),
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
            ->groupBy(fn ($i) => $i->CateogoriaInsumo === 'REPARACIONES' ? 'ACCESORIOS Y REFACCIONES' : $i->NombreInsumo)
            ->each(function ($grupo, $nombre) use ($meses, $gerenciaId, &$resultado) {
                foreach ($meses as $mes) {
                    $costo = $grupo->filter(fn ($i) => strcasecmp($i->MesDePago ?? '', $mes) === 0)->sum('CostoAnual');
                    $resultado->push((object)[
                        'NombreInsumo' => $nombre,
                        'Mes'          => $mes,
                        'Costo'        => (int) round($costo),
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
                foreach ($meses as $numMes => $mes) {
                    $mesNum = $numMes + 1;
                    $costo = $grupo
                        ->filter(fn ($l) => $l->FechaFianza && $l->FechaFianza->month === $mesNum)
                        ->sum('CostoFianza');
                    $resultado->push((object)[
                        'NombreInsumo' => $nombre,
                        'Mes'          => $mes,
                        'Costo'        => (int) round($costo),
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
            $costo = $insumosHardware->filter(fn ($i) => strcasecmp($i->MesDePago ?? '', $mes) === 0)->sum('CostoAnual');
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

        // Ordenar: NombreInsumo (A-Z) → número de mes
        return $resultado->sortBy([
            fn ($a, $b) => strcmp($a->NombreInsumo, $b->NombreInsumo),
            fn ($a, $b) => $mesIndice[$a->Mes] <=> $mesIndice[$b->Mes],
        ])->values();
    }
}
