<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class InventarioInsumo extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;


    public $table = 'inventarioinsumo';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';





    protected $primaryKey = 'InventarioID';
    protected $keyType = 'int'; 

    public $fillable = [
        'EmpleadoID',
        'InsumoID',
        'CateogoriaInsumo',
        'NombreInsumo',
        'CostoMensual',
        'CostoAnual',
        'FrecuenciaDePago',
        'Observaciones',
        'FechaAsignacion',
        'NumSerie',
        'Comentarios',
        'MesDePago'
    ];

  

}
