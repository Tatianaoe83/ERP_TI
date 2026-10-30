<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudMovimiento extends Model
{
    protected $table = 'solicitudes_movimiento';

    protected $primaryKey = 'SolicitudMovimientoID';

    protected $dates = ['autorizado_en', 'aplicado_en'];

    public $fillable = [
        'EmpleadoID',
        'ObraID',
        'CargoID',
        'ResidenteControlID',
        'AutorizadorID',
        'AutorizadorRol',
        'Justificacion',
        'Respuesta',
        'Estatus',
        'Folio',
        'autorizado_en',
        'aplicado_en',
    ];

    public function solicitante()
    {
        return $this->belongsTo(Empleados::class, 'EmpleadoID', 'EmpleadoID');
    }

    public function obra()
    {
        return $this->belongsTo(Obras::class, 'ObraID', 'ObraID');
    }

    public function cargo()
    {
        return $this->belongsTo(CargoMovimiento::class, 'CargoID', 'CargoID');
    }

    public function residente()
    {
        return $this->belongsTo(Empleados::class, 'ResidenteControlID', 'EmpleadoID');
    }

    public function autorizador()
    {
        return $this->belongsTo(Empleados::class, 'AutorizadorID', 'EmpleadoID');
    }

    public function archivos()
    {
        return $this->hasMany(SolicitudMovimientoArchivo::class, 'SolicitudMovimientoID', 'SolicitudMovimientoID');
    }

    public function comentarios()
    {
        return $this->hasMany(SolicitudMovimientoComentario::class, 'SolicitudMovimientoID', 'SolicitudMovimientoID');
    }

    public function lineas()
    {
        return $this->hasMany(SolicitudMovimientoLinea::class, 'SolicitudMovimientoID', 'SolicitudMovimientoID')->orderBy('Orden');
    }
}
