<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Settings
    |--------------------------------------------------------------------------
    |
    | These settings control user-related functionality and limitations
    |
    */
    'users' => [
        'max_devices' => env('MAX_DEVICES_PER_USER', 2),
        'trial_period_hours' => env('TRIAL_PERIOD_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Settings
    |--------------------------------------------------------------------------
    |
    | Define subscription types and their prices (in FCFA)
    |
    */
    'subscriptions' => [
        'yearly' => [
            'price' => env('YEARLY_SUBSCRIPTION_PRICE', 10000),
            'description' => 'Full channel access for one year',
            'duration_days' => 365,
        ],
        'daily' => [
            'price' => env('DAILY_SUBSCRIPTION_PRICE', 200),
            'description' => 'Ad-free access for one day',
            'duration_days' => 1,
        ],
        'premium_yearly' => [
            'price' => env('PREMIUM_YEARLY_PRICE', 15000),
            'description' => 'Premium yearly subscription with no ads',
            'duration_days' => 365,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Channel Categories
    |--------------------------------------------------------------------------
    |
    | Define available channel categories
    |
    */
    'channel_categories' => [
        'sports' => 'Sports',
        'movies' => 'Movies',
        'series' => 'TV Series',
        'news' => 'News',
        'kids' => 'Kids',
        'music' => 'Music',
        'documentary' => 'Documentary',
        'entertainment' => 'Entertainment',
    ],

    /*
    |--------------------------------------------------------------------------
    | Advertisement Settings
    |--------------------------------------------------------------------------
    |
    | Configure advertisement related settings
    |
    */
    'advertisements' => [
        'types' => [
            'video' => 'Video Advertisement',
            'banner' => 'Banner Advertisement',
            'sponsored' => 'Sponsored Content',
        ],
        'video_duration_seconds' => 30,
        'positions' => [
            'pre_roll' => 'Before content',
            'mid_roll' => 'During content',
            'post_roll' => 'After content',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | Configure payment related settings
    |
    */
    'payments' => [
        'methods' => [
            'apaym' => 'Apaym Payment Gateway',
            'mobile_money' => 'Mobile Money',
            'card' => 'Credit/Debit Card',
        ],
        'currencies' => [
            'default' => 'XOF',
            'symbol' => 'FCFA',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Referral Program Settings
    |--------------------------------------------------------------------------
    |
    | Configure referral program settings
    |
    */
    'referrals' => [
        'reward_days' => 30, // Number of free days for successful referral
        'code_length' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Configure API related settings
    |
    */
    'api' => [
        'pagination' => [
            'per_page' => 15,
            'max_per_page' => 100,
        ],
        'rate_limits' => [
            'public' => [
                'max_attempts' => 60,
                'decay_minutes' => 1,
            ],
            'authenticated' => [
                'max_attempts' => 120,
                'decay_minutes' => 1,
            ],
        ],
    ],
];