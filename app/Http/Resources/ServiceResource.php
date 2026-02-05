<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'category_id' => $this->service_category_id,
            'category_name' => $this->whenLoaded('category', fn() => $this->category->name),
        ];
    }
}
