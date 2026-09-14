<?php

namespace App\Http\Resources\Color;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ColorCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => ColorResource::collection($this->collection),
        ];
    }
}
