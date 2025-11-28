<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'sku' => $this->sku,
            'brand' => $this->brand,
            'fragrance_notes' => $this->fragrance_notes,
            'size' => $this->size,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'inventory' => new InventoryResource($this->whenLoaded('inventory')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'average_rating' => $this->when(
                $this->relationLoaded('reviews'),
                fn() => round((float) $this->reviews->avg('rating'), 2)
            ),
            'reviews_count' => $this->when(
                $this->relationLoaded('reviews'),
                fn() => $this->reviews->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

