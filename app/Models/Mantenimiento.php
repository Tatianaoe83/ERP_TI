<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'mantenimientos';

    protected $fillable = [
        'AnioProgramacion',
        'EmpleadoID',
        'InventarioID',
        'TipoMantenimiento',
        'Folio',
        'FechaDeCompra',
        'FechaMantenimiento',
        'FechaReprogramada',
        'Comentario',
        'Estatus',
        'RealizadoPor',
        'FechaRealizado',
    ];

    protected $casts = [
        'AnioProgramacion' => 'integer',
        'EmpleadoID' => 'integer',
        'InventarioID' => 'integer',
        'FechaDeCompra' => 'date',
        'FechaMantenimiento' => 'date',
        'FechaReprogramada' => 'date',
        'FechaRealizado' => 'datetime',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'EmpleadoID', 'EmpleadoID')->withTrashed();
    }

    public function inventarioEquipo()
    {
        return $this->belongsTo(InventarioEquipo::class, 'InventarioID', 'InventarioID');
    }

    public function getNombreEmpleadoAttribute($value)
    {
        return optional($this->empleadoAsignado())->NombreEmpleado ?: $value;
    }

    public function getNombreGerenciaAttribute($value)
    {
        $gerencia = optional(optional(optional($this->empleadoAsignado())->puestos)->departamentos)->gerencia;

        return optional($gerencia)->NombreGerencia ?: $value;
    }

    public function getEmpleadoBajaAttribute(): bool
    {
        $empleado = $this->empleadoAsignado();

        return $empleado && !$empleado->Estado;
    }

    public function getRequierePersonaFisicaAttribute(): bool
    {
        $empleado = $this->empleadoAsignado();

        if (!$empleado) {
            return true;
        }

        return !$empleado->Estado || strtoupper((string) $empleado->tipo_persona) !== 'FISICA';
    }

    public function getMotivoAsignacionPersonaFisicaAttribute(): string
    {
        $empleado = $this->empleadoAsignado();

        if (!$empleado) {
            return 'Equipo sin empleado asignado';
        }

        if (!$empleado->Estado) {
            return 'Empleado dado de baja';
        }

        if (strtoupper((string) $empleado->tipo_persona) !== 'FISICA') {
            return 'No está asignado a una persona física';
        }

        return '';
    }

    public function getEstatusMantenimientoAttribute(): string
    {
        if ($this->Estatus === 'Realizado') {
            return $this->Estatus;
        }

        if ($this->EmpleadoBaja) {
            return 'Baja';
        }

        return $this->RequierePersonaFisica ? 'Sin persona física' : $this->Estatus;
    }

    private function empleadoAsignado()
    {
        return optional($this->inventarioEquipo)->empleados ?: $this->empleado;
    }
}
