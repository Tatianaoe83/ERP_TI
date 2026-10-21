<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PresupuestoInterno extends Model
{
    use SoftDeletes;

    public $table = 'presupuestos_internos';

    protected $primaryKey = 'PresupuestoInternoID';

    public $fillable = [
        'Nombre',
    ];

    protected $casts = [
        'PresupuestoInternoID' => 'integer',
        'Nombre' => 'string',
    ];

    public function empleados()
    {
        return $this->hasMany(Empleados::class, 'PresupuestoInternoID', 'PresupuestoInternoID');
    }

    public static function visiblesPara(?int $userId)
    {
        $asignados = DB::table('presupuesto_interno_usuarios')
            ->where('users_id', $userId)
            ->pluck('PresupuestoInternoID');

        return static::query()
            ->when($asignados->isNotEmpty(), fn ($q) => $q->whereIn('PresupuestoInternoID', $asignados))
            ->orderBy('Nombre')
            ->get(['PresupuestoInternoID', 'Nombre']);
    }
}
