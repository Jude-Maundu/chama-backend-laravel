<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('loan_type', ['emergency', 'development', 'education', 'welfare']);
            $table->decimal('amount', 12, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('duration_months');
            $table->decimal('monthly_payment', 12, 2);
            $table->decimal('total_payable', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->decimal('penalty_amount', 10, 2)->default(0);
            $table->text('purpose');
            $table->enum('status', ['pending', 'approved', 'disbursed', 'rejected', 'completed', 'defaulted']);
            $table->date('application_date');
            $table->date('approval_date')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->date('first_payment_date')->nullable();
            $table->date('next_payment_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('mpesa_transaction_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};