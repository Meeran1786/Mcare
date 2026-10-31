<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'address_id')) {
                $table->foreignId('address_id')->nullable()->after('prescription_id')->constrained()->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'delivery_time')) {
                $table->dateTime('delivery_time')->nullable()->after('total_amount');
            }

            if (!Schema::hasColumn('orders', 'delivery_note')) {
                $table->text('delivery_note')->nullable()->after('delivery_time');
            }

            if (!Schema::hasColumn('orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('delivery_note');
            }
        });

        // MySQL-only syntax; skipped on SQLite so the test suite can run.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','accepted','preparing','ready_for_delivery','out_for_delivery','delivered','completed','cancelled','rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'address_id')) {
                $table->dropConstrainedForeignId('address_id');
            }

            if (Schema::hasColumn('orders', 'delivery_time')) {
                $table->dropColumn('delivery_time');
            }

            if (Schema::hasColumn('orders', 'delivery_note')) {
                $table->dropColumn('delivery_note');
            }

            if (Schema::hasColumn('orders', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
        });

        // MySQL-only syntax; skipped on SQLite so the test suite can run.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('accepted','preparing','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'accepted'");
        }
    }
};
