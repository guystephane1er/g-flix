<?php

namespace App\Http\Controllers\Api;

use App\Models\IptvChannel;
use App\Services\ChannelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChannelController extends BaseController
{
    protected ChannelService $channelService;

    /**
     * Create a new ChannelController instance.
     *
     * @param ChannelService $channelService
     */
    public function __construct(ChannelService $channelService)
    {
        $this->middleware('jwt');
        $this->channelService = $channelService;
    }

    /**
     * Get list of channels.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['category', 'search', 'sort_by', 'sort_direction']);
            $perPage = $request->get('per_page', 15);
            
            $channels = $this->channelService->getChannels($filters, $perPage);
            return $this->sendPaginated($channels, 'Channels retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve channels', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get channel details.
     *
     * @param IptvChannel $channel
     * @return JsonResponse
     */
    public function show(IptvChannel $channel): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            $canAccess = $this->channelService->canAccessChannel($user, $channel);

            return $this->sendSuccess([
                'channel' => $channel,
                'can_access' => $canAccess,
            ], 'Channel details retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve channel details', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get channel streaming URL.
     *
     * @param IptvChannel $channel
     * @return JsonResponse
     */
    public function getStreamingUrl(IptvChannel $channel): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            $streamingData = $this->channelService->getStreamingUrl($channel, $user);
            
            return $this->sendSuccess($streamingData, 'Streaming URL generated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to generate streaming URL', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get channel categories.
     *
     * @return JsonResponse
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = $this->channelService->getCategories();
            return $this->sendSuccess($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve categories', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get popular channels.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPopularChannels(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $channels = $this->channelService->getPopularChannels($limit);
            
            return $this->sendSuccess($channels, 'Popular channels retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve popular channels', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get recommended channels for user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecommendedChannels(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            $limit = $request->get('limit', 10);
            
            $channels = $this->channelService->getRecommendedChannels($user, $limit);
            return $this->sendSuccess($channels, 'Recommended channels retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve recommended channels', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get channel statistics (Admin only).
     *
     * @return JsonResponse
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user->isAdmin()) {
                return $this->sendForbidden('Access denied');
            }

            $statistics = $this->channelService->getChannelStatistics();
            return $this->sendSuccess($statistics, 'Channel statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve channel statistics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new channel (Admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user->isAdmin()) {
                return $this->sendForbidden('Access denied');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'category' => 'required|string',
                'm3u8_link' => 'required|url',
                'description' => 'nullable|string',
                'thumbnail_url' => 'nullable|url',
                'status' => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return $this->sendValidationError($validator->errors()->toArray());
            }

            $channel = IptvChannel::create($request->all());
            return $this->sendSuccess($channel, 'Channel created successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to create channel', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update a channel (Admin only).
     *
     * @param Request $request
     * @param IptvChannel $channel
     * @return JsonResponse
     */
    public function update(Request $request, IptvChannel $channel): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user->isAdmin()) {
                return $this->sendForbidden('Access denied');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'category' => 'sometimes|string',
                'm3u8_link' => 'sometimes|url',
                'description' => 'nullable|string',
                'thumbnail_url' => 'nullable|url',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return $this->sendValidationError($validator->errors()->toArray());
            }

            $channel->update($request->all());
            return $this->sendSuccess($channel, 'Channel updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to update channel', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete a channel (Admin only).
     *
     * @param IptvChannel $channel
     * @return JsonResponse
     */
    public function destroy(IptvChannel $channel): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user->isAdmin()) {
                return $this->sendForbidden('Access denied');
            }

            $channel->delete();
            return $this->sendSuccess(null, 'Channel deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete channel', ['error' => $e->getMessage()]);
        }
    }
}