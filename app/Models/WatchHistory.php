<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'watch_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'channel_id',
        'watched_at',
        'duration',
        'device_info',
        'ip_address'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'watched_at' => 'datetime',
        'duration' => 'integer'
    ];

    /**
     * Get the user that owns the watch history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the channel that was watched.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(IptvChannel::class, 'channel_id');
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by channel.
     */
    public function scopeForChannel($query, int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Scope a query to get recent history.
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('watched_at', 'desc')
                    ->limit($limit);
    }

    /**
     * Get the duration in human readable format
     */
    public function getDurationForHumans(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Create a new watch history entry
     */
    public static function createEntry(
        int $userId, 
        int $channelId, 
        string $deviceInfo = null, 
        string $ipAddress = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'channel_id' => $channelId,
            'watched_at' => now(),
            'device_info' => $deviceInfo,
            'ip_address' => $ipAddress
        ]);
    }
}