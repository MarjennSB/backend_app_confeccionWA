<?php

namespace App\Http\Resources\Guide;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'guide_number'  => $this->guide_number,
            'issue_date'    => $this->issue_date,
            'attached_file' => $this->attached_file ? url('storage/' . $this->attached_file) : null,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'    => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
