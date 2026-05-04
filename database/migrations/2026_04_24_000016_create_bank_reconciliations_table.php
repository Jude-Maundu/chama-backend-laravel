<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->enum('file_type', ['csv', 'pdf', 'xlsx'])->default('csv');
            $table->date('statement_date');
            $table->decimal('statement_balance', 18, 2);
            $table->decimal('system_balance', 18, 2);
            $table->decimal('variance', 18, 2)->default(0);
            $table->enum('status', ['uploaded', 'processing', 'matched', 'reviewed', 'approved'])->default('uploaded');
            $table->integer('total_transactions')->default(0);
            $table->integer('matched_transactions')->default(0);
            $table->integer('unmatched_transactions')->default(0);
            $table->json('discrepancies')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
