<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->integer('installment_number');
            $table->decimal('due_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('principal_paid', 12, 2)->default(0);
            $table->decimal('interest_paid', 12, 2)->default(0);
            $table->decimal('penalty_paid', 10, 2)->default(0);
            $table->decimal('balance_after', 12, 2);
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue']);
            $table->string('transaction_id')->nullable();
            $table->enum('payment_method', ['mpesa', 'cash', 'bank'])->nullable();
            $table->timestamps();
            $table->index('loan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};