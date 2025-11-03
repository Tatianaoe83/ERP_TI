<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\LineasTelefonicas;

class UpdateLineasTelefonicasRequest extends FormRequest
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
        $rules = LineasTelefonicas::$rules;
        
        return $rules;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Asegurar que Disponible siempre tenga un valor si no está presente
        if (!$this->has('Disponible')) {
            $this->merge(['Disponible' => 0]);
        }
    }
}
