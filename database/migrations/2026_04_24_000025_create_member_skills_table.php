<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->string('skill_name');
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->text('description')->nullable();
            $table->text('experience')->nullable();
            $table->boolean('willing_to_share')->default(false);
            $table->integer('endorsements')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_skills');
    }
};
