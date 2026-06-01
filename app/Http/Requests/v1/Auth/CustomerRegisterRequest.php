<?php

namespace App\Http\Requests\v1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone|regex:/^01[0125][0-9]{8}$/',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'city_id' => 'required|exists:cities,id',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be a valid Egyptian number (e.g., 01xxxxxxxxx)',
            'phone.unique' => 'This phone number is already registered',
            'city_id.exists' => 'Selected city is invalid',
        ];
    }
}
