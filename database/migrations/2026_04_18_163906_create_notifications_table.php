<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->string('type')->nullable();
            $table->string('channel')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};