<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->index();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('faqs')->insert([
            [
                'type' => 'help',
                'question' => 'What are substitutes or generics?',
                'answer' => 'Substitutes or generics are medicines that contain the same drug salts and provide the same therapeutic effect as branded medicines.',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'help',
                'question' => 'How can I place an order?',
                'answer' => 'Upload your prescription, choose your address and patient details, then submit the order request from the app.',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'help',
                'question' => 'How long does delivery take?',
                'answer' => 'Delivery time depends on pharmacy confirmation and your location. You will receive updates after your order is accepted.',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'help',
                'question' => 'Can I cancel my order?',
                'answer' => 'You can cancel an order before it is processed or dispatched by the pharmacy.',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'payment',
                'question' => 'Will I have to pay now to complete my order?',
                'answer' => 'No. Currently the app supports cash on delivery, so you can place the order and pay when the medicines are delivered.',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'payment',
                'question' => 'How can I pay later?',
                'answer' => 'Select cash on delivery and place your order. Payment will be collected at the time of delivery.',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'payment',
                'question' => 'Will my delivery be delayed in case I pay late?',
                'answer' => 'For cash on delivery orders, delivery is not delayed by online payment confirmation. Please keep the payment ready when your order arrives.',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
