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
            $this->merge(['Estado' => $empleado ? (int) ($empleado->getAttributes()['Estado'] ?? ($empleado->Estado ? 1 : 0)) : 1]);
        } else {
            $this->merge(['Estado' => (int) $this->input('Estado')]);
        }
    }

    public function messages()
    {
        return [
            'Correo.unique' => 'El correo ya está registrado en otro empleado activo.',
        ];
    }
}
