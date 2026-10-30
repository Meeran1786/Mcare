<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-and-conditions',
                'content' => '<p>Please update these Terms & Conditions from the admin dashboard with your latest MCare service terms.</p>',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<p>Please update this Privacy Policy from the admin dashboard with your latest MCare privacy practices.</p>',
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
