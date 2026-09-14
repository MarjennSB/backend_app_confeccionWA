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
        Schema::create('production_guide', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('production')->cascadeOnDelete();
            $table->foreignId('guide_id')->constrained('guides')->cascadeOnDelete();
            $table->unique(['production_id', 'guide_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_guide');
    }
};
