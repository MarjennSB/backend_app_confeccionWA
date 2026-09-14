<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class InvoiceCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => InvoiceResource::collection($this->collection),
        ];
    }
}
