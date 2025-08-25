<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Tertipos
 * @package App\Models
 * @version August 15, 2025, 5:54 pm UTC
 *
 * @property \Illuminate\Database\Eloquent\Collection $subtipos
 * @property string $NombreTertipo
 */
class Tertipos extends Model
{
    use SoftDeletes;


    public $table = 'tertipo';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];



    public $fillable = [
        'NombreTertipo'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'TertipoID' => 'integer',
        'NombreTertipo' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreTertipo' => 'required|string|max:100'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function subtipos()
    {
        return $this->hasMany(\App\Models\Subtipo::class, 'TertipoID');
    }
}
