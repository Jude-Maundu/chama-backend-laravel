<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insurance_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('provider');
            $table->text('description');
            $table->decimal('premium_amount', 15, 2);
            $table->foreignId('currency_id')->constrained();
            $table->text('coverage_details');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_products');
    }
};
