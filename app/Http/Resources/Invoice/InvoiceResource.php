<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'production_id'  => $this->production_id,
            'invoice_number' => $this->invoice_number,
            'total_amount'   => $this->total_amount,
            'currency'       => $this->currency,
            'issue_date'     => $this->issue_date,
            'due_date'       => $this->due_date,
            'payment_status' => $this->payment_status,
            'attached_file'  => $this->attached_file ? url('storage/' . $this->attached_file) : null,
            'is_active'      => $this->is_active,
            'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'     => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
