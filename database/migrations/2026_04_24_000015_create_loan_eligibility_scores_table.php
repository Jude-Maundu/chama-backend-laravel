<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up(): void
    {
        Schema::create('loan_eligibility_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->integer('score')->default(0); // 0-100 score
            $table->enum('rating', ['poor', 'fair', 'good', 'excellent'])->default('fair');
            $table->decimal('recommended_loan_limit', 18, 2)->default(0);
            $table->decimal('recommended_interest_rate', 5, 2)->default(0); // Percentage
            $table->text('factors')->nullable(); // JSON array of scoring factors
            $table->string('risk_level')->default('medium'); // low, medium, high, very_high
            $table->integer('contribution_score')->default(0);
            $table->integer('repayment_score')->default(0);
            $table->integer('attendance_score')->default(0);
            $table->integer('communication_score')->default(0);
            $table->integer('default_history_score')->default(0);
            $table->timestamp('calculated_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'chama_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_eligibility_scores');
    }
};
