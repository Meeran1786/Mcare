<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('pharmacy_name');
            $table->string('license_number');
            $table->string('gst_number')->nullable();

            $table->string('address_line');
            $table->string('area');
            $table->string('city');
            $table->string('state');
            $table->string('pincode', 10);

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();

            $table->boolean('is_verified')->default(false);
            $table->boolean('is_delivery_available')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
    }
};
