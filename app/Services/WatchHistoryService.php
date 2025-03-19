<?php

namespace App\Services;

use App\Models\User;
use App\Models\WatchHistory;
use App\Models\IptvChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class WatchHistoryService
{
    /**
     * Get user's watch history.
     *
     * @param User $user
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserHistory(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WatchHistory::with('channel')
            ->where('user_id', $user->id);

        if (isset($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('watched_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('watched_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('watched_at', 'desc')->paginate($perPage);
    }

    /**
     * Record watch history entry.
     *
     * @param User $user
     * @param IptvChannel $channel
     * @param int $duration
     * @param array $deviceInfo
     * @return WatchHistory
     */
    public function recordWatch(
        User $user,
        IptvChannel $channel,
        int $duration,
        array $deviceInfo = []
    ): WatchHistory {
        return WatchHistory::create([
            'user_id' => $user->id,
            'channel_id' => $channel->id,
            'watched_at' => now(),
            'duration' => $duration,
            'device_info' => json_encode($deviceInfo),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Update watch duration.
     *
     * @param WatchHistory $history
     * @param int $duration
     * @return WatchHistory
     */
    public function updateDuration(WatchHistory $history, int $duration): WatchHistory
    {
        $history->update(['duration' => $duration]);
        return $history;
    }

    /**
     * Get user's viewing statistics.
     *
     * @param User $user
     * @return array
     */
    public function getUserStatistics(User $user): array
    {
        $cacheKey = "user_watch_stats_{$user->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user) {
            $totalWatchTime = WatchHistory::where('user_id', $user->id)
                ->sum('duration');

            $channelStats = WatchHistory::where('user_id', $user->id)
                ->select('channel_id', DB::raw('COUNT(*) as views'), DB::raw('SUM(duration) as total_duration'))
                ->groupBy('channel_id')
                ->with('channel:id,name,category')
                ->get();

            $categoryStats = $channelStats->groupBy('channel.category')
                ->map(function ($items) {
                    return [
                        'views' => $items->sum('views'),
                        'duration' => $items->sum('total_duration'),
                    ];
                });

            $deviceStats = WatchHistory::where('user_id', $user->id)
                ->select('device_info', DB::raw('COUNT(*) as count'))
                ->groupBy('device_info')
                ->get()
                ->map(function ($item) {
                    $deviceInfo = json_decode($item->device_info, true);
                    return [
                        'platform' => $deviceInfo['platform'] ?? 'unknown',
                        'count' => $item->count,
                    ];
                });

            return [
                'total_watch_time' => $totalWatchTime,
                'total_channels_watched' => $channelStats->count(),
                'favorite_channels' => $channelStats->sortByDesc('views')->take(5),
                'category_breakdown' => $categoryStats,
                'device_usage' => $deviceStats,
            ];
        });
    }

    /**
     * Get global viewing statistics.
     *
     * @return array
     */
    public function getGlobalStatistics(): array
    {
        return Cache::remember('global_watch_stats', 3600, function () {
            $totalWatchTime = WatchHistory::sum('duration');
            $activeUsers = WatchHistory::distinct('user_id')->count('user_id');
            
            $popularChannels = WatchHistory::select('channel_id', 
                    DB::raw('COUNT(*) as views'),
                    DB::raw('SUM(duration) as total_duration'))
                ->groupBy('channel_id')
                ->with('channel:id,name,category')
                ->orderByDesc('views')
                ->limit(10)
                ->get();

            $categoryStats = WatchHistory::join('iptv_channels', 'watch_history.channel_id', '=', 'iptv_channels.id')
                ->select('iptv_channels.category',
                    DB::raw('COUNT(DISTINCT watch_history.user_id) as unique_viewers'),
                    DB::raw('COUNT(*) as total_views'),
                    DB::raw('SUM(duration) as total_duration'))
                ->groupBy('iptv_channels.category')
                ->get();

            $hourlyDistribution = WatchHistory::select(
                    DB::raw('HOUR(watched_at) as hour'),
                    DB::raw('COUNT(*) as count'))
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            return [
                'total_watch_time' => $totalWatchTime,
                'active_users' => $activeUsers,
                'popular_channels' => $popularChannels,
                'category_statistics' => $categoryStats,
                'hourly_distribution' => $hourlyDistribution,
            ];
        });
    }

    /**
     * Get user's watch history for a specific channel.
     *
     * @param User $user
     * @param IptvChannel $channel
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChannelHistory(User $user, IptvChannel $channel, int $limit = 10)
    {
        return WatchHistory::where('user_id', $user->id)
            ->where('channel_id', $channel->id)
            ->orderBy('watched_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clear user's watch history.
     *
     * @param User $user
     * @param IptvChannel|null $channel
     * @return void
     */
    public function clearHistory(User $user, ?IptvChannel $channel = null): void
    {
        $query = WatchHistory::where('user_id', $user->id);
        
        if ($channel) {
            $query->where('channel_id', $channel->id);
        }
        
        $query->delete();
        Cache::forget("user_watch_stats_{$user->id}");
    }

    /**
     * Get recently watched channels for user.
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentlyWatched(User $user, int $limit = 5)
    {
        return WatchHistory::where('user_id', $user->id)
            ->select('channel_id')
            ->distinct()
            ->with('channel')
            ->orderBy('watched_at', 'desc')
            ->limit($limit)
            ->get()
            ->pluck('channel');
    }
}