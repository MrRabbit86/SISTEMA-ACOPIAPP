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
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ci' => ['nullable', 'required_if:rol,ciudadano', 'string', 'max:15', 'unique:ciudadanos,ci'],
            'zona' => ['nullable', 'string', 'max:150'],
            'nit' => ['nullable', 'required_if:rol,empresa', 'string', 'max:20', 'unique:empresas_acopiadoras,nit'],
            'razon_social' => ['nullable', 'required_if:rol,empresa', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'ci.required_if' => 'El carnet de identidad es obligatorio para ciudadanos.',
            'nit.required_if' => 'El NIT es obligatorio para empresas.',
            'razon_social.required_if' => 'La razón social es obligatoria para empresas.',
            'rol.in' => 'El rol debe ser ciudadano o empresa.',
        ];
    }
}