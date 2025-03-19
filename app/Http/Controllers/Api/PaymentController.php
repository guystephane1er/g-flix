<?php

namespace App\Http\Controllers\Api;

use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends BaseController
{
    protected PaymentService $paymentService;
    protected SubscriptionService $subscriptionService;

    /**
     * Create a new PaymentController instance.
     *
     * @param PaymentService $paymentService
     * @param SubscriptionService $subscriptionService
     */
    public function __construct(
        PaymentService $paymentService,
        SubscriptionService $subscriptionService
    ) {
        $this->middleware('jwt');
        $this->paymentService = $paymentService;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Initialize a payment transaction.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function initializePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subscription_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        try {
            if (!$this->subscriptionService->isValidSubscriptionType($request->subscription_type)) {
                return $this->sendError('Invalid subscription type');
            }

            $user = Auth::guard('api')->user();
            $result = $this->paymentService->initializeTransaction(
                $user,
                $request->subscription_type
            );

            return $this->sendSuccess($result, 'Payment initialized successfully');
        } catch (\Exception $e) {
            return $this->sendError('Payment initialization failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Verify a payment transaction.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        try {
            $result = $this->paymentService->verifyTransaction($request->transaction_id);
            return $this->sendSuccess($result, 'Payment verification completed');
        } catch (\Exception $e) {
            return $this->sendError('Payment verification failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle payment callback from Apaym.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $result = $this->paymentService->handleCallback($request->all());
            return $this->sendSuccess($result, 'Payment callback processed');
        } catch (\Exception $e) {
            return $this->sendError('Payment callback processing failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle payment cancellation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleCancellation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->sendValidationError($validator->errors()->toArray());
        }

        try {
            // Verify the transaction and mark it as cancelled if needed
            $result = $this->paymentService->verifyTransaction($request->transaction_id);
            return $this->sendSuccess($result, 'Payment cancellation handled');
        } catch (\Exception $e) {
            return $this->sendError('Payment cancellation failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get user's payment history.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $user = Auth::guard('api')->user();
            $payments = $this->paymentService->getUserPaymentHistory($user, $perPage);
            
            return $this->sendPaginated($payments, 'Payment history retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve payment history', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get subscription details.
     *
     * @return JsonResponse
     */
    public function getSubscriptionDetails(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            $details = $this->subscriptionService->getSubscriptionDetails($user);
            
            return $this->sendSuccess($details, 'Subscription details retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve subscription details', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get available subscription plans.
     *
     * @return JsonResponse
     */
    public function getSubscriptionPlans(): JsonResponse
    {
        try {
            $plans = config('gflix.subscriptions');
            return $this->sendSuccess($plans, 'Subscription plans retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve subscription plans', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get payment statistics (Admin only).
     *
     * @return JsonResponse
     */
    public function getPaymentStatistics(): JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user->isAdmin()) {
                return $this->sendForbidden('Access denied');
            }

            $statistics = $this->paymentService->getPaymentStatistics();
            return $this->sendSuccess($statistics, 'Payment statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve payment statistics', ['error' => $e->getMessage()]);
        }
    }
}