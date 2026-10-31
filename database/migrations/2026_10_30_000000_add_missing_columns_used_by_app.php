<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds columns the code already writes to but no earlier migration created.
 * Without them, saving the FCM token after OTP sign-up, vendor pharmacy
 * registration and prescription upload all fail with "Unknown column".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token')->nullable()->after('preferred_language');
            }
        });

        Schema::table('pharmacies', function (Blueprint $table) {
            if (!Schema::hasColumn('pharmacies', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('pharmacies', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('license_number');
            }

            if (!Schema::hasColumn('pharmacies', 'landmark')) {
                $table->string('landmark')->nullable()->after('area');
            }

            if (!Schema::hasColumn('pharmacies', 'pharmacy_photo')) {
                $table->string('pharmacy_photo')->nullable()->after('gst_file');
            }
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('prescriptions', 'address_id')) {
                $table->foreignId('address_id')->nullable()->after('user_id')
                    ->constrained('addresses')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'address_id')) {
                $table->dropConstrainedForeignId('address_id');
            }
        });

        Schema::table('pharmacies', function (Blueprint $table) {
            foreach (['owner_name', 'registration_number', 'landmark', 'pharmacy_photo'] as $column) {
                if (Schema::hasColumn('pharmacies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });
    }
};
