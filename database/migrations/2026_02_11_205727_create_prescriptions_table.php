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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();

            // User who uploaded prescription
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Location of user when uploading
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Optional notes
            $table->text('notes')->nullable();

            // Overall status
            $table->enum('status', ['pending', 'sent', 'completed', 'cancelled'])
                ->default('pending');

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
