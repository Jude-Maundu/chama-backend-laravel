<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['contribution', 'loan_disbursement', 'loan_repayment', 'dividend', 'penalty', 'refund', 'expense']);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('reference_type')->nullable(); // Contribution, Loan, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description');
            $table->date('transaction_date');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['user_id', 'transaction_date']);
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};