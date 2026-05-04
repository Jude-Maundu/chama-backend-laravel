<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up(): void
    {
        Schema::create('emergency_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_fund_id')->constrained('emergency_funds')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->decimal('amount', 18, 2);
            $table->string('reason');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_withdrawals');
    }
};
