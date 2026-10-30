<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_date')) {
                $table->date('delivery_date')->nullable()->after('delivery_time');
            }

            if (!Schema::hasColumn('orders', 'delivery_clock_time')) {
                $table->time('delivery_clock_time')->nullable()->after('delivery_date');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'user_id')) {
                $table->foreignId('user_id')->nullable()->change();
            }

            if (!Schema::hasColumn('notifications', 'image_path')) {
                $table->string('image_path')->nullable()->after('body');
            }

            if (!Schema::hasColumn('notifications', 'target_type')) {
                $table->string('target_type')->nullable()->after('reference_id');
            }

            if (!Schema::hasColumn('notifications', 'send_mode')) {
                $table->string('send_mode')->nullable()->after('target_type');
            }

            if (!Schema::hasColumn('notifications', 'status')) {
                $table->string('status')->default('sent')->after('send_mode');
            }

            if (!Schema::hasColumn('notifications', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('notifications', 'sent_count')) {
                $table->unsignedInteger('sent_count')->default(0)->after('scheduled_at');
            }

            if (!Schema::hasColumn('notifications', 'failed_count')) {
                $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
            }

            if (!Schema::hasColumn('notifications', 'opened_count')) {
                $table->unsignedInteger('opened_count')->default(0)->after('failed_count');
            }
        });

        Schema::create('notification_receivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('fcm_token')->nullable();
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('notification_receivers');

        Schema::table('notifications', function (Blueprint $table) {
            foreach ([
                'image_path',
                'target_type',
                'send_mode',
                'status',
                'scheduled_at',
                'sent_count',
                'failed_count',
                'opened_count',
            ] as $column) {
                if (Schema::hasColumn('notifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_clock_time')) {
                $table->dropColumn('delivery_clock_time');
            }

            if (Schema::hasColumn('orders', 'delivery_date')) {
                $table->dropColumn('delivery_date');
            }
        });
    }
};
