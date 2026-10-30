<?php

namespace App\Services;

use App\Models\Empleados;
use App\Models\Gerencia;
use App\Models\Obras;
use App\Models\UnidadesDeNegocio;

/**
 * A quién pide firma una solicitud de compra.
 * El primer visto bueno es el jefe inmediato (se puede cambiar en el formulario).
 * El siguiente es el gerente de la persona, o el director si esa gerencia no tiene gerente.
 * En la unidad de construcción el gerente no sale del puesto: sale del centro de costos
 * o de la ubicación (obra).
 */
class SolicitudFlujoService
{
    public function jefeInmediato(Empleados $empleado): ?Empleados
    {
        $jefeId = (int) ($empleado->JefeDirectoID ?? 0);
        if ($jefeId <= 0 || $jefeId === (int) $empleado->EmpleadoID) {
            return null;
        }

        return Empleados::query()->fisicosActivos()->find($jefeId);
    }

    /**
     * Un solo destinatario: el gerente, o el director si no hay gerente.
     * Si la persona elegida ya es el gerente o el director, la solicitud se queda con ella.
     *
     * @return array{empleado: ?Empleados, rol: string, detalle: string, gerencia: ?Gerencia}
     */
    public function destinoVisible(Empleados $persona): array
    {
        $persona->loadMissing('puestos.departamentos.gerencia');
        $puesto = $this->normalizar((string) ($persona->puestos->NombrePuesto ?? ''));
        $gerencia = $persona->puestos->departamentos->gerencia ?? null;

        if (str_contains($puesto, 'GERENTE')) {
            return [
                'empleado' => $persona,
                'rol' => 'Gerente',
                'detalle' => $gerencia ? 'Gerencia ' . $gerencia->NombreGerencia : '',
                'gerencia' => $gerencia,
            ];
        }

        if (str_contains($puesto, 'DIRECTOR')) {
            return [
                'empleado' => $persona,
                'rol' => 'Director',
                'detalle' => $gerencia ? 'Gerencia ' . $gerencia->NombreGerencia : '',
                'gerencia' => $gerencia,
            ];
        }

        return $this->destinoTrasSupervisor($persona);
    }

    /**
     * @return array{empleado: ?Empleados, rol: string, detalle: string, gerencia: ?Gerencia}
     */
    public function destinoTrasSupervisor(Empleados $empleado): array
    {
        $empleado->loadMissing([
            'puestos.departamentos.gerencia.unidadesdenegocio',
            'obras',
            'centroCosto',
        ]);

        $gerencia = $empleado->puestos->departamentos->gerencia ?? null;
        $obra = $empleado->obras;

        if ($this->esUnidadConstruccion($gerencia, $obra)) {
            return $this->destinoConstruccion($empleado, $gerencia, $obra);
        }

        if (!$gerencia) {
            return $this->vacio('La persona no tiene gerencia.');
        }

        $gerente = $this->gerenteDeGerencia($gerencia);
        if ($gerente) {
            return [
                'empleado' => $gerente,
                'rol' => 'Gerente',
                'detalle' => 'Gerencia ' . $gerencia->NombreGerencia,
                'gerencia' => $gerencia,
            ];
        }

        $director = $this->directorDe($gerencia);
        if ($director) {
            return [
                'empleado' => $director,
                'rol' => 'Director',
                'detalle' => 'La gerencia ' . $gerencia->NombreGerencia . ' no tiene gerente. Se envía al director.',
                'gerencia' => $gerencia,
            ];
        }

        return $this->vacio('No se encontró el gerente ni el director de ' . $gerencia->NombreGerencia . '.', $gerencia);
    }

    /**
     * @return array{empleado: ?Empleados, rol: string, detalle: string, gerencia: ?Gerencia}
     */
    private function destinoConstruccion(Empleados $empleado, ?Gerencia $gerencia, ?Obras $obra): array
    {
        $porCentro = $this->gerentePorCentroCosto($empleado);
        if ($porCentro) {
            return [
                'empleado' => $porCentro,
                'rol' => $this->rolDePersona($porCentro),
                'detalle' => 'Centro de costos ' . ($empleado->centroCosto->NombreCentro ?? ''),
                'gerencia' => $gerencia,
            ];
        }

        $porObra = $this->gerentePorObra($obra);
        if ($porObra) {
            return [
                'empleado' => $porObra,
                'rol' => $this->rolDePersona($porObra),
                'detalle' => 'Ubicación ' . ($obra->NombreObra ?? ''),
                'gerencia' => $gerencia,
            ];
        }

        if ($gerencia) {
            $director = $this->directorDe($gerencia);
            if ($director) {
                return [
                    'empleado' => $director,
                    'rol' => 'Director',
                    'detalle' => 'La ubicación no tiene gerente. Se envía al director.',
                    'gerencia' => $gerencia,
                ];
            }
        }

        return $this->vacio('No se encontró el gerente de la ubicación ni el director.', $gerencia);
    }

    private function esUnidadConstruccion(?Gerencia $gerencia, ?Obras $obra): bool
    {
        $unidadId = $gerencia->UnidadNegocioID ?? $obra->UnidadNegocioID ?? null;
        if (!$unidadId) {
            return false;
        }

        $nombre = (string) (UnidadesDeNegocio::query()->where('UnidadNegocioID', $unidadId)->value('NombreEmpresa') ?? '');
        $nombre = $this->normalizar($nombre);

        return str_contains($nombre, 'CONSTRUCTORA') || str_contains($nombre, 'CONSTRUCCION');
    }

    private function gerenteDeGerencia(Gerencia $gerencia): ?Empleados
    {
        $nombre = trim((string) $gerencia->NombreGerente);
        if ($nombre === '') {
            return null;
        }

        return $this->empleadoPorNombre($nombre);
    }

    private function gerentePorCentroCosto(Empleados $empleado): ?Empleados
    {
        $centro = $empleado->centroCosto;
        $texto = $this->normalizar((string) ($centro->NombreCentro ?? ''));
        if ($texto === '') {
            return null;
        }

        $texto = trim(preg_replace('/\s+/', ' ', preg_replace('/\b(G\d+|20\d{2})\b/', ' ', $texto)));
        $mejor = null;
        $mejorPuntos = 0;

        $gerencias = Gerencia::query()
            ->whereNotNull('NombreGerente')
            ->where('NombreGerente', '!=', '')
            ->get();

        foreach ($gerencias as $gerencia) {
            $palabras = array_values(array_filter(
                explode(' ', $this->normalizar($gerencia->NombreGerencia)),
                fn ($palabra) => strlen($palabra) >= 5 && !in_array($palabra, ['GERENCIA', 'DIRECCION'], true)
            ));
            if ($palabras === []) {
                continue;
            }

            $aciertos = 0;
            foreach ($palabras as $palabra) {
                if (str_contains($texto, $palabra)) {
                    $aciertos++;
                }
            }

            if ($aciertos === count($palabras) && $aciertos > $mejorPuntos) {
                $mejor = $gerencia;
                $mejorPuntos = $aciertos;
            }
        }

        return $mejor ? $this->gerenteDeGerencia($mejor) : null;
    }

    private function gerentePorObra(?Obras $obra): ?Empleados
    {
        if (!$obra) {
            return null;
        }

        $encargado = $this->empleadoPorNombre($obra->EncargadoDeObra);
        if ($encargado && $this->puestoContiene($encargado, 'GERENTE')) {
            return $encargado;
        }

        $enSitio = Empleados::query()
            ->with('puestos')
            ->fisicosActivos()
            ->where('ObraID', $obra->ObraID)
            ->whereHas('puestos', fn ($q) => $q->where('NombrePuesto', 'like', '%GERENTE%'))
            ->get();

        if ($enSitio->count() === 1) {
            return $enSitio->first();
        }

        $gerencia = Gerencia::query()
            ->where('UnidadNegocioID', $obra->UnidadNegocioID)
            ->get()
            ->first(fn ($item) => $this->normalizar($item->NombreGerencia) === $this->normalizar($obra->NombreObra));

        if ($gerencia) {
            $gerente = $this->gerenteDeGerencia($gerencia);
            if ($gerente) {
                return $gerente;
            }
        }

        if ($encargado && $this->puestoContiene($encargado, 'DIRECTOR')) {
            return $encargado;
        }

        return null;
    }

    private function directorDe(Gerencia $gerencia): ?Empleados
    {
        $direcciones = Gerencia::query()
            ->where('DireccionID', $gerencia->DireccionID)
            ->where('NombreGerencia', 'like', 'DIRECCION%')
            ->whereNotNull('NombreGerente')
            ->where('NombreGerente', '!=', '')
            ->get()
            ->sortBy(fn ($item) => str_starts_with($this->normalizar($item->NombreGerencia), 'DIRECCION GENERAL') ? 0 : 1);

        foreach ($direcciones as $direccion) {
            $director = $this->empleadoPorNombre($direccion->NombreGerente);
            if ($director) {
                return $director;
            }
        }

        $unidadId = (int) $gerencia->UnidadNegocioID;
        $candidatos = Empleados::query()
            ->with('puestos')
            ->fisicosActivos()
            ->whereHas('puestos', function ($q) use ($unidadId) {
                $q->where('NombrePuesto', 'like', '%DIRECTOR%')
                    ->whereHas('departamentos', function ($d) use ($unidadId) {
                        $d->whereHas('gerencia', fn ($g) => $g->where('UnidadNegocioID', $unidadId));
                    });
            })
            ->get()
            ->filter(function ($empleado) {
                $nombre = $this->normalizar($empleado->NombreEmpleado);

                return !str_contains($nombre, 'EDIFICACION') && !str_contains($nombre, 'ESTRUCTURA');
            });

        return $candidatos->first();
    }

    private function empleadoPorNombre(?string $nombre): ?Empleados
    {
        $nombre = trim((string) $nombre);
        if ($nombre === '' || $this->esMarcador($nombre)) {
            return null;
        }

        $exacto = Empleados::query()->fisicosActivos()->where('NombreEmpleado', $nombre)->first();
        if ($exacto) {
            return $exacto;
        }

        $tokens = array_values(array_filter(
            explode(' ', $this->normalizar($nombre)),
            fn ($token) => strlen($token) >= 3
        ));
        if (count($tokens) < 2) {
            return null;
        }

        $consulta = Empleados::query()->with('puestos')->fisicosActivos();
        foreach ($tokens as $token) {
            $consulta->where('NombreEmpleado', 'like', '%' . $token . '%');
        }

        $candidatos = $consulta->limit(8)->get();
        if ($candidatos->isEmpty()) {
            return null;
        }

        return $candidatos->sortByDesc(function ($empleado) {
            $puesto = $this->normalizar((string) ($empleado->puestos->NombrePuesto ?? ''));
            if (str_contains($puesto, 'GERENTE')) {
                return 2;
            }
            if (str_contains($puesto, 'DIRECTOR')) {
                return 1;
            }

            return 0;
        })->first();
    }

    private function rolDePersona(Empleados $empleado): string
    {
        return $this->puestoContiene($empleado, 'DIRECTOR') ? 'Director' : 'Gerente';
    }

    private function puestoContiene(Empleados $empleado, string $aguja): bool
    {
        $empleado->loadMissing('puestos');

        return str_contains($this->normalizar((string) ($empleado->puestos->NombrePuesto ?? '')), $aguja);
    }

    private function esMarcador(string $nombre): bool
    {
        $clave = $this->normalizar($nombre);

        return in_array($clave, ['N A', 'NA', 'POR REGISTRAR', 'INVESTIGAR', 'NULL', 'NINGUNO', 'SIN ASIGNAR'], true);
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtoupper(trim($texto), 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);
        $texto = preg_replace('/[^A-Z0-9 ]/', ' ', $texto);

        return trim(preg_replace('/\s+/', ' ', $texto));
    }

    /**
     * @return array{empleado: null, rol: string, detalle: string, gerencia: ?Gerencia}
     */
    private function vacio(string $detalle, ?Gerencia $gerencia = null): array
    {
        return [
            'empleado' => null,
            'rol' => '',
            'detalle' => $detalle,
            'gerencia' => $gerencia,
        ];
    }
}
