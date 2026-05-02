<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chama_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['admin', 'treasurer', 'secretary', 'member'])->default('member');
            $table->enum('status', ['pending', 'active', 'inactive', 'rejected'])->default('pending');
            $table->string('position')->nullable();
            $table->date('joined_at')->nullable();
            $table->date('exited_at')->nullable();
            $table->json('permissions')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique(['chama_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chama_members');
    }
};
