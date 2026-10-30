<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('reminders', 'last_sent_at')) {
                $table->timestamp('last_sent_at')->nullable()->after('timezone');
            }

            if (!Schema::hasColumn('reminders', 'last_sent_key')) {
                $table->string('last_sent_key', 32)->nullable()->after('last_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            if (Schema::hasColumn('reminders', 'last_sent_key')) {
                $table->dropColumn('last_sent_key');
            }

            if (Schema::hasColumn('reminders', 'last_sent_at')) {
                $table->dropColumn('last_sent_at');
            }
        });
    }
};
