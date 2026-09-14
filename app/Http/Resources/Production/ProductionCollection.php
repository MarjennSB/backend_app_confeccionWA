<?php

namespace App\Http\Resources\Production;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductionCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => ProductionResource::collection($this->collection),
        ];
    }
}
