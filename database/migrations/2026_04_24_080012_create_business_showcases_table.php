<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_showcases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('chama_id')->constrained()->onDelete('cascade');
            $table->string('business_name');
            $table->text('description');
            $table->string('industry');
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_info');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_showcases');
    }
};
