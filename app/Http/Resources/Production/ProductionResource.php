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
            'unit_price'                => $this->unit_price,
            'purchase_order_number'     => $this->purchase_order_number,
            'production_order_number'   => $this->production_order_number,
            'purchase_order_id'         => $this->purchase_order_id,
            'is_active'                 => $this->is_active,
            'colors'                    => $this->colors->map(fn($c) => ['id' => $c->id, 'name' => $c->name]),
            'guides'                    => $this->guides->map(fn($g) => ['id' => $g->id, 'guide_number' => $g->guide_number, 'issue_date' => $g->issue_date ? \Carbon\Carbon::parse($g->issue_date)->format('Y-m-d') : null]),
            'created_at'                => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'                => $this->updated_at?->format('Y-m-d H:i:s'),
            'production_date'           => $this->created_at?->format('Y-m-d'),
        ];
    }
}
