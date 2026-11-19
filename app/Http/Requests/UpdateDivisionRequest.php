<?php

namespace App\Http\Requests;

use App\Models\Division;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDivisionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return Division::$rules;
    }
}
