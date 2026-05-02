<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['shares', 'real_estate', 'fixed_deposit', 'business', 'saccco', 'treasury_bills']);
            $table->decimal('amount_invested', 12, 2);
            $table->decimal('current_value', 12, 2)->nullable();
            $table->decimal('expected_return_rate', 5, 2);
            $table->decimal('actual_return', 12, 2)->nullable();
            $table->date('investment_date');
            $table->date('maturity_date')->nullable();
            $table->text('description');
            $table->enum('status', ['active', 'matured', 'sold', 'loss']);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('investments');
    }
};