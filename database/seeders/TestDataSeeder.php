<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = User::firstOrCreate(
                ['email' => "testuser{$i}@example.com"],
                [
                    'name' => "Test User {$i}",
                    'password' => bcrypt('password'),
                    'is_premium' => $i <= 2, // First 2 users are premium
                    'is_admin' => false,
                    'created_at' => now()->subDays(rand(1, 30)),
                ]
            );
        }

        // Create admin user if not exists
        if (!User::where('is_admin', true)->exists()) {
            User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Admin User',
                    'password' => bcrypt('password'),
                    'is_premium' => true,
                    'is_admin' => true,
                ]
            );
        }

        // Create conversations and messages for the last 30 days
        foreach ($users as $user) {
            for ($conv = 1; $conv <= rand(2, 5); $conv++) {
                $conversation = Conversation::create([
                    'user_id' => $user->id,
                    'title' => "Beauty Consultation {$conv}",
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);

                // Create messages for each conversation
                $messageCount = rand(5, 20);
                for ($msg = 1; $msg <= $messageCount; $msg++) {
                    $isUser = $msg % 2 === 1; // Alternate between user and AI messages
                    
                    $messageDate = $conversation->created_at->addMinutes(rand(1, 120));
                    
                    Message::create([
                        'conversation_id' => $conversation->id,
                        'content' => $isUser ? 
                            $this->getUserMessage($msg) : 
                            $this->getAIMessage($msg),
                        'role' => $isUser ? 'user' : 'assistant',
                        'created_at' => $messageDate,
                    ]);
                }
            }
        }
    }

    private function getUserMessage($index): string
    {
        $messages = [
            "What's the best skincare routine for oily skin?",
            "Can you recommend a good foundation for my skin tone?",
            "How do I apply eyeliner properly?",
            "What are the latest makeup trends?",
            "I need help with contouring techniques",
            "What's a good moisturizer for dry skin?",
            "How do I choose the right lipstick color?",
            "What's the difference between BB and CC cream?",
            "Can you help me with eyebrow shaping?",
            "What are some good anti-aging products?",
        ];
        
        return $messages[($index - 1) % count($messages)];
    }

    private function getAIMessage($index): string
    {
        $messages = [
            "For oily skin, I recommend a gentle cleanser, salicylic acid toner, niacinamide serum, and oil-free moisturizer. Use a clay mask 2-3 times per week.",
            "I'd be happy to help you find the perfect foundation! What's your skin undertone - warm, cool, or neutral?",
            "Start with a thin line close to your lash line. Use short strokes and build up gradually. Practice makes perfect!",
            "Current trends include dewy skin, bold lips, graphic eyeliner, and natural brows. Would you like specific product recommendations?",
            "Contouring is about creating shadows and highlights. Start with a shade 2-3 times darker than your skin tone in the hollows of your cheeks.",
            "For dry skin, look for moisturizers with hyaluronic acid, ceramides, or glycerin. Apply to damp skin for better absorption.",
            "Consider your skin undertone and the occasion. For everyday, try MLBB (my lips but better) shades that enhance your natural color.",
            "BB cream provides light coverage with skincare benefits, while CC cream offers color correction and medium coverage.",
            "Eyebrow shape should complement your face shape. Start by mapping your brows using the golden ratio technique.",
            "Look for products with retinol, vitamin C, peptides, and sunscreen. Start slowly with active ingredients to avoid irritation.",
        ];
        
        return $messages[($index - 1) % count($messages)];
    }
}
