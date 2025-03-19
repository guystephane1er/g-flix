<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class PaymentService
{
    protected SubscriptionService $subscriptionService;
    protected string $apiKey;
    protected string $apiSecret;
    protected string $baseUrl;

    /**
     * Create a new PaymentService instance.
     *
     * @param SubscriptionService $subscriptionService
     */
    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->apiKey = Config::get('services.apaym.api_key');
        $this->apiSecret = Config::get('services.apaym.api_secret');
        $this->baseUrl = Config::get('services.apaym.base_url');
    }

    /**
     * Initialize a payment transaction.
     *
     * @param User $user
     * @param string $subscriptionType
     * @return array
     * @throws \Exception
     */
    public function initializeTransaction(User $user, string $subscriptionType): array
    {
        if (!$this->subscriptionService->isValidSubscriptionType($subscriptionType)) {
            throw new \Exception('Invalid subscription type');
        }

        $amount = $this->subscriptionService->getSubscriptionPrice($subscriptionType);
        $transactionId = $this->generateTransactionId();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transactions/initialize', [
                'amount' => $amount,
                'currency' => Config::get('gflix.payments.currencies.default'),
                'transaction_id' => $transactionId,
                'callback_url' => route('api.payments.callback'),
                'cancel_url' => route('api.payments.cancel'),
                'customer' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'subscription_type' => $subscriptionType,
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to initialize payment: ' . $response->body());
            }

            $data = $response->json();

            // Create pending payment record
            Payment::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'status' => 'pending',
                'payment_method' => 'apaym',
                'transaction_id' => $transactionId,
                'subscription_type' => $subscriptionType,
                'payment_details' => [
                    'apaym_reference' => $data['reference'] ?? null,
                    'payment_url' => $data['payment_url'] ?? null,
                ],
            ]);

            return [
                'transaction_id' => $transactionId,
                'payment_url' => $data['payment_url'],
                'amount' => $amount,
                'currency' => Config::get('gflix.payments.currencies.default'),
            ];

        } catch (\Exception $e) {
            throw new \Exception('Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify a payment transaction.
     *
     * @param string $transactionId
     * @return array
     * @throws \Exception
     */
    public function verifyTransaction(string $transactionId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/transactions/verify/' . $transactionId);

            if (!$response->successful()) {
                throw new \Exception('Failed to verify payment: ' . $response->body());
            }

            $data = $response->json();
            $payment = Payment::where('transaction_id', $transactionId)->first();

            if (!$payment) {
                throw new \Exception('Payment record not found');
            }

            if ($data['status'] === 'success') {
                $payment->status = 'completed';
                $payment->payment_details = array_merge(
                    $payment->payment_details ?? [],
                    ['verification_response' => $data]
                );
                $payment->save();

                // Activate subscription
                $this->subscriptionService->activateSubscription(
                    $payment->user,
                    $payment->subscription_type,
                    $payment->payment_method,
                    $payment->transaction_id
                );
            } else {
                $payment->status = 'failed';
                $payment->payment_details = array_merge(
                    $payment->payment_details ?? [],
                    ['verification_response' => $data]
                );
                $payment->save();
            }

            return [
                'status' => $payment->status,
                'transaction_id' => $transactionId,
                'payment_details' => $payment->payment_details,
            ];

        } catch (\Exception $e) {
            throw new \Exception('Payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle payment callback.
     *
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function handleCallback(array $data): array
    {
        // Verify the callback signature
        if (!$this->verifyCallbackSignature($data)) {
            throw new \Exception('Invalid callback signature');
        }

        return $this->verifyTransaction($data['transaction_id']);
    }

    /**
     * Generate a unique transaction ID.
     *
     * @return string
     */
    protected function generateTransactionId(): string
    {
        return 'GFLIX-' . Str::random(20);
    }

    /**
     * Verify callback signature.
     *
     * @param array $data
     * @return bool
     */
    protected function verifyCallbackSignature(array $data): bool
    {
        $signature = $data['signature'] ?? '';
        $expectedSignature = hash_hmac('sha256', json_encode($data['transaction_id']), $this->apiSecret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get payment history for a user.
     *
     * @param User $user
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserPaymentHistory(User $user, int $perPage = 15)
    {
        return Payment::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get payment statistics.
     *
     * @return array
     */
    public function getPaymentStatistics(): array
    {
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $monthlyRevenue = Payment::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        
        $successRate = Payment::count() > 0 
            ? (Payment::where('status', 'completed')->count() / Payment::count()) * 100 
            : 0;

        return [
            'total_revenue' => $totalRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'success_rate' => round($successRate, 2),
            'total_transactions' => Payment::count(),
            'completed_transactions' => Payment::where('status', 'completed')->count(),
            'failed_transactions' => Payment::where('status', 'failed')->count(),
            'pending_transactions' => Payment::where('status', 'pending')->count(),
        ];
    }
}