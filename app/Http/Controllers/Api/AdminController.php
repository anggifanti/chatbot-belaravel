<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get admin dashboard overview stats
     */
    public function getDashboardStats(Request $request)
    {
        // Total users
        $totalUsers = User::nonAdmins()->count();
        $newUsersToday = User::nonAdmins()->whereDate('created_at', today())->count();
        $premiumUsers = User::premium()->count();
        
        // Total conversations and messages
        $totalConversations = Conversation::count();
        $totalMessages = Message::count();
        $messagesToday = Message::whereDate('messages.created_at', today())->count();
        
        // Active users (last 7 days)
        $activeUsers = User::nonAdmins()->active(7)->count();
        
        // Usage stats by day (last 7 days)
        $usageByDate = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $messageCount = Message::whereDate('messages.created_at', $date)->count();
            $userCount = User::nonAdmins()->whereDate('created_at', $date)->count();
            
            $usageByDate[] = [
                'date' => $date,
                'messages' => $messageCount,
                'new_users' => $userCount
            ];
        }
        
        return response()->json([
            'success' => true,
            'stats' => [
                'total_users' => $totalUsers,
                'new_users_today' => $newUsersToday,
                'premium_users' => $premiumUsers,
                'active_users_7_days' => $activeUsers,
                'total_conversations' => $totalConversations,
                'total_messages' => $totalMessages,
                'messages_today' => $messagesToday,
                'usage_by_date' => $usageByDate,
            ]
        ]);
    }

    /**
     * Get all users with pagination
     */
    public function getUsers(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        
        $query = User::nonAdmins()
            ->withCount(['conversations', 'messages'])
            ->with(['latestConversation'])
            ->orderBy('created_at', 'desc');
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $users = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * Get specific user details with stats
     */
    public function getUserDetails(Request $request, $userId)
    {
        $user = User::nonAdmins()
            ->withCount(['conversations', 'messages'])
            ->findOrFail($userId);
        
        // Get user's message activity by date (last 30 days)
        $messageActivity = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = $user->messages()
                ->whereDate('messages.created_at', $date)
                ->count();
            
            $messageActivity[] = [
                'date' => $date,
                'count' => $count
            ];
        }
        
        return response()->json([
            'success' => true,
            'user' => $user,
            'stats' => [
                'message_activity' => $messageActivity,
                'total_messages' => $user->total_messages,
                'monthly_messages' => $user->monthly_message_count,
                'remaining_messages' => $user->remaining_messages,
                'user_type' => $user->user_type,
                'is_premium' => $user->isPremium(),
            ]
        ]);
    }

    /**
     * Get user's conversations
     */
    public function getUserConversations(Request $request, $userId)
    {
        $user = User::nonAdmins()->findOrFail($userId);
        $perPage = $request->get('per_page', 10);
        
        $conversations = $user->conversations()
            ->withCount('messages')
            ->with(['latestMessage'])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'conversations' => $conversations
        ]);
    }

    /**
     * Get specific conversation with all messages
     */
    public function getConversationDetails(Request $request, $conversationId)
    {
        $conversation = Conversation::with(['user', 'messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->findOrFail($conversationId);
        
        return response()->json([
            'success' => true,
            'conversation' => $conversation
        ]);
    }

    /**
     * Delete a user and all their data
     */
    public function deleteUser(Request $request, $userId)
    {
        $user = User::nonAdmins()->findOrFail($userId);
        
        // Delete user (conversations and messages will cascade delete)
        $user->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Update user's premium status
     */
    public function updateUserPremium(Request $request, $userId)
    {
        $request->validate([
            'is_premium' => 'required|boolean',
            'subscription_expires_at' => 'nullable|date'
        ]);
        
        $user = User::nonAdmins()->findOrFail($userId);
        
        $user->update([
            'is_premium' => $request->is_premium,
            'subscription_expires_at' => $request->subscription_expires_at
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'User premium status updated successfully',
            'user' => $user->fresh()
        ]);
    }

    /**
     * Delete a specific conversation
     */
    public function deleteConversation(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully'
        ]);
    }

    /**
     * Get system-wide chat statistics
     */
    public function getChatStats(Request $request)
    {
        $days = $request->get('days', 30);
        
        // Messages per day
        $messagesPerDay = Message::select(
                DB::raw('DATE(messages.created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('messages.created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('DATE(messages.created_at)'))
            ->orderBy('date')
            ->get();
        
        // Top active users - simplified query
        $topUsers = User::nonAdmins()
            ->whereHas('conversations.messages', function ($query) use ($days) {
                $query->where('messages.created_at', '>=', now()->subDays($days));
            })
            ->withCount(['messages' => function ($query) use ($days) {
                $query->where('messages.created_at', '>=', now()->subDays($days));
            }])
            ->orderBy('messages_count', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json([
            'success' => true,
            'stats' => [
                'messages_per_day' => $messagesPerDay,
                'top_active_users' => $topUsers,
                'total_messages_period' => Message::where('messages.created_at', '>=', now()->subDays($days))->count(),
                'total_conversations_period' => Conversation::where('created_at', '>=', now()->subDays($days))->count(),
            ]
        ]);
    }
}
