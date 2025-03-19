<?php

namespace App\Http\Controllers\Api;

use App\Models\Advertisement;
use App\Services\AdvertisementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdvertisementController extends BaseController
{
    protected AdvertisementService $advertisementService;

    /**
     * Create a new AdvertisementController instance.
     *
     * @param AdvertisementService $advertisementService
     */
    public function __construct(AdvertisementService $advertisementService)
    {
        $this->middleware('jwt');
        $this->advertisementService = $advertisementService;
    }

    /**
     * Get next advertisement.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNextAd(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|string',
                'position' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->sendValidationError($validator->errors()->toArray());
            }

            $user = Auth::guard('api')->user();
            $ad = $this->advertisementService->getNextAd(
                $user,
                $request->type,
                $request->position
            );

            if (!$ad) {
                return $this->sendSuccess(null, 'No advertisement available');
            }

            $this->advertisementService->recordView($ad);

            return $this->sendSuccess($ad, 'Advertisement retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisement', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Record advertisement click.
     *
     * @param Advertisement $advertisement
     * @return JsonResponse
     */
    public function recordClick(Advertisement $advertisement): JsonResponse
    {
        try {
            $this->advertisementService->recordClick($advertisement);
            return $this->sendSuccess(null, 'Click recorded successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to record click', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get advertisement types.
     *
     * @return JsonResponse
     */
    public function getTypes(): JsonResponse
    {
        try {
            $types = $this->advertisementService->getTypes();
            return $this->sendSuccess($types, 'Advertisement types retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisement types', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get advertisement positions.
     *
     * @return JsonResponse
     */
    public function getPositions(): JsonResponse
    {
        try {
            $positions = $this->advertisementService->getPositions();
            return $this->sendSuccess($positions, 'Advertisement positions retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisement positions', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get advertisement statistics (Admin only).
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

            $statistics = $this->advertisementService->getStatistics();
            return $this->sendSuccess($statistics, 'Advertisement statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisement statistics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get advertisement schedule (Admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSchedule(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user->isAdmin()) {
                return $this->sendForbidden('Access denied');
            }

            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
            ]);

            if ($validator->fails()) {
                return $this->sendValidationError($validator->errors()->toArray());
            }

            $schedule = $this->advertisementService->getSchedule(
                $request->start_date,
                $request->end_date
            );

            return $this->sendSuccess($schedule, 'Advertisement schedule retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve advertisement schedule', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new advertisement (Admin only).
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
                'type' => 'required|string',
                'status' => 'required|in:active,inactive',
                'title' => 'required|string|max:255',
                'content_url' => 'required|url',
                'duration' => 'nullable|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
            ]);

            if ($validator->fails()) {
                return $this->sendValidationError($validator->errors()->toArray());
            }

            $advertisement = Advertisement::create($request->all());
            return $this->sendSuccess($advertisement, 'Advertisement created successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to create advertisement', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update an advertisement (Admin only).
     *
     * @param Request $request
     * @param Advertisement $advertisement
     * @return JsonResponse
     */
    public function update(Request $request, Advertisement $advertisement): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user->isAdmin()) {
                return $this->sendForbidden('Access denied');
            }

            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|string',
                'status' => 'sometimes|in:active,inactive',
                'title' => 'sometimes|string|max:255',
                'content_url' => 'sometimes|url',
                'duration' => 'nullable|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
            ]);

            if ($validator->fails()) {
                return $this->sendValidationError($validator->errors()->toArray());
            }

            $advertisement->update($request->all());
            return $this->sendSuccess($advertisement, 'Advertisement updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to update advertisement', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete an advertisement (Admin only).
     *
     * @param Advertisement $advertisement
     * @return JsonResponse
     */
    public function destroy(Advertisement $advertisement): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user->isAdmin()) {
                return $this->sendForbidden('Access denied');
            }

            $advertisement->delete();
            return $this->sendSuccess(null, 'Advertisement deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete advertisement', ['error' => $e->getMessage()]);
        }
    }
}