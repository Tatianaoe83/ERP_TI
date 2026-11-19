<?php

namespace App\Http\Requests;

use App\Models\Direccion;
use Illuminate\Foundation\Http\FormRequest;

class CreateDireccionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return Direccion::$rules;
    }
}
