<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class UserService
{
    /**
     * Get user statistics
     */
    public function getUserStats(User $user): array
    {
        $stats = [
            'total_conversations' => $user->getConversationCount(),
            'total_messages' => $user->getTotalMessageCount(),
            'messages_today' => $user->getMessagesToday(),
            'joined_date' => $user->created_at->format('Y-m-d'),
            'is_premium' => $user->is_premium,
            'remaining_messages' => $user->remaining_messages,
        ];

        // Add average rating if available
        $averageRating = $this->getUserAverageRating($user);
        if ($averageRating !== null) {
            $stats['average_rating'] = round($averageRating, 2);
        }

        // Add usage by date for the last 30 days
        $stats['usage_by_date'] = $this->getUsageByDate($user);

        return $stats;
    }

    /**
     * Get user's average rating
     */
    private function getUserAverageRating(User $user): ?float
    {
        $ratings = $user->ratings()
            ->where('rating', '>', 0)
            ->get();

        if ($ratings->isEmpty()) {
            return null;
        }

        return $ratings->avg('rating');
    }

    /**
     * Get usage by date for the last 30 days
     */
    private function getUsageByDate(User $user): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(29);

        $usage = [];
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $messageCount = $user->messages()
                ->whereDate('messages.created_at', $date->format('Y-m-d'))
                ->count();

            $usage[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $messageCount,
            ];
        }

        return $usage;
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update(array_filter($data));
        return $user->fresh();
    }
}
