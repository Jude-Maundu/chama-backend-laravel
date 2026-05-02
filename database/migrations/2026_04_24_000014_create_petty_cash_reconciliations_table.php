<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_cash_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_account_id')->constrained('petty_cash_accounts')->onDelete('cascade');
            $table->foreignId('reconciled_by')->constrained('users')->onDelete('cascade');
            $table->decimal('recorded_balance', 18, 2);
            $table->decimal('physical_count', 18, 2);
            $table->decimal('variance', 18, 2);
            $table->text('variance_explanation')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->date('reconciliation_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_reconciliations');
    }
};
