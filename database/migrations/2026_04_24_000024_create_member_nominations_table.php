<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up(): void
    {
        Schema::create('member_nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->foreignId('nominating_member_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('nominated_member_id')->constrained('users')->onDelete('cascade');
            $table->string('nomination_type'); // successor, beneficiary, etc.
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->decimal('share_percentage', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_nominations');
    }
};
