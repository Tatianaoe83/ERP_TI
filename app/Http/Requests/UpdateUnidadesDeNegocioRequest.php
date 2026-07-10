<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\UnidadesDeNegocio;

class UpdateUnidadesDeNegocioRequest extends FormRequest
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
        $id = $this->route('unidadesDeNegocio');

        return [
            'NombreEmpresa' => 'required|string|max:100',
            'RFC'           => 'required|string|max:13|unique:unidadesdenegocio,RFC,' . $id . ',UnidadNegocioID',
            'Direccion'     => 'required|string|max:150',
            'NumTelefono'   => 'required|string|max:10|unique:unidadesdenegocio,NumTelefono,' . $id . ',UnidadNegocioID',
            'estado'        => 'boolean',
        ];
    }
}
