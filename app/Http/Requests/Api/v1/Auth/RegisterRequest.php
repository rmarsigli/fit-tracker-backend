<?php declare(strict_types=1);

namespace App\Http\Requests\Api\v1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nome é obrigatório',
            'username.required' => 'Nome de usuário é obrigatório',
            'username.unique' => 'Este nome de usuário já está em uso',
            'email.required' => 'E-mail é obrigatório',
            'email.email' => 'E-mail deve ser um endereço válido',
            'email.unique' => 'Este e-mail já está cadastrado',
            'password.required' => 'Senha é obrigatória',
            'password.confirmed' => 'As senhas não coincidem',
        ];
    }
}
