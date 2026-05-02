<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('target_amount', 18, 2);
            $table->decimal('current_amount', 18, 2)->default(0);
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->date('target_date');
            $table->enum('status', ['active', 'completed', 'cancelled', 'paused'])->default('active');
            $table->string('icon')->nullable(); // For visual representation
            $table->integer('priority')->default(0); // 0=low, 1=medium, 2=high
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_goals');
    }
};
