<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('agenda');
            $table->dateTime('meeting_date');
            $table->string('venue');
            $table->string('virtual_link')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->text('minutes')->nullable();
            $table->string('minutes_file')->nullable();
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled']);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index('meeting_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('meetings');
    }
};