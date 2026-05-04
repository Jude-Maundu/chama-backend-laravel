<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up(): void
    {
        Schema::create('petty_cash_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_account_id')->constrained('petty_cash_accounts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('description');
            $table->decimal('amount', 18, 2);
            $table->enum('status', ['submitted', 'approved', 'rejected', 'paid'])->default('submitted');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_notes')->nullable();
            $table->string('receipt_path')->nullable();
            $table->json('receipt_files')->nullable(); // Multiple receipts
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_claims');
    }
};
