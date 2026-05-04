<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('question');
            $table->text('description')->nullable();
            $table->enum('poll_type', ['yes_no', 'multiple_choice', 'weighted'])->default('yes_no');
            $table->json('options')->nullable(); // Options for multiple choice
            $table->boolean('is_anonymous')->default(true);
            $table->boolean('allow_multiple_votes')->default(false);
            $table->datetime('start_time');
            $table->datetime('end_time')->nullable();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->json('results')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};
