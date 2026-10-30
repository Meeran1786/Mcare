<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use App\Models\SocialLink;
use App\Models\User;

class SiteContentController extends Controller
{
    public function site()
    {
        return response()->json([
            'success' => true,
            'data' => $this->siteContent(),
        ]);
    }

    public function servicesPage()
    {
        $completedOrders = Order::whereIn('status', ['completed', 'delivered'])->count();
        $partners = User::where('role', 'vendor')->count();
        $members = User::where('role', 'user')->count();
        $services = Service::where('is_active', true)->orderBy('created_at')->get();
        $activeServices = $services->count();

        return response()->json([
            'success' => true,
            'data' => [
                'hero' => [
                    'eyebrow' => 'Our Services',
                    'title' => 'Mcare+ is your trusted health companion helping you stay on top of daily medications, manage refills, and care for your entire family, all from one simple app.',
                    'subtitle' => 'Built in India, for India.',
                ],
                'app_links' => $this->appLinks(),
                'services' => $activeServices
                    ? $services->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'icon' => 'pharmacy',
                            'title' => $service->title,
                            'description' => $service->description,
                            'image_url' => $service->image_url,
                        ];
                    })->values()
                    : $this->serviceCards(),
                'stats' => [
                    ['label' => 'Members supported', 'value' => $members, 'suffix' => '+'],
                    ['label' => 'Trusted partners', 'value' => $partners, 'suffix' => '+'],
                    ['label' => 'Orders completed', 'value' => $completedOrders, 'suffix' => '+'],
                    ['label' => 'Active services', 'value' => $activeServices, 'suffix' => '+'],
                ],
                'why_choose' => [
                    ['icon' => 'shield', 'title' => 'Reliable & Secure', 'description' => 'Your information is safe with us'],
                    ['icon' => 'heart', 'title' => 'Easy to Use', 'description' => 'Simple interface for all age groups'],
                    ['icon' => 'target', 'title' => 'All-in-One Health Solution', 'description' => 'Everything you need in one app'],
                    ['icon' => 'headset', 'title' => 'Dedicated Support', 'description' => 'We are here to help you always'],
                ],
                'cta' => [
                    'title' => 'Talk to Our Health Experts?',
                    'button_text' => 'Lets Get Started',
                    'url' => '/contact',
                ],
                'site' => $this->siteContent(),
            ],
        ]);
    }

    private function siteContent(): array
    {
        return [
            'footer' => [
                'about' => 'MCARE is a digital healthcare platform that makes medical services simple and accessible. We help users manage their health with easy doctor consultations, medicine delivery, and reliable care all in one place.',
                'menu' => [
                    ['label' => 'Home', 'url' => '/'],
                    ['label' => 'About Us', 'url' => '/about'],
                    ['label' => 'Services', 'url' => '/services'],
                    ['label' => 'Contact Us', 'url' => '/contact'],
                    ['label' => 'Privacy Policy', 'url' => '/pages/privacy-policy'],
                    ['label' => 'Terms & Conditions', 'url' => '/pages/terms-conditions'],
                ],
                'contact' => [
                    'address' => '16-2-664/1, Yonus Plaza, 2nd Floor, Government Printing Press Rd, Malakpet, Hyderabad, Telangana 500024',
                    'phone' => '9391035778',
                    'email' => 'Support.Mcare@maqsusi.care info.Mcare@maqsusi.care',
                ],
                'copyright' => 'MCare+. All rights reserved.',
            ],
            'social_links' => $this->socialLinks(),
            'app_links' => $this->appLinks(),
        ];
    }

    private function socialLinks()
    {
        $links = SocialLink::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['platform', 'label', 'url']);

        if ($links->isNotEmpty()) {
            return $links;
        }

        return [
            ['platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://www.facebook.com/'],
            ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://www.instagram.com/'],
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/'],
            ['platform' => 'x', 'label' => 'X', 'url' => 'https://x.com/'],
            ['platform' => 'whatsapp', 'label' => 'WhatsApp', 'url' => 'https://wa.me/919391035778'],
        ];
    }

    private function appLinks(): array
    {
        return [
            'play_store' => [
                'label' => 'Android app on Google Play',
                'url' => 'https://play.google.com/store',
            ],
            'app_store' => [
                'label' => 'Download on the App Store',
                'url' => 'https://www.apple.com/app-store/',
            ],
        ];
    }

    private function serviceCards(): array
    {
        return [
            [
                'icon' => 'bell',
                'title' => 'Smart Medication Reminders',
                'description' => 'Get personalised alerts for every dose morning, afternoon, and night. Stay consistent with timely app and WhatsApp notifications designed to match your daily routine. Never miss a tablet and keep your treatment on track with ease.',
            ],
            [
                'icon' => 'refill',
                'title' => 'Auto Refill & Prescription Management',
                'description' => 'Mcare+ tracks your medicine usage and ensures timely refills before stock runs out. It automatically connects with trusted pharmacies to place orders, helping you avoid last-minute stress and keeping your treatment uninterrupted at all times.',
            ],
            [
                'icon' => 'pharmacy',
                'title' => 'Trusted Pharmacy Connection',
                'description' => 'Order medicines from pharmacies you already trust with complete confidence. Mcare+ ensures quality, authenticity, and a smooth ordering experience, giving you peace of mind every time you purchase your medications.',
            ],
            [
                'icon' => 'chart',
                'title' => 'Health Insights & Adherence Reports',
                'description' => 'Monitor your weekly adherence score, track missed doses, and access detailed reports. Share these insights with your doctor to improve consultations and make better, more informed decisions about your health.',
            ],
            [
                'icon' => 'delivery',
                'title' => 'Medicine Delivery to Your Door',
                'description' => 'Get medicines delivered quickly from top Indian pharmacies through the app. Compare prices, check delivery times, and enjoy a fast, reliable service that ensures your medicines reach you without delay.',
            ],
            [
                'icon' => 'family',
                'title' => 'Care for Your Entire Family',
                'description' => 'Manage medications for your entire family from one account with ease. Add members, track their schedules, and ensure everyone stays healthy and follows their treatment plans without missing a dose.',
            ],
        ];
    }
}
