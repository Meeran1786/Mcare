<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE prescriptions MODIFY status ENUM('pending','sent','accepted','preparing','ready_for_delivery','out_for_delivery','delivered','completed','cancelled','rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE prescriptions MODIFY status ENUM('pending','sent','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
