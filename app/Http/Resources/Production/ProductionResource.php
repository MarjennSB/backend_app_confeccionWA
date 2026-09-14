<?php

namespace App\Http\Resources\Production;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'user_id'                   => $this->user_id,
            'user_name'                 => $this->user?->name,
            'quantity'                  => $this->quantity,
            'purchase_order_number'     => $this->purchase_order_number,
            'production_order_number'   => $this->production_order_number,
            'purchase_order_id'         => $this->purchase_order_id,
            'is_active'                 => $this->is_active,
            'created_at'                => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'                => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
