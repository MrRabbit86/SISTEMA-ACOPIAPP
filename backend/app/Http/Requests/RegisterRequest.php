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
            'telefono' => ['required', 'string', 'regex:/^\d{7,15}$/'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ci' => ['nullable', 'required_if:rol,ciudadano', 'string', 'regex:/^\d{5,12}$/', 'unique:ciudadanos,ci'],
            'zona' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'ci.required_if' => 'El carnet de identidad es obligatorio para ciudadanos.',
            'ci.regex' => 'El carnet de identidad debe contener solo dígitos (5 a 12 números, sin letras ni espacios).',
            'telefono.required' => 'El número de teléfono es obligatorio.',
            'telefono.regex' => 'El teléfono debe contener solo dígitos (7 a 15 números, sin letras ni espacios).',
            'rol.in' => 'El rol debe ser ciudadano o empresa.',
        ];
    }
}