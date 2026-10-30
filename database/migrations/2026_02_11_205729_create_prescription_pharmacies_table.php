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
        Schema::create('prescription_pharmacies', function (Blueprint $table) {
            $table->id();

            // Which prescription
            $table->foreignId('prescription_id')
                ->constrained()
                ->cascadeOnDelete();

            // Which pharmacy received it
            $table->foreignId('pharmacy_id')
                ->constrained()
                ->cascadeOnDelete();

            // Pharmacy response status
            $table->enum('status', [
                'sent',
                'viewed',
                'accepted',
                'rejected'
            ])->default('sent');

            // Optional rejection reason
            $table->string('rejection_reason')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_pharmacies');
    }
};
