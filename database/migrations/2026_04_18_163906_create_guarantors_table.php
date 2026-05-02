<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('guarantor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount_guaranteed', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('guarantors');
    }
};