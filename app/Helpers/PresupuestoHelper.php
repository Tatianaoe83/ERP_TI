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
        $campoCosto = $tipo === 'mens' ? 'CostoMensual' : 'CostoAnual';
        $tiposPersona = $modo === 'presupuesto' ? ['FISICA', 'EXTRAORDINARIO'] : ['FISICA', 'REFERENCIADO'];
        
        return Empleados::query()
            ->whereIn('tipo_persona', $tiposPersona)
            ->whereHas('puestos.departamentos.gerencia', function($query) use ($gerenciaId) {
                $query->where('gerencia.GerenciaID', $gerenciaId);
            })
            // Solo empleados con insumos de hardware
            ->whereHas('inventarioinsumo', function($query) {
                $query->whereIn('CateogoriaInsumo', ['LAPTOP', 'MONITOR', 'NO BREAK', 'STARLINK', 'TABLET', 'IMPRESORA']);
            })
            ->with([
                'puestos:PuestoID,NombrePuesto',
                'inventarioinsumo' => function($query) {
                    $query->select('InventarioID', 'EmpleadoID', 'NombreInsumo', 'CateogoriaInsumo', 'CostoMensual', 'CostoAnual')
                          ->whereIn('CateogoriaInsumo', ['LAPTOP', 'MONITOR', 'NO BREAK', 'STARLINK', 'TABLET', 'IMPRESORA']);
                }
            ])
            ->orderByDesc('NombreEmpleado') // DESC como en el stored procedure
            ->get()
            ->flatMap(function($empleado) use ($campoCosto) {
                return $empleado->inventarioinsumo->map(function($insumo) use ($empleado, $campoCosto) {
                    return (object)[
                        'EmpleadoID' => $empleado->EmpleadoID,
                        'NombreEmpleado' => $empleado->NombreEmpleado,
                        'NombrePuesto' => $empleado->puestos->NombrePuesto ?? null,
                        'NombreInsumo' => $insumo->NombreInsumo,
                        'CateogoriaInsumo' => $insumo->CateogoriaInsumo,
                        'CostoTotal' => (int) round($insumo->$campoCosto ?? 0),
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
}
