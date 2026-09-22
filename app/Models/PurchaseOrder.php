<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

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
