<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_id')->constrained('referrals')->onDelete('cascade');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('converted_at')->nullable();
            $table->enum('status', ['pending', 'converted', 'expired'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_conversions');
    }
};
