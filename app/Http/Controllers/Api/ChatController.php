<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class ChatController extends Controller
{
    private $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'conversation_id' => 'nullable|exists:conversations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $conversationId = $request->conversation_id;

            // Check if user has reached message limit
            if ($user->hasReachedMessageLimit()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached your message limit for this month. Please upgrade to premium.',
                ], 429);
            }

            // Get existing conversation or prepare for new one (but don't create yet)
            $conversation = null;
            $isNewConversation = false;
            
            if (!$conversationId) {
                // Mark as new conversation but don't create until API succeeds
                $isNewConversation = true;
                $conversationTitle = substr($request->message, 0, 50) . '...';
            } else {
                // Verify existing conversation belongs to user
                $conversation = Conversation::where('id', $conversationId)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
            }

            // Get conversation history for context (only for existing conversations)
            $context = [];
            if (!$isNewConversation && $conversation) {
                $messages = Message::where('conversation_id', $conversationId)
                    ->orderBy('created_at', 'asc')
                    ->get();

                $context = $messages->map(function ($message) {
                    return [
                        'role' => $message->role,
                        'content' => $message->content,
                    ];
                })->toArray();
            }

            // **IMPORTANT: Try Gemini API call FIRST, before saving anything**
            $aiResponse = $this->geminiService->generateResponseWithHistory($request->message, $context);

            // Only if API call succeeds, create conversation (if new) and save messages
            if ($isNewConversation) {
                $conversation = Conversation::create([
                    'user_id' => $user->id,
                    'title' => $conversationTitle,
                ]);
                $conversationId = $conversation->id;
            }

            $userMessage = Message::create([
                'conversation_id' => $conversationId,
                'role' => 'user',
                'content' => $request->message,
            ]);

            $aiMessage = Message::create([
                'conversation_id' => $conversationId,
                'role' => 'assistant',
                'content' => $aiResponse,
            ]);

            // Increment user's message count only after successful API call
            $user->incrementMessageCount();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'conversation_id' => $conversationId,
                'response' => $aiResponse,
                'user_message' => $userMessage,
                'ai_message' => $aiMessage,
            ]);

        } catch (\Exception $e) {
            // If API fails, no conversations or messages are saved to database
            \Log::error('Chat API Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function sendGuestMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
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
            $sessionId = $request->session_id ?? 'guest_' . session()->getId();
            
            // Check guest message limit using a more persistent approach
            // Store in both session and cache to prevent reset on refresh
            $cacheKey = "guest_messages_{$sessionId}";
            $sessionKey = "guest_messages_{$sessionId}";
            
            // Try to get from session first, then cache as fallback
            $guestMessageCount = session($sessionKey, null);
            if ($guestMessageCount === null) {
                $guestMessageCount = cache($cacheKey, 0);
                // Restore to session if found in cache
                if ($guestMessageCount > 0) {
                    session([$sessionKey => $guestMessageCount]);
                }
            }
            
            if ($guestMessageCount >= 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the limit of 2 free messages. Please register to continue chatting.',
                    'limit_reached' => true,
                ], 429);
            }

            // Generate AI response directly (no database storage for guests)
            $aiResponse = $this->geminiService->generateResponse($request->message);

            // Increment guest message count and store in both session and cache
            $newCount = $guestMessageCount + 1;
            session([$sessionKey => $newCount]);
            
            // Store in cache for 24 hours to persist across session resets
            cache([$cacheKey => $newCount], now()->addDay());

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'response' => $aiResponse,
                'session_id' => $sessionId,
                'remaining_messages' => 3 - $newCount,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getConversations(Request $request)
    {
        $conversations = Conversation::where('user_id', $request->user()->id)
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    public function getConversation(Request $request, $id)
    {
        $conversation = Conversation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with('messages')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    public function deleteConversation(Request $request, $id)
    {
        $conversation = Conversation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully',
        ]);
    }
}