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
        $tables = ['production', 'purchase_orders', 'invoices', 'guides', 'colors'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table_bp) {
                $table_bp->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['production', 'purchase_orders', 'invoices', 'guides', 'colors'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table_bp) {
                $table_bp->dropSoftDeletes();
            });
        }
    }
};
