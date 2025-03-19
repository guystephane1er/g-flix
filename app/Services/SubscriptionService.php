<?php

namespace App\Services;

use App\Models\User;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class SubscriptionService
{
    /**
     * Check if a subscription type is valid.
     *
     * @param string $type
     * @return bool
     */
    public function isValidSubscriptionType(string $type): bool
    {
        return array_key_exists($type, Config::get('gflix.subscriptions'));
    }

    /**
     * Get subscription price.
     *
     * @param string $type
     * @return int|null
     */
    public function getSubscriptionPrice(string $type): ?int
    {
        return Config::get("gflix.subscriptions.{$type}.price");
    }

    /**
     * Get subscription duration in days.
     *
     * @param string $type
     * @return int|null
     */
    public function getSubscriptionDuration(string $type): ?int
    {
        return Config::get("gflix.subscriptions.{$type}.duration_days");
    }

    /**
     * Calculate subscription end date.
     *
     * @param string $type
     * @param Carbon|null $startDate
     * @return Carbon
     */
    public function calculateEndDate(string $type, ?Carbon $startDate = null): Carbon
    {
        $startDate = $startDate ?? now();
        $duration = $this->getSubscriptionDuration($type);
        
        return $startDate->copy()->addDays($duration);
    }

    /**
     * Activate subscription for user.
     *
     * @param User $user
     * @param string $type
     * @param string $paymentMethod
     * @param string $transactionId
     * @return Payment
     */
    public function activateSubscription(
        User $user,
        string $type,
        string $paymentMethod,
        string $transactionId
    ): Payment {
        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $this->getSubscriptionPrice($type),
            'status' => 'completed',
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'subscription_type' => $type,
            'subscription_ends_at' => $this->calculateEndDate($type),
            'payment_details' => [
                'type' => $type,
                'duration_days' => $this->getSubscriptionDuration($type)
            ]
        ]);

        // Update user subscription status
        $user->update([
            'active_subscriptions' => true,
            'trial_ends_at' => null // End trial period if exists
        ]);

        return $payment;
    }

    /**
     * Deactivate subscription for user.
     *
     * @param User $user
     * @return void
     */
    public function deactivateSubscription(User $user): void
    {
        $user->update([
            'active_subscriptions' => false
        ]);
    }

    /**
     * Check if user has access to premium features.
     *
     * @param User $user
     * @return bool
     */
    public function hasPremiumAccess(User $user): bool
    {
        $latestPayment = $user->payments()
            ->where('status', 'completed')
            ->where('subscription_type', 'premium_yearly')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '>', now())
            ->latest()
            ->first();

        return $latestPayment !== null;
    }

    /**
     * Check if user should see ads.
     *
     * @param User $user
     * @return bool
     */
    public function shouldSeeAds(User $user): bool
    {
        // Users with premium yearly subscription don't see ads
        if ($this->hasPremiumAccess($user)) {
            return false;
        }

        // Check for daily ad-free subscription
        $latestDailyPayment = $user->payments()
            ->where('status', 'completed')
            ->where('subscription_type', 'daily')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '>', now())
            ->latest()
            ->first();

        return $latestDailyPayment === null;
    }

    /**
     * Get subscription status details for user.
     *
     * @param User $user
     * @return array
     */
    public function getSubscriptionDetails(User $user): array
    {
        $latestPayment = $user->payments()
            ->where('status', 'completed')
            ->whereNotNull('subscription_ends_at')
            ->latest()
            ->first();

        return [
            'has_active_subscription' => $user->hasActiveSubscription(),
            'is_premium' => $this->hasPremiumAccess($user),
            'shows_ads' => $this->shouldSeeAds($user),
            'subscription_type' => $latestPayment?->subscription_type ?? null,
            'subscription_ends_at' => $latestPayment?->subscription_ends_at,
            'trial_ends_at' => $user->trial_ends_at,
            'is_in_trial' => $user->trial_ends_at && $user->trial_ends_at->isFuture(),
        ];
    }

    /**
     * Process referral reward.
     *
     * @param User $referrer
     * @return void
     */
    public function processReferralReward(User $referrer): void
    {
        $rewardDays = Config::get('gflix.referrals.reward_days', 30);
        
        // Extend current subscription or trial period
        if ($referrer->trial_ends_at && $referrer->trial_ends_at->isFuture()) {
            $referrer->trial_ends_at = $referrer->trial_ends_at->addDays($rewardDays);
        } elseif ($referrer->active_subscriptions) {
            $latestPayment = $referrer->payments()
                ->where('status', 'completed')
                ->whereNotNull('subscription_ends_at')
                ->latest()
                ->first();

            if ($latestPayment) {
                $latestPayment->subscription_ends_at = $latestPayment->subscription_ends_at->addDays($rewardDays);
                $latestPayment->save();
            }
        } else {
            // If no active subscription, start a new trial period
            $referrer->trial_ends_at = now()->addDays($rewardDays);
        }

        $referrer->save();
    }
}