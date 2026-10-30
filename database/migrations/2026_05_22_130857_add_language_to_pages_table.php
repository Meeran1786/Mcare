<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            if (!Schema::hasColumn('pages', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
            }
            if (!Schema::hasColumn('pages', 'title_te')) {
                $table->string('title_te')->nullable()->after('title_en');
            }
            if (!Schema::hasColumn('pages', 'title_hi')) {
                $table->string('title_hi')->nullable()->after('title_te');
            }
            if (!Schema::hasColumn('pages', 'title_ur')) {
                $table->string('title_ur')->nullable()->after('title_hi');
            }
            if (!Schema::hasColumn('pages', 'content_en')) {
                $table->longText('content_en')->nullable()->after('content');
            }
            if (!Schema::hasColumn('pages', 'content_te')) {
                $table->longText('content_te')->nullable()->after('content_en');
            }
            if (!Schema::hasColumn('pages', 'content_hi')) {
                $table->longText('content_hi')->nullable()->after('content_te');
            }
            if (!Schema::hasColumn('pages', 'content_ur')) {
                $table->longText('content_ur')->nullable()->after('content_hi');
            }
        });

        DB::table('pages')
            ->whereNull('title_en')
            ->update(['title_en' => DB::raw('title')]);

        DB::table('pages')
            ->whereNull('content_en')
            ->update(['content_en' => DB::raw('content')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'title_en',
                'title_te',
                'title_hi',
                'title_ur',
                'content_en',
                'content_te',
                'content_hi',
                'content_ur',
            ]);
        });
    }
};
