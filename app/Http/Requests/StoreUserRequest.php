<?php

namespace App\Http\Requests;

use App\Rules\CedulaEcuatoriana;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'apellido' => ['required', 'string', 'max:150'],
            'cedula' => ['required', 'string', 'size:10', new CedulaEcuatoriana, 'unique:users,cedula'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'activo' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
