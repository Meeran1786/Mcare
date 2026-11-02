<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orders: the pharmacy's price, which the patient approves before the
 * medicines are prepared.
 * reminders: how many tablets are left, so the patient is told before they
 * run out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('quote_amount', 10, 2)->nullable()->after('total_amount');
            $table->string('quote_note', 500)->nullable()->after('quote_amount');
            $table->string('quote_status', 10)->nullable()->after('quote_note'); // pending | approved | declined
            $table->timestamp('quoted_at')->nullable()->after('quote_status');
            $table->timestamp('quote_responded_at')->nullable()->after('quoted_at');
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->unsignedInteger('pills_left')->nullable()->after('medicine_name');
            $table->decimal('pills_per_dose', 5, 2)->default(1)->after('pills_left');
            $table->timestamp('pills_counted_at')->nullable()->after('pills_per_dose');
            $table->timestamp('refill_alerted_at')->nullable()->after('pills_counted_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['quote_amount', 'quote_note', 'quote_status', 'quoted_at', 'quote_responded_at']);
        });

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(['pills_left', 'pills_per_dose', 'pills_counted_at', 'refill_alerted_at']);
        });
    }
};
