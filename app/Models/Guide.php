<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guide extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'guide_number',
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
        return $this->belongsToMany(Production::class, 'production_guide')
                    ->using(ProductionGuide::class);
    }
}
