<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $languages = ['en', 'te', 'hi', 'ur'];

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

        if (Schema::hasColumn('pages', 'language')) {
            $this->mergeLanguageRowsIntoSinglePageRows();
            $this->dropLegacyLanguageIndexes();

            Schema::table('pages', function (Blueprint $table) {
                if (Schema::hasColumn('pages', 'language')) {
                    $table->dropColumn('language');
                }
            });
        } else {
            DB::table('pages')
                ->whereNull('title_en')
                ->update(['title_en' => DB::raw('title')]);

            DB::table('pages')
                ->whereNull('content_en')
                ->update(['content_en' => DB::raw('content')]);
        }

        $this->ensureSlugIsUnique();
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            foreach ([
                'title_en',
                'title_te',
                'title_hi',
                'title_ur',
                'content_en',
                'content_te',
                'content_hi',
                'content_ur',
            ] as $column) {
                if (Schema::hasColumn('pages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function mergeLanguageRowsIntoSinglePageRows(): void
    {
        $groups = DB::table('pages')->orderBy('id')->get()->groupBy('slug');

        foreach ($groups as $slug => $rows) {
            $base = $rows->firstWhere('language', 'en') ?: $rows->first();
            $payload = [
                'title' => $base->title,
                'content' => $base->content,
                'title_en' => $base->title,
                'content_en' => $base->content,
            ];

            foreach ($rows as $row) {
                $language = in_array($row->language, $this->languages, true) ? $row->language : 'en';
                $payload["title_{$language}"] = $row->title;
                $payload["content_{$language}"] = $row->content;
            }

            DB::table('pages')->where('id', $base->id)->update($payload);
            DB::table('pages')
                ->where('slug', $slug)
                ->where('id', '!=', $base->id)
                ->delete();
        }
    }

    private function dropLegacyLanguageIndexes(): void
    {
        try {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropUnique('pages_slug_language_unique');
            });
        } catch (Throwable $e) {
            //
        }

        try {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropIndex(['slug', 'language']);
            });
        } catch (Throwable $e) {
            //
        }
    }

    private function ensureSlugIsUnique(): void
    {
        try {
            Schema::table('pages', function (Blueprint $table) {
                $table->unique('slug');
            });
        } catch (Throwable $e) {
            // The original pages table already has this unique index in most installs.
        }
    }
};
