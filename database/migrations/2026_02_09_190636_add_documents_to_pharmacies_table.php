<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->string('license_file')->nullable()->after('license_number');
            $table->string('gst_file')->nullable()->after('gst_number');
            $table->string('verification_status')
                  ->default('pending')
                  ->after('is_delivery_available');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->dropColumn([
                'license_file',
                'gst_file',
                'verification_status',
            ]);
        });
    }
};
