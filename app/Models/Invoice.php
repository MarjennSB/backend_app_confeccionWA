<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'production_id',
        'invoice_number',
        'total_amount',
        'currency',
        'issue_date',
        'due_date',
        'payment_status',
        'attached_file',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }
}
