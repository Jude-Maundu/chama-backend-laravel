<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->string('profession')->nullable();
            $table->string('company')->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->text('skills')->nullable(); // JSON array
            $table->text('interests')->nullable(); // JSON array
            $table->string('linkedin')->nullable();
            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->boolean('show_contact_info')->default(false);
            $table->boolean('show_profile_publicly')->default(false);
            $table->timestamps();
            
            $table->unique(['user_id', 'chama_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};
