<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Subtipos
 * @package App\Models
 * @version August 15, 2025, 5:53 pm UTC
 *
 * @property \App\Models\Tertipo $tertipoid
 * @property \Illuminate\Database\Eloquent\Collection $tipotickets
 * @property string $NombreSubtipo
 * @property integer $TertipoID
 */
class Subtipos extends Model
{
    use SoftDeletes;


    public $table = 'subtipo';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'NombreSubtipo',
        'TertipoID'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'SubtipoID' => 'integer',
        'NombreSubtipo' => 'string',
        'TertipoID' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreSubtipo' => 'required|string|max:100',
        'TertipoID' => 'nullable|integer'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function tertipoid()
    {
        return $this->belongsTo(Tertipos::class, 'TertipoID', 'TertipoID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function tipotickets()
    {
        return $this->hasMany(\App\Models\Tipoticket::class, 'SubtipoID');
    }
}
