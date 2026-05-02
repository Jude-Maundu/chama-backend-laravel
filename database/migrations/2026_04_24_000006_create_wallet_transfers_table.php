<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_wallet_id')->constrained('member_wallets')->onDelete('cascade');
            $table->foreignId('to_wallet_id')->constrained('member_wallets')->onDelete('cascade');
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->decimal('amount', 18, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transfers');
    }
};
