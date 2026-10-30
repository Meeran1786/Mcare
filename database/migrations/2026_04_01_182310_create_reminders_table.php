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
        Schema::create('reminders', function (Blueprint $table) {

            $table->id();

            /* USER RELATION */
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            /* REMINDER TIME */
            $table->time('time');

            /* DAYS (JSON: ["Sun","Mon"]) */
            $table->json('days');

            /* ACTIVE / INACTIVE */
            $table->boolean('is_active')->default(true);

            /* OPTIONAL NOTE (e.g. After food) */
            $table->string('note')->nullable();

            /* OPTIONAL MEDICINE NAME */
            $table->string('medicine_name')->nullable();

            /* CREATED / UPDATED */
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};