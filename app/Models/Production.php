<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $table = 'production';

    protected $fillable = [
        'user_id',
        'purchase_order_id',
        'purchase_order_number',
        'production_order_number',
        'quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function colors()
    {
        return $this->belongsToMany(Color::class, 'production_color')
                    ->using(ProductionColor::class);
    }

    public function guides()
    {
        return $this->belongsToMany(Guide::class, 'production_guide')
                    ->using(ProductionGuide::class);
    }
}
