<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('vote_choice');
            $table->decimal('vote_weight', 5, 2)->default(1); // For weighted voting
            $table->timestamp('voted_at');
            $table->timestamps();
            
            $table->unique(['poll_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
    }
};
