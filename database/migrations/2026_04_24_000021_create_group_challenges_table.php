<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('challenge_type', ['savings', 'attendance', 'contribution', 'custom'])->default('savings');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('target_amount', 18, 2)->nullable();
            $table->decimal('reward_amount', 18, 2)->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->json('participants')->nullable(); // Participant IDs and their progress
            $table->json('winners')->nullable(); // Winner IDs
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_challenges');
    }
};
