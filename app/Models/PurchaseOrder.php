<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'order_number',
        'unit_price',
        'total_amount',
        'currency',
        'issue_date',
        'attached_file',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function productions()
    {
        return $this->hasMany(Production::class);
    }
}
