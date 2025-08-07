<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

            // Create new conversation if not provided
            if (!$conversationId) {
                $conversation = Conversation::create([
                    'user_id' => $user->id,
                    'title' => substr($request->message, 0, 50) . '...',
                ]);
                $conversationId = $conversation->id;
            } else {
                $conversation = Conversation::where('id', $conversationId)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
            }

            // Save user message
            Message::create([
                'conversation_id' => $conversationId,
                'role' => 'user',
                'content' => $request->message,
            ]);

            // Get conversation history for context
            $messages = Message::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'asc')
                ->get();

            $context = $messages->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            })->toArray();

            // Generate AI response using the more advanced method
            $aiResponse = $this->geminiService->generateResponseWithHistory($request->message, $context);

            // Save AI response
            $aiMessage = Message::create([
                'conversation_id' => $conversationId,
                'role' => 'assistant',
                'content' => $aiResponse,
            ]);

            // Increment user's message count
            $user->incrementMessageCount();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'conversation_id' => $conversationId,
                'response' => $aiResponse,
                'ai_message' => $aiMessage,
            ]);

        } catch (\Exception $e) {
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
            
            // Check guest message limit using session
            $guestMessageCount = session("guest_messages_{$sessionId}", 0);
            
            if ($guestMessageCount >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the limit of 3 free messages. Please register to continue chatting.',
                    'limit_reached' => true,
                ], 429);
            }

            // Generate AI response directly (no database storage for guests)
            $aiResponse = $this->geminiService->generateResponse($request->message);

            // Increment guest message count
            $newCount = $guestMessageCount + 1;
            session(["guest_messages_{$sessionId}" => $newCount]);

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