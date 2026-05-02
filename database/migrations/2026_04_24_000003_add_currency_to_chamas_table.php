<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chamas', function (Blueprint $table) {
            $table->foreignId('primary_currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->decimal('default_contribution_amount', 18, 2)->nullable();
            $table->boolean('allow_multiple_currencies')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('chamas', function (Blueprint $table) {
            $table->dropForeign(['primary_currency_id']);
            $table->dropColumn(['primary_currency_id', 'default_contribution_amount', 'allow_multiple_currencies']);
        });
    }
};
