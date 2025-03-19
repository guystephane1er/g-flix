<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'type',
        'status',
        'title',
        'content_url',
        'duration',
        'start_date',
        'end_date',
        'views',
        'clicks'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration' => 'integer',
        'views' => 'integer',
        'clicks' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

    /**
     * Scope a query to only include active advertisements.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $now = now();
                        $q->whereNull('start_date')
                          ->orWhere('start_date', '<=', $now);
                    })
                    ->where(function($q) {
                        $now = now();
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $now);
                    });
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Increment the view count
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Increment the click count
     */
    public function incrementClicks(): void
    {
        $this->increment('clicks');
    }

    /**
     * Check if the advertisement is currently active
     */
    public function isActive(): bool
    {
        $now = now();
        
        return $this->status === 'active' &&
               (!$this->start_date || $this->start_date <= $now) &&
               (!$this->end_date || $this->end_date >= $now);
    }

    /**
     * Get the click-through rate (CTR)
     */
    public function getClickThroughRate(): float
    {
        if ($this->views === 0) {
            return 0;
        }
        
        return ($this->clicks / $this->views) * 100;
    }
}