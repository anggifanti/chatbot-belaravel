<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RatingService
{
    /**
     * Submit a rating with duplicate check
     */
    public function submitRating(array $data, ?User $user, string $ipAddress): Rating
    {
        // Check for duplicate ratings (prevent spam)
        $existingRating = Rating::where(function ($query) use ($user, $ipAddress) {
                if ($user) {
                    // For authenticated users: check by user_id only
                    $query->where('user_id', $user->id);
                } else {
                    // For guests: check by ip_address only
                    $query->where('ip_address', $ipAddress);
                }
            })
            ->where('created_at', '>=', now()->subHours(24)) // One rating per 24 hours
            ->first();

        if ($existingRating) {
            throw new \Exception('You have already submitted a rating in the last 24 hours');
        }

        return Rating::create([
            'user_id' => $user?->id,
            'rating' => $data['rating'],
            'feedback' => $data['feedback'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Get ratings with pagination and filters
     */
    public function getRatings(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Rating::with('user:id,name,email')
            ->latest();

        // Apply filters
        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (!empty($filters['has_feedback'])) {
            $query->whereNotNull('feedback');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get rating statistics
     */
    public function getRatingStats(): array
    {
        $totalRatings = Rating::count();
        $ratingsWithFeedback = Rating::whereNotNull('feedback')->count();
        $averageRating = Rating::avg('rating');

        // Get distribution
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = Rating::where('rating', $i)->count();
        }

        return [
            'total_ratings' => $totalRatings,
            'ratings_with_feedback' => $ratingsWithFeedback,
            'average_rating' => round($averageRating, 1),
            'distribution' => $distribution,
        ];
    }

    /**
     * Get user-specific ratings
     */
    public function getUserRatings(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Rating::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }
}
