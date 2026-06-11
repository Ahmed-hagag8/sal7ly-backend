<?php

namespace App\Http\Requests\v1\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:service_categories,id',
            'description' => 'required|string|max:2000',
            'address' => 'required|string|max:500',
            'city_id' => 'required|exists:cities,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'preferred_date' => 'nullable|date|after:today',
            'preferred_time' => 'nullable|date_format:H:i',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg',
        ];
    }

    public function messages(): array
    {
        return [
            'images.max' => 'You can upload maximum 5 images',
            'preferred_date.after' => 'Preferred date must be in the future',
        ];
    }
}
