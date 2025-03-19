<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Pagination\LengthAwarePaginator;

class AdvertisementService
{
    protected SubscriptionService $subscriptionService;

    /**
     * Create a new AdvertisementService instance.
     *
     * @param SubscriptionService $subscriptionService
     */
    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get active advertisements with optional filtering.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAdvertisements(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Advertisement::query()->active();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        return $query->paginate($perPage);
    }

    /**
     * Get next advertisement for user.
     *
     * @param User $user
     * @param string $type
     * @param string $position
     * @return Advertisement|null
     */
    public function getNextAd(User $user, string $type, string $position): ?Advertisement
    {
        if (!$this->subscriptionService->shouldSeeAds($user)) {
            return null;
        }

        return Cache::remember("next_ad_{$user->id}_{$type}_{$position}", 300, function () use ($type, $position) {
            return Advertisement::active()
                ->where('type', $type)
                ->inRandomOrder()
                ->first();
        });
    }

    /**
     * Record advertisement view.
     *
     * @param Advertisement $ad
     * @return void
     */
    public function recordView(Advertisement $ad): void
    {
        $ad->increment('views');
    }

    /**
     * Record advertisement click.
     *
     * @param Advertisement $ad
     * @return void
     */
    public function recordClick(Advertisement $ad): void
    {
        $ad->increment('clicks');
    }

    /**
     * Get advertisement statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return Cache::remember('ad_statistics', 3600, function () {
            $totalAds = Advertisement::count();
            $activeAds = Advertisement::active()->count();
            $totalViews = Advertisement::sum('views');
            $totalClicks = Advertisement::sum('clicks');
            
            $ctr = $totalViews > 0 ? ($totalClicks / $totalViews) * 100 : 0;

            $performanceByType = Advertisement::selectRaw('
                type,
                COUNT(*) as count,
                SUM(views) as total_views,
                SUM(clicks) as total_clicks,
                CASE 
                    WHEN SUM(views) > 0 
                    THEN (SUM(clicks) / SUM(views)) * 100 
                    ELSE 0 
                END as ctr
            ')
            ->groupBy('type')
            ->get();

            return [
                'total_ads' => $totalAds,
                'active_ads' => $activeAds,
                'total_views' => $totalViews,
                'total_clicks' => $totalClicks,
                'overall_ctr' => round($ctr, 2),
                'performance_by_type' => $performanceByType,
            ];
        });
    }

    /**
     * Get advertisement schedule for a time period.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getSchedule(string $startDate, string $endDate): array
    {
        return Advertisement::whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate])
            ->orWhere(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $startDate)
                      ->where('end_date', '>=', $endDate);
            })
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'type' => $ad->type,
                    'start_date' => $ad->start_date,
                    'end_date' => $ad->end_date,
                    'status' => $ad->status,
                ];
            })
            ->toArray();
    }

    /**
     * Check if an advertisement should be shown.
     *
     * @param Advertisement $ad
     * @return bool
     */
    public function shouldShowAd(Advertisement $ad): bool
    {
        if ($ad->status !== 'active') {
            return false;
        }

        $now = now();

        if ($ad->start_date && $ad->start_date->isFuture()) {
            return false;
        }

        if ($ad->end_date && $ad->end_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get advertisement types with counts.
     *
     * @return array
     */
    public function getTypes(): array
    {
        return Cache::remember('ad_types', 3600, function () {
            $types = Config::get('gflix.advertisements.types');
            $counts = Advertisement::active()
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            $result = [];
            foreach ($types as $key => $name) {
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
     * Get advertisement positions.
     *
     * @return array
     */
    public function getPositions(): array
    {
        return Config::get('gflix.advertisements.positions', []);
    }
}