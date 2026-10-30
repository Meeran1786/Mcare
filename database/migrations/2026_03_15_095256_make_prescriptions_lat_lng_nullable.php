<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->decimal('latitude',10,7)->nullable()->change();
            $table->decimal('longitude',10,7)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->decimal('latitude',10,7)->nullable(false)->change();
            $table->decimal('longitude',10,7)->nullable(false)->change();
        });
    }
};

