<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'rol' => ['required', Rule::in([Rol::CIUDADANO, Rol::EMPRESA])],
            'telefono' => ['required', 'string', 'min:7', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ci' => ['nullable', 'required_if:rol,ciudadano', 'string', 'max:15', 'unique:ciudadanos,ci'],
            'zona' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'ci.required_if' => 'El carnet de identidad es obligatorio para ciudadanos.',
            'telefono.required' => 'El número de teléfono es obligatorio.',
            'rol.in' => 'El rol debe ser ciudadano o empresa.',
        ];
    }
}