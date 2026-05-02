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
        $tables = ['loans', 'contributions', 'meetings', 'transactions', 'dividends'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'chama_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('chama_id')->nullable()->constrained('chamas')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['loans', 'contributions', 'meetings', 'transactions', 'dividends'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'chama_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign([$table . '_chama_id_foreign']);
                    $table->dropColumn('chama_id');
                });
            }
        }
    }
};
