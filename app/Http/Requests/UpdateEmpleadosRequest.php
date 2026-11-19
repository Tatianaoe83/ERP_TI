<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Empleados;

class UpdateEmpleadosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return Empleados::rulesFor(
            $this->input('tipo_persona'),
            (int) $this->route('empleado'),
            $this->input('Estado')
        );
    }

    protected function prepareForValidation()
    {
        if ($this->input('Estado') === null || $this->input('Estado') === '') {
            $empleado = Empleados::find($this->route('empleado'));
            $estado = $empleado ? (int) ($empleado->getAttributes()['Estado'] ?? ($empleado->Estado ? 1 : 0)) : 1;
        } else {
            $estado = (int) $this->input('Estado');
        }

        $this->merge($this->datosEmpleado($estado));
    }

    private function datosEmpleado($estado)
    {
        $tipo = strtoupper(trim((string) $this->input('tipo_persona')));
        $soloNombre = in_array($tipo, ['EXTRAORDINARIO', 'REFERENCIADO'], true);
        $datos = [
            'Estado' => $estado,
            'tipo_persona' => $tipo !== '' ? $tipo : null,
        ];

        if ($soloNombre) {
            $datos['PuestoID'] = null;
            $datos['ObraID'] = null;
            $datos['CentroCostoID'] = null;
            $datos['JefeDirectoID'] = null;
            $datos['NumTelefono'] = null;
            $datos['Correo'] = null;

            return $datos;
        }

        $jefe = $this->input('JefeDirectoID');
        $telefono = $this->input('NumTelefono');
        $correo = $this->input('Correo');
        $centro = $this->input('CentroCostoID');
        $datos['CentroCostoID'] = ($centro === null || $centro === '') ? null : $centro;
        $datos['JefeDirectoID'] = ($jefe === null || $jefe === '') ? null : $jefe;
        $datos['NumTelefono'] = ($telefono === null || $telefono === '') ? null : $telefono;
        $datos['Correo'] = ($correo === null || $correo === '') ? null : $correo;

        return $datos;
    }

    public function messages()
    {
        return [
            'Correo.unique' => 'El correo ya está registrado en otro empleado activo.',
        ];
    }
}
