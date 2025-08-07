<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class TestChatStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:chat-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test chat statistics data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing chat statistics...');
        
        // Count total records
        $totalUsers = User::count();
        $totalConversations = Conversation::count();
        $totalMessages = Message::count();
        
        $this->info("Total Users: {$totalUsers}");
        $this->info("Total Conversations: {$totalConversations}");
        $this->info("Total Messages: {$totalMessages}");
        
        // Test the exact query from AdminController
        $days = 30;
        
        $this->info("\n--- Messages Per Day (Last {$days} days) ---");
        $messagesPerDay = Message::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
            
        foreach ($messagesPerDay as $stat) {
            $this->info("Date: {$stat->date} - Count: {$stat->count}");
        }
        
        if ($messagesPerDay->isEmpty()) {
            $this->warn('No messages found in the last 30 days!');
            
            // Check the oldest message
            $oldestMessage = Message::orderBy('created_at')->first();
            if ($oldestMessage) {
                $this->info("Oldest message date: {$oldestMessage->created_at}");
            } else {
                $this->warn('No messages found at all!');
            }
        }
        
        $this->info("\n--- Top Active Users ---");
        $topUsers = User::where('is_admin', false)
            ->whereHas('conversations.messages', function ($query) use ($days) {
                $query->where('messages.created_at', '>=', now()->subDays($days));
            })
            ->withCount(['messages' => function ($query) use ($days) {
                $query->where('messages.created_at', '>=', now()->subDays($days));
            }])
            ->orderBy('messages_count', 'desc')
            ->limit(10)
            ->get();
            
        foreach ($topUsers as $user) {
            $this->info("User: {$user->name} - Messages: {$user->messages_count}");
        }
        
        if ($topUsers->isEmpty()) {
            $this->warn('No active users found!');
        }
    }
}
