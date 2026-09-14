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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->decimal('unit_price', 10, 4); // Precio unitario que mencionas
            $table->date('issue_date')->nullable(); // Fecha de emisión (30/07/2026)
            $table->string('attached_file', 255)->nullable();
            $table->boolean('is_active')->default(true); // active - inactive
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
