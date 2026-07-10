<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Tickets
 * @package App\Models
 * @version October 14, 2025, 5:38 pm UTC
 *
 * @property integer $TicketID
 * @property integer $CodeAnyDesk
 * @property string $Descripcion
 * @property string $Resolucion
 * @property string $imagen
 * @property integer $Numero
 * @property string $Prioridad
 * @property string $Estatus
 * @property string $Clasificacion
 * @property integer $ResponsableTI
 * @property integer $EmpleadoID
 * @property integer $TipoID
 * @property integer $SubtipoID
 * @property integer $TertipoID
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon|null $FechaInicioProgreso
 * @property \Carbon\Carbon|null $FechaFinProgreso
 * @property \Carbon\Carbon|null $fecha_ultima_notificacion_exceso
 * @property \App\Models\Empleado $empleado
 * @property \App\Models\Empleado $responsableTI
 * @property \App\Models\Tipoticket $tipoticket
 * @property \App\Models\Subtipos $subtipo
 * @property \App\Models\Tertipos $tertipo
 * @property-read float|null $tiempo_respuesta
 * @property-read string|null $tiempo_respuesta_formateado
 * @property-read float|null $tiempo_progreso
 * @property-read float|null $tiempo_resolucion
 * @property-read string|null $tiempo_resolucion_formateado
 * @property-read int $id
 * @property-read string $estado
 */
class Tickets extends Model
{
    use SoftDeletes;

    public $table = 'tickets';
    
    protected $primaryKey = 'TicketID';
    public $incrementing = true;
    protected $keyType = 'int';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = ['deleted_at'];

    public $fillable = [
        'CodeAnyDesk',
        'Descripcion',
        'Resolucion', // <--- AGREGADO AQUÍ PARA PERMITIR GUARDADO
        'imagen',
        'Numero',
        'Prioridad',
        'Estatus',
        'Clasificacion',
        'ResponsableTI',
        'EmpleadoID',
        'TipoID',
        'SubtipoID',
        'TertipoID',
        'FechaInicioProgreso',
        'FechaFinProgreso',
        'fecha_ultima_notificacion_exceso'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'TicketID' => 'integer',
        'CodeAnyDesk' => 'integer',
        'Descripcion' => 'string',
        'Resolucion' => 'string', // <--- AGREGADO AQUÍ
        'imagen' => 'string',
        'Numero' => 'integer',
        'Prioridad' => 'string',
        'Estatus' => 'string',
        'Clasificacion' => 'string',
        'ResponsableTI' => 'integer',
        'EmpleadoID' => 'integer',
        'TipoID' => 'integer',
        'SubtipoID' => 'integer',
        'TertipoID' => 'integer',
        'FechaInicioProgreso' => 'datetime',
        'FechaFinProgreso' => 'datetime',
        'fecha_ultima_notificacion_exceso' => 'datetime'
    ];

    /**
     * Alias para Livewire wire:key (id único del ticket).
     */
    public function getIdAttribute(): int
    {
        return $this->TicketID;
    }

    /**
     * Alias para Livewire wire:key (estado para detectar re-render).
     */
    public function getEstadoAttribute(): string
    {
        return $this->Estatus ?? '';
    }

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'CodeAnyDesk' => 'nullable',
        'Descripcion' => 'required|string',
        'Resolucion' => 'nullable|string', // <--- AGREGADO AQUÍ (Opcional)
        'imagen' => 'nullable|string',
        'Numero' => 'nullable',
        'Prioridad' => 'required|string',
        'Estatus' => 'nullable|string',
        'ResponsableTI' => 'nullable|integer',
        'EmpleadoID' => 'required|integer',
        'TipoID' => 'nullable|integer',
        'created_at' => 'nullable',
        'updated_at' => 'nullable',
        'deleted_at' => 'nullable'
    ];

    public function calificacion()
    {
        return $this->hasOne(Calificacion::class, 'ticket_id', 'TicketID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'EmpleadoID', 'EmpleadoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function responsableTI()
    {
        return $this->belongsTo(Empleados::class, 'ResponsableTI', 'EmpleadoID');
    }

    /**
     * Relación con los mensajes del chat
     */
    public function chat()
    {
        return $this->hasMany(TicketChat::class, 'ticket_id', 'TicketID')->orderBy('created_at', 'asc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function tipoticket()
    {
        return $this->belongsTo(Tipoticket::class, 'TipoID', 'TipoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function subtipo()
    {
        return $this->belongsTo(Subtipos::class, 'SubtipoID', 'SubtipoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function tertipo()
    {
        return $this->belongsTo(Tertipos::class, 'TertipoID', 'TertipoID');
    }

    /**
     * Boot del modelo para manejar eventos
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($ticket) {
            // Si el estatus está cambiando
            if ($ticket->isDirty('Estatus')) {
                $nuevoEstatus = $ticket->Estatus;
                $estatusAnterior = $ticket->getOriginal('Estatus');

                // Si cambia a "En progreso" y no tiene fecha de inicio
                if ($nuevoEstatus === 'En progreso' && !$ticket->FechaInicioProgreso) {
                    $ticket->FechaInicioProgreso = now();
                    // Resetear la fecha de última notificación cuando inicia progreso
                    $ticket->fecha_ultima_notificacion_exceso = null;
                }

                // Si cambia a "Cerrado" y tiene fecha de inicio pero no de fin
                if ($nuevoEstatus === 'Cerrado' && $ticket->FechaInicioProgreso && !$ticket->FechaFinProgreso) {
                    $ticket->FechaFinProgreso = now();
                    // Resetear la fecha de última notificación cuando se cierra
                    $ticket->fecha_ultima_notificacion_exceso = null;
                }
            }
        });
    }

    /**
     * Calcular horas laborales entre dos fechas
     * Horarios: L-V 9:00-18:00, Sábado 9:00-14:00, Domingo no cuenta
     * * @param \Carbon\Carbon $fechaInicio
     * @param \Carbon\Carbon $fechaFin
     * @return float Horas laborales en decimal
     */
    protected function calcularHorasLaborales($fechaInicio, $fechaFin)
    {
        if (!$fechaInicio || !$fechaFin) {
            return 0;
        }

        // Si la fecha fin es anterior a la fecha inicio, retornar 0
        if ($fechaFin->lt($fechaInicio)) {
            return 0;
        }

        $horas = 0;
        $actual = $fechaInicio->copy();
        $fin = $fechaFin->copy();

        // Normalizar fecha inicio
        $actual = $this->normalizarFechaInicio($actual);
        
        // Si después de normalizar, la fecha inicio es mayor o igual a la fin, retornar 0
        if ($actual->gte($fin)) {
            return 0;
        }

        while ($actual->lt($fin)) {
            $diaSemana = $actual->dayOfWeek; // 0=Domingo, 1=Lunes, ..., 6=Sábado

            // Si es domingo, saltar al lunes
            if ($diaSemana == 0) {
                $actual->addDay()->setTime(9, 0, 0);
                continue;
            }

            // Determinar horario laboral del día
            $horaInicio = 9;
            $horaFin = ($diaSemana == 6) ? 14 : 18; // Sábado hasta las 14:00, otros días hasta las 18:00

            // Asegurar que estamos dentro del horario laboral
            if ($actual->hour < $horaInicio) {
                $actual->setTime($horaInicio, 0, 0);
            }

            // Calcular el fin del día laboral
            $finDelDia = $actual->copy()->setTime($horaFin, 0, 0);
            
            // Si la fecha fin está en el mismo día
            if ($actual->isSameDay($fin)) {
                // Calcular horas hasta la fecha fin (limitado por el fin del día)
                $finCalculo = $fin->lt($finDelDia) ? $fin : $finDelDia;
                
                // Si la fecha fin está antes del inicio del horario, no cuenta
                if ($finCalculo->lte($actual)) {
                    break;
                }
                
                // Calcular diferencia en segundos y convertir a horas
                $segundos = $actual->diffInSeconds($finCalculo);
                $horas += $segundos / 3600;
                break;
            }

            // Calcular horas del día actual hasta el fin del horario laboral
            $segundos = $actual->diffInSeconds($finDelDia);
            $horas += $segundos / 3600;

            // Avanzar al siguiente día hábil
            if ($diaSemana == 6) {
                // Si es sábado, saltar al lunes
                $actual->addDays(2)->setTime(9, 0, 0);
            } else {
                // Día siguiente
                $actual->addDay()->setTime(9, 0, 0);
            }
        }

        return round($horas, 2);
    }

    /**
     * Normalizar fecha de inicio al horario laboral más cercano
     * * @param \Carbon\Carbon $fecha
     * @return \Carbon\Carbon
     */
    protected function normalizarFechaInicio($fecha)
    {
        $fecha = $fecha->copy();
        $diaSemana = $fecha->dayOfWeek;
        $hora = $fecha->hour;
        $minuto = $fecha->minute;

        // Si es domingo, ir al lunes 9:00
        if ($diaSemana == 0) {
            return $fecha->addDay()->setTime(9, 0, 0);
        }

        $horaFin = ($diaSemana == 6) ? 14 : 18;

        // Si está antes del horario laboral, ajustar al inicio
        if ($hora < 9) {
            return $fecha->setTime(9, 0, 0);
        }

        // Si está después del horario laboral, ir al siguiente día hábil
        if ($hora >= $horaFin) {
            if ($diaSemana == 6) {
                return $fecha->addDays(2)->setTime(9, 0, 0);
            } else {
                return $fecha->addDay()->setTime(9, 0, 0);
            }
        }

        return $fecha;
    }

    /**
     * Obtener tiempo de respuesta en horas laborales
     * Diferencia entre created_at y FechaInicioProgreso
     * Representa: cuánto esperó el usuario antes de que alguien empezara a atender el ticket
     */
    public function getTiempoRespuestaAttribute()
    {
        if (!$this->FechaInicioProgreso) {
            return null;
        }

        return $this->calcularHorasLaborales($this->created_at, $this->FechaInicioProgreso);
    }

    /**
     * Obtener tiempo de respuesta formateado
     */
    public function getTiempoRespuestaFormateadoAttribute()
    {
        $horas = $this->getTiempoRespuestaAttribute();
        
        if ($horas === null) {
            return null;
        }

        return $this->formatearHoras($horas);
    }

    /**
     * Obtener tiempo en progreso en horas laborales
     * Diferencia entre FechaInicioProgreso y ahora (o FechaFinProgreso si ya cerró)
     */
    public function getTiempoProgresoAttribute()
    {
        if (!$this->FechaInicioProgreso) {
            return null;
        }

        $fechaFin = $this->FechaFinProgreso ?? now();

        return $this->calcularHorasLaborales($this->FechaInicioProgreso, $fechaFin);
    }

    /**
     * Calcular el tiempo de resolución en horas laborales (para tickets cerrados)
     * Diferencia entre FechaInicioProgreso y FechaFinProgreso
     */
    public function getTiempoResolucionAttribute()
    {
        if (!$this->FechaInicioProgreso || !$this->FechaFinProgreso) {
            return null;
        }

        return $this->calcularHorasLaborales($this->FechaInicioProgreso, $this->FechaFinProgreso);
    }

    /**
     * Obtener el tiempo de resolución formateado
     */
    public function getTiempoResolucionFormateadoAttribute()
    {
        $horas = $this->getTiempoResolucionAttribute();
        
        if ($horas === null) {
            return null;
        }

        return $this->formatearHoras($horas);
    }

    /**
     * Formatear horas laborales a formato legible
     * * @param float $horas
     * @return string
     */
    protected function formatearHoras($horas)
    {
        if ($horas == 0) {
            return '0 horas';
        }

        $horasEnteras = floor($horas);
        $minutos = round(($horas - $horasEnteras) * 60);
        
        // Si los minutos son 60, ajustar
        if ($minutos >= 60) {
            $horasEnteras += 1;
            $minutos = 0;
        }

        $partes = [];
        
        // Mostrar días si son más de 8 horas (aproximadamente un día laboral)
        if ($horasEnteras >= 8) {
            $dias = floor($horasEnteras / 8);
            $horasRestantes = $horasEnteras % 8;
            
            if ($dias > 0) {
                $partes[] = $dias . ' día' . ($dias > 1 ? 's' : '');
            }
            
            if ($horasRestantes > 0) {
                $partes[] = $horasRestantes . ' hora' . ($horasRestantes > 1 ? 's' : '');
            }
        } else {
            if ($horasEnteras > 0) {
                $partes[] = $horasEnteras . ' hora' . ($horasEnteras > 1 ? 's' : '');
            }
        }
        
        if ($minutos > 0 && count($partes) < 2) {
            $partes[] = $minutos . ' minuto' . ($minutos > 1 ? 's' : '');
        }

        return implode(', ', $partes) ?: '0 horas';
    }

    public const COLUMNAS_VISTA = [
        'nuevos'    => 'Nuevos',
        'proceso'   => 'En Progreso',
        'resueltos' => 'Resueltos',
    ];

    public const COLORES_COLUMNA = [
        'nuevos'    => 'bg-yellow-500',
        'proceso'   => 'bg-blue-500',
        'resueltos' => 'bg-green-500',
    ];

    public static function colorPrioridad(?string $prioridad): string
    {
        return match ($prioridad) {
            'Baja'  => '#22c55e',
            'Media' => '#eab308',
            'Alta'  => '#ef4444',
            default => '#94a3b8',
        };
    }

    public static function formatearNombreEmpleado(?string $nombreCompleto, ?string $area = null): string
    {
        return TicketMantenimiento::formatearNombreEmpleado($nombreCompleto, $area);
    }

    public static function formatearTiempoTarjeta(?array $tiempoInfo): ?array
    {
        if (!$tiempoInfo) {
            return null;
        }

        $estilos = [
            'normal'     => [
                'bg'     => 'bg-green-50 dark:bg-green-900/20',
                'text'   => 'text-green-700 dark:text-green-300',
                'border' => 'border-green-100 dark:border-green-800',
                'bar'    => 'bg-green-500',
            ],
            'por_vencer' => [
                'bg'     => 'bg-yellow-50 dark:bg-yellow-900/20',
                'text'   => 'text-yellow-700 dark:text-yellow-300',
                'border' => 'border-yellow-100 dark:border-yellow-800',
                'bar'    => 'bg-yellow-500',
            ],
            'agotado'    => [
                'bg'     => 'bg-red-50 dark:bg-red-900/20',
                'text'   => 'text-red-700 dark:text-red-300',
                'border' => 'border-red-100 dark:border-red-800',
                'bar'    => 'bg-red-500',
            ],
        ];

        $estado = $tiempoInfo['estado'] ?? 'normal';

        return [
            'transcurrido' => $tiempoInfo['transcurrido'],
            'estimado'     => $tiempoInfo['estimado'],
            'porcentaje'   => min(100, (float) ($tiempoInfo['porcentaje'] ?? 0)),
            'estado'       => $estado,
            'texto'        => "{$tiempoInfo['transcurrido']}h / {$tiempoInfo['estimado']}h",
            'estilo'       => $estilos[$estado] ?? $estilos['normal'],
        ];
    }

    public static function procesarTiemposProgreso($tickets): array
    {
        $ticketsExcedidos = [];
        $tiemposProgreso = [];

        $ticketsEnProgreso = collect($tickets)
            ->where('Estatus', 'En progreso')
            ->whereNotNull('FechaInicioProgreso');

        foreach ($ticketsEnProgreso as $ticket) {
            if (!$ticket->tipoticket || !$ticket->tipoticket->TiempoEstimadoMinutos) {
                $tiemposProgreso[$ticket->TicketID] = null;
                continue;
            }

            $tiempoEstimadoHoras = $ticket->tipoticket->TiempoEstimadoMinutos / 60;
            $tiempoTranscurrido = $ticket->tiempo_progreso ?? 0;

            $porcentajeUsado = $tiempoEstimadoHoras > 0
                ? ($tiempoTranscurrido / $tiempoEstimadoHoras) * 100
                : 0;

            $tiemposProgreso[$ticket->TicketID] = [
                'transcurrido' => round($tiempoTranscurrido, 1),
                'estimado'     => round($tiempoEstimadoHoras, 1),
                'porcentaje'   => round($porcentajeUsado, 1),
                'estado'       => $porcentajeUsado >= 100
                    ? 'agotado'
                    : ($porcentajeUsado >= 80 ? 'por_vencer' : 'normal'),
            ];

            if ($tiempoTranscurrido > $tiempoEstimadoHoras) {
                $tiempoExcedido = round($tiempoTranscurrido - $tiempoEstimadoHoras, 2);
                $porcentajeExcedido = round(($tiempoTranscurrido / $tiempoEstimadoHoras) * 100, 1);

                $ticketsExcedidos[] = [
                    'id'                  => $ticket->TicketID,
                    'descripcion'         => \Illuminate\Support\Str::limit($ticket->Descripcion, 80),
                    'responsable'         => $ticket->responsableTI
                        ? $ticket->responsableTI->NombreEmpleado
                        : 'Sin asignar',
                    'empleado'            => $ticket->empleado
                        ? $ticket->empleado->NombreEmpleado
                        : 'Sin empleado',
                    'prioridad'           => $ticket->Prioridad,
                    'tiempo_estimado'     => round($tiempoEstimadoHoras, 2),
                    'tiempo_respuesta'    => round($tiempoTranscurrido, 2),
                    'tiempo_excedido'     => $tiempoExcedido,
                    'porcentaje_excedido' => $porcentajeExcedido,
                    'categoria'           => $ticket->tipoticket
                        ? $ticket->tipoticket->NombreTipo
                        : 'Sin categoría',
                ];
            }
        }

        usort($ticketsExcedidos, fn ($a, $b) => $b['tiempo_excedido'] <=> $a['tiempo_excedido']);

        return [
            'tiemposProgreso'  => $tiemposProgreso,
            'ticketsExcedidos' => $ticketsExcedidos,
        ];
    }

    public static function mapaNotificacionesPendientes($ticketIds): array
    {
        $ids = collect($ticketIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return TicketChat::query()
            ->whereIn('ticket_id', $ids)
            ->where('notificaciones_pendientes', '>', 0)
            ->selectRaw('ticket_id, SUM(notificaciones_pendientes) as total')
            ->groupBy('ticket_id')
            ->pluck('total', 'ticket_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public static function formatearColeccionParaVista($tickets, array $tiemposProgreso = [], array $notificacionesMap = []): array
    {
        return collect($tickets)->map(function ($ticket) use ($tiemposProgreso, $notificacionesMap) {
            $id = $ticket->TicketID;

            return self::formatearTicketParaVista(
                $ticket,
                $tiemposProgreso[$id] ?? null,
                (int) ($notificacionesMap[$id] ?? 0)
            );
        })->values()->all();
    }

    public static function formatearTicketParaVista(self $ticket, ?array $tiempoInfo = null, int $notificaciones = 0): array
    {
        $ticket->loadMissing(['empleado', 'responsableTI', 'tipoticket']);

        $empleadoCorto = $ticket->empleado
            ? self::formatearNombreEmpleado($ticket->empleado->NombreEmpleado)
            : '';

        $responsableCorto = $ticket->responsableTI
            ? self::formatearNombreEmpleado($ticket->responsableTI->NombreEmpleado)
            : '';

        return [
            'id'                    => $ticket->TicketID,
            'TicketID'              => $ticket->TicketID,
            'descripcion'           => $ticket->Descripcion,
            'descripcion_tarjeta'   => \Illuminate\Support\Str::limit($ticket->Descripcion ?? '', 120),
            'prioridad'             => $ticket->Prioridad,
            'color_prioridad'       => self::colorPrioridad($ticket->Prioridad),
            'estatus'               => $ticket->Estatus,
            'categoria'             => $ticket->tipoticket?->NombreTipo ?? '',
            'numero'                => $ticket->Numero ?? '',
            'code_anydesk'          => $ticket->CodeAnyDesk ?? '',
            'empleado'              => $ticket->empleado ? [
                'nombre' => $ticket->empleado->NombreEmpleado,
                'correo' => $ticket->empleado->Correo ?? '',
            ] : null,
            'empleado_corto'        => $empleadoCorto,
            'correo'                => $ticket->empleado?->Correo ?? '',
            'responsable'           => $ticket->responsableTI ? [
                'nombre' => $ticket->responsableTI->NombreEmpleado,
            ] : null,
            'responsable_nombre'    => $responsableCorto,
            'created_at'            => optional($ticket->created_at)->toIso8601String(),
            'fecha_inicio_progreso' => optional($ticket->FechaInicioProgreso)->toIso8601String(),
            'updated_at'            => optional($ticket->updated_at)->toIso8601String(),
            'tiempo'                => self::formatearTiempoTarjeta($tiempoInfo),
            'tiempo_estado'         => $tiempoInfo['estado'] ?? '',
            'notificaciones'        => $notificaciones,
        ];
    }
}