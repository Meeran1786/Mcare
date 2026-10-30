<?php

namespace Database\Seeders;

use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class SocialLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://www.facebook.com/', 'sort_order' => 1],
            ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://www.instagram.com/', 'sort_order' => 2],
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/', 'sort_order' => 3],
            ['platform' => 'x', 'label' => 'X', 'url' => 'https://x.com/', 'sort_order' => 4],
            ['platform' => 'whatsapp', 'label' => 'WhatsApp', 'url' => 'https://wa.me/919391035778', 'sort_order' => 5],
        ];

        foreach ($links as $link) {
            SocialLink::firstOrCreate(
                ['platform' => $link['platform']],
                $link + ['is_active' => true]
            );
        }
    }
}
