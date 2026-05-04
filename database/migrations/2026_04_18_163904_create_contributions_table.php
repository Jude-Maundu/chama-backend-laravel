<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up()
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('penalty', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_method', ['mpesa', 'cash', 'bank_transfer', 'cheque']);
            $table->string('transaction_id')->nullable()->unique();
            $table->string('mpesa_receipt')->nullable();
            $table->date('payment_date');
            $table->date('due_date');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('receipt_number')->unique()->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index(['user_id', 'payment_date']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contributions');
    }
};