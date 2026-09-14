<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function productions()
    {
        return $this->belongsToMany(Production::class, 'production_color')
                    ->using(ProductionColor::class);
    }
}
