<?php

/*
| Paid plans. Prices are in paise (₹1 = 100 paise) and are only ever shown on
| the website: the apps show which plan someone is on, never a price or a buy
| button, because Apple and Google do not allow outside payment for digital
| plans inside an app.
*/

return [

    // New accounts get the paid plan free for this many days. 0 turns trials off.
    'trial_days' => (int) env('PLAN_TRIAL_DAYS', 30),

    'plans' => [
        'family' => [
            'name' => 'Mcare Family',
            'role' => 'user',
            'tagline' => 'Peace of mind for the whole family',
            'prices' => [
                'monthly' => ['amount' => 12100, 'days' => 30, 'label' => 'per month'],
                'yearly' => ['amount' => 99900, 'days' => 365, 'label' => 'per year'],
            ],
            'features' => [
                'Up to 5 family members get missed-dose alerts',
                'Running-low and refill alerts sent to family too',
                'Weekly health report for family and the doctor',
                'Voice assistant in 12 Indian languages',
                'Priority delivery from partner pharmacies',
            ],
        ],
        'pharmacy' => [
            'name' => 'Mcare Pharmacy Premium',
            'role' => 'vendor',
            'tagline' => 'More customers, less paperwork',
            'prices' => [
                'monthly' => ['amount' => 49900, 'days' => 30, 'label' => 'per month'],
                'yearly' => ['amount' => 499900, 'days' => 365, 'label' => 'per year'],
            ],
            'features' => [
                'Listed first near patients, with a Verified badge',
                'First offer on refill orders from your customers',
                'Saved price lists and one-tap quotes',
                'Sales dashboard and customer list',
                'GST invoices for every order',
            ],
        ],
    ],

    // What free and paid accounts may do. Code asks PlanService::limit().
    'limits' => [
        'free' => ['family_members' => 1],
        'family' => ['family_members' => 5],
        'pharmacy' => [],
    ],

    // Shown on the website footer and policy pages. Razorpay checks these.
    'business' => [
        'name' => env('BUSINESS_NAME', 'Mcare'),
        'legal_name' => env('BUSINESS_LEGAL_NAME', env('BUSINESS_NAME', 'Mcare')),
        'email' => env('BUSINESS_EMAIL', 'support@maqsusi.care'),
        'phone' => env('BUSINESS_PHONE'),
        'address' => env('BUSINESS_ADDRESS'),
    ],
];
