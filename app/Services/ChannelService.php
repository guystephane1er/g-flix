<?php

namespace App\Services;

use App\Models\IptvChannel;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Pagination\LengthAwarePaginator;

class ChannelService
{
    protected SubscriptionService $subscriptionService;

    /**
     * Create a new ChannelService instance.
     *
     * @param SubscriptionService $subscriptionService
     */
    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get all active channels with optional filtering.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getChannels(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = IptvChannel::query()->active();

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($filters['sort_by'], $direction);
        } else {
            $query->orderBy('view_count', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get channel streaming URL with authentication token.
     *
     * @param IptvChannel $channel
     * @param User $user
     * @return array
     * @throws \Exception
     */
    public function getStreamingUrl(IptvChannel $channel, User $user): array
    {
        if (!$this->canAccessChannel($user, $channel)) {
            throw new \Exception('Subscription required to access this channel');
        }

        // Generate streaming token
        $token = $this->generateStreamingToken($channel->id, $user->id);

        // Create watch history entry
        WatchHistory::createEntry(
            $user->id,
            $channel->id,
            $this->getDeviceInfo(),
            request()->ip()
        );

        // Increment channel view count
        $channel->incrementViewCount();

        return [
            'stream_url' => $this->appendTokenToUrl($channel->m3u8_link, $token),
            'token' => $token,
            'expires_in' => Config::get('gflix.streaming.token_lifetime', 3600), // 1 hour default
        ];
    }

    /**
     * Check if user can access the channel.
     *
     * @param User $user
     * @param IptvChannel $channel
     * @return bool
     */
    public function canAccessChannel(User $user, IptvChannel $channel): bool
    {
        return $user->hasActiveSubscription() || 
               ($user->trial_ends_at && $user->trial_ends_at->isFuture());
    }

    /**
     * Get channel categories with counts.
     *
     * @return array
     */
    public function getCategories(): array
    {
        return Cache::remember('channel_categories', 3600, function () {
            $categories = Config::get('gflix.channel_categories');
            $counts = IptvChannel::active()
                ->selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray();

            $result = [];
            foreach ($categories as $key => $name) {
                $result[] = [
                    'key' => $key,
                    'name' => $name,
                    'count' => $counts[$key] ?? 0,
                ];
            }

            return $result;
        });
    }

    /**
     * Get popular channels.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPopularChannels(int $limit = 10)
    {
        return Cache::remember('popular_channels', 1800, function () use ($limit) {
            return IptvChannel::active()
                ->orderBy('view_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get recommended channels for user.
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecommendedChannels(User $user, int $limit = 10)
    {
        // Get user's most watched categories
        $favoriteCategories = WatchHistory::where('user_id', $user->id)
            ->join('iptv_channels', 'watch_history.channel_id', '=', 'iptv_channels.id')
            ->selectRaw('iptv_channels.category, COUNT(*) as count')
            ->groupBy('iptv_channels.category')
            ->orderBy('count', 'desc')
            ->limit(3)
            ->pluck('category');

        if ($favoriteCategories->isEmpty()) {
            return $this->getPopularChannels($limit);
        }

        return IptvChannel::active()
            ->whereIn('category', $favoriteCategories)
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate streaming token.
     *
     * @param int $channelId
     * @param int $userId
     * @return string
     */
    protected function generateStreamingToken(int $channelId, int $userId): string
    {
        $data = [
            'channel_id' => $channelId,
            'user_id' => $userId,
            'expires' => time() + Config::get('gflix.streaming.token_lifetime', 3600),
        ];

        return encrypt(json_encode($data));
    }

    /**
     * Append token to streaming URL.
     *
     * @param string $url
     * @param string $token
     * @return string
     */
    protected function appendTokenToUrl(string $url, string $token): string
    {
        $separator = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
        return "{$url}{$separator}token={$token}";
    }

    /**
     * Get device information from request.
     *
     * @return string
     */
    protected function getDeviceInfo(): string
    {
        $userAgent = request()->userAgent();
        $platform = request()->header('X-Platform', 'unknown');
        $version = request()->header('X-App-Version', 'unknown');

        return json_encode([
            'user_agent' => $userAgent,
            'platform' => $platform,
            'version' => $version,
        ]);
    }

    /**
     * Get channel statistics.
     *
     * @return array
     */
    public function getChannelStatistics(): array
    {
        return Cache::remember('channel_statistics', 3600, function () {
            $totalChannels = IptvChannel::count();
            $activeChannels = IptvChannel::active()->count();
            $totalViews = IptvChannel::sum('view_count');
            
            $popularCategories = IptvChannel::selectRaw('category, SUM(view_count) as total_views')
                ->groupBy('category')
                ->orderBy('total_views', 'desc')
                ->limit(5)
                ->get();

            return [
                'total_channels' => $totalChannels,
                'active_channels' => $activeChannels,
                'total_views' => $totalViews,
                'popular_categories' => $popularCategories,
            ];
        });
    }
}