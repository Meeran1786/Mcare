<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            if (!Schema::hasColumn('faqs', 'question_en')) {
                $table->string('question_en')->nullable()->after('question');
            }
            if (!Schema::hasColumn('faqs', 'question_te')) {
                $table->string('question_te')->nullable()->after('question_en');
            }
            if (!Schema::hasColumn('faqs', 'question_hi')) {
                $table->string('question_hi')->nullable()->after('question_te');
            }
            if (!Schema::hasColumn('faqs', 'question_ur')) {
                $table->string('question_ur')->nullable()->after('question_hi');
            }
            if (!Schema::hasColumn('faqs', 'answer_en')) {
                $table->text('answer_en')->nullable()->after('answer');
            }
            if (!Schema::hasColumn('faqs', 'answer_te')) {
                $table->text('answer_te')->nullable()->after('answer_en');
            }
            if (!Schema::hasColumn('faqs', 'answer_hi')) {
                $table->text('answer_hi')->nullable()->after('answer_te');
            }
            if (!Schema::hasColumn('faqs', 'answer_ur')) {
                $table->text('answer_ur')->nullable()->after('answer_hi');
            }
        });

        DB::table('faqs')
            ->whereNull('question_en')
            ->update(['question_en' => DB::raw('question')]);

        DB::table('faqs')
            ->whereNull('answer_en')
            ->update(['answer_en' => DB::raw('answer')]);
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropColumn([
                'question_en',
                'question_te',
                'question_hi',
                'question_ur',
                'answer_en',
                'answer_te',
                'answer_hi',
                'answer_ur',
            ]);
        });
    }
};
