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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->nullable()->unique()->constrained('production')->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->decimal('total_amount', 10, 2); // Hasta 99 millones, con 2 decimales
            $table->string('currency', 3)->default('USD'); // 'USD' o 'PEN'
            $table->date('issue_date'); // Fecha de emisión
            $table->date('due_date')->nullable(); // Fecha de vencimiento
            $table->enum('payment_status', ['PENDIENTE', 'PAGADA', 'ANULADA'])->default('PENDIENTE');
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
        Schema::dropIfExists('invoices');
    }
};
