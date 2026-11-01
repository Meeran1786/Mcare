<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * dose_logs: one row per scheduled dose that was taken, skipped or missed.
 * caregiver_links: a family member who follows a patient's medicines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dose_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reminder_id')->constrained()->cascadeOnDelete();
            // The dose's planned time in the reminder's own time zone, "Y-m-d H:i".
            $table->string('scheduled_for', 16);
            $table->string('status', 10); // taken | skipped | missed
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('caregivers_notified_at')->nullable();
            $table->timestamps();

            $table->unique(['reminder_id', 'scheduled_for']);
            $table->index(['user_id', 'scheduled_for']);
        });

        Schema::create('caregiver_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('caregiver_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('invite_code', 6)->nullable()->unique();
            $table->timestamp('invite_expires_at')->nullable();
            $table->string('status', 10)->default('pending'); // pending | active
            $table->timestamps();

            $table->unique(['patient_id', 'caregiver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caregiver_links');
        Schema::dropIfExists('dose_logs');
    }
};
