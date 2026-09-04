<?php

namespace App\Services;

use App\Mail\TareasProgramadasAviso;
use App\Models\Empleados;
use App\Models\TicketTarea;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Avisos por correo de las tareas programadas (métricas mensuales):
 *  - el día que el sistema las genera,
 *  - y en la primera corrida después de que se vuelven críticas.
 *
 * Ambos avisos se marcan en la tarea para no repetirlos en la corrida siguiente.
 */
class TicketTareaNotificacionService
{
    /**
     * Cruza los usuarios configurados contra empleados para sacar su correo.
     *
     * users.email está vacío en toda la tabla, así que el correo real vive en
     * empleados.Correo. El único enlace entre ambas tablas es el nombre, y no siempre
     * viene idéntico (dobles espacios, acentos, mayúsculas), por eso se normaliza
     * antes de comparar. Devuelve una fila por usuario configurado, resuelto o no,
     * para poder reportar quién se quedó sin correo.
     *
     * @return Collection<int, array{username:string, nombre:string, correo:?string, origen:string}>
     */
    public function resolverDestinatarios(): Collection
    {
        $usernames = (array) config('tareas.notificados', []);

        if ($usernames === []) {
            return collect();
        }

        $usuarios = User::whereIn('username', $usernames)->get(['id', 'username', 'name', 'email']);

        // Se indexan los empleados por nombre normalizado. Si hubiera homónimos gana
        // el activo, que es el que sigue trabajando aquí.
        $empleados = Empleados::query()
            ->whereNotNull('Correo')
            ->where('Correo', '!=', '')
            ->orderByRaw('Estado = 1 DESC')
            ->get(['NombreEmpleado', 'Correo', 'Estado'])
            ->groupBy(fn ($e) => $this->normalizarNombre($e->NombreEmpleado))
            ->map(fn ($grupo) => $grupo->first());

        return $usuarios->map(function ($usuario) use ($empleados) {
            $propio = trim((string) $usuario->email);

            if ($propio !== '' && filter_var($propio, FILTER_VALIDATE_EMAIL)) {
                return [
                    'username' => $usuario->username,
                    'nombre' => $usuario->name,
                    'correo' => $propio,
                    'origen' => 'users.email',
                ];
            }

            $empleado = $empleados->get($this->normalizarNombre((string) $usuario->name));
            $correo = trim((string) optional($empleado)->Correo);

            return [
                'username' => $usuario->username,
                'nombre' => $usuario->name,
                'correo' => $correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL) ? $correo : null,
                'origen' => $empleado ? 'empleados.Correo' : 'sin empleado',
            ];
        })->values();
    }

    /** Correos únicos a los que se manda el aviso, más los extra de configuración. */
    public function destinatarios(): array
    {
        return $this->resolverDestinatarios()
            ->pluck('correo')
            ->merge(config('tareas.correos_extra', []))
            ->map(fn ($correo) => trim((string) $correo))
            ->filter(fn ($correo) => $correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    /** Usuarios configurados que no tienen un correo utilizable. */
    public function destinatariosSinCorreo(): Collection
    {
        $encontrados = $this->resolverDestinatarios();

        $faltantes = $encontrados->whereNull('correo')->values();

        // Un username mal escrito en la config ni siquiera aparece como usuario.
        $inexistentes = collect(config('tareas.notificados', []))
            ->diff($encontrados->pluck('username'))
            ->map(fn ($username) => [
                'username' => $username,
                'nombre' => '—',
                'correo' => null,
                'origen' => 'usuario inexistente',
            ]);

        return $faltantes->merge($inexistentes)->values();
    }

    /**
     * Deja los nombres comparables entre users.name y empleados.NombreEmpleado:
     * sin acentos, sin dobles espacios y en mayúsculas.
     */
    private function normalizarNombre(?string $nombre): string
    {
        $limpio = trim((string) $nombre);

        if ($limpio === '') {
            return '';
        }

        $sinAcentos = strtr($limpio, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);

        return mb_strtoupper(preg_replace('/\s+/u', ' ', $sinAcentos));
    }

    /**
     * Tareas programadas recién generadas que todavía no se avisan.
     *
     * No se filtra por "creadas hoy": una métrica generada después de las 9:30 (con el
     * botón "Generar mes actual" o porque el cron de la mañana falló) se quedaría sin
     * aviso para siempre. Se toma la ventana de días de config para no revivir tareas
     * viejas si alguien limpia las marcas a mano.
     */
    public function pendientesDeAvisoCreacion(): Collection
    {
        $dias = max(1, (int) config('tareas.ventana_aviso_creacion_dias', 3));

        return TicketTarea::with('asignado')
            ->where('tipo', TicketTarea::TIPO_METRICA)
            ->whereNull('notificado_creacion_at')
            ->where('created_at', '>=', now()->subDays($dias)->startOfDay())
            ->orderBy('fecha_compromiso')
            ->get();
    }

    /** Tareas programadas que ya se volvieron críticas y siguen sin avisarse. */
    public function pendientesDeAvisoCritica(): Collection
    {
        return TicketTarea::with('asignado')
            ->pendientes()
            ->where('tipo', TicketTarea::TIPO_METRICA)
            ->where('prioridad', TicketTarea::PRIORIDAD_CRITICA)
            ->whereNull('notificado_critica_at')
            ->orderBy('fecha_compromiso')
            ->get();
    }

    /**
     * Manda un solo correo con el lote y marca las tareas. Devuelve cuántas se avisaron.
     */
    public function enviar(Collection $tareas, string $tipo, bool $dryRun = false): int
    {
        if ($tareas->isEmpty()) {
            return 0;
        }

        $destinatarios = $this->destinatarios();

        if ($destinatarios === []) {
            Log::warning('Tareas programadas: no hay destinatarios con correo válido.', [
                'tipo' => $tipo,
                'configurados' => config('tareas.notificados'),
            ]);

            return 0;
        }

        if ($dryRun) {
            return $tareas->count();
        }

        Mail::to($destinatarios)->send(new TareasProgramadasAviso($tareas, $tipo));

        $columna = $tipo === TareasProgramadasAviso::TIPO_CRITICAS
            ? 'notificado_critica_at'
            : 'notificado_creacion_at';

        TicketTarea::whereIn('id', $tareas->pluck('id'))->update([$columna => now()]);

        return $tareas->count();
    }

    public function notificarCreadas(bool $dryRun = false): int
    {
        return $this->enviar($this->pendientesDeAvisoCreacion(), TareasProgramadasAviso::TIPO_CREADAS, $dryRun);
    }

    public function notificarCriticas(bool $dryRun = false): int
    {
        return $this->enviar($this->pendientesDeAvisoCritica(), TareasProgramadasAviso::TIPO_CRITICAS, $dryRun);
    }
}
