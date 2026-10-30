<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('reminders', 'timezone')) {
                $table->string('timezone')->default('Asia/Kolkata')->after('days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            if (Schema::hasColumn('reminders', 'timezone')) {
                $table->dropColumn('timezone');
            }
        });
    }
};
