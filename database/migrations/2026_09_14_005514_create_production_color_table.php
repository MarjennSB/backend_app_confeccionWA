<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_color', function (Blueprint $table) {
            $table->id();

            $table->foreignId('production_id')->constrained('production')->cascadeOnDelete();
            $table->foreignId('color_id')->constrained('colors')->cascadeOnDelete();

            $table->unique(['production_id', 'color_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_color');
    }
};
