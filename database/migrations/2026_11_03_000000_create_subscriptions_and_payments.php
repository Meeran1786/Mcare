<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per paid period or trial. The plan is active while now is
        // between starts_at and ends_at.
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 30);
            $table->string('kind', 20); // trial | monthly | yearly | granted
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->foreignId('payment_id')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'ends_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 30);
            $table->string('period', 20);
            $table->unsignedInteger('amount'); // paise
            $table->string('currency', 3)->default('INR');
            $table->string('razorpay_order_id')->unique();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('status', 20)->default('created'); // created | paid
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('payments');
    }
};
