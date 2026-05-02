<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petty_cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('balance', 18, 2)->default(0);
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->foreignId('custodian_id')->constrained('users')->onDelete('cascade');
            $table->decimal('float_amount', 18, 2)->default(0); // Starting cash
            $table->enum('status', ['active', 'inactive', 'under_reconciliation'])->default('active');
            $table->timestamps();
            
            $table->unique(['chama_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petty_cash_accounts');
    }
};
