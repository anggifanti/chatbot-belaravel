<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'rating',
        'feedback',
        'ip_address',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Rating values
    const RATING_MIN = 1;
    const RATING_MAX = 5;

    /**
     * Get the user that owns the rating
     */
    /**
     * Get the user who submitted the rating
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for specific rating value
     */
    public function scopeWithRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope for ratings by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for guest ratings
     */
    public function scopeGuestRatings($query)
    {
        return $query->whereNull('user_id')->whereNotNull('session_id');
    }

    /**
     * Get average rating for the application
     */
    public static function getAverageRating()
    {
        return self::avg('rating');
    }

    /**
     * Get rating distribution for the application
     */
    public static function getRatingDistribution()
    {
        return self::selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->get()
            ->pluck('count', 'rating')
            ->toArray();
    }
}
