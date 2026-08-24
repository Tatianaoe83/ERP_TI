<?php

namespace App\Models;

use App\Helpers\PresupuestoAsignacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;


class InventarioEquipo extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;



    public $table = 'inventarioequipo';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';




    protected $primaryKey = 'InventarioID';
    protected $keyType = 'int';

    public $fillable = [
        'EmpleadoID',
        'CategoriaEquipo',
        'GerenciaEquipoID',
        'Marca',
        'Caracteristicas',
        'Modelo',
        'Precio',
        'FechaAsignacion',
        'NumSerie',
        'Folio',
        'GerenciaEquipo',
        'Comentarios',
        'FechaDeCompra',
        'tipoEquipo',
        'MesDePago'
    ];

    protected $casts = [
        'tipoEquipo' => 'integer',
    ];

    /** Modalidades de la columna tipoEquipo (mismos valores que PresupuestoAsignacion). */
    public const TIPO_NO_PRESUPUESTADO = PresupuestoAsignacion::STOCK;
    public const TIPO_PRESUPUESTADO    = PresupuestoAsignacion::EXTRA;
    public const TIPO_COMPARTIDO       = PresupuestoAsignacion::COMPARTIDO;
    public const TIPO_PROPIO           = PresupuestoAsignacion::PROPIO;

    /**
     * Lo que se ve como inventario actual. El compartido también aparece aquí
     * porque es un solo registro que vive en las dos vistas; sólo el extra (1)
     * es proyección pura.
     */
    public const TIPOS_STOCK = [
        self::TIPO_NO_PRESUPUESTADO,
        self::TIPO_COMPARTIDO,
        self::TIPO_PROPIO,
    ];

    public function empleados()
    {
        return $this->belongsTo(\App\Models\Empleados::class, 'EmpleadoID');
    }

    public function gerencia()
    {
        return $this->belongsTo(Gerencia::class, 'GerenciaEquipoID', 'GerenciaID');
    }

   /*  public function gerenciaid()
    {
        return $this->belongsTo(Gerencia::class, 'GerenciaID');
    } */
}
