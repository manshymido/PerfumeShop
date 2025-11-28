<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'shipping_address_id' => $this->shipping_address_id,
            'shipping_address' => new ShippingAddressResource($this->whenLoaded('shippingAddress')),
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'shipping_cost' => (float) $this->shipping_cost,
            'total' => (float) $this->total,
            'status' => $this->status,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'tracking_number' => $this->tracking_number,
            'can_be_cancelled' => $this->canBeCancelled(),
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

