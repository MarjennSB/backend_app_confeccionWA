<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductionColor extends Pivot
{
    protected $table = 'production_color';
    public $timestamps = false;
}
