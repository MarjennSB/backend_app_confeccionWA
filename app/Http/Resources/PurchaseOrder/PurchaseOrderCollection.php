<?php

namespace App\Http\Resources\PurchaseOrder;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseOrderCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => PurchaseOrderResource::collection($this->collection),
        ];
    }
}
