<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Tickets
 * @package App\Models
 * @version October 14, 2025, 5:38 pm UTC
 *
 * @property \App\Models\Empleado $responsableti
 * @property \App\Models\Empleado $empleadoid
 * @property integer $CodeAnyDesk
 * @property string $Descripcion
 * @property string $imagen
 * @property integer $Numero
 * @property string $Prioridad
 * @property string $Estatus
 * @property integer $ResponsableTI
 * @property integer $EmpleadoID
 * @property integer $TipoID
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
        'imagen',
        'Numero',
        'Prioridad',
        'Estatus',
        'ResponsableTI',
        'EmpleadoID',
        'TipoID',
        'FechaInicioProgreso',
        'FechaFinProgreso'
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
        'imagen' => 'string',
        'Numero' => 'integer',
        'Prioridad' => 'string',
        'Estatus' => 'string',
        'ResponsableTI' => 'integer',
        'EmpleadoID' => 'integer',
        'TipoID' => 'integer',
        'FechaInicioProgreso' => 'datetime',
        'FechaFinProgreso' => 'datetime'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'CodeAnyDesk' => 'nullable',
        'Descripcion' => 'required|string',
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
                }

                // Si cambia a "Cerrado" y tiene fecha de inicio pero no de fin
                if ($nuevoEstatus === 'Cerrado' && $ticket->FechaInicioProgreso && !$ticket->FechaFinProgreso) {
                    $ticket->FechaFinProgreso = now();
                }
            }
        });
    }

    /**
     * Calcular horas laborales entre dos fechas
     * Horarios: L-V 9:00-18:00, Sábado 9:00-14:00, Domingo no cuenta
     * 
     * @param \Carbon\Carbon $fechaInicio
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
     * 
     * @param \Carbon\Carbon $fecha
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
     * Obtener tiempo de respuesta en horas laborales (para tickets en progreso)
     * Diferencia entre FechaInicioProgreso y ahora
     */
    public function getTiempoRespuestaAttribute()
    {
        if (!$this->FechaInicioProgreso || $this->Estatus !== 'En progreso') {
            return null;
        }

        return $this->calcularHorasLaborales($this->FechaInicioProgreso, now());
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
     * 
     * @param float $horas
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
}
