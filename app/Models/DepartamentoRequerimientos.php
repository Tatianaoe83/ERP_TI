<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class DepartamentoRequerimientos
 * @package App\Models
 * @version February 4, 2026, 4:53 pm CST
 *
 * @property \App\Models\Departamento $departamentoid
 * @property string $categoria
 * @property string $nombre
 * @property integer $DepartamentoID
 * @property boolean $activo
 * @property boolean $seleccionado
 * @property boolean $realizado
 * @property boolean $opcional
 */
class DepartamentoRequerimientos extends Model
{
    public $table = 'departamento_requerimientos';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'categoria',
        'nombre',
        'DepartamentoID',
        'seleccionado',
        'realizado',
        'opcional'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'categoria' => 'string',
        'nombre' => 'string',
        'DepartamentoID' => 'integer',
        'seleccionado' => 'boolean',
        'realizado' => 'boolean',
        'opcional' => 'boolean'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'DepartamentoID');
    }

    public function scopeByDepartamentos($query, int $departamentoId)
    {
        return $query->where('DepartamentoID', $departamentoId);
    }

    public function scopeSeleccionados($query)
    {
        return $query->where('seleccionado', true);
    }

    public function scopeRealizados($query)
    {
        return $query->where('realizado', true);
    }

    public function scopeOpcionales($query)
    {
        return $query->where('opcional', true);
    }
}
