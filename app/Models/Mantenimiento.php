<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'mantenimientos';

    protected $fillable = [
        'EmpleadoID',
        'InventarioID',
        'NombreEmpleado',
        'NombreGerencia',
        'TipoMantenimiento',
        'FechaMantenimiento',
        'Estatus',
        'RealizadoPor',
        'FechaRealizado',
    ];

    protected $casts = [
        'EmpleadoID' => 'integer',
        'InventarioID' => 'integer',
        'FechaMantenimiento' => 'date',
        'FechaRealizado' => 'datetime',
    ];
}
