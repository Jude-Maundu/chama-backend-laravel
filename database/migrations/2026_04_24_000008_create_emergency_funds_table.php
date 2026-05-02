<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->decimal('total_balance', 18, 2)->default(0);
            $table->decimal('minimum_balance', 18, 2)->default(0);
            $table->text('purpose')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            $table->unique('chama_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_funds');
    }
};
