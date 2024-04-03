<?php

namespace App\Http\Requests;

use App\Rules\Adult;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'unique:users,email'],
            'password' =>  ['required', 'string', 'min:8'],
            'phone_number' => ['nullable', 'string', 'unique:users,phone'],
            'date_of_birth' => ['required', 'date', new Adult()]
        ];
    }
}
