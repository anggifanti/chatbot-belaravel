<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    /**
     * Submit a rating
     */
    public function submitRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
            'session_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Try to get authenticated user (optional authentication)
            $user = null;
            if ($request->bearerToken()) {
                $user = $request->user('sanctum');
            }
            
            // Check for duplicate ratings (prevent spam)
            $existingRating = Rating::where(function ($query) use ($user, $request) {
                    if ($user) {
                        // For authenticated users: check by user_id only
                        $query->where('user_id', $user->id);
                    } else {
                        // For guests: check by ip_address only
                        $query->where('ip_address', $request->ip());
                    }
                })
                ->where('created_at', '>=', now()->subHours(24)) // One rating per 24 hours
                ->first();

            if ($existingRating) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already rated this recently. Please wait 24 hours before rating again.',
                ], 429);
            }

            // Create the rating
            $rating = Rating::create([
                'user_id' => $user ? $user->id : null,
                'session_id' => $user ? null : $request->session_id,
                'rating' => $request->rating,
                'feedback' => $request->feedback,
                'ip_address' => $request->ip(),
                'submitted_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your rating!',
                'rating' => [
                    'id' => $rating->id,
                    'rating' => $rating->rating,
                    'submitted_at' => $rating->submitted_at,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Rating submission error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit rating',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rating statistics
     */
    public function getRatingStats(Request $request)
    {
        try {
            $stats = [
                'average_rating' => round(Rating::getAverageRating(), 2),
                'total_ratings' => Rating::count(),
                'rating_distribution' => Rating::getRatingDistribution(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get rating statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's ratings
     */
    public function getUserRatings(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $ratings = Rating::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'ratings' => $ratings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user ratings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all ratings for admin
     */
    public function getAdminRatings(Request $request)
    {
        try {
            $rating = $request->get('rating');
            $perPage = $request->get('per_page', 15);

            $query = Rating::with(['user'])
                ->orderBy('created_at', 'desc');

            if ($rating) {
                $query->where('rating', $rating);
            }

            $ratings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'ratings' => $ratings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get ratings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed rating statistics for admin
     */
    public function getAdminRatingStats(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            $startDate = now()->subDays($days);

            $stats = [
                'average_rating' => round(Rating::avg('rating'), 2),
                'total_ratings' => Rating::count(),
                'ratings_with_feedback' => Rating::whereNotNull('feedback')->count(),
                'distribution' => Rating::getRatingDistribution(),
                'recent_feedback' => Rating::whereNotNull('feedback')
                    ->where('created_at', '>=', $startDate)
                    ->with(['user'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
                'ratings_over_time' => $this->getRatingsOverTime($days),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get rating statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get ratings over time for charts
     */
    private function getRatingsOverTime($days)
    {
        $startDate = now()->subDays($days);
        
        return Rating::selectRaw('DATE(created_at) as date, AVG(rating) as average_rating, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'average_rating' => round($item->average_rating, 2),
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Detect platform from user agent
     */
    private function detectPlatform($userAgent)
    {
        $platforms = [
            'mobile' => '/Mobile|Android|iPhone|iPad/',
            'tablet' => '/iPad|Tablet/',
            'desktop' => '/Windows|Mac|Linux/',
        ];

        foreach ($platforms as $platform => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $platform;
            }
        }

        return 'unknown';
    }
}
