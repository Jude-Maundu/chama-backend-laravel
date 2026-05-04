<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->foreignId('to_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('rate', 18, 8);
            $table->timestamp('fetched_at')->nullable();
            $table->string('source')->default('cbk'); // Central Bank, API, etc.
            $table->timestamps();
            $table->unique(['from_currency_id', 'to_currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
