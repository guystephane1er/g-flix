<?php

namespace Database\Seeders;

use App\Models\IptvChannel;
use Illuminate\Database\Seeder;

class IptvChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            // Sports Channels
            [
                'name' => 'Sports Plus',
                'category' => 'sports',
                'm3u8_link' => 'https://stream.gflix.com/sports-plus/index.m3u8',
                'status' => 'active',
                'description' => 'Live sports coverage including football, basketball, and more',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/sports-plus.jpg',
            ],
            [
                'name' => 'Football TV',
                'category' => 'sports',
                'm3u8_link' => 'https://stream.gflix.com/football-tv/index.m3u8',
                'status' => 'active',
                'description' => '24/7 football coverage from around the world',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/football-tv.jpg',
            ],

            // Movies Channels
            [
                'name' => 'Movie Central',
                'category' => 'movies',
                'm3u8_link' => 'https://stream.gflix.com/movie-central/index.m3u8',
                'status' => 'active',
                'description' => 'Latest blockbuster movies and classics',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/movie-central.jpg',
            ],
            [
                'name' => 'Action Max',
                'category' => 'movies',
                'm3u8_link' => 'https://stream.gflix.com/action-max/index.m3u8',
                'status' => 'active',
                'description' => 'Non-stop action movies 24/7',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/action-max.jpg',
            ],

            // Series Channels
            [
                'name' => 'Series Hub',
                'category' => 'series',
                'm3u8_link' => 'https://stream.gflix.com/series-hub/index.m3u8',
                'status' => 'active',
                'description' => 'Your favorite TV series all day',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/series-hub.jpg',
            ],
            [
                'name' => 'Drama Plus',
                'category' => 'series',
                'm3u8_link' => 'https://stream.gflix.com/drama-plus/index.m3u8',
                'status' => 'active',
                'description' => 'Best drama series from around the world',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/drama-plus.jpg',
            ],

            // News Channels
            [
                'name' => 'News 24',
                'category' => 'news',
                'm3u8_link' => 'https://stream.gflix.com/news-24/index.m3u8',
                'status' => 'active',
                'description' => '24/7 news coverage',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/news-24.jpg',
            ],
            [
                'name' => 'Business News',
                'category' => 'news',
                'm3u8_link' => 'https://stream.gflix.com/business-news/index.m3u8',
                'status' => 'active',
                'description' => 'Latest business and financial news',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/business-news.jpg',
            ],

            // Kids Channels
            [
                'name' => 'Kids World',
                'category' => 'kids',
                'm3u8_link' => 'https://stream.gflix.com/kids-world/index.m3u8',
                'status' => 'active',
                'description' => 'Entertainment for children of all ages',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/kids-world.jpg',
            ],
            [
                'name' => 'Cartoon Plus',
                'category' => 'kids',
                'm3u8_link' => 'https://stream.gflix.com/cartoon-plus/index.m3u8',
                'status' => 'active',
                'description' => 'Best cartoons and animated shows',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/cartoon-plus.jpg',
            ],

            // Music Channels
            [
                'name' => 'Music Box',
                'category' => 'music',
                'm3u8_link' => 'https://stream.gflix.com/music-box/index.m3u8',
                'status' => 'active',
                'description' => '24/7 music hits',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/music-box.jpg',
            ],
            [
                'name' => 'Hip Hop TV',
                'category' => 'music',
                'm3u8_link' => 'https://stream.gflix.com/hip-hop-tv/index.m3u8',
                'status' => 'active',
                'description' => 'Best hip hop and urban music',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/hip-hop-tv.jpg',
            ],

            // Documentary Channels
            [
                'name' => 'Discovery Plus',
                'category' => 'documentary',
                'm3u8_link' => 'https://stream.gflix.com/discovery-plus/index.m3u8',
                'status' => 'active',
                'description' => 'Educational and informative documentaries',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/discovery-plus.jpg',
            ],
            [
                'name' => 'Nature TV',
                'category' => 'documentary',
                'm3u8_link' => 'https://stream.gflix.com/nature-tv/index.m3u8',
                'status' => 'active',
                'description' => 'Nature and wildlife documentaries',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/nature-tv.jpg',
            ],

            // Entertainment Channels
            [
                'name' => 'Entertainment One',
                'category' => 'entertainment',
                'm3u8_link' => 'https://stream.gflix.com/entertainment-one/index.m3u8',
                'status' => 'active',
                'description' => 'General entertainment channel',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/entertainment-one.jpg',
            ],
            [
                'name' => 'Reality TV',
                'category' => 'entertainment',
                'm3u8_link' => 'https://stream.gflix.com/reality-tv/index.m3u8',
                'status' => 'active',
                'description' => 'Best reality shows 24/7',
                'thumbnail_url' => 'https://assets.gflix.com/thumbnails/reality-tv.jpg',
            ],
        ];

        foreach ($channels as $channel) {
            IptvChannel::create($channel);
        }
    }
}