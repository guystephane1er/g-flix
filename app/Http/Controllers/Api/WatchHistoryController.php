<?php

namespace App\Http\Controllers\Api;

use App\Models\IptvChannel;
use App\Models\WatchHistory;
use App\Services\WatchHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WatchHistoryController extends BaseController
{
    protected WatchHistoryService $watchHistoryService;

    /**
     * Create a new WatchHistoryController instance.
     *
     * @param WatchHistoryService $watchHistoryService
     */
    public function __construct(WatchHistoryService $watchHistoryService)
    {
        $this->middleware('jwt');
        $this->watchHistoryService = $watchHistoryService;
    }

    /**
     * Get user's watch history.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['channel_id', 'date_from', 'date_to']);
            $perPage = $request->get('per_page', 15);
            
            $user = Auth::guard('api')->user();
            $history = $this->watchHistoryService->getUserHistory($user, $filters, $perPage);
            
            return $this->sendPaginated($history, 'Watch history retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve watch history', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Record watch history.
     *
     * @param Request $request
     * @param IptvChannel $channel
     * @return JsonResponse
     */
    public function store(Request $request, IptvChannel $channel): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'duration' => 'required|integer|min:0',
                'device_info' => 'required|array',
            ]);

            if ($validator->fails()) {
                return $this->sendValidationError($validator->errors()->toArray());
            }

            $user = Auth::guard('api')->user();
            $history = $this->watchHistoryService->recordWatch(
                $user,
                $channel,
                $request->duration,
                $request->device_info
            );

            return $this->sendSuccess($history, 'Watch history recorded successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to record watch history', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update watch duration.
     *
     * @param Request $request
     * @param WatchHistory $history
     * @return JsonResponse
     */
    public function updateDuration(Request $request, WatchHistory $history): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'duration' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->sendValidationError($validator->errors()->toArray());
            }

            $user = Auth::guard('api')->user();
            
            if ($history->user_id !== $user->id) {
                return $this->sendForbidden('Access denied');
            }

            $history = $this->watchHistoryService->updateDuration($history, $request->duration);
            return $this->sendSuccess($history, 'Watch duration updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to update watch duration', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get user's viewing statistics.
     *
     * @return JsonResponse
     */
    public function getUserStatistics(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            $statistics = $this->watchHistoryService->getUserStatistics($user);
            
            return $this->sendSuccess($statistics, 'User statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve user statistics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get global viewing statistics (Admin only).
     *
     * @return JsonResponse
     */
    public function getGlobalStatistics(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user->isAdmin()) {
                return $this->sendForbidden('Access denied');
            }

            $statistics = $this->watchHistoryService->getGlobalStatistics();
            return $this->sendSuccess($statistics, 'Global statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve global statistics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get channel watch history.
     *
     * @param Request $request
     * @param IptvChannel $channel
     * @return JsonResponse
     */
    public function getChannelHistory(Request $request, IptvChannel $channel): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            $limit = $request->get('limit', 10);
            
            $history = $this->watchHistoryService->getChannelHistory($user, $channel, $limit);
            return $this->sendSuccess($history, 'Channel history retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve channel history', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear watch history.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            $channel = null;

            if ($request->has('channel_id')) {
                $channel = IptvChannel::findOrFail($request->channel_id);
            }

            $this->watchHistoryService->clearHistory($user, $channel);
            return $this->sendSuccess(null, 'Watch history cleared successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to clear watch history', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get recently watched channels.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentlyWatched(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            $limit = $request->get('limit', 5);
            
            $channels = $this->watchHistoryService->getRecentlyWatched($user, $limit);
            return $this->sendSuccess($channels, 'Recently watched channels retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve recently watched channels', ['error' => $e->getMessage()]);
        }
    }
}