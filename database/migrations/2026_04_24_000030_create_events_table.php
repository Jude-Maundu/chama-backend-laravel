<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('event_type', ['social', 'training', 'fundraiser', 'celebration', 'other'])->default('social');
            $table->datetime('start_date');
            $table->datetime('end_date')->nullable();
            $table->string('location')->nullable();
            $table->string('venue_url')->nullable(); // For virtual events
            $table->decimal('ticket_price', 18, 2)->default(0);
            $table->integer('max_attendees')->nullable();
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->json('images')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('agenda')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
