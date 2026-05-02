<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chama_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained()->onDelete('cascade');
            $table->foreignId('invited_by')->constrained('users');
            $table->string('email');
            $table->string('token')->unique();
            $table->enum('role', ['admin', 'treasurer', 'secretary', 'member'])->default('member');
            $table->enum('status', ['pending', 'accepted', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chama_invitations');
    }
};
