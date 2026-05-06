<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up()
    {
        if (!Schema::hasTable('profiles')) {
            Schema::create('profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('national_id')->unique();
                $table->string('gender')->nullable();
                $table->date('dob')->nullable();
                $table->string('occupation')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('postal_code')->nullable();
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_phone')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_account_number')->nullable();
                $table->string('mpesa_number')->nullable();
                $table->date('join_date')->default(now());
                $table->date('exit_date')->nullable();
                $table->text('notes')->nullable();
                $table->string('profile_photo')->nullable();
                $table->string('id_document')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('profiles', 'user_id')) {
                    $table->foreignId('user_id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('profiles', 'national_id')) {
                    $table->string('national_id')->unique();
                }
                if (!Schema::hasColumn('profiles', 'gender')) {
                    $table->string('gender')->nullable();
                }
                if (!Schema::hasColumn('profiles', 'dob')) {
                    $table->date('dob')->nullable();
                }
                if (!Schema::hasColumn('profiles', 'join_date')) {
                    $table->date('join_date')->default(now());
                }
                if (!Schema::hasColumn('profiles', 'exit_date')) {
                    $table->date('exit_date')->nullable();
                }
                if (!Schema::hasColumn('profiles', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (!Schema::hasColumn('profiles', 'profile_photo')) {
                    $table->string('profile_photo')->nullable();
                }
                if (!Schema::hasColumn('profiles', 'id_document')) {
                    $table->string('id_document')->nullable();
                }
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained();
                $table->string('action');
                $table->string('table_name')->nullable();
                $table->unsignedBigInteger('record_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                $table->index(['table_name', 'record_id']);
                $table->index('created_at');
            });
        } else {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->constrained();
                }
                if (!Schema::hasColumn('audit_logs', 'action')) {
                    $table->string('action');
                }
                if (!Schema::hasColumn('audit_logs', 'table_name')) {
                    $table->string('table_name')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'record_id')) {
                    $table->unsignedBigInteger('record_id')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'old_values')) {
                    $table->json('old_values')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'new_values')) {
                    $table->json('new_values')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'ip_address')) {
                    $table->string('ip_address')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'user_agent')) {
                    $table->string('user_agent')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'created_at')) {
                    $table->timestamps();
                }
                if (!Schema::hasColumn('audit_logs', 'table_name') || !Schema::hasColumn('audit_logs', 'record_id')) {
                    $table->index(['table_name', 'record_id']);
                }
                if (!Schema::hasColumn('audit_logs', 'created_at')) {
                    $table->index('created_at');
                }
            });
        }
    }

    public function down()
    {
        // This migration is intended as schema repair for production.
        // Rolling it back may remove required columns from legacy tables, so it is intentionally left empty.
    }
};
