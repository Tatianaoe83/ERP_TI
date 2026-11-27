<?php

namespace App\Services;

use App\Models\ActualizacionCapitalHumano;
use App\Models\CentroCosto;
use App\Models\Departamentos;
use App\Models\Direccion;
use App\Models\Empleados;
use App\Models\Gerencia;
use App\Models\Obras;
use App\Models\Puestos;
use App\Models\UnidadesDeNegocio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SincronizacionCapitalHumano
{
    private $departamentos = null;

    private $motivoAlta = null;

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

        $resultado = ['bajas' => 0, 'cambios' => 0, 'reingresos' => 0, 'altas' => 0, 'sin_empleado' => 0, 'omitidos' => 0];

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
                    : ($this->motivoAlta ?: 'Esta persona no está en TI.'),
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
                if (!$empleado) {
                    $resultado['omitidos']++;
                    continue;
                }
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

    private function asegurarPuesto(array &$puestos, $fila): array
    {
        $this->completarEstructura($fila);
        $nombre = trim((string) ($fila->puesto ?? ''));
        $area = trim((string) ($fila->area ?? ''));
        $existente = $this->buscarPuesto($puestos, $nombre, $area);
        if ($existente) {
            return ['id' => $existente, 'creado' => false];
        }
        if ($nombre === '') {
            return ['id' => null, 'creado' => false];
        }

        $departamentoId = $area !== '' ? $this->asegurarDepartamento($area, $fila) : null;
        $puesto = Puestos::create([
            'NombrePuesto' => mb_substr(mb_strtoupper($nombre, 'UTF-8'), 0, 75),
            'DepartamentoID' => $departamentoId,
        ]);
        $clave = $this->clave($puesto->NombrePuesto);
        if ($area !== '') {
            $puestos['area'][$this->clave($puesto->NombrePuesto.'|'.$area)] = $puesto->PuestoID;
        }
        if (!isset($puestos['unico'][$clave])) {
            $puestos['unico'][$clave] = $puesto->PuestoID;
        }

        return ['id' => $puesto->PuestoID, 'creado' => true];
    }

    private function completarEstructura($fila): void
    {
        if (!empty($fila->estructura_lista) || empty($fila->puesto_id)) {
            return;
        }

        $fila->estructura_lista = true;
        $unidadId = !empty($fila->unidad_negocio_id) ? (int) $fila->unidad_negocio_id : null;
        $estructura = DB::connection('capital_humano')->selectOne("
            SELECT p.nombre AS puesto,
                   a.nombre AS area,
                   g.nombre AS gerencia,
                   d.nombre AS direccion,
                   u.nombre AS unidad
            FROM puestos p
            LEFT JOIN areas a ON a.id = p.area_id
            LEFT JOIN gerencias g ON g.id = a.gerencia_id
            LEFT JOIN direcciones d ON d.id = COALESCE(a.direccion_id, p.direccion_id)
            LEFT JOIN unidades_negocio u ON u.id = COALESCE(?, (
                SELECT pun.unidad_negocio_id
                FROM puesto_unidad_negocio pun
                WHERE pun.puesto_id = p.id
                LIMIT 1
            ))
            WHERE p.id = ?
        ", [$unidadId, (int) $fila->puesto_id]);

        if (!$estructura) {
            return;
        }

        if (empty($fila->puesto)) {
            $fila->puesto = $estructura->puesto;
        }
        if (empty($fila->area)) {
            $fila->area = $estructura->area;
        }
        $fila->gerencia = $estructura->gerencia ?: null;
        $fila->direccion = $estructura->direccion ?: null;
        $fila->unidad = $estructura->unidad ?: ($fila->unidad ?? null);
    }

    private function asegurarDepartamento(string $nombreArea, $fila): ?int
    {
        $clave = $this->clave($nombreArea);
        if ($clave === '') {
            return null;
        }

        foreach ($this->indiceDepartamentos() as $id => $nombre) {
            if ($this->clave($nombre) === $clave) {
                return (int) $id;
            }
        }

        $gerenciaId = $this->asegurarGerencia($fila);
        if (!$gerenciaId) {
            return null;
        }

        $departamento = Departamentos::create([
            'NombreDepartamento' => mb_substr(mb_strtoupper($nombreArea, 'UTF-8'), 0, 50),
            'GerenciaID' => $gerenciaId,
        ]);
        $this->departamentos[$departamento->DepartamentoID] = $departamento->NombreDepartamento;

        return (int) $departamento->DepartamentoID;
    }

    private function indiceDepartamentos(): array
    {
        if ($this->departamentos === null) {
            $this->departamentos = [];
            foreach (Departamentos::query()->get(['DepartamentoID', 'NombreDepartamento']) as $departamento) {
                $this->departamentos[$departamento->DepartamentoID] = $departamento->NombreDepartamento;
            }
        }

        return $this->departamentos;
    }

    private function asegurarGerencia($fila): ?int
    {
        $nombre = trim((string) ($fila->gerencia ?: ($fila->direccion ?? '')));
        if ($nombre === '') {
            return null;
        }

        $clave = $this->clave($nombre);
        foreach (Gerencia::query()->get(['GerenciaID', 'NombreGerencia']) as $gerencia) {
            $actual = $this->clave($gerencia->NombreGerencia);
            if ($actual === $clave || $actual === 'DIRECCION '.$clave || $this->terminaCon($actual, $clave)) {
                return (int) $gerencia->GerenciaID;
            }
        }

        $unidadId = $this->buscarUnidad($fila->unidad ?? null);
        if (!$unidadId) {
            return null;
        }

        $direccionId = Direccion::query()->where('UnidadNegocioID', $unidadId)->value('DireccionID');
        if (!$direccionId) {
            return null;
        }

        $gerencia = Gerencia::create([
            'NombreGerencia' => mb_substr(mb_strtoupper($nombre, 'UTF-8'), 0, 100),
            'UnidadNegocioID' => $unidadId,
            'DireccionID' => $direccionId,
            'estado' => 1,
        ]);

        return (int) $gerencia->GerenciaID;
    }

    private function terminaCon(string $texto, string $sufijo): bool
    {
        $marca = ' '.$sufijo;

        return strlen($texto) > strlen($marca) && substr($texto, -strlen($marca)) === $marca;
    }

    private function buscarUnidad(?string $nombre): ?int
    {
        $clave = $this->clave((string) $nombre);
        if ($clave === '') {
            return null;
        }

        $mejor = null;
        $largo = PHP_INT_MAX;
        foreach (UnidadesDeNegocio::query()->get(['UnidadNegocioID', 'NombreEmpresa']) as $unidad) {
            $actual = $this->clave($unidad->NombreEmpresa);
            if ($actual === $clave) {
                return (int) $unidad->UnidadNegocioID;
            }
            if (strpos($actual, $clave.' ') === 0 && strlen($actual) < $largo) {
                $mejor = (int) $unidad->UnidadNegocioID;
                $largo = strlen($actual);
            }
        }

        return $mejor;
    }

    private function asegurarObra(array &$obras, ?string $nombre, ?string $unidadNombre): array
    {
        $nombre = trim((string) $nombre);
        if ($nombre === '') {
            return ['id' => null, 'creado' => false];
        }

        $clave = $this->clave($nombre);
        if (isset($obras[$clave])) {
            return ['id' => $obras[$clave], 'creado' => false];
        }

        $mejor = null;
        $largo = PHP_INT_MAX;
        foreach ($obras as $obraClave => $id) {
            if (strpos($obraClave, $clave.' ') === 0 && strlen($obraClave) < $largo) {
                $mejor = $id;
                $largo = strlen($obraClave);
            }
        }
        if ($mejor) {
            $obras[$clave] = $mejor;

            return ['id' => $mejor, 'creado' => false];
        }

        $unidadId = $this->buscarUnidad($unidadNombre);
        if (!$unidadId) {
            return ['id' => null, 'creado' => false];
        }

        $obra = Obras::create([
            'NombreObra' => mb_substr(mb_strtoupper($nombre, 'UTF-8'), 0, 100),
            'Direccion' => 'POR DEFINIR',
            'EncargadoDeObra' => 'POR DEFINIR',
            'UnidadNegocioID' => $unidadId,
            'estado' => 1,
        ]);
        $obras[$clave] = $obra->ObraID;

        return ['id' => $obra->ObraID, 'creado' => true];
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

    private function crearDesdeCapitalHumano($fila, array &$puestos, array &$obras, array &$centros, array &$empleados): ?Empleados
    {
        $this->motivoAlta = null;
        $nombre = $this->nombreCompleto($fila);

        try {
            $puesto = !empty($fila->puesto_id) ? $this->asegurarPuesto($puestos, $fila) : ['id' => null];
            if (empty($fila->ubicacion) && !empty($fila->ubicacion_id)) {
                $fila->ubicacion = DB::connection('capital_humano')->table('ubicaciones')->where('id', $fila->ubicacion_id)->value('nombre');
            }
            $obra = !empty($fila->ubicacion)
                ? $this->asegurarObra($obras, $fila->ubicacion, $fila->unidad ?? null)
                : ['id' => null];

            if (empty($puesto['id']) || empty($obra['id'])) {
                $falta = empty($puesto['id']) ? 'un puesto' : 'una ubicación';
                $this->motivoAlta = 'No se dio de alta porque falta '.$falta.' que se pueda guardar en TI.';
                Log::warning($nombre.': '.$this->motivoAlta);

                return null;
            }

            $empleado = new Empleados();
            $empleado->NombreEmpleado = mb_substr(mb_strtoupper($nombre, 'UTF-8'), 0, 100);
            $empleado->tipo_persona = 'FISICA';
            $empleado->Estado = 1;
            $empleado->PuestoID = $puesto['id'];
            $empleado->ObraID = $obra['id'];
            $empleado->NumTelefono = $this->telefono($fila->telefono ?? null);
            $empleado->Correo = $this->correoDisponible($fila->correo_institucional ?? null);
            $empleado->save();
        } catch (\Throwable $e) {
            $this->motivoAlta = 'No se dio de alta: '.$e->getMessage();
            Log::warning($nombre.': '.$this->motivoAlta);

            return null;
        }

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
