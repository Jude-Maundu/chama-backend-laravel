<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up(): void
    {
        Schema::create('member_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->string('milestone_type'); // birthday, anniversary, achievement, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('milestone_date');
            $table->boolean('is_notified')->default(false);
            $table->string('badge_type')->nullable();
            $table->integer('points_earned')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_milestones');
    }
};
