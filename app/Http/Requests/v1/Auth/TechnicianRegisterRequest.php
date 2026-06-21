<?php

namespace App\Http\Requests\v1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TechnicianRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone|unique:blacklisted_phones,phone|regex:/^01[0125][0-9]{8}$/',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'city_id' => 'required|exists:cities,id',
            'service_category_id' => 'required|exists:service_categories,id',
            'years_of_experience' => 'nullable|integer|min:0|max:50',
            'bio' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be a valid Egyptian number',
            'phone.unique' => 'This phone number is already registered or has been blocked from registering.',
            'service_category_id.exists' => 'Selected service category is invalid',
        ];
    }
}
