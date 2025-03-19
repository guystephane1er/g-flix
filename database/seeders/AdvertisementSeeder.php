<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AdvertisementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ads = [
            // Video Advertisements
            [
                'type' => 'video',
                'status' => 'active',
                'title' => 'Premium Subscription Promo',
                'content_url' => 'https://ads.gflix.com/videos/premium-promo.mp4',
                'duration' => 30,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
                'views' => 0,
                'clicks' => 0,
            ],
            [
                'type' => 'video',
                'status' => 'active',
                'title' => 'Sports Package Offer',
                'content_url' => 'https://ads.gflix.com/videos/sports-promo.mp4',
                'duration' => 15,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(2),
                'views' => 0,
                'clicks' => 0,
            ],

            // Banner Advertisements
            [
                'type' => 'banner',
                'status' => 'active',
                'title' => 'Movie Marathon Weekend',
                'content_url' => 'https://ads.gflix.com/banners/movie-marathon.jpg',
                'duration' => null,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addWeeks(2),
                'views' => 0,
                'clicks' => 0,
            ],
            [
                'type' => 'banner',
                'status' => 'active',
                'title' => 'Kids Channel Special',
                'content_url' => 'https://ads.gflix.com/banners/kids-special.jpg',
                'duration' => null,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(1),
                'views' => 0,
                'clicks' => 0,
            ],

            // Sponsored Content
            [
                'type' => 'sponsored',
                'status' => 'active',
                'title' => 'Featured Documentary Series',
                'content_url' => 'https://ads.gflix.com/sponsored/documentary-series.jpg',
                'duration' => null,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addWeeks(3),
                'views' => 0,
                'clicks' => 0,
            ],
            [
                'type' => 'sponsored',
                'status' => 'active',
                'title' => 'Live Sports Event Coverage',
                'content_url' => 'https://ads.gflix.com/sponsored/sports-event.jpg',
                'duration' => null,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addWeeks(1),
                'views' => 0,
                'clicks' => 0,
            ],

            // Pre-roll Video Ads
            [
                'type' => 'video',
                'status' => 'active',
                'title' => 'New Series Preview',
                'content_url' => 'https://ads.gflix.com/videos/series-preview.mp4',
                'duration' => 20,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(1),
                'views' => 0,
                'clicks' => 0,
            ],
            [
                'type' => 'video',
                'status' => 'active',
                'title' => 'App Download Promotion',
                'content_url' => 'https://ads.gflix.com/videos/app-promo.mp4',
                'duration' => 15,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(2),
                'views' => 0,
                'clicks' => 0,
            ],

            // Mid-roll Banner Ads
            [
                'type' => 'banner',
                'status' => 'active',
                'title' => 'Subscribe Now Offer',
                'content_url' => 'https://ads.gflix.com/banners/subscribe-offer.jpg',
                'duration' => null,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(1),
                'views' => 0,
                'clicks' => 0,
            ],
            [
                'type' => 'banner',
                'status' => 'active',
                'title' => 'Premium Content Access',
                'content_url' => 'https://ads.gflix.com/banners/premium-content.jpg',
                'duration' => null,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addWeeks(6),
                'views' => 0,
                'clicks' => 0,
            ],

            // Upcoming Events Sponsored Content
            [
                'type' => 'sponsored',
                'status' => 'active',
                'title' => 'Upcoming Live Events',
                'content_url' => 'https://ads.gflix.com/sponsored/live-events.jpg',
                'duration' => null,
                'start_date' => Carbon::now()->addDays(7),
                'end_date' => Carbon::now()->addDays(14),
                'views' => 0,
                'clicks' => 0,
            ],
            [
                'type' => 'sponsored',
                'status' => 'active',
                'title' => 'Special Holiday Programming',
                'content_url' => 'https://ads.gflix.com/sponsored/holiday-special.jpg',
                'duration' => null,
                'start_date' => Carbon::now()->addWeeks(2),
                'end_date' => Carbon::now()->addWeeks(4),
                'views' => 0,
                'clicks' => 0,
            ],
        ];

        foreach ($ads as $ad) {
            Advertisement::create($ad);
        }
    }
}