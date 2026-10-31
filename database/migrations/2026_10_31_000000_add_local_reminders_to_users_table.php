<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The rebuilt Mcare+ app (2.0) rings medicine reminders as local phone
 * alarms. It sets this flag when it saves its push token, so the server
 * stops sending a second, duplicate reminder push to that user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'local_reminders')) {
                $table->boolean('local_reminders')->default(false)->after('fcm_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'local_reminders')) {
                $table->dropColumn('local_reminders');
            }
        });
    }
};
