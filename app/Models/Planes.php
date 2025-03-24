<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class Planes
 * @package App\Models
 * @version January 27, 2025, 6:42 pm UTC
 *
 * @property integer $CompaniaID
 * @property string $NombrePlan
 * @property number $PrecioPlan
 */
class Planes extends Model
{
    use HasFactory,SoftDeletes;


    public $table = 'planes';
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'ID';

    public $fillable = [
        'CompaniaID',
        'NombrePlan',
        'PrecioPlan'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'ID' => 'integer',
        'CompaniaID' => 'integer',
        'NombrePlan' => 'string',
        'PrecioPlan' => 'decimal:2'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'CompaniaID' => 'required|integer',
        'NombrePlan' => 'required|string|max:50',
        'PrecioPlan' => 'required|numeric'
    ];

    public function companiaslineastelefonicasid()
    {
        return $this->belongsTo(\App\Models\CompaniasLineasTelefonicas::class, 'CompaniaID');
    }


    
}
