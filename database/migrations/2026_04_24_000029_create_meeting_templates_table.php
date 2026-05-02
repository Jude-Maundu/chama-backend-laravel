<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('template_type', ['agm', 'quarterly_review', 'emergency', 'regular', 'custom'])->default('regular');
            $table->json('agenda_items')->nullable();
            $table->json('required_attendees')->nullable();
            $table->integer('expected_duration')->default(60); // minutes
            $table->string('location_template')->nullable();
            $table->boolean('requires_voting')->default(false);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_templates');
    }
};
