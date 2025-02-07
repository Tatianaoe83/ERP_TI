<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventarioInsumo extends Model
{
    use HasFactory;

    use SoftDeletes;


    public $table = 'inventarioinsumo';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


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
