<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('app_settings', 'user_latest_version')) {
                $table->string('user_latest_version')->nullable()->after('user_app_store');
            }

            if (!Schema::hasColumn('app_settings', 'user_force_update')) {
                $table->boolean('user_force_update')->default(false)->after('user_latest_version');
            }

            if (!Schema::hasColumn('app_settings', 'vendor_latest_version')) {
                $table->string('vendor_latest_version')->nullable()->after('vendor_app_store');
            }

            if (!Schema::hasColumn('app_settings', 'vendor_force_update')) {
                $table->boolean('vendor_force_update')->default(false)->after('vendor_latest_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            foreach ([
                'user_latest_version',
                'user_force_update',
                'vendor_latest_version',
                'vendor_force_update',
            ] as $column) {
                if (Schema::hasColumn('app_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
