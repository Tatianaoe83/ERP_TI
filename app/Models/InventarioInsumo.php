<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class InventarioInsumo extends Model
{
    use HasFactory;



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

  

}
