<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'           => 'The name field is required.',
            'email.required'          => 'The email field is required.',
            'email.unique'            => 'This email is already registered.',
            'password.required'       => 'The password field is required.',
            'password.min'            => 'The password must be at least 8 characters.',
            'password.confirmed'      => 'Password confirmation does not match.',
            'password.mixed_case'     => 'The password must contain at least one uppercase and one lowercase letter.',
            'password.numbers'        => 'The password must contain at least one number.',
            'password.symbols'        => 'The password must contain at least one special character.',
        ];
    }
}
