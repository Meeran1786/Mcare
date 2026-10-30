<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('label')->nullable(); // Home, Work
            $table->string('name');
            $table->string('phone');

            $table->text('address_line');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();

            $table->decimal('latitude',10,7);
            $table->decimal('longitude',10,7);

            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('addresses');
    }
};
