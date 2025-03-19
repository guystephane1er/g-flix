<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'active_subscriptions',
        'connected_devices_count',
        'referral_code',
        'trial_ends_at',
        'google_id',
        'is_admin'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active_subscriptions' => 'boolean',
            'trial_ends_at' => 'datetime',
            'connected_devices_count' => 'integer',
            'is_admin' => 'boolean'
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'is_admin' => $this->is_admin,
            'email' => $this->email
        ];
    }

    /**
     * Get the user's payment history.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the user's watch history.
     */
    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    /**
     * Check if user has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->active_subscriptions || 
               ($this->trial_ends_at && $this->trial_ends_at->isFuture());
    }

    /**
     * Check if user can connect new device
     */
    public function canConnectNewDevice(): bool
    {
        return $this->connected_devices_count < 2;
    }

    /**
     * Increment connected devices count
     */
    public function incrementConnectedDevices(): void
    {
        if ($this->connected_devices_count >= 2) {
            throw new \Exception('Maximum device limit reached');
        }
        
        $this->increment('connected_devices_count');
    }

    /**
     * Decrement connected devices count
     */
    public function decrementConnectedDevices(): void
    {
        if ($this->connected_devices_count > 0) {
            $this->decrement('connected_devices_count');
        }
    }

    /**
     * Check if the user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Make the user an admin
     */
    public function makeAdmin(): void
    {
        $this->is_admin = true;
        $this->save();
    }

    /**
     * Remove admin privileges from the user
     */
    public function removeAdmin(): void
    {
        $this->is_admin = false;
        $this->save();
    }

    /**
     * Get active users scope
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get users with active subscriptions scope
     */
    public function scopeWithActiveSubscription($query)
    {
        return $query->where('active_subscriptions', true)
                    ->orWhere(function($q) {
                        $q->whereNotNull('trial_ends_at')
                          ->where('trial_ends_at', '>', now());
                    });
    }
}
