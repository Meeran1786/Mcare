<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_medicines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pharmacy_id');
            $table->unsignedBigInteger('medicine_id');

            $table->decimal('price', 8, 2);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_available')->default(true);

            $table->timestamps();

            $table->unique(['pharmacy_id', 'medicine_id']);

            $table->foreign('pharmacy_id')->references('id')->on('pharmacies')->cascadeOnDelete();
            $table->foreign('medicine_id')->references('id')->on('medicines')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_medicines');
    }
};
