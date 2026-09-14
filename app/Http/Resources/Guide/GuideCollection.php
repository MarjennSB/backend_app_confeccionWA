<?php

namespace App\Http\Resources\Guide;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GuideCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => GuideResource::collection($this->collection),
        ];
    }
}
