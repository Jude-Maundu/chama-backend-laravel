<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_request_id')->unique();
            $table->string('checkout_request_id')->unique();
            $table->string('mpesa_receipt_number')->nullable()->unique();
            $table->decimal('amount', 10, 2);
            $table->string('phone_number');
            $table->enum('transaction_type', ['stkpush', 'b2c', 'c2b', 'balance_check']);
            $table->enum('status', ['pending', 'completed', 'failed', 'timeout', 'cancelled']);
            $table->string('result_code')->nullable();
            $table->string('result_description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('reference_id')->nullable(); // Contribution ID or Loan ID
            $table->string('reference_type')->nullable();
            $table->text('callback_data')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('phone_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};