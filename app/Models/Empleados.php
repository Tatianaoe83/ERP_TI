<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Eloquent as Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Contracts\Auditable;


/**
 * Class Empleados
 * @package App\Models
 * @version January 24, 2025, 9:39 pm UTC
 *
 * @property \App\Models\Obra $obraid
 * @property \App\Models\Puesto $puestoid
 * @property string $NombreEmpleado
 * @property integer $PuestoID
 * @property integer $ObraID
 * @property string $NumTelefono
 * @property string $Correo
 * @property boolean $Estado
 */
class Empleados extends Model implements Auditable
{
    use HasFactory, SoftDeletes;
    use \OwenIt\Auditing\Auditable;


    public $table = 'empleados';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    protected $dates = ['deleted_at'];


    protected $primaryKey = 'EmpleadoID';
    protected $keyType = 'int';

    public $fillable = [
        'NombreEmpleado',
        'PuestoID',
        'ObraID',
        'NumTelefono',
        'Correo',
        'Estado',
        'tipo_persona'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'EmpleadoID' => 'integer',
        'NombreEmpleado' => 'string',
        'PuestoID' => 'integer',
        'ObraID' => 'integer',
        'NumTelefono' => 'string',
        'Correo' => 'string',
        'Estado' => 'boolean',
        'tipo_persona' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'NombreEmpleado' => 'required|string|max:100',
        'PuestoID' => 'required|integer',
        'ObraID' => 'required|integer',
        'NumTelefono' => 'required|string|max:50',
        'Correo' => 'required|string|max:150',
        'Estado' => 'required|boolean',
        'tipo_persona' => 'required|in:FISICA,REFERENCIADO,EXTRAORDINARIO|required',
        'deleted_at' => 'nullable'
    ];

    public static function rulesFor(?string $tipoPersona = null, ?int $empleadoId = null, $estado = 1): array
    {
        $esFisica = strtoupper((string) $tipoPersona) === 'FISICA';
        $estaActivo = in_array($estado, [1, '1', true], true);

        $correoRules = $esFisica
            ? ['required', 'string', 'email', 'max:150']
            : ['nullable', 'string', 'email', 'max:150'];

        if ($estaActivo) {
            $uniqueCorreo = Rule::unique('empleados', 'Correo')
                ->where(fn ($query) => $query->where('Estado', 1)->whereNull('deleted_at'));

            if ($empleadoId) {
                $uniqueCorreo->ignore($empleadoId, 'EmpleadoID');
            }

            $correoRules[] = $uniqueCorreo;
        }

        return [
            'NombreEmpleado' => 'required|string|max:100',
            'PuestoID' => 'required|integer',
            'ObraID' => 'required|integer',
            'NumTelefono' => $esFisica
                ? 'required|string|regex:/^[0-9]{10}$/'
                : 'nullable|string|max:50',
            'Correo' => $correoRules,
            'Estado' => 'required|boolean',
            'tipo_persona' => 'required|in:FISICA,REFERENCIADO,EXTRAORDINARIO',
            'deleted_at' => 'nullable',
        ];
    }

    public static function correoUtilizable(?string $correo): bool
    {
        $correo = trim((string) $correo);

        return $correo !== '' && strtoupper($correo) !== 'N/A';
    }

    public static function correoEnUsoPorActivo(?string $correo, ?int $exceptEmpleadoId = null): bool
    {
        if (!self::correoUtilizable($correo)) {
            return false;
        }

        $query = static::query()
            ->where('Estado', 1)
            ->whereNull('deleted_at')
            ->where('Correo', trim($correo));

        if ($exceptEmpleadoId) {
            $query->where('EmpleadoID', '!=', $exceptEmpleadoId);
        }

        return $query->exists();
    }

    public static function puedeActivarse(Empleados $empleado): array
    {
        $esFisica = strtoupper((string) $empleado->tipo_persona) === 'FISICA';
        $correo = trim((string) $empleado->Correo);

        if ($esFisica && !self::correoUtilizable($correo)) {
            return [false, 'No se puede activar: el empleado físico debe tener un correo válido.'];
        }

        if (self::correoUtilizable($correo) && self::correoEnUsoPorActivo($correo, $empleado->EmpleadoID)) {
            return [false, 'No se puede activar: el correo ya está registrado en otro empleado activo.'];
        }

        return [true, ''];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function obras()
    {
        return $this->belongsTo(\App\Models\Obras::class, 'ObraID');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function puestos()
    {
        return $this->belongsTo(Puestos::class, 'PuestoID');
    }

    public function inventarioequipo()
    {
        return $this->hasMany(InventarioEquipo::class, 'EmpleadoID', 'EmpleadoID');
    }

    public function inventarioinsumo()
    {
        return $this->hasMany(InventarioInsumo::class, 'EmpleadoID', 'EmpleadoID');
    }

    public function inventariolineas()
    {
        return $this->hasMany(InventarioLineas::class, 'EmpleadoID', 'EmpleadoID');
    }

    public function departamentos()
    {
        return $this->hasOneThrough(
            Departamentos::class,
            Puestos::class,
            'PuestoID',
            'DepartamentoID',
            'PuestoID',
            'DepartamentoID',
        );
    }

    public function gerencia()
    {
        return $this->hasOneThrough(
            Gerencia::class,
            Departamentos::class,
            'DepartamentoID',
            'GerenciaID',
            'DepartamentoID',
            'GerenciaID',
        );
    }

    public function unidadesdenegocio()
    {
        return $this->hasOneThrough(
            UnidadesDeNegocio::class,
            Gerencia::class,
            'GerenciaID',
            'UnidadNegocioID',
            'GerenciaID',
            'UnidadNegocioID',
        );
    }
}
