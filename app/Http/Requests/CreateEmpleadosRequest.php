<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Empleados;

class CreateEmpleadosRequest extends FormRequest
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
            null,
            $this->input('Estado')
        );
    }

    protected function prepareForValidation()
    {
        $estado = $this->input('Estado');

        if ($estado === null || $estado === '') {
            $estado = 1;
        }

        $this->merge(['Estado' => (int) $estado]);
    }

    public function messages()
    {
        return [
            'Correo.unique' => 'El correo ya está registrado en otro empleado activo.',
        ];
    }
}
