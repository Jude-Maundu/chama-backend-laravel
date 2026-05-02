<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->enum('wallet_type', ['mpesa', 'airtel_money', 'tigo_pesa', 'internal']);
            $table->string('external_wallet_id')->nullable(); // M-Pesa ID, etc.
            $table->decimal('balance', 18, 2)->default(0);
            $table->boolean('is_primary')->default(false);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamps();
            
            $table->unique(['user_id', 'chama_id', 'wallet_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_wallets');
    }
};
