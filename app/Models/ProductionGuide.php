<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductionGuide extends Pivot
{
    protected $table = 'production_guide';
    public $timestamps = false;
}
