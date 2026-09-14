<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'document_type_id'    => $this->document_type_id,
            'document_type_name'  => $this->documentType?->name,
            'document_number'     => $this->document_number,
            'name'                => $this->name,
            'last_name_father'    => $this->last_name_father,
            'last_name_mother'    => $this->last_name_mother,
            'gender_id'           => $this->gender_id,
            'gender_name'         => $this->gender?->name,
            'email'               => $this->email,
            'email_verified_at'   => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'rol_nombre'          => $this->roles->first()?->name,
            'is_active'           => $this->is_active,
            'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'          => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}