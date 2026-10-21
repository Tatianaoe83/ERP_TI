<?php

namespace App\Services;

use App\Models\ActualizacionCapitalHumano;
use App\Models\CentroCosto;
use App\Models\Empleados;
use App\Models\Obras;
use App\Models\Puestos;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SincronizacionCapitalHumano
{
    public function traer(): array
    {
        try {
            DB::connection('capital_humano')->getPdo();
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo leer la base de Capital Humano.');
        }

        $empleados = $this->indiceEmpleados();
        $puestos = $this->indicePuestos();
        $obras = $this->indiceObras();
        $centros = $this->indiceCentros();

        $resultado = ['bajas' => 0, 'cambios' => 0, 'reingresos' => 0, 'altas' => 0, 'sin_empleado' => 0];

        $this->sincronizarPlantilla($empleados, $puestos, $obras, $centros, $resultado);
        $this->traerAltasBajas($empleados, $puestos, $obras, $centros, $resultado);
        $this->traerCambiosDePuesto($empleados, $puestos, $obras, $centros, $resultado);
        $resultado['bajas_auto'] = $this->cerrarBajasSinInventario();
        $resultado['bajas'] = ActualizacionCapitalHumano::query()
            ->where('tipo', 'BAJA')
            ->where('estado', 'pendiente_inventario')
            ->count();

        return $resultado;
    }

    private function traerAltasBajas(array $empleados, array &$puestos, array &$obras, array &$centros, array &$resultado): void
    {
        $filas = DB::connection('capital_humano')->select("
            SELECT m.id, m.empleado_id, m.tipo, m.fecha, m.motivo,
                   e.apellido_paterno, e.apellido_materno, e.nombre,
                   e.puesto_id, e.ubicacion_id, e.centro_costo_id, e.jefe_inmediato_id,
                   e.unidad_negocio_id, e.telefono, e.correo_institucional, e.activo
            FROM empleado_movimientos_alta_baja m
            INNER JOIN empleados e ON e.id = m.empleado_id
            ORDER BY m.id
        ");

        foreach ($filas as $fila) {
            $registro = $this->registro('alta_baja', $fila->id);
            $nombre = $this->nombreCompleto($fila);
            $empleado = $empleados[$this->clave($nombre)] ?? null;

            if ($fila->tipo === 'BAJA') {
                if ($registro && $registro->estado === 'aplicado') {
                    continue;
                }
                $estado = $empleado ? 'pendiente_inventario' : 'sin_empleado';
                $resumen = 'Se dio de baja. Se debe traspasar el inventario.';
                if ($registro && $registro->estado === $estado && $registro->resumen === $resumen) {
                    continue;
                }
                $this->guardarRegistro($registro, [
                    'origen' => 'alta_baja',
                    'origen_id' => $fila->id,
                    'tipo' => 'BAJA',
                    'empleado_ti_id' => $empleado->EmpleadoID ?? null,
                    'nombre' => $nombre,
                    'fecha' => $fila->fecha,
                    'resumen' => $resumen,
                    'estado' => $estado,
                ]);
                $empleado ? $resultado['bajas']++ : $resultado['sin_empleado']++;
                continue;
            }

            if ($fila->tipo === 'REINGRESO') {
                if ($registro) {
                    continue;
                }
                if ($empleado) {
                    $this->aplicarUbicacionActual($empleado, $fila, $puestos, $obras, $centros, $empleados);
                    $empleado->Estado = 1;
                    $empleado->save();
                    $this->registrar([
                        'origen' => 'alta_baja',
                        'origen_id' => $fila->id,
                        'tipo' => 'REINGRESO',
                        'empleado_ti_id' => $empleado->EmpleadoID,
                        'nombre' => $nombre,
                        'fecha' => $fila->fecha,
                        'resumen' => 'Reingreso. Se actualizó el empleado con su puesto, ubicación y centro de costos actuales.',
                        'estado' => 'aplicado',
                    ]);
                    $resultado['reingresos']++;
                    continue;
                }
            }

            if ($registro && $registro->estado !== 'sin_empleado') {
                continue;
            }

            if (!$empleado && $fila->tipo === 'ALTA') {
                $empleado = $this->crearDesdeCapitalHumano($fila, $puestos, $obras, $centros, $empleados);
            }

            $this->guardarRegistro($registro, [
                'origen' => 'alta_baja',
                'origen_id' => $fila->id,
                'tipo' => $fila->tipo,
                'empleado_ti_id' => $empleado->EmpleadoID ?? null,
                'nombre' => $nombre,
                'fecha' => $fila->fecha,
                'resumen' => $empleado
                    ? 'Se agregó. Aún no tiene inventario. TI hace la asignación.'
                    : 'Esta persona no está en TI.',
                'estado' => $empleado ? 'aplicado' : 'sin_empleado',
            ]);
            if ($empleado && $fila->tipo === 'ALTA') {
                $resultado['altas']++;
            } elseif (!$empleado) {
                $resultado['sin_empleado']++;
            }
        }
    }

    private function traerCambiosDePuesto(array $empleados, array &$puestos, array &$obras, array &$centros, array &$resultado): void
    {
        $filas = DB::connection('capital_humano')->select("
            SELECT m.id, m.fecha_movimiento, m.puesto_id, m.puesto_anterior_id,
                   m.ubicacion_id, m.ubicacion_anterior_id, m.centro_costo_id, m.centro_costo_anterior_id,
                   e.apellido_paterno, e.apellido_materno, e.nombre, e.jefe_inmediato_id,
                   p.nombre AS puesto, a.nombre AS area,
                   pa.nombre AS puesto_anterior, aa.nombre AS area_anterior,
                   u.nombre AS ubicacion, ua.nombre AS ubicacion_anterior,
                   c.nombre AS centro, ca.nombre AS centro_anterior
            FROM empleado_movimientos_puesto m
            INNER JOIN empleados e ON e.id = m.empleado_id
            LEFT JOIN puestos p ON p.id = m.puesto_id
            LEFT JOIN areas a ON a.id = p.area_id
            LEFT JOIN puestos pa ON pa.id = m.puesto_anterior_id
            LEFT JOIN areas aa ON aa.id = pa.area_id
            LEFT JOIN ubicaciones u ON u.id = m.ubicacion_id
            LEFT JOIN ubicaciones ua ON ua.id = m.ubicacion_anterior_id
            LEFT JOIN centros_costos c ON c.id = m.centro_costo_id
            LEFT JOIN centros_costos ca ON ca.id = m.centro_costo_anterior_id
            ORDER BY m.id
        ");

        foreach ($filas as $fila) {
            if ($this->yaExiste('puesto', $fila->id)) {
                continue;
            }

            $nombre = $this->nombreCompleto($fila);
            $empleado = $empleados[$this->clave($nombre)] ?? null;

            if (!$empleado) {
                $this->registrar([
                    'origen' => 'puesto',
                    'origen_id' => $fila->id,
                    'tipo' => 'CAMBIO',
                    'nombre' => $nombre,
                    'fecha' => $fila->fecha_movimiento,
                    'resumen' => 'Cambio en Capital Humano sin empleado correspondiente en TI.',
                    'estado' => 'sin_empleado',
                ]);
                $resultado['sin_empleado']++;
                continue;
            }

            $notas = $this->aplicarCambio($empleado, $fila, $puestos, $obras, $centros, $empleados);
            $this->registrar([
                'origen' => 'puesto',
                'origen_id' => $fila->id,
                'tipo' => 'CAMBIO',
                'empleado_ti_id' => $empleado->EmpleadoID,
                'nombre' => $nombre,
                'fecha' => $fila->fecha_movimiento,
                'resumen' => $notas === [] ? 'El empleado ya estaba en ese lugar.' : implode(' ', $notas),
                'estado' => $this->estadoDelCambio($notas),
            ]);
            $resultado['cambios']++;
        }
    }

    private function aplicarUbicacionActual($empleado, $fila, array &$puestos, array &$obras, array &$centros, array $empleados): void
    {
    }

    private function aplicarCambio($empleado, $fila, array &$puestos, array &$obras, array &$centros, array $empleados): array
    {
        return [];
    }

    private function sincronizarPlantilla(array &$empleados, array &$puestos, array &$obras, array &$centros, array &$resultado): void
    {
        $filas = DB::connection('capital_humano')->select("
            SELECT id, apellido_paterno, apellido_materno, nombre,
                   puesto_id, ubicacion_id, centro_costo_id, jefe_inmediato_id,
                   unidad_negocio_id, telefono, correo_institucional, activo, fecha_baja
            FROM empleados
            ORDER BY id
        ");

        foreach ($filas as $fila) {
            $nombre = $this->nombreCompleto($fila);
            if ($nombre === '') {
                continue;
            }

            $clave = $this->clave($nombre);
            $empleado = $empleados[$clave] ?? null;
            $activo = (int) $fila->activo === 1;
            $creado = $empleado === null;

            if (!$empleado) {
                $empleado = $this->crearDesdeCapitalHumano($fila, $puestos, $obras, $centros, $empleados);
                $empleados[$clave] = $empleado;
            } elseif ($activo) {
                $this->aplicarUbicacionActual($empleado, $fila, $puestos, $obras, $centros, $empleados);
                if (!(int) $empleado->Estado) {
                    $empleado->Estado = 1;
                    $empleado->save();
                    $resultado['reingresos']++;
                }
            }

            $registro = $this->registro('empleado', $fila->id);

            if ($activo) {
                if ($registro && $registro->tipo === 'BAJA' && $registro->estado === 'pendiente_inventario') {
                    $registro->estado = 'aplicado';
                    $registro->resumen = 'Reingresó en Capital Humano. Sigue en TI para el inventario.';
                    $registro->save();
                }
                if (!$creado || $registro) {
                    continue;
                }
                $this->registrar([
                    'origen' => 'empleado',
                    'origen_id' => $fila->id,
                    'tipo' => 'ALTA',
                    'empleado_ti_id' => $empleado->EmpleadoID,
                    'nombre' => $empleado->NombreEmpleado,
                    'fecha' => now()->toDateString(),
                    'resumen' => 'Se agregó. Aún no tiene inventario. TI hace la asignación.',
                    'estado' => 'aplicado',
                ]);
                $resultado['altas']++;
                continue;
            }

            $resumen = 'Se dio de baja. Se debe traspasar el inventario.';
            if ($registro && $registro->tipo === 'BAJA' && $registro->estado === 'pendiente_inventario' && $registro->resumen === $resumen) {
                continue;
            }
            if ($registro && $registro->estado === 'aplicado' && $registro->tipo === 'BAJA') {
                continue;
            }

            $this->guardarRegistro($registro, [
                'origen' => 'empleado',
                'origen_id' => $fila->id,
                'tipo' => 'BAJA',
                'empleado_ti_id' => $empleado->EmpleadoID,
                'nombre' => $empleado->NombreEmpleado,
                'fecha' => $fila->fecha_baja ?: now()->toDateString(),
                'resumen' => $resumen,
                'estado' => 'pendiente_inventario',
            ]);
            $resultado['bajas']++;
        }
    }

    private function cerrarBajasSinInventario(): int
    {
        $cerradas = 0;
        $pendientes = ActualizacionCapitalHumano::query()
            ->with('empleado')
            ->where('tipo', 'BAJA')
            ->where('estado', 'pendiente_inventario')
            ->get();

        foreach ($pendientes as $registro) {
            $empleado = $registro->empleado;
            if (!$empleado) {
                continue;
            }

            $piezas = $empleado->inventarioequipo()->count()
                + $empleado->inventarioinsumo()->count()
                + $empleado->inventariolineas()->count();

            if ($piezas > 0) {
                continue;
            }

            $empleado->Estado = 0;
            $empleado->save();
            $registro->estado = 'aplicado';
            $registro->resumen = 'Baja registrada en automático. No tenía inventario.';
            $registro->save();
            $cerradas++;
        }

        return $cerradas;
    }

    private function estadoDelCambio(array $notas): string
    {
        foreach ($notas as $nota) {
            if (strpos($nota, 'No hay') === 0) {
                return 'sin_equivalente';
            }
        }

        return 'aplicado';
    }

    private function nombreDeJefe(int $id): ?string
    {
        $jefe = DB::connection('capital_humano')->selectOne(
            'SELECT apellido_paterno, apellido_materno, nombre FROM empleados WHERE id = ?',
            [$id]
        );

        return $jefe ? $this->nombreCompleto($jefe) : null;
    }

    private function buscarPuesto(array $puestos, string $nombre, string $area): ?int
    {
        $conArea = $this->clave($nombre.'|'.$area);
        if (isset($puestos['area'][$conArea])) {
            return $puestos['area'][$conArea];
        }

        $solo = $this->clave($nombre);

        return $puestos['unico'][$solo] ?? null;
    }

    private function buscarOCrearCentro(array &$centros, string $nombre): ?int
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return null;
        }

        $clave = $this->clave($nombre);
        if (isset($centros[$clave])) {
            return $centros[$clave];
        }

        $centro = CentroCosto::create(['NombreCentro' => mb_strtoupper($nombre, 'UTF-8')]);
        $centros[$clave] = $centro->CentroCostoID;

        return $centro->CentroCostoID;
    }

    private function indiceEmpleados(): array
    {
        $indice = [];
        foreach (Empleados::query()->orderByDesc('Estado')->orderBy('EmpleadoID')->get() as $empleado) {
            $clave = $this->clave($empleado->NombreEmpleado);
            if (!isset($indice[$clave])) {
                $indice[$clave] = $empleado;
            }
        }

        return $indice;
    }

    private function indicePuestos(): array
    {
        $area = [];
        $conteo = [];
        $ids = [];

        $puestos = Puestos::query()->with('departamentos')->get();
        foreach ($puestos as $puesto) {
            $nombre = $this->clave($puesto->NombrePuesto);
            $conteo[$nombre] = ($conteo[$nombre] ?? 0) + 1;
            $ids[$nombre] = $puesto->PuestoID;
            $nombreArea = $puesto->departamentos->NombreDepartamento ?? '';
            $area[$this->clave($puesto->NombrePuesto.'|'.$nombreArea)] = $puesto->PuestoID;
        }

        $unico = [];
        foreach ($conteo as $nombre => $cantidad) {
            if ($cantidad === 1) {
                $unico[$nombre] = $ids[$nombre];
            }
        }

        return ['area' => $area, 'unico' => $unico];
    }

    private function indiceObras(): array
    {
        $indice = [];
        foreach (Obras::query()->get(['ObraID', 'NombreObra']) as $obra) {
            $indice[$this->clave($obra->NombreObra)] = $obra->ObraID;
        }

        return $indice;
    }

    private function indiceCentros(): array
    {
        $indice = [];
        foreach (CentroCosto::query()->get(['CentroCostoID', 'NombreCentro']) as $centro) {
            $indice[$this->clave($centro->NombreCentro)] = $centro->CentroCostoID;
        }

        return $indice;
    }

    private function crearDesdeCapitalHumano($fila, array &$puestos, array &$obras, array &$centros, array &$empleados): Empleados
    {
        $empleado = new Empleados();
        $empleado->NombreEmpleado = mb_substr(mb_strtoupper($this->nombreCompleto($fila), 'UTF-8'), 0, 100);
        $empleado->tipo_persona = 'FISICA';
        $empleado->Estado = 1;
        $empleado->NumTelefono = $this->telefono($fila->telefono ?? null);
        $empleado->Correo = $this->correoDisponible($fila->correo_institucional ?? null);

        $empleado->save();
        $empleados[$this->clave($empleado->NombreEmpleado)] = $empleado;

        return $empleado;
    }

    private function telefono(?string $telefono): ?string
    {
        $telefono = trim((string) $telefono);

        return $telefono === '' ? null : mb_substr($telefono, 0, 50);
    }

    private function correoDisponible(?string $correo): ?string
    {
        $correo = trim((string) $correo);
        if ($correo === '' || strtoupper(preg_replace('/[\s\/.]+/', '', $correo)) === 'NA') {
            return null;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $ocupado = Empleados::query()
            ->where('Correo', $correo)
            ->where('Estado', 1)
            ->exists();

        return $ocupado ? null : mb_substr($correo, 0, 150);
    }

    private function registro(string $origen, $origenId): ?ActualizacionCapitalHumano
    {
        return ActualizacionCapitalHumano::query()
            ->where('origen', $origen)
            ->where('origen_id', $origenId)
            ->first();
    }

    private function yaExiste(string $origen, $origenId): bool
    {
        return $this->registro($origen, $origenId) !== null;
    }

    private function guardarRegistro(?ActualizacionCapitalHumano $registro, array $datos): void
    {
        if ($registro) {
            $registro->fill($datos)->save();

            return;
        }

        $this->registrar($datos);
    }

    private function registrar(array $datos): void
    {
        ActualizacionCapitalHumano::create($datos);
    }

    private function nombreCompleto($fila): string
    {
        return trim(implode(' ', array_filter([
            $fila->apellido_paterno ?? '',
            $fila->apellido_materno ?? '',
            $fila->nombre ?? '',
        ])));
    }

    private function clave(string $texto): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', $texto));
        $texto = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        return mb_strtoupper($texto, 'UTF-8');
    }
}
