<?php

namespace App\Services;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        // User statistics
        $totalUsers = User::where('is_admin', false)->count();
        $newUsersToday = User::where('is_admin', false)
            ->whereDate('created_at', today())
            ->count();
        $premiumUsers = User::where('is_premium', true)->count();
        
        // Conversation and message statistics
        $totalConversations = Conversation::count();
        $totalMessages = Message::count();
        $messagesToday = Message::whereDate('created_at', today())->count();
        
        // Active users (last 7 days)
        $activeUsers = User::where('is_admin', false)
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();
        
        // Usage stats by day (last 7 days)
        $usageByDate = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $messageCount = Message::whereDate('created_at', $date)->count();
            $userCount = User::where('is_admin', false)
                ->whereDate('created_at', $date)
                ->count();
            
            $usageByDate[] = [
                'date' => $date,
                'messages' => $messageCount,
                'new_users' => $userCount
            ];
        }
        
        return [
            'total_users' => $totalUsers,
            'new_users_today' => $newUsersToday,
            'premium_users' => $premiumUsers,
            'total_conversations' => $totalConversations,
            'total_messages' => $totalMessages,
            'messages_today' => $messagesToday,
            'active_users' => $activeUsers,
            'usage_by_date' => $usageByDate,
        ];
    }

    /**
     * Get users with pagination and filters
     */
    public function getUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::where('is_admin', false)
            ->withCount(['messages', 'conversations'])
            ->latest();

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply premium filter
        if (isset($filters['is_premium'])) {
            $query->where('is_premium', $filters['is_premium']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get chat statistics
     */
    public function getChatStats(): array
    {
        // Total statistics (all time)
        $totalMessages = Message::count();
        $totalConversations = Conversation::count();
        $totalUsers = User::where('is_admin', false)->count();
        
        // Period-specific statistics (last 30 days)
        $periodStart = now()->subDays(30);
        $totalMessagesPeriod = Message::where('created_at', '>=', $periodStart)->count();
        $totalConversationsPeriod = Conversation::where('created_at', '>=', $periodStart)->count();
        
        $averageMessagesPerConversation = $totalConversations > 0 
            ? round($totalMessages / $totalConversations, 1) 
            : 0;

        // Messages per day for the last 30 days
        $messagesPerDay = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = Message::whereDate('created_at', $date)->count();
            
            $messagesPerDay[] = [
                'date' => $date,
                'count' => $count
            ];
        }

        // Top active users (by message count in the last 30 days)
        $topActiveUsers = User::where('is_admin', false)
            ->withCount(['messages' => function ($query) use ($periodStart) {
                $query->where('messages.created_at', '>=', $periodStart);
            }])
            ->orderBy('messages_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'messages_count' => $user->messages_count,
                    'is_premium' => $user->is_premium ?? false,
                ];
            });

        // Peak usage hours (last 7 days)
        $peakHours = Message::where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('HOUR(created_at) as hour, COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->first();

        return [
            'total_messages' => $totalMessages,
            'total_conversations' => $totalConversations,
            'total_users' => $totalUsers,
            'total_messages_period' => $totalMessagesPeriod,
            'total_conversations_period' => $totalConversationsPeriod,
            'average_messages_per_conversation' => $averageMessagesPerConversation,
            'messages_per_day' => $messagesPerDay,
            'top_active_users' => $topActiveUsers,
            'peak_hour' => $peakHours?->hour ?? 0,
            'peak_hour_count' => $peakHours?->count ?? 0,
        ];
    }

    /**
     * Get user details with statistics
     */
    public function getUserDetails(int $userId): array
    {
        $user = User::where('is_admin', false)
            ->withCount(['messages', 'conversations'])
            ->findOrFail($userId);

        // Recent conversations
        $recentConversations = $user->conversations()
            ->with(['messages' => function ($query) {
                $query->latest()->limit(3);
            }])
            ->latest()
            ->limit(5)
            ->get();

        // Message activity (last 30 days)
        $messageActivity = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = $user->messages()
                ->whereDate('created_at', $date)
                ->count();
            
            $messageActivity[] = [
                'date' => $date,
                'count' => $count
            ];
        }

        return [
            'user' => $user,
            'recent_conversations' => $recentConversations,
            'message_activity' => $messageActivity,
        ];
    }

    /**
     * Get user conversations for admin panel
     */
    public function getUserConversations(int $userId): Collection
    {
        $user = User::where('is_admin', false)->findOrFail($userId);
        
        return $user->conversations()
            ->with(['messages' => function ($query) {
                $query->latest()->limit(5);
            }])
            ->withCount('messages')
            ->latest()
            ->get();
    }

    /**
     * Delete a user
     */
    public function deleteUser(int $userId): void
    {
        $user = User::where('is_admin', false)->findOrFail($userId);
        
        // Delete associated conversations and messages
        $user->conversations()->delete();
        $user->messages()->delete();
        
        // Delete the user
        $user->delete();
    }

    /**
     * Update user premium status
     */
    public function updateUserPremium(int $userId, bool $isPremium): User
    {
        $user = User::where('is_admin', false)->findOrFail($userId);
        
        $user->update([
            'is_premium' => $isPremium,
            'subscription_expires_at' => $isPremium ? null : null,
        ]);
        
        return $user->fresh();
    }

    /**
     * Get conversation details
     */
    public function getConversationDetails(int $conversationId): Conversation
    {
        return Conversation::with(['user', 'messages' => function ($query) {
            $query->orderBy('created_at');
        }])
        ->findOrFail($conversationId);
    }

    /**
     * Delete a conversation
     */
    public function deleteConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);
        
        // Delete associated messages
        $conversation->messages()->delete();
        
        // Delete the conversation
        $conversation->delete();
    }
}
