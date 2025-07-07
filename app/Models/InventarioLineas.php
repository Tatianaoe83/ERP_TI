<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class InventarioLineas extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;



    public $table = 'inventariolineas';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';




    protected $primaryKey = 'InventarioID';
    protected $keyType = 'int';

    public $fillable = [

        'EmpleadoID',
        'NumTelefonico',
        'Compania',
        'PlanTel',
        'CostoRentaMensual',
        'CuentaPadre',
        'CuentaHija',
        'TipoLinea',
        'ObraID',
        'Obra',
        'FechaFianza',
        'CostoFianza',
        'FechaAsignacion',
        'Estado',
        'Comentarios',
        'MontoRenovacionFianza',
        'LineaID'

    ];

    protected $casts = [
        'FechaFianza' => 'date', // Laravel lo manejará como una fecha automáticamente
    ];

    public function empleados()
    {
        return $this->belongsTo(\App\Models\Empleados::class, 'EmpleadoID');
    }

    public function obras()
    {
        return $this->belongsTo(Obras::class, 'ObraID');
    }

    public function gerenciaid()
    {
        return $this->belongsTo(\App\Models\Gerencia::class, 'GerenciaEquipoID');
    }

    public function lineas()
    {
        return $this->belongsTo(LineasTelefonicas::class, 'LineaID');
    }
}
