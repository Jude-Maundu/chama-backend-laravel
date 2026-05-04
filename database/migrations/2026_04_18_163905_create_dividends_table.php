<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up()
    {
        Schema::create('dividends', function (Blueprint $table) {
            $table->id();
            $table->string('period'); // Q1-2024, 2024-Annual
            $table->decimal('total_amount', 12, 2);
            $table->decimal('total_shares', 12, 2);
            $table->decimal('per_share_amount', 10, 2);
            $table->date('calculation_date');
            $table->date('distribution_date')->nullable();
            $table->enum('status', ['calculated', 'approved', 'distributed', 'cancelled']);
            $table->foreignId('calculated_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dividends');
    }
};