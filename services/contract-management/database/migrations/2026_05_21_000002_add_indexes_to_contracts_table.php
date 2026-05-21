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
        Schema::table('contracts', function (Blueprint $table) {
            $table->index('start_date');
            $table->index('end_date');
            $table->index('created_by');
            $table->index('bp_name');
            $table->unique('serial_number');
            $table->index(['status_id', 'end_date'], 'idx_contracts_status_end_date');
            $table->index(['supplier_id', 'status_id'], 'idx_contracts_supplier_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['start_date']);
            $table->dropIndex(['end_date']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['bp_name']);
            $table->dropUnique(['serial_number']);
            $table->dropIndex('idx_contracts_status_end_date');
            $table->dropIndex('idx_contracts_supplier_status');
        });
    }
};
