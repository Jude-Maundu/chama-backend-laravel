<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['present', 'absent', 'excused', 'late']);
            $table->time('arrival_time')->nullable();
            $table->text('excuse_reason')->nullable();
            $table->boolean('signed_minutes')->default(false);
            $table->timestamps();
            
            $table->unique(['meeting_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance');
    }
};