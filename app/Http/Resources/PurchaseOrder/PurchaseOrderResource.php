<?php

namespace App\Http\Resources\PurchaseOrder;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'order_number'  => $this->order_number,
            'unit_price'    => $this->unit_price,
            'issue_date'    => $this->issue_date,
            'attached_file' => $this->attached_file ? url('storage/' . $this->attached_file) : null,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'    => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
