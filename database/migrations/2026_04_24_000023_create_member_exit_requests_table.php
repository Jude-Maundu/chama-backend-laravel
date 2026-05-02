<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_exit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('chama_id')->constrained('chamas')->onDelete('cascade');
            $table->string('reason')->nullable();
            $table->text('comments')->nullable();
            $table->enum('status', ['requested', 'approved', 'rejected', 'processing', 'completed'])->default('requested');
            $table->decimal('refund_amount', 18, 2)->default(0);
            $table->enum('refund_status', ['pending', 'processed', 'cancelled'])->default('pending');
            $table->date('exit_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_exit_requests');
    }
};
