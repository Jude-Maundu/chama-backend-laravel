<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'decimal'])->default('string');
            $table->string('group_name')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        // Insert default settings
        DB::table('settings')->insert([
            ['key' => 'chama_name', 'value' => 'My Chama', 'type' => 'string', 'group_name' => 'general', 'description' => 'Chama organization name'],
            ['key' => 'chama_email', 'value' => 'info@chama.com', 'type' => 'string', 'group_name' => 'general', 'description' => 'Chama contact email'],
            ['key' => 'chama_phone', 'value' => '254700000000', 'type' => 'string', 'group_name' => 'general', 'description' => 'Chama contact phone'],
            ['key' => 'currency', 'value' => 'KES', 'type' => 'string', 'group_name' => 'general', 'description' => 'Currency code'],
            ['key' => 'monthly_contribution', 'value' => '5000', 'type' => 'decimal', 'group_name' => 'contributions', 'description' => 'Default monthly contribution amount'],
            ['key' => 'late_penalty_percentage', 'value' => '5', 'type' => 'decimal', 'group_name' => 'contributions', 'description' => 'Penalty percentage for late payments'],
            ['key' => 'penalty_grace_days', 'value' => '5', 'type' => 'integer', 'group_name' => 'contributions', 'description' => 'Days after due date before penalty applies'],
            ['key' => 'contribution_due_day', 'value' => '5', 'type' => 'integer', 'group_name' => 'contributions', 'description' => 'Day of month when contributions are due'],
            ['key' => 'loan_interest_rate', 'value' => '10', 'type' => 'decimal', 'group_name' => 'loans', 'description' => 'Default loan interest rate percentage'],
            ['key' => 'max_loan_ratio', 'value' => '3', 'type' => 'decimal', 'group_name' => 'loans', 'description' => 'Maximum loan amount as multiple of savings'],
            ['key' => 'min_loan_amount', 'value' => '1000', 'type' => 'decimal', 'group_name' => 'loans', 'description' => 'Minimum loan amount'],
            ['key' => 'max_loan_amount', 'value' => '500000', 'type' => 'decimal', 'group_name' => 'loans', 'description' => 'Maximum loan amount'],
            ['key' => 'max_active_loans', 'value' => '2', 'type' => 'integer', 'group_name' => 'loans', 'description' => 'Maximum active loans per member'],
            ['key' => 'min_membership_months', 'value' => '3', 'type' => 'integer', 'group_name' => 'loans', 'description' => 'Minimum months before loan eligibility'],
            ['key' => 'mpesa_shortcode', 'value' => '174379', 'type' => 'string', 'group_name' => 'mpesa', 'description' => 'M-Pesa Paybill shortcode'],
            ['key' => 'mpesa_consumer_key', 'value' => '', 'type' => 'string', 'group_name' => 'mpesa', 'description' => 'M-Pesa API consumer key'],
            ['key' => 'mpesa_consumer_secret', 'value' => '', 'type' => 'string', 'group_name' => 'mpesa', 'description' => 'M-Pesa API consumer secret'],
            ['key' => 'mpesa_environment', 'value' => 'sandbox', 'type' => 'string', 'group_name' => 'mpesa', 'description' => 'M-Pesa environment (sandbox/production)'],
            ['key' => 'contribution_reminder_days', 'value' => '3', 'type' => 'integer', 'group_name' => 'notifications', 'description' => 'Days before due date to send reminders'],
            ['key' => 'meeting_reminder_hours', 'value' => '24', 'type' => 'integer', 'group_name' => 'notifications', 'description' => 'Hours before meeting to send reminders'],
            ['key' => 'smtp_host', 'value' => '', 'type' => 'string', 'group_name' => 'email', 'description' => 'SMTP server host'],
            ['key' => 'smtp_port', 'value' => '587', 'type' => 'integer', 'group_name' => 'email', 'description' => 'SMTP server port'],
            ['key' => 'smtp_encryption', 'value' => 'tls', 'type' => 'string', 'group_name' => 'email', 'description' => 'SMTP encryption (tls/ssl)'],
            ['key' => 'fiscal_year_start', 'value' => 'January', 'type' => 'string', 'group_name' => 'general', 'description' => 'Start month of fiscal year'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
