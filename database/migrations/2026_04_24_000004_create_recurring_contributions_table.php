<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 18, 2);
            $table->enum('frequency', ['daily', 'weekly', 'bi_weekly', 'monthly', 'quarterly', 'yearly']);
            $table->enum('payment_method', ['mpesa', 'airtel_money', 'tigo_pesa', 'bank_transfer']);
            $table->enum('status', ['active', 'paused', 'cancelled', 'failed'])->default('active');
            $table->timestamp('start_date');
            $table->timestamp('next_due_date')->nullable();
            $table->timestamp('last_processed_at')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->text('failure_reason')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
            
            $table->unique(['chama_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_contributions');
    }
};
