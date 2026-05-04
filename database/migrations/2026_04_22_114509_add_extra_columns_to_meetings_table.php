<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;


    public function up()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('type')->default('general')->after('title');
            $table->boolean('send_notifications')->default(true)->after('created_by');
            $table->boolean('require_rsvp')->default(false)->after('send_notifications');
        });
    }

    public function down()
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['type', 'send_notifications', 'require_rsvp']);
        });
    }
};