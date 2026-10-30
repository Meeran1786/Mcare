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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pharmacy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('delivery_lat', 10, 7);
            $table->decimal('delivery_lng', 10, 7);

            $table->enum('status', [
                'accepted',
                'preparing',
                'out_for_delivery',
                'delivered',
                'cancelled'
            ])->default('accepted');

            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->enum('payment_method', ['cod', 'qr'])->default('cod');

            $table->decimal('total_amount', 10, 2)->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
