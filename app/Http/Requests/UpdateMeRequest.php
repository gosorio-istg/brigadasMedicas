<?php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'apellido' => ['sometimes', 'required', 'string', 'max:150'],
            'cedula' => ['sometimes', 'required', 'string', 'size:10', new CedulaEcuatoriana, Rule::unique('users', 'cedula')->ignore($userId)],
            'email' => ['sometimes', 'required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }
}
